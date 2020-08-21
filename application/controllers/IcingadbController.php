<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2
namespace Icinga\Module\Cube\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Cube\Common\AddTabs;
use Icinga\Module\Cube\Common\IcingaDb;
use Icinga\Module\Cube\CubeSettings;
use Icinga\Module\Cube\HostCube;
use Icinga\Module\Cube\HostDbQuery;
use Icinga\Module\Cube\SelectDimensionForm;
use Icinga\Module\Cube\ServiceCube;
use Icinga\Module\Cube\ServiceDbQuery;
use ipl\Html\Html;
use ipl\Sql\Select;
use ipl\Stdlib\Str;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;
use PDO;

/**
 * Icingadb controller
 */
class IcingadbController extends CompatController
{
    use IcingaDb;
    use AddTabs;

    /**
     * @var int Limit for the dimensions
     *
     * "Add a dimension" drop-down menu is removed when the limit is exceeded.
     */
    protected static $DIMENSION_LIMIT = 3;

    protected $slices = [];

    protected $urlDimensions;

    protected $isSetShowSettings;

    public function init()
    {
        $this->isSetShowSettings = $this->params->get('showsettings');
        //$urlDimensions is null or string
        $this->urlDimensions = array_filter(
            Str::trimSplit($this->params->get('dimensions'))
        );

        $dimensionsWithoutSlices = $this->urlDimensions;
        // get slices
        foreach ($this->urlDimensions as $key => $dimension) {
            if ($this->params->has($dimension)) {
                unset($dimensionsWithoutSlices[$key]);
                $this->slices[$dimension] = $this->params->get($dimension);
            }
        }
        // prepare header string for slices
        $sliceStr = $dimensionsWithoutSlices === [] || $this->slices === [] ? '' : ', ';
        foreach ($this->slices as $key => $slice) {
            if ($key !== array_keys($this->slices)[0]) {
                $sliceStr .= ', ';
            }
            $sliceStr .= $key . ' = ' . $slice;
        }

        $header = Html::tag(
            'h1',
            ['class' => 'dimension-header'],
            'Cube: ' . implode(' -> ', $dimensionsWithoutSlices) . $sliceStr
        );
        $this->addControl($header);

        $this->addControl($this->showSettings());

        $this->setAutorefreshInterval(15);
    }

    public function hostsAction()
    {
        $this->addTabs('hosts');
        $this->prepare('host');


        if (! empty($this->urlDimensions)) {
            $this->addContent((new HostCube(
                (new HostDbQuery)->getResult($this->urlDimensions, $this->slices),
                $this->urlDimensions,
                $this->slices
            )));
        }
    }

    public function servicesAction()
    {
        $this->addTabs('services');
        $this->prepare('service');

        if (! empty($this->urlDimensions)) {
            $this->addContent((new ServiceCube(
                (new ServiceDbQuery)->getResult($this->urlDimensions, $this->slices),
                $this->urlDimensions,
                $this->slices
            )));
        }
    }

    protected function cubeSettings()
    {
        return (new CubeSettings())
            ->setBaseUrl(Url::fromRequest())
            ->setSlices($this->slices)
            ->setDimensions($this->urlDimensions);
    }

    protected function showSettings()
    {
        if (empty($this->urlDimensions) || $this->isSetShowSettings) {
            return new ActionLink(
                $this->translate('Hide settings'),
                Url::fromRequest()->remove('showsettings'),
                'wrench',
                ['data-base-target' => '_self']
            );
        }
        return  new ActionLink(
            $this->translate('Show settings'),
            Url::fromRequest()->addParams(['showsettings' => 1]),
            'wrench',
            ['data-base-target' => '_self']
        );
    }

    protected function selectDimensionForm($cubeType)
    {
        $select = (new Select())
            ->columns('customvar.name')
            ->from($cubeType)
            ->join(
                $cubeType . '_customvar',
                $cubeType . '_customvar.' . $cubeType . '_id = ' . $cubeType . '.id'
            )
            ->join(
                'customvar',
                'customvar.id = ' . $cubeType . '_customvar.customvar_id'
            )
            ->groupBy('customvar.name');

        $dimensions = $this->getDb()->select($select)->fetchAll(PDO::FETCH_COLUMN, 0);

        // remove already selected items from the option list
        foreach ($this->urlDimensions as $item) {
            if (($key = array_search($item, $dimensions)) !== false) {
                unset($dimensions[$key]);
            }
        }

        $urlDimensionsAsString = implode(',', $this->urlDimensions);

        return  (new SelectDimensionForm())
            ->on(SelectDimensionForm::ON_SUCCESS, function ($selectForm) use ($urlDimensionsAsString) {
                if (empty($urlDimensionsAsString)) {
                    // get the selected value
                    $newUrlDimensions = $selectForm->getValue('dimensions');
                } else {
                    $newUrlDimensions = $urlDimensionsAsString . ',' . $selectForm->getValue('dimensions');
                }

                $this->redirectNow(Url::fromRequest()->with('dimensions', $newUrlDimensions));
            })
            ->setDimensions($dimensions)
            ->handleRequest(ServerRequest::fromGlobals());
    }

    protected function prepare($cubeType)
    {
        $selectForm = $this->selectDimensionForm($cubeType);
        if ($this->isSetShowSettings || empty($this->urlDimensions)) {
            $this->addContent($selectForm);
        }

        if (count($this->urlDimensions) === static::$DIMENSION_LIMIT) {
            $selectForm->remove($selectForm->getElement('dimensions'));
        }

        if (!empty($this->urlDimensions) && $this->isSetShowSettings) {
            $this->addContent($this->cubeSettings());
        }
    }
}
