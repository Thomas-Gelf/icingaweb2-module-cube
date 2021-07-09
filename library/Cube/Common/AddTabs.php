<?php
namespace Icinga\Module\Cube\Common;

use Icinga\Web\Widget\Tab;
use ipl\Web\Url;

trait AddTabs
{
    public function addTabs($active)
    {
        $param = '';
        if (! empty(Url::fromRequest()->getParams()->toString())) {
            $param = '?' . Url::fromRequest()->getParams()->toString();
        }
        $this->getTabs()->add('hosts', new Tab([
            'title' => 'hosts',
            'url' => Url::fromPath('cube/icingadb/hosts' . $param),
        ]));

        $this->getTabs()->add('services', new Tab([
            'title' => 'services',
            'url' => Url::fromPath('cube/icingadb/services' . $param),
        ]));

        $this->getTabs()->activate($active);
    }
}
