<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2

namespace Icinga\Module\Cube;

use Icinga\Web\Url;

class DimensionParams
{

    /**
     * @var array Raw dimensions
     */
    protected $dimensions = [];

    /**
     * @var string encoded dimensions separated by coma
     */
    protected $params;

    // For the form: DimensionsParam::fromUrl($url)
    public static function fromUrl(Url $url)
    {
        return static::fromString($url->getParam('dimensions'));
    }

    public static function fromArray(array $dimensions = [])
    {
        $self = new static();

        $self->dimensions = array_map('rawurldecode', array_filter($dimensions));

        return $self;
    }

    // For the controller: DimensionsParam::fromArray($this->params->shift('dimensions'))
    public static function fromString($dimensions)
    {
        return static::fromArray(explode(',', $dimensions));
    }


    /**
     * @param $dimension
     *
     * @return $this
     */
    public function add($dimension)
    {
        if (! empty($dimension)) {
            $this->dimensions[] = $dimension;
        }

        return $this;
    }

    /**
     * Overwrite dimensions
     *
     * @param $dimensions
     *
     * @return $this
     */
    public static function update($dimensions)
    {
        $self =  new static();
        $self->dimensions = $dimensions;

        return $self;
    }

    /**
     * @return string encoded dimensions separated by coma
     */
    public function getParams()
    {
        return implode(',', array_map('rawurlencode', $this->dimensions));
    }

    /**
     * @return array
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }
}
