<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2
namespace Icinga\Module\Cube;

use ipl\Web\Compat\CompatForm;

class SelectDimensionForm extends CompatForm
{
    /** @var array Available dimensions */
    protected $dimensions;

    /**
     * Get dimensions
     *
     * @return array
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

    protected function assemble()
    {
        $this->addElement(
            'select',
            'dimensions',
            [
                'class'         => 'autosubmit',
                'label'         => 'Dimension',
                'multiOptions'  => array_merge(
                    ['' => '+ Add a dimension'],
                    array_combine($this->getDimensions(), $this->getDimensions())
                ),
                'disable'       => [''],
                'value'         => ''
            ]
        );
    }
}
