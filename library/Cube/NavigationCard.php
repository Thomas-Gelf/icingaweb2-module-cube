<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2
namespace Icinga\Module\Cube;

use dipl\Html\Icon;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Link;

class NavigationCard extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'action-links'];

    protected $tag = 'ul';

    protected $url;

    protected $target;

    protected $icon;

    protected $title;

    protected $description;


    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTaget()
    {
        return $this->target;
    }

    /**
     * @param string data-base-target
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon ?: 'forward';
    }

    /**
     * @param mixed $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }


    protected function assemble()
    {
        $this->add(
            html::tag(
                'li',
                [],
                new Link(
                    [
                        new Icon($this->getIcon()),
                        html::tag('span', ['class' => 'title'], $this->getTitle()),
                        html::tag('p', [], $this->getDescription())
                    ],
                    $this->getUrl(),
                    ['data-base-target' => '_self']
                )
            )
        );
    }
}
