<?php

namespace App\Modules\SmartAudit;

use App\Modules\SmartAudit\Contracts\Auditable;
use App\Modules\SmartAudit\Events\Audited;

class Auditor
{
    /**
     * Perform an audit.
     *
     * @param \App\Modules\SmartAudit\Contracts\Auditable $model
     *
     * @return void
     * @throws \App\Modules\SmartAudit\Exceptions\AuditingException
     */
    public static function execute(Auditable $model)
    {
        // Check
        if (! $model->readyForAuditing()) {
            return;
        }

        $auditData = $model->toAudit();
        // Skip audit if no values to save.
        if (! empty($auditData)) {
            // Audit
            $audit = call_user_func([$model->getAuditModelFQCN(), 'create'], $auditData);

            // Dispatch event
            event(new Audited($model, $audit));
        }
    }
}
