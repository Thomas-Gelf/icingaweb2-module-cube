<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2
namespace Icinga\Module\Cube;

use ipl\Html\Html;
use ipl\Web\Widget\Link;

/**
 * Class ServiceCube
 *
 * This class contains all logic functions for ServiceCube
 *
 * @package Icinga\Module\Cube
 */
class ServiceCube extends MonitoringCube
{

    /**
     * @var string[] wrapper container class
     */
    protected $defaultAttributes = ['class' => 'service-cube'];

    protected $measureLabel;

    protected function createMeasureInfo($measure, $header)
    {
        $hasProblem = true;

        if ($measure->count_critical > 0) {
            $measureLabel = 'count_critical';
            $measureLabelUrlSuffix = $this->getStateInfo(2, false);
            $measureClasses[] = 'critical';
            if ($measure->count_critical_unhandled === 0) {
                $measureLabelUrlSuffix = $this->getStateInfo(2, true);
                $measureClasses[] = 'handled';
            } else {
                $measureLabel = 'count_critical_unhandled';
            }
        } elseif ($measure->count_warning > 0) {
            $measureLabel = 'count_warning';
            $measureLabelUrlSuffix = $measureLabelUrlSuffix = $this->getStateInfo(1, false);
            $measureClasses[] = 'warning';
            if ($measure->count_warning_unhandled === 0) {
                $measureLabelUrlSuffix = $measureLabelUrlSuffix = $this->getStateInfo(1, true);
                $measureClasses[] = 'handled';
            } else {
                $measureLabel = 'count_warning_unhandled';
            }
        } elseif ($measure->count_unknown > 0) {
            $measureLabel = 'count_unknown';
            $measureLabelUrlSuffix = $measureLabelUrlSuffix = $this->getStateInfo(3, false);
            $measureClasses[] = 'unknown';
            if ($measure->count_unknown_unhandled === 0) {
                $measureLabelUrlSuffix = $measureLabelUrlSuffix = $this->getStateInfo(3, true);
                $measureClasses[] = 'handled';
            } else {
                $measureLabel = 'count_unknown_unhandled';
            }
        } else {
            $measureLabelUrlSuffix = $measureLabelUrlSuffix = $this->getStateInfo(0);
            $measureLabel = 'count_ok';
            $hasProblem = false;
            $measureClasses[] = 'ok';
        }

        $this->measureLabel = $measureLabel;

        return (new MeasureInfo())
            ->setMeasureCountDetails($this->prepareMeasureCountDetails($measure))
            ->setProblem($hasProblem)
            ->setMeasureLabel($measure->$measureLabel)
            ->setMeasureCssClasses($measureClasses)
            ->setMeasureLabelUrlSuffix($measureLabelUrlSuffix);
    }

    /**
     * @param $measure
     *
     * @return
     */
    protected function prepareMeasureCountDetails($measure)
    {
        $el = Html::tag('span', ['class' => 'others']);

        if ($measure->count_ok > 0) {
            if ($measure->cnt === $measure->count_ok) {
                return null;
            }

            $this->createCountSpan($measure, 'ok', $el);
        }

        if ($measure->count_warning > 0) {
            $this->createCountSpan($measure, 'warning', $el);
        }

        if ($measure->count_critical > 0) {
            $this->createCountSpan($measure, 'critical', $el);
        }

        if ($measure->count_unknown > 0) {
            $this->createCountSpan($measure, 'unknown', $el);
        }

        return $el;
    }

    /**
     * Set given params in an array
     *
     * @param int $state
     *  state 1 = ok |
     *  state 2 = warning |
     *  state 3 = critical |
     *  state 4 = unknown
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

    private function createCountSpan($measure, $state, &$el)
    {
        if ($state === 'ok' || $state === 0) {
            $measureLabelUrlSuffix = $this->getStateInfo(0);
            $el->add(new Link(
                Html::tag('span', ['class' => 'ok'], $measure->count_ok),
                $this->url->with(array_merge($this->getUrlParams($measure), $measureLabelUrlSuffix)),
                ['data-base-target' => '_next']
            ));
            return;
        }

        $stateArr = [
            1 => 'warning',
            2 => 'critical',
            3 => 'unknown'
        ];

        if (is_string($state)) {
            if ($stateAsNr = array_search($state, $stateArr)) {
                $stateAsString = $stateArr[$stateAsNr];
            }
        } else {
            $stateAsNr = $state;
            $stateAsString = $stateArr[$stateAsNr];
        }

        $countAll = 'count_' . $stateAsString;
        $unhandled = 'count_' . $stateAsString . '_unhandled';

        if ($this->measureLabel !== $countAll) {
            $measureLabelUrlSuffix = $this->getStateInfo($stateAsNr, true);

            $el->add(new Link(
                Html::tag('span', ['class' => "{$stateAsString} handled"], $measure->$countAll - $measure->$unhandled),
                $this->url->with(array_merge($this->getUrlParams($measure), $measureLabelUrlSuffix)),
                ['data-base-target' => '_next']
            ));

            if ($this->measureLabel !== $unhandled) {
                $measureLabelUrlSuffix = $this->getStateInfo($stateAsNr, false);

                $el->add(new Link(
                    Html::tag('span', ['class' => "{$stateAsString}"], $measure->$countAll),
                    $this->url->with(array_merge($this->getUrlParams($measure), $measureLabelUrlSuffix)),
                    ['data-base-target' => '_next']
                ));
            }
        }
    }

    protected function getParamUrlPrefix()
    {
        return 'service.vars.';
    }

    protected function getPath()
    {
        return 'icingadb/services';
    }

    protected function getDetailPath()
    {
        return 'services-details';
    }
}
