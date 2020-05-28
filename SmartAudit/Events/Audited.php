<?php

namespace App\Modules\SmartAudit\Events;

use App\Modules\SmartAudit\Contracts\Audit;
use App\Modules\SmartAudit\Contracts\Auditable;

class Audited
{
    /**
     * The Auditable model.
     *
     * @var \App\Modules\SmartAudit\Contracts\Auditable
     */
    public $model;

    /**
     * The Audit model.
     *
     * @var \App\Modules\SmartAudit\Contracts\Audit|null
     */
    public $audit;

    /**
     * Create a new Audited event instance.
     *
     * @param \App\Modules\SmartAudit\Contracts\Auditable $model
     * @param \App\Modules\SmartAudit\Contracts\Audit $audit
     */
    public function __construct(Auditable $model, Audit $audit = null)
    {
        $this->model = $model;
        $this->audit = $audit;
    }
}
