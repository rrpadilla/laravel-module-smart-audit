<?php

namespace App\Modules\SmartAudit;

use App\Modules\SmartAudit\Contracts\Audit;
use Illuminate\Database\Eloquent\Model;

class AuditModel extends Model implements Audit
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audits';

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        // Note: Please do not add 'auditable_id' in here, as it will break non-integer PK models
    ];

    public function auditable()
    {
        return $this->morphTo();

        // Include trashed models if using SoftDeletes
        // return $this->morphTo()->withTrashed();
    }

    public function user()
    {
        return $this->morphTo();

        // Include trashed models if using SoftDeletes
        // return $this->morphTo()->withTrashed();
    }
}
