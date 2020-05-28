<?php

namespace App\Modules\SmartAudit;

class OldNew
{
    /**
     * @var array
     */
    public $old;

    /**
     * @var array
     */
    public $new;

    /**
     * @param array $old
     * @param array $new
     */
    public function __construct($old, $new)
    {
        $this->old = $old;
        $this->new = $new;
    }
}
