<?php

namespace Icinga\Module\Cube;

use ipl\Html\HtmlElement;

/**
 * Class MeasureInfo
 *
 * A measure object containing all unique information about a measure
 *
 *  * # Example Usage
 * ```
 * $measureInfo = new MeasureInfo()
 *         ->setMeasureLabel()
 *         ->setMeasureCssClasses;
 *
 * and later you can get this information using getters
 * ```
 * @package Icinga\Module\Cube
 */
class MeasureInfo
{
    /**
     * @var array CSS classes
     */
    protected $measureCssClasses = [];

    /**
     * @var bool false if measure state is ok, otherwise true
     */
    protected $hasProblem;

    /**
     * Small count span(s) in cube footer
     *
     * @var HtmlElement|null
     */
    protected $measureCountDetails;

    /**
     * @var int Main count for measure that appears in the center
     */
    protected $measureLabel;

    /**
     * @var array Url suffix for main count according to the state of measure
     *
     */
    protected $measureLabelUrlSuffix = [];

    /**
     * Get url suffix for main count according to the state of measure
     *
     * @return array
     */
    public function getMeasureLabelUrlSuffix()
    {
        return $this->measureLabelUrlSuffix;
    }

    /**
     * Set url suffix for main count according to the state of measure
     *
     * @param array $measureLabelUrlSuffix
     *
     * @return $this
     */
    public function setMeasureLabelUrlSuffix($measureLabelUrlSuffix)
    {
        $this->measureLabelUrlSuffix = $measureLabelUrlSuffix;

        return $this;
    }



    /**
     * Get main count for measure that appears in the center
     *
     * @return integer
     */
    public function getMeasureLabel()
    {
        return $this->measureLabel;
    }

    /**
     * Set main count for measure that appears in the center
     *
     * @param integer $measureLabel
     *
     * @return  $this
     */
    public function setMeasureLabel($measureLabel)
    {
        $this->measureLabel = $measureLabel;

        return $this;
    }




    /**
     * Get measure css classes
     *
     * @return string of classes
     */
    public function getMeasureCssClasses()
    {
        return implode(' ', $this->measureCssClasses);
    }

    /**
     * Set measure css classes
     *
     * @param array $measureCssClasses
     *
     * @return  $this
     */
    public function setMeasureCssClasses($measureCssClasses)
    {
        $this->measureCssClasses = $measureCssClasses;

        return $this;
    }


    /**
     * Return false if measure state is ok, otherwise true
     *
     * @return bool
     */
    public function hasProblem()
    {
        return $this->hasProblem;
    }

    /**
     * Pass false if measure state is ok, otherwise true
     *
     * @param bool $hasProblem
     *
     * @return  $this
     */
    public function setProblem($hasProblem)
    {
        $this->hasProblem = $hasProblem;

        return $this;
    }

    /**
     * Get small count span(s) in cube footer
     *
     * @return HtmlElement|null
     */
    public function getMeasureCountDetails()
    {
        return $this->measureCountDetails;
    }

    /**
     * Set small count span(s) in cube footer
     *
     * @param HtmlElement|null $measureCountDetails
     *
     * @return  $this
     */
    public function setMeasureCountDetails($measureCountDetails)
    {
        $this->measureCountDetails = $measureCountDetails;

        return $this;
    }
}
