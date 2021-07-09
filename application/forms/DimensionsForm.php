<?php
// Icinga Web 2 Cube Module | (c) 2016 Icinga GmbH | GPLv2

namespace Icinga\Module\Cube\Forms;

use Icinga\Module\Cube\Cube;
use Icinga\Module\Cube\DimensionParams;
use Icinga\Module\Cube\Web\Form\QuickForm;
use Icinga\Module\Cube\Web\IconHelper;

class DimensionsForm extends QuickForm
{
    /**
     * @var Cube
     */
    private $cube;

    public function setCube(Cube $cube)
    {
        $this->cube = $cube;
        return $this;
    }

    public function setup()
    {
        $cube = $this->cube;

        if (count($cube->listDimensions()) < 3) {
            $dimensions = array_diff(
                $cube->listAdditionalDimensions(),
                $cube->listDimensions()
            );

            if (! empty($dimensions)) {
                $dimensions = array_combine($dimensions, $dimensions);
            }

            $this->addElement('select', 'addDimension', [
                'multiOptions'  => [null => $this->translate('+ Add a dimension')] + $dimensions,
                'decorators'    => ['ViewHelper'],
                'class'         => 'autosubmit'
            ]);
        }

        $dimensions = $cube->listDimensions();
        $cnt = count($dimensions);
        foreach ($dimensions as $pos => $dimension) {
            $this->addDimensionButtons($dimension, $pos, $cnt);
        }

        foreach ($cube->getSlices() as $key => $value) {
            $this->addSlice($key, $value);
        }


        $this->setSubmitLabel(false);
    }

    protected function addSlice($key, $value)
    {
        $view = $this->getView();

        $sha1Key = sha1($key);
        $this->addElement('button', 'removeSlice_' . $sha1Key, array(
            'label' => null,
            'decorators' => array('ViewHelper'),
            'value' => $key,
            'type' => 'submit',
            'escape' => false,
            'name' => 'removeSlice',
            'class' => 'dimension-control icon-cancel'
        ));

        $label = $view->escape(
            sprintf(
                '%s: %s = %s',
                $view->translate('Slice/Filter'),
                $key,
                $value
            )
        );

        $this->addHtml(
            '<span class="dimension-name">' . $label . '</span>',
            array('name' => 'slice_' . $sha1Key)
        );

        $this->addSimpleDisplayGroup(
            array(
                'removeSlice_' . $sha1Key,
                'slice_' . $sha1Key,
            ),
            $key,
            array('class' => 'dimensions')
        );
    }

    protected function addDimensionButtons($dimension, $pos, $total)
    {
        $this->addHtml(
            '<span class="dimension-name">' . $this->getView()->escape($dimension) . '</span>',
            array('name' => 'dimension_' . $dimension)
        );
        $icons = IconHelper::instance();
        $sha1 = sha1($dimension);
        $this->addElement('button', 'removeDimension_' . $sha1, array(
            'label' => null,
            'decorators' => array('ViewHelper'),
            'title' => sprintf($this->translate('Remove dimension "%s"'), $dimension),
            'value' => $dimension,
            'type' => 'submit',
            'escape' => false,
            'class' => 'dimension-control icon-cancel',
            'name' => 'removeDimension'
        ));

        if ($pos > 0) {
            $this->addElement('button', 'moveDimensionUp_' . $sha1, array(
                'label' => null,
                'decorators' => array('ViewHelper'),
                'title' => sprintf($this->translate('Move dimension "%s" up'), $dimension),
                'value' => $dimension,
                'type' => 'submit',
                'escape' => false,
                'class' => 'dimension-control icon-angle-double-up',
                'name' => 'moveDimensionUp'
            ));
        }

        if ($pos + 1 !== $total) {
            $this->addElement('button', 'moveDimensionDown_' . $sha1, array(
                'label' => null,
                'decorators' => array('ViewHelper'),
                'title' => sprintf($this->translate('Move dimension "%s" down'), $dimension),
                'value' => $dimension,
                'type' => 'submit',
                'escape' => false,
                'class' => 'dimension-control icon-angle-double-down',
                'name' => 'moveDimensionDown'
            ));
        }

        $this->addSimpleDisplayGroup(
            array(
                'removeDimension_' . $sha1,
                'moveDimensionUp_' . $sha1,
                'moveDimensionDown_' . $sha1,
                'dimension_' . $dimension,
            ),
            $dimension,
            array('class' => 'dimensions')
        );
    }

    public function onRequest()
    {
        parent::onRequest();
        if (! $this->hasBeenSent()) {
            return;
        }

        $url = $this->getSuccessUrl();
        $post = $this->getRequest()->getPost();
        $this->populate($post);
        $cube = $this->cube;
        $dimension = null;

        if ($dimension = $this->getSentValue('addDimension')) {
            $url->setParam('dimensions', DimensionParams::fromUrl($url)->add($dimension)->getParams());
            $this->setSuccessUrl($url->without('addDimension'));
            $this->redirectOnSuccess($this->translate('New dimension has been added'));
        }

        if (isset($post['removeDimension'])) {
            $dimension = $post['removeDimension'];
            $cube->removeDimension($dimension);
        } elseif (isset($post['moveDimensionUp'])) {
            $dimension = $post['moveDimensionUp'];
            $cube->moveDimensionUp($dimension);
        } elseif (isset($post['moveDimensionDown'])) {
            $dimension = $post['moveDimensionDown'];
            $cube->moveDimensionDown($dimension);
        } elseif (isset($post['removeSlice'])) {
            $dimension = $post['removeSlice'];
            $url->getParams()->remove(rawurlencode($dimension));
        }

        if ($dimension) {
            $dimensions = array_merge($cube->listDimensions(), $cube->listSlices());
            if (! isset($post['removeSlice'])) {
                $url->setParam('dimensions', DimensionParams::update($dimensions)->getParams());
            }
            $this->redirectAndExit($url);
        }
    }
}
