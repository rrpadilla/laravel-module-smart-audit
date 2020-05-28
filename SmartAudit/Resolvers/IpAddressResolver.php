<?php

namespace App\Modules\SmartAudit\Resolvers;

use App\Modules\SmartAudit\Contracts\Resolver;
use Illuminate\Support\Facades\Request;

class IpAddressResolver implements Resolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(): string
    {
        return Request::ip();
    }
}
