<?php
// Icinga Web 2 Cube Module | (c) 2020 Icinga GmbH | GPLv2

namespace Icinga\Module\Cube\ProvidedHook\Cube;

use Icinga\Module\Cube\Hook\IcingadbHook;
use Icinga\Module\Cube\NavigationCard;
use Icinga\Module\Cube\ServiceDbQuery;
use ipl\Web\Url;

class IcingadbStatus extends IcingadbHook
{

    public function prepareActionLinks($db, $slices)
    {
        $type = 'host';
        if ($db instanceof ServiceDbQuery) {
            $type = 'service';
        }

        $url = $this->prepareUrl($type, $slices);

        if ($type === 'host') {
            return (new NavigationCard())
                ->setTitle('show hosts status')
                ->setDescription('This shows all matching hosts and their current state in the monitoring module')
                ->setIcon('host')
                ->setUrl($url);
        }

        return (new NavigationCard())
            ->setTitle('show services status')
            ->setDescription('This shows all matching services and their current state in the monitoring module')
            ->setIcon('host')
            ->setUrl($url);
    }

    private function prepareUrl($type, $slices)
    {
        $paramsWithPrefix = [];
        foreach ($slices as $dimension => $slice) {
            $paramsWithPrefix[$type . '.vars.' . $dimension] = $slice;
        }

        return Url::fromPath('icingadb/'. $type . 's')->with($paramsWithPrefix);
    }
}
