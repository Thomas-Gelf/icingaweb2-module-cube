<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2
namespace Icinga\Module\Cube;

use RecursiveIteratorIterator;
use SplStack;

/**
 * Build cube from iterable Node
 *
 * Build dimensions and measures
 *
 * @package Icinga\Module\Cube
 */
class CubeBuilder extends RecursiveIteratorIterator
{
    protected $stack;

    /**
     * @var  BaseCube
     */
    protected $cube;

    /**
     * @var  bool
     */
    protected $isLoopBeginning = false;

    /**
     * CubeBuilder constructor.
     *
     * @param iterable $data Rows from database
     *
     * @param BaseCube $cube Contains dimensions, slices and related methods
     */
    public function __construct($data, BaseCube $cube)
    {
        $this->cube = $cube;

        parent::__construct(
            (new RollupIterator($data, $cube)),
            static::CHILD_FIRST | static::LEAVES_ONLY
        );
    }

    /**
     * @param integer $index
     *
     * @return string dimension at given index if exists, otherwise dimension at 0 index
     */
    public function getDimension($index)
    {
        if (array_key_exists($index, $this->getDimensions())) {
            return $this->getDimensions()[$index];
        }

        return $this->getDimensions()[0];
    }

    /**
     * @return array all dimensions, excluded slices
     */
    public function getDimensions()
    {
        return $this->cube->getDimensionsWithoutSliceValues();
    }

    /**
     * {@inheritdoc}
     */
    public function beginIteration()
    {
        $this->stack = new SplStack();
        $this->stack->push($this->cube);
        $this->isLoopBeginning = true;
    }

    /**
     * {@inheritdoc}
     */
    public function beginChildren()
    {
        // in first iteration, we dont have enough data to push on stack
        if ($this->isLoopBeginning) {
            $this->isLoopBeginning = false;
        } else {
            $this->stack->push(
                $this->cube->renderDimension(
                    // value of parent node
                    $this->getSubIterator($this->getDepth() - 1)->current()->getValue(),
                    $this->getDimension($this->getDepth() - 2),
                    count($this->getDimensions()) - $this->getDepth()
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function endChildren()
    {
        if ($this->stack->count() > 1) {
            $childEl = $this->stack->pop();
            $this->stack->top()->add($childEl);
        }
    }

    /**
     * build the cube
     *
     * loop only render measures
     *
     * CubeBuilder::beginChildren() render the dimensions
     *
     * @param BaseCube $cube
     *
     */
    public static function build(BaseCube $cube)
    {
        $builder = new static($cube->getData(), $cube);

        foreach ($builder as $item) {
            if ($builder->getInnerIterator()->hasChildren()) {
                continue;
            }

            $builder->stack->top()->add(
                $builder->cube->renderMeasure($item->getValue(), $builder->getDimension($builder->getDepth() - 1))
            );
        }
    }
}
