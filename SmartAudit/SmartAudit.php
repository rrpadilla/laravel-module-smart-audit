<?php

namespace App\Modules\SmartAudit;

use App\Modules\SmartAudit\Contracts\Audit;

class SmartAudit
{
    /**
     * @var array
     */
    public $modifiedKeys;

    /**
     * @var array
     */
    public $modified;

    /**
     * @var \App\Modules\SmartAudit\Contracts\Auditable
     */
    public $oldModel;

    /**
     * @var \App\Modules\SmartAudit\Contracts\Auditable
     */
    public $newModel;

    /**
     * @var \App\Modules\SmartAudit\Contracts\Audit
     */
    public $audit;

    /**
     * @param \App\Modules\SmartAudit\Contracts\Audit $audit
     */
    public function __construct(Audit $audit)
    {
        $this->audit = $audit;
        $auditableClassName = get_class($audit->auditable);

        // Modified Auditable attributes
        $this->modifiedKeys = [];
        $this->modified = [];

        if (count($audit->new_values)) {
            $this->newModel = new $auditableClassName();
            $this->newModel->setRawAttributes($audit->new_values, true);
            foreach ($audit->new_values as $attribute => $value) {
                $this->modified[$attribute]['new'] = $value;
            }
        }

        if (count($audit->old_values)) {
            $this->oldModel = new $auditableClassName();
            $this->oldModel->setRawAttributes($audit->old_values, true);
            foreach ($audit->old_values as $attribute => $value) {
                $this->modified[$attribute]['old'] = $value;
            }
        }
        $this->modifiedKeys = array_keys($this->modified);
    }
}
