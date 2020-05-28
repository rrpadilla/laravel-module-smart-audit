<?php

namespace App\Modules\SmartAudit\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Auditable
{
    /**
     * Auditable Model audits.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function audits(): MorphMany;

    /**
     * Get the Audit model to be used.
     *
     * @return string
     */
    public function getAuditModelFQCN(): string;

    /**
     * Return data for an Audit.
     *
     * @return array
     * @throws \App\Modules\SmartAudit\Exceptions\AuditingException
     *
     */
    public function toAudit(): array;

    /**
     * Is the model ready for auditing?
     *
     * @return bool
     */
    public function readyForAuditing(): bool;

    /**
     * Set the Audit event.
     *
     * @param string $event
     *
     * @return Auditable
     */
    public function setAuditEvent(string $event): Auditable;

    /**
     * Get the Audit event that is set.
     *
     * @return string|null
     */
    public function getAuditEvent();

    /**
     * Get the events that trigger an Audit.
     *
     * @return array
     */
    public function getAuditEvents(): array;

    /**
     * Get the (Auditable) attributes included in audit.
     *
     * @return array
     */
    public function getAuditInclude(): array;

    /**
     * Get the (Auditable) attributes excluded from audit.
     *
     * @return array
     */
    public function getAuditExclude(): array;
}
