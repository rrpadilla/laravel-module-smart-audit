<?php

namespace App\Modules\SmartAudit\Contracts;

interface Audit
{
    /**
     * Get the auditable model to which this Audit belongs.
     *
     * @return mixed
     */
    public function auditable();

    /**
     * User responsible for the changes.
     *
     * @return mixed
     */
    public function user();
}
