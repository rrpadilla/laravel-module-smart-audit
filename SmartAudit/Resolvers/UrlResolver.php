<?php

namespace App\Modules\SmartAudit\Resolvers;

use App\Modules\SmartAudit\Contracts\Resolver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

class UrlResolver implements Resolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        if (App::runningInConsole()) {
            return 'console';
        }

        return Request::fullUrl();
    }
}
