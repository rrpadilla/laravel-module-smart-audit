<?php

namespace App\Modules\SmartAudit\Resolvers;

use App\Modules\SmartAudit\Contracts\Resolver;
use Illuminate\Support\Facades\Request;

class UserAgentResolver implements Resolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        return Request::header('User-Agent');
    }
}
