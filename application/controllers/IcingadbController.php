<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2
namespace Icinga\Module\Cube\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Hook;
use Icinga\Module\Cube\Common\AddTabs;
use Icinga\Module\Cube\Common\IcingaDb;
use Icinga\Module\Cube\CubeSettings;
use Icinga\Module\Cube\DimensionParams;
use Icinga\Module\Cube\HostCube;
use Icinga\Module\Cube\HostDbQuery;
use Icinga\Module\Cube\NavigationCard;
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

    /**
     * @var array slices
     */
    protected $slices = [];


    /** decoded dimensions from url
     *
     * @var array
     *
     */
    protected $urlDimensions;

    /** true if url param showsettings is set
     *
     * @var bool
     */
    protected $isSetShowSettings;

    /** dimensions without slices
     * @var
     */
    protected $dimensionsWithoutSlices;

    public function init()
    {
        $this->isSetShowSettings = (bool)$this->params->get('showsettings');

        //double decoding 1x with params->get(), 1x with DimensionParam
        // we need this to ignore the coma in a string, when making from string an array separated with coma
        $this->urlDimensions = DimensionParams::fromString($this->params->get('dimensions'))->getDimensions();
        $this->dimensionsWithoutSlices = $this->urlDimensions;
        // get slices
        foreach ($this->urlDimensions as $key => $dimension) {
            // because params are double encoded
            $doubleEncodedDimension = DimensionParams::update(rawurlencode($dimension))->getParams();

            if ($value = $this->params->get($doubleEncodedDimension)) {
                unset($this->dimensionsWithoutSlices[$key]);
                $this->slices[$dimension] = $value;
            }
        }

        $this->setAutorefreshInterval(15);
    }

    public function hostsAction()
    {
        $this->addControl($this->getHeader());
        $this->addControl($this->showSettings());
        $this->addTabs('hosts');
        $this->prepare('host');


        if (! empty($this->urlDimensions)) {
            $this->addContent(
                (
                    new HostCube(
                        (new HostDbQuery)->getResult($this->urlDimensions, $this->slices),
                        $this->urlDimensions,
                        $this->slices
                    )
                )
            );
        }
    }

    public function servicesAction()
    {
        $this->addControl($this->getHeader());
        $this->addControl($this->showSettings());
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

    public function hostsDetailsAction()
    {
        $this->prepareDetailPage((new HostDbQuery()));
    }

    public function servicesDetailsAction()
    {
        $this->prepareDetailPage((new ServiceDbQuery()));
    }

    public function prepareDetailPage($db)
    {
        $this->setTitle('Icingadb Cube Details');
        $headerStr = null;

        foreach ($this->slices as $dimension => $value) {
            if ($headerStr) {
                $headerStr .= ', ';
            }
            $headerStr .= $dimension . ' = ' . $value;
        }
        $this->addControl(Html::tag('h1', ['class' => 'dimension-header'], $headerStr));

        foreach (Hook::all('cube/Icingadb') as $hook) {
            $element = $hook->prepareActionLinks(
                ($db),
                $this->slices
            );
            if ($element) {
                $this->addContent($element);
            }
        }
    }

    protected function cubeSettings()
    {
        return (new CubeSettings())
            ->setBaseUrl(Url::fromRequest())
            ->setSlices($this->slices)
            ->setDimensions($this->urlDimensions)
            ->setDimensionsWithoutSlices($this->dimensionsWithoutSlices);
    }

    protected function getHeader()
    {
        $sliceStr = $this->dimensionsWithoutSlices === [] || $this->slices === [] ? '' : ', ';
        foreach ($this->slices as $key => $slice) {
            if ($key !== array_keys($this->slices)[0]) {
                $sliceStr .= ', ';
            }
            $sliceStr .= $key . ' = ' . $slice;
        }

        return Html::tag(
            'h1',
            ['class' => 'dimension-header'],
            'Cube: ' . implode(' -> ', $this->dimensionsWithoutSlices) . $sliceStr
        );
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
            if (($key = array_search($item, $dimensions))) {
                unset($dimensions[$key]);
            }
        }

        $hasNoDimensions = empty($this->urlDimensions);

        return  (new SelectDimensionForm())
            ->on(SelectDimensionForm::ON_SUCCESS, function ($selectForm) use ($hasNoDimensions) {
                if (($hasNoDimensions)) {
                    // get selected value
                    // double encoding 1x with setParam, 1x with DimensionParam
                    $this->redirectNow(
                        Url::fromRequest()->setParam(
                            'dimensions',
                            DimensionParams::update([$selectForm->getValue('dimensions')])->getParams()
                        )
                    );
                }

                $this->redirectNow(
                    Url::fromRequest()->setParam(
                        'dimensions',
                        DimensionParams::fromUrl(Url::fromRequest())
                            ->add($selectForm->getValue('dimensions'))->getParams()
                    )
                );
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
