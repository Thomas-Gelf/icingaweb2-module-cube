<?php
// Icinga Web 2 Cube Module | (c) 2016 Icinga GmbH | GPLv2

namespace Icinga\Module\Cube\Hook;

abstract class IcingadbHook
{
    abstract public function prepareActionLinks($db, $slices);
}
