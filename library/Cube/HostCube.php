<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2
namespace Icinga\Module\Cube;

use ipl\Html\Html;
use ipl\Web\Widget\Link;

/**
 * Class HostCube
 *
 * This class contains all logic functions for HostCube
 *
 * @package Icinga\Module\Cube
 */
class HostCube extends MonitoringCube
{
    /**
     * @var string[] wrapper container class
     */
    protected $defaultAttributes = ['class' => 'host-cube'];


    protected function createMeasureInfo($measure, $header)
    {
        $hasProblem = true;
        if ($measure->count_down > 0) {
            $measureLabelUrlSuffix = $this->getStateInfo(1, false);
            $measureLabel = $measure->count_down;
            $measureClasses[] = 'critical';
            if ($measure->count_down_unhandled === 0) {
                $measureLabelUrlSuffix = $this->getStateInfo(1, true);
                $measureClasses[] = 'handled';
            }
        } else {
            $measureLabel = $measure->count_up;
            $measureLabelUrlSuffix = $this->getStateInfo(0);
            $hasProblem = false;
            $measureClasses[] = 'ok';
        }

        return (new MeasureInfo())
            ->setMeasureCountDetails($this->prepareMeasureCountDetails($measure))
            ->setProblem($hasProblem)
            ->setMeasureLabel($measureLabel)
            ->setMeasureCssClasses($measureClasses)
            ->setMeasureLabelUrlSuffix($measureLabelUrlSuffix);
    }

    protected function getPath()
    {
        return 'icingadb/hosts';
    }


    protected function getDetailPath()
    {
        return 'hosts-details';
    }


    protected function getParamUrlPrefix()
    {
        return 'host.vars.';
    }

    /**
     * Set given params in an array
     *
     * @param int $state
     *
     * @param null|bool $isHandled
     *
     * @return array
     */
    private function getStateInfo($state, $isHandled = null)
    {
        $stateInfo = [];
        $stateInfo['state.soft_state'] = $state;

        if ($isHandled !== null) {
            $isHandledStr = $isHandled ? 'y' : 'n';
            $stateInfo['state.is_handled'] = $isHandledStr;
        }

        return $stateInfo;
    }

    /**
     * @param $measure
     *
     * @return
     */
    protected function prepareMeasureCountDetails($measure)
    {
        if ($measure->count_down === 0 || $measure->count_up === 0) {
            return null;
        }

        return  Html::tag(
            'span',
            ['class' => 'others'],
            [
                new Link(
                    Html::tag('span', ['class' => 'ok'], $measure->count_up),
                    $this->url->with(array_merge($this->getUrlParams($measure), $this->getStateInfo(0))),
                    ['data-base-target' => '_next']
                )
            ]
        );
    }
}
