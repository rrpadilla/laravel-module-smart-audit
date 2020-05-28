<?php

namespace App\Modules\SmartAudit\Contracts;

interface Resolver
{
    /**
     * Resolve the meta.
     *
     * @return mixed|null
     */
    public static function resolve();
}
