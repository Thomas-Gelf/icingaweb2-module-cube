<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2
namespace Icinga\Module\Cube;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

/**
 * Abstract base class for Cube
 *
 * This class includes the main functions and variables, that are necessary to create a cube
 *
 * @package Icinga\Module\Cube
 */
abstract class BaseCube extends BaseHtmlElement
{
    /**
     * @var array Rows from database
     */
    protected $data;

    /**
     * @var array dimensions given in the url
     */
    protected $dimensions;

    /**
     * @var array dimensions without slice values
     */
    protected $dimensionsWithoutSliceValues;

    /**
     * @var array slices
     */
    protected $slices;


    public function __construct($data, array $dimensions, array $slices = null)
    {
        $this->data = $data;
        $this->dimensions = $dimensions;
        $this->slices = $slices;
        $this->init();
    }

    /**
     * Overwrite this function if you want to add more constructor variables
     */
    public function init()
    {
    }


    /**
     * Get the data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the dimensions
     *
     * @return array
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * @return bool
     */
    public function hasSlices()
    {
        return ! empty($this->slices);
    }

    /**
     * Get the slices
     *
     * @return array|null
     */
    public function getSlices()
    {
        return $this->slices;
    }

    /**
     * Get all unsliced dimensions
     *
     * @return array
     */
    public function getDimensionsWithoutSliceValues()
    {
        if (isset($this->dimensionsWithoutSliceValues)) {
            return $this->dimensionsWithoutSliceValues;
        }

        if ($this->hasSlices()) {
            foreach ($this->dimensions as $key => $dimension) {
                if (! array_key_exists($dimension, $this->slices)) {
                    $this->dimensionsWithoutSliceValues[] = $dimension;
                }
            }
            return $this->dimensionsWithoutSliceValues;
        }

        return $this->dimensions;
    }

    /**
     * Render dimensions
     *
     * @param object $dimension
     *
     * @param string $header
     *
     * @param int $level
     *
     * @return HtmlElement
     */
    abstract public function renderDimension($dimension, $header, $level);

    /**
     * Render measure
     *
     * @param object $measure
     *
     * @param string $header
     *
     * @return HtmlElement
     */
    abstract public function renderMeasure($measure, $header);

    /**
     * @inheritDoc
     */
    protected function assemble()
    {
        CubeBuilder::build($this);
    }
}
