<?php

namespace Icinga\Module\Cube;

use Icinga\Module\Cube\Common\IcingaDb;
use ipl\Sql\Select;
use mysql_xdevapi\Exception;

class ServiceDbQuery
{

    use IcingaDb;

    /**
     * @param array $urlDimensions
     * @param null $slices
     * @return array
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function getResult($urlDimensions, $slices = null)
    {
        $select = (new Select())
            ->from('service s')
            ->join(
                "service_state state",
                "state.service_id = s.id"
            );

        $columns = [];
        foreach ($urlDimensions as $dimension) {
            $dimensionJunction = $this->getDb()->quoteIdentifier($dimension . '_junction');
            $dim = $this->getDb()->quoteIdentifier($dimension);

            $select
                ->join(
                    "service_customvar {$dimensionJunction}",
                    "{$dimensionJunction}.service_id = s.id"
                )
                ->join(
                    "customvar {$dim}",
                    "{$dim}.id = {$dimensionJunction}.customvar_id 
                    AND {$dim}.name = \"{$dimension}\""
                );

            $columns[$dim] = $dimension . '.value';
        }

        $groupByValues = $columns;
        $columns['cnt'] = 'SUM(1)';
        $columns['count_ok'] = 'SUM(CASE WHEN state.soft_state = 0 THEN  1 ELSE 0 END)';
        $columns['count_warning'] = 'SUM(CASE WHEN state.soft_state = 1 THEN  1 ELSE 0 END)';
        $columns['count_warning_unhandled'] = 'SUM(CASE WHEN state.soft_state = 1 
        AND state.is_handled = "n" THEN  1 ELSE 0 END)';
        $columns['count_critical'] = 'SUM(CASE WHEN state.soft_state = 2 THEN  1 ELSE 0 END)';
        $columns['count_critical_unhandled'] = 'SUM(CASE WHEN state.soft_state = 2 
        AND state.is_handled = "n" THEN  1 ELSE 0 END)';
        $columns['count_unknown'] = 'SUM(CASE WHEN state.soft_state = 3 THEN  1 ELSE 0 END)';
        $columns['count_unknown_unhandled'] = 'SUM(CASE WHEN state.soft_state = 3 
        AND state.is_handled = "n" THEN  1 ELSE 0 END)';
        // dimension is the last key here
        $groupByValues[$dim] .= ' WITH ROLLUP';

        $select
            ->columns($columns)
            ->groupBy($groupByValues);

        if (!empty($slices)) {
            foreach ($slices as $key => $value) {
                $select
                    ->where("{$this->getDb()->quoteIdentifier($key)}.value = '\"{$value}\"'");
            }
        }

        return $this->getDb()->select($select)->fetchAll();
    }
}
