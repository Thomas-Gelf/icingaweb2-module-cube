<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2
namespace Icinga\Module\Cube;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

/**
 * Class CubeSettings
 *
 * Create cube settings (up and down arrows and cancel button for dimension)
 *
 * @package Icinga\Module\Cube
 */
class CubeSettings extends BaseHtmlElement
{
    /** @var Url */
    protected $baseUrl;

    /** @var array all dimensions including slice*/
    protected $dimensions = [];

    /** @var string */
    protected $dimensionsParam = 'dimensions';

    protected $defaultAttributes = ['class' => 'cube-settings'];

    protected $tag = 'div';

    /** @var array sliced dimensions*/
    protected $slices = [];

    /** @var array dimensions that are not sliced yet*/
    protected $dimensionsWithoutSlices = [];

    /**
     * @return Url
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param Url $baseUrl
     *
     * @return $this
     */
    public function setBaseUrl(Url $baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * Get dimensions
     *
     * @return array all url dimensions including slices
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Set dimensions
     *
     * @param array $dimensions
     *
     * @return $this
     */
    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    /**
     * @return string
     */
    public function getDimensionsParam()
    {
        return $this->dimensionsParam;
    }

    /**
     * @param string $dimensionsParam
     *
     * @return $this
     */
    public function setDimensionsParam($dimensionsParam)
    {
        $this->dimensionsParam = $dimensionsParam;

        return $this;
    }

    /**
     * @param array $slices
     *
     * @return $this
     */
    public function setSlices($slices)
    {
        $this->slices = $slices;

        return $this;
    }

    /**
     * @return array url slice dimensions
     */
    public function getSlices()
    {
        return $this->slices;
    }

    public function setDimensionsWithoutSlices($dimensionsWithoutSlices)
    {
        $this->dimensionsWithoutSlices = $dimensionsWithoutSlices;

        return $this;
    }

    /**
     * Check if the given dimension is sliced
     *
     * @param string $dimension
     *
     * @return bool
     */
    public function isSlice($dimension)
    {
        return array_key_exists($dimension, $this->getSlices());
    }

    /**
     * @return array url dimensions that are not sliced yet
     */
    public function getDimensionsWithoutSlices()
    {
        return $this->dimensionsWithoutSlices;
    }

    private function prepareSliceCancelUrl($dimension)
    {
        $slices = $this->getSlices();
        unset($slices[$dimension]);
        $dimensions = $this->getDimensionsWithoutSlices();
        $dimensions[] = $dimension;
        $allDimensions = array_merge($dimensions, array_keys($slices));

        return $this->getBaseUrl()->setParam(
            'dimensions',
            DimensionParams::update($allDimensions)->getParams()
        )->without(DimensionParams::update(rawurlencode($dimension))->getParams());
    }

    /**
     * @param int $indexToMove index value of array value that has to be moved
     *
     * @param boolean $isDirectionUp move direction
     *
     * @return array swapped array
     */
    private function swapArray($indexToMove, $isDirectionUp)
    {
        $urlDimensions = array_merge($this->getDimensionsWithoutSlices(), array_keys($this->getSlices()));
        $otherIndex = $isDirectionUp ? $indexToMove - 1 : $indexToMove + 1;
        if (isset($urlDimensions[$otherIndex])) {
            $tempVal = $urlDimensions[$otherIndex];
            $urlDimensions[$otherIndex] = $urlDimensions[$indexToMove];
            $urlDimensions[$indexToMove] = $tempVal;
        }

        return  $urlDimensions;
    }

    protected function assemble()
    {
        $allDimensions = array_merge($this->getDimensionsWithoutSlices(), array_keys($this->getSlices()));
        $content = [];
        $slicedContent = [];

        foreach ($allDimensions as $key => $dimension) {
            $dimensions = $allDimensions;
            // add all other dimensions to link except the current one
            unset($dimensions[$key]);

            $dimensionName = $dimension;
            if ($this->isSlice($dimension)) {
                $dimensionName = 'Slice/Filter: ' . $dimension . ' = ' . $this->getSlices()[$dimension];
            }

            $element = Html::tag('div');
            // if the given dimension is included in slice values, set cancel link without this dimension
            $element->add(new Link(
                new Icon('cancel'),
                $cancelUrl = $this->isSlice($dimension)
                    ? $this->prepareSliceCancelUrl($dimension)
                    : $this->getBaseUrl()->with(
                        [
                            $this->getDimensionsParam() => DimensionParams::fromArray($dimensions)->getParams()
                        ]
                    ),
                ['class' => 'cube-settings-btn', 'title' => 'Remove dimension "' . $dimension . '"' ]
            ));

            if ($this->isSlice($dimension)) {
                $element->add(Html::tag('span', ['class' => 'dimension-name'], $dimensionName));
                $slicedContent[] = $element;
                continue;
            }

            if ($key) {
                $lastArrowUpClass = $key == count($allDimensions) - 1 || $this->isSlice($allDimensions[$key + 1])
                    ? ' last-up-arrow'
                    : null;
                $element->add(new Link(
                    new Icon('angle-double-up'),
                    $this->getBaseUrl()->with(
                        [
                            $this->getDimensionsParam()
                            => DimensionParams::fromArray($this->swapArray($key, true))->getParams()
                        ]
                    ),
                    [
                        'class' => 'cube-settings-btn' . $lastArrowUpClass,
                        'title' => 'Move dimension "' . $dimension . '" up'
                    ]
                ));
            }

            if ($key != count($allDimensions) - 1 && ! $this->isSlice($allDimensions[$key + 1])) {
                $element->add(new Link(
                    new Icon('angle-double-down'),
                    $this->getBaseUrl()->with(
                        [
                            $this->getDimensionsParam()
                            => DimensionParams::fromArray($this->swapArray($key, false))->getParams()
                        ]
                    ),
                    ['class' => 'cube-settings-btn', 'title' => 'Move dimension "' . $dimension . '" down']
                ));
            }

            $element->add(Html::tag('span', ['class' => 'dimension-name'], $dimensionName));

            $content[] = $element;
        }
        // Add sliced content at the end of the list
        $content = array_merge($content, $slicedContent);

        $this->add(Html::tag('ul', ['class' => 'dimension-list'], Html::wrapEach($content, 'li')));
    }
}
