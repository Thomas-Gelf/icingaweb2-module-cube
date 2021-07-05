<?php
// Icinga Web 2 Cube Module | (c) 2016 Icinga GmbH | GPLv2

namespace Icinga\Module\Cube;

use Icinga\Data\Db\DbConnection;

abstract class DbCube extends Cube
{
    /** @var DbConnection */
    protected $connection;

    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var ZfSelectWrapper The inner query fetching all required data */
    protected $innerQuery;

    /** @var \Zend_Db_Select The rollup query, creating grouped sums over innerQuery */
    protected $rollupQuery;

    /** @var \Zend_Db_Select The outer query, orders respecting NULL values, rollup first */
    protected $fullQuery;

    /** @var string Database name. Allows to eventually join over multiple dbs  */
    protected $dbName;

    /** @var array Key/value array containing our chosen facts and the corresponding SQL expression */
    protected $factColumns = array();

    /**
     * A DbCube must provide a list of all available columns
     *
     * This is a key/value array with the key being the fact name / column alias
     * and
     *
     * @return array
     */
    abstract public function getAvailableFactColumns();

    /**
     * @return \Zend_Db_Select
     */
    abstract public function prepareInnerQuery();

    /**
     * Set a database connection
     *
     * @param DbConnection $connection
     * @return $this
     */
    public function setConnection(DbConnection $connection)
    {
        $this->connection = $connection;
        $this->db = $connection->getDbAdapter();
        return $this;
    }

    /**
     * Prepare the query and fetch all data
     *
     * @return array
     */
    public function fetchAll()
    {
        $query = $this->fullQuery();
        return $this->db()->fetchAll($query);
    }

    /**
     * Choose a one or more facts
     *
     * This also initializes a fact column lookup array
     *
     * @param  array $facts
     * @return $this
     */
    public function chooseFacts(array $facts)
    {
        parent::chooseFacts($facts);

        $this->factColumns = array();
        $columns = $this->getAvailableFactColumns();
        foreach ($this->chosenFacts as $name) {
            $this->factColumns[$name] = $columns[$name];
        }

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setDbName($name)
    {
        $this->dbName = $name;
        return $this;
    }

    /**
     * Gives back the table name, eventually prefixed with a defined DB name
     *
     * @param string $name
     * @return string
     */
    public function tableName($name)
    {
        if ($this->dbName === null) {
            return $name;
        } else {
            return $this->dbName . '.' . $name;
        }
    }

    /**
     * Returns an eventually defined DB name
     *
     * @return string|null
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * Get our inner query
     *
     * Hint: mostly used to get rid of NULL values
     *
     * @return ZfSelectWrapper
     */
    public function innerQuery()
    {
        if ($this->innerQuery === null) {
            $this->innerQuery = new ZfSelectWrapper($this->prepareInnerQuery());
        }

        return $this->innerQuery;
    }

    /**
     * We first prepare the queries and to finalize it later on
     *
     * This way dimensions can be added one by one, they will be allowed to
     * optionally join additional tables or apply other modifications late
     * in the process
     *
     * @return void
     */
    public function finalizeInnerQuery()
    {
        $query = $this->innerQuery()->unwrap();
        $columns = array();
        foreach ($this->dimensions as $name => $dimension) {
            $dimension->addToCube($this);
            if ($this->hasSlice($name)) {
                $query->where(
                    $dimension->getColumnExpression($this) . ' = ?',
                    $this->slices[$name]
                );
            } else {
                $columns[$name] = $dimension->getColumnExpression($this);
            }
        }

        $c = [];

        foreach ($columns + $this->factColumns as $k => $v) {
            $c[$this->db()->quoteIdentifier([$k])] = $v;
        }

        $query->columns($c);
    }

    /**
     * Lazy-load our full query
     *
     * @return \Zend_Db_Select
     */
    public function fullQuery()
    {
        if ($this->fullQuery === null) {
            $this->fullQuery = $this->prepareFullQuery();
        }

        return $this->fullQuery;
    }

    /**
     * Lazy-load our full query
     *
     * @return \Zend_Db_Select
     */
    protected function rollupQuery()
    {
        if ($this->rollupQuery === null) {
            $this->rollupQuery = $this->prepareRollupQuery();
        }

        return $this->rollupQuery;
    }

    /**
     * The full query wraps the rollup query in a sub-query to work around
     * MySQL limitations. This is required to not get into trouble when ordering,
     * especially combined with the need to keep control over (eventually desired)
     * NULL value fact columns
     *
     * @return \Zend_Db_Select
     */
    protected function prepareFullQuery()
    {
        $alias = 'rollup';
        $cols = $this->listColumns();

        $columns = array();

        foreach ($cols as $col) {
            $columns[$this->db()->quoteIdentifier([$col])] = $alias . '.' . $this->db()->quoteIdentifier([$col]);
        }

        $select = $this->db()->select()->from(
            array($alias => $this->rollupQuery()),
            $columns
        );

        foreach ($columns as $col) {
            $select->order('(' . $col . ' IS NOT NULL)');
            $select->order($col);
        }

        return $select;
    }

    /**
     * Provide access to our DB
     *
     * @return \Zend_Db_Adapter_Abstract
     */
    public function db()
    {
        return $this->db;
    }

    /**
     * Whether our connection is PostgreSQL
     *
     * @return bool
     */
    public function isPgsql()
    {
        return $this->connection->getDbType() === 'pgsql';
    }


    /**
     * This prepares the rollup query
     *
     * Inner query is wrapped in a subquery, summaries for all facts are
     * fetched. Rollup considers all defined dimensions and expects them
     * to exist as columns in the innerQuery
     *
     * @return \Zend_Db_Select
     */
    protected function prepareRollupQuery()
    {
        $alias = 'sub';

        $dimensions = array_map(function ($val) {
            return $this->db()->quoteIdentifier([$val]);
        }, $this->listDimensions());
        $this->finalizeInnerQuery();
        $columns = array();
        foreach ($dimensions as $dimension) {
            $columns[$dimension] = $alias . '.' . $dimension;
        }

        foreach ($this->listFacts() as $fact) {
            $columns[$fact] = 'SUM(' . $fact . ')';
        }

        $select = $this->db()->select()->from(
            array($alias => $this->innerQuery()->unwrap()),
            $columns
        );

        if ($this->isPgsql()) {
            $select->group('ROLLUP (' . implode(', ', $dimensions) . ')');
        } else {
            $select->group('(' . implode('), (', $dimensions) . ') WITH ROLLUP');
        }

        return $select;
    }
}
