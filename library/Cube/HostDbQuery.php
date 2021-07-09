<?php

namespace Icinga\Module\Cube;

use Icinga\Module\Cube\Common\IcingaDb;
use ipl\Sql\Select;
use PDO;

class HostDbQuery
{

    use IcingaDb;


    public function getResult($urlDimensions, $slices = null)
    {

        $select = (new Select())
            ->from('host h')
            ->join(
                "host_state state",
                "state.host_id = h.id"
            );

        $columns = [];
        foreach ($urlDimensions as $dimension) {
            $dimensionJunction = $this->getDb()->quoteIdentifier($dimension . '_junction');
            $dim = $this->getDb()->quoteIdentifier($dimension);

            $select
                ->join(
                    "host_customvar {$dimensionJunction}",
                    "{$dimensionJunction}.host_id = h.id"
                )
                ->join(
                    "customvar {$dim}",
                    "{$dim}.id = {$dimensionJunction}.customvar_id 
                    AND {$dim}.name = \"{$dimension}\""
                );

            $columns[$dim] = $dim . '.value';
        }

        $groupByValues = $columns;
        $columns['cnt'] = 'SUM(1)';
        $columns['count_up'] = 'SUM(CASE WHEN state.soft_state = 0 THEN  1 ELSE 0 END)';
        $columns['count_down'] = 'SUM(CASE WHEN state.soft_state = 1 THEN  1 ELSE 0 END)';
        $columns['count_down_unhandled'] = 'SUM(CASE WHEN state.soft_state = 1 
        AND state.is_handled = "n" THEN  1 ELSE 0 END)';
        // dimension is the last key here
        $groupByValues[$dim] .= ' WITH ROLLUP';

        $select
            ->columns($columns)
            ->groupBy($groupByValues);

        if (! empty($slices)) {
            foreach ($slices as $key => $value) {
                $select
                    ->where("{$this->getDb()->quoteIdentifier($key)}.value = '\"{$value}\"'");
            }
        }

        return $this->getDb()->select($select)->fetchAll();
    }

    /**
     * @param $slices
     *
     * @return array host name
     */
    public function getHostNames($slices)
    {

        $select = (new Select())
            ->from('host h');

        foreach ($slices as $dimension => $value) {
            $dimensionJunction = $this->getDb()->quoteIdentifier($dimension . '_junction');
            $dim = $this->getDb()->quoteIdentifier($dimension);

            $select
                ->join(
                    "host_customvar {$dimensionJunction}",
                    "{$dimensionJunction}.host_id = h.id"
                )
                ->join(
                    "customvar {$dim}",
                    "{$dim}.id = {$dimensionJunction}.customvar_id 
                    AND {$dim}.name = \"{$dimension}\""
                );
        }
        $select->columns('h.name');
        foreach ($slices as $dimension => $value) {
            $dim = $this->getDb()->quoteIdentifier($dimension);
            $select
                ->where("{$dim}.value = '\"{$value}\"'");
        }

        return $this->getDb()->select($select)->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}
