<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2
namespace Icinga\Module\Cube;

use Icinga\Data\Tree\TreeNode;
use Icinga\Data\Tree\TreeNodeIterator;
use IteratorAggregate;
use SplStack;

/**
 * Class RollupIterator
 *
 * This class help to make an iterable TreeNode data structure
 *
 *  * # Example Usage
 * ```
 * $tree = new RollupIterator($data, $cube);
 * ```
 *
 * @package Icinga\Module\Cube
 */
class RollupIterator implements IteratorAggregate
{
    /**
     * @var iterable
     */
    protected $data;

    /**
     * @var BaseCube includes dimensions, slices and related methods
     */
    protected $cube;

    /**
     * RollupIterator constructor.
     *
     * @param iterable $data Rows from database
     *
     * @param BaseCube $cube
     */
    public function __construct($data, BaseCube $cube)
    {
        $this->data = $data;
        $this->cube = $cube;
    }

    /**
     * decode the given data with json_decode() and cast it into an object
     *
     * @param $data
     *
     * @return object
     */
    private function decode($data)
    {
        $arr = [];
        foreach ($data as $key => $d) {
            $res = json_decode($d, true);

            if (is_bool($res)) {
                $res = $res ? 'true' : 'false';
            }

            $arr[$key] = $res;
        }

        return (object) $arr;
    }

    /**
     * Checks if given data is affected by slice
     *
     * @param object $data
     *
     * @return bool
     */
    private function isAffectedBySlice($data)
    {
        // suppose we slice the dimension location with the value of berlin
        // we get $slice = ['location' => 'berlin']

        foreach ($this->cube->getSlices() as $slicedDimension => $value) {
            // $data->location === 'berlin'
            if ($data->$slicedDimension === $value) {
                $dimensionKey = array_search($slicedDimension, $this->cube->getDimensions());
                // get the next dimension if given, otherwise the last value of array
                $nextDimension = isset($this->cube->getDimensions()[$dimensionKey + 1])
                    ? $this->cube->getDimensions()[$dimensionKey + 1]
                    : $this->cube->getDimensions()[$dimensionKey];
                // the sliced values should not be shown as a cube, so we don't add that values to the TreeNode
                // we know it should be skipped, if the next given dimension in data is null, or the current
                // dimension is a measure
                if ($data->$nextDimension === null || $slicedDimension === $nextDimension) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $pending = new TreeNode();
        $tiers = new SplStack();

        // if all given dimensions were sliced, there is nothing left to render
        if (array_keys($this->cube->getSlices()) === array_values($this->cube->getDimensions())) {
            return new TreeNodeIterator(new TreeNode());
        }

        foreach ($this->data as $data) {
            $data = $this->decode($data);

            // slice affected data should be skipped
            if ($this->isAffectedBySlice($data)) {
                continue;
            }

            foreach ($this->cube->getDimensionsWithoutSliceValues() as $dimension) {
                if ($data->$dimension === null) {
                    $tempStack = new SplStack();
                    $pending->setValue($data);

                    while (true) {
                        if ($tiers->isEmpty() || $tiers->top()->getValue()->$dimension === null) {
                            break;
                        }
                        // we have to store values in tempStore first because of the following reason:
                        // tiers = [0,1,2,3]; so when we pop() tiers to tempStack,
                        // tempStack will be [3,2,1,0], but we want to add it in TreeNode as ascending order
                        $tempStack->push($tiers->pop());
                    }
                    while (! $tempStack->isEmpty()) {
                        // so here we do like:
                        // tempStack = [3,2,1,0];
                        // and pending will be after pop like = [0,1,2,3];
                        $pending->appendChild($tempStack->pop());
                    }

                    $tiers->push($pending);
                    $pending = new TreeNode();
                    continue 2;
                }
            }

            $pending->appendChild((new TreeNode)->setValue($data));
        }

        while ($tiers->count()) {
            $pending->appendChild($tiers->pop());
        }

        return new TreeNodeIterator($pending);
    }
}
