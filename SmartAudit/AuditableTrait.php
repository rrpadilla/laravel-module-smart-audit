<?php

namespace App\Modules\SmartAudit;

use App\Modules\SmartAudit\Contracts\Auditable;
use App\Modules\SmartAudit\Exceptions\AuditingException;
use App\Modules\SmartAudit\Resolvers\IpAddressResolver;
use App\Modules\SmartAudit\Resolvers\UrlResolver;
use App\Modules\SmartAudit\Resolvers\UserAgentResolver;
use App\Modules\SmartAudit\Resolvers\UserResolver;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

trait AuditableTrait
{
    /**
     * Is auditing disabled?
     *
     * @var bool
     */
    public static $auditingDisabled = false;

    /**
     * Console events should be audited (eg. php artisan db:seed).
     */
    public static $auditInConsole = true;

    /**
     * Audit event name.
     *
     * @var string
     */
    protected $auditEvent;

    /**
     * Auditable events.
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    /**
     * Auditable boot logic.
     *
     * @return void
     */
    public static function bootAuditableTrait()
    {
        if (! static::$auditingDisabled && static::isAuditingEnabled()) {
            static::observe(new AuditableObserver());
        }
    }

    /**
     * Determine whether auditing is enabled.
     *
     * @return bool
     */
    public static function isAuditingEnabled(): bool
    {
        if (App::runningInConsole()) {
            return static::$auditInConsole;
        }

        return true;
    }

    /**
     * Disable Auditing.
     *
     * @return void
     */
    public static function disableAuditing()
    {
        static::$auditingDisabled = true;
    }

    /**
     * Enable Auditing.
     *
     * @return void
     */
    public static function enableAuditing()
    {
        static::$auditingDisabled = false;
    }

    /**
     * {@inheritdoc}
     */
    public function audits(): MorphMany
    {
        return $this->morphMany($this->getAuditModelFQCN(), 'auditable');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditModelFQCN(): string
    {
        return AuditModel::class;
    }

    /**
     * {@inheritdoc}
     */
    public function toAudit(): array
    {
        if (! $this->readyForAuditing()) {
            throw new AuditingException('A valid audit event has not been set');
        }

        $attributeGetter = $this->auditResolveAttributeGetter($this->auditEvent);

        if (! method_exists($this, $attributeGetter)) {
            throw new AuditingException(sprintf('Unable to handle "%s" event, %s() method missing', $this->auditEvent, $attributeGetter));
        }

        $oldNew = $this->$attributeGetter();
        $old = $oldNew->old;
        $new = $oldNew->new;

        // Skip audit if no values to save.
        if (empty($old) && empty($new)) {
            return [];
        }

        $morphPrefix = 'user';
        $user = UserResolver::resolve();

        return [
            'old_values' => $old,
            'new_values' => $new,
            'event' => $this->auditEvent,
            'auditable_id' => $this->getKey(),
            'auditable_type' => $this->getMorphClass(),
            $morphPrefix.'_id' => $user ? $user->getAuthIdentifier() : null,
            $morphPrefix.'_type' => $user ? $user->getMorphClass() : null,
            'url' => UrlResolver::resolve(),
            'ip_address' => IpAddressResolver::resolve(),
            'user_agent' => UserAgentResolver::resolve(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function readyForAuditing(): bool
    {
        if (static::$auditingDisabled) {
            return false;
        }

        return $this->isEventAuditable($this->auditEvent);
    }

    /**
     * Determine whether an event is auditable.
     *
     * @param string $event
     *
     * @return bool
     */
    protected function isEventAuditable($event): bool
    {
        return is_null($event) ? false : is_string($this->auditResolveAttributeGetter($event));
    }

    /**
     * Attribute getter method resolver.
     * Could be any of this:
     * getCreatedEventAttributes,
     * getUpdatedEventAttributes,
     * getDeletedEventAttributes,
     * getRestoredEventAttributes
     *
     * @param string $event
     *
     * @return string|null
     */
    protected function auditResolveAttributeGetter($event)
    {
        return sprintf('get%sEventAttributes', ucfirst($event));
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditEvent()
    {
        return $this->auditEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuditEvent(string $event): Auditable
    {
        $this->auditEvent = $this->isEventAuditable($event) ? $event : null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditEvents(): array
    {
        return $this->auditEvents ?? [
                'created',
                'updated',
                'deleted',
                'restored',
            ];
    }

    /**
     * Determine if an attribute is eligible for auditing.
     *
     * @param string $attribute
     *
     * @return bool
     */
    protected function isAttributeAuditable(string $attribute): bool
    {
        // The attribute should not be audited
        $exclude = $this->getAuditExclude();
        if (! empty($exclude) && in_array($attribute, $exclude, true)) {
            return false;
        }

        // The attribute is auditable when explicitly
        // listed or when the include array is empty
        $include = $this->getAuditInclude();

        return empty($include) || in_array($attribute, $include, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditExclude(): array
    {
        return $this->auditExclude ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditInclude(): array
    {
        return $this->auditInclude ?? [];
    }

    /**
     * Get the old/new attributes of a created event.
     *
     * @return \App\Modules\SmartAudit\OldNew
     */
    protected function getCreatedEventAttributes(): OldNew
    {
        $new = [];

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $new[$attribute] = $value;
            }
        }

        return new OldNew([], $new);
    }

    /**
     * Get the old/new attributes of an updated event.
     *
     * @return \App\Modules\SmartAudit\OldNew
     */
    protected function getUpdatedEventAttributes(): OldNew
    {
        $old = [];
        $new = [];

        foreach ($this->getDirty() as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $old[$attribute] = Arr::get($this->original, $attribute);
                $new[$attribute] = Arr::get($this->attributes, $attribute);
            }
        }

        return new OldNew($old, $new);
    }

    /**
     * Get the old/new attributes of a restored event.
     *
     * @return \App\Modules\SmartAudit\OldNew
     */
    protected function getRestoredEventAttributes(): OldNew
    {
        // A restored event is just a deleted event in reverse
        $oldNew = $this->getDeletedEventAttributes();

        return new OldNew($oldNew->new, $oldNew->old);
    }

    /**
     * Get the old/new attributes of a deleted event.
     *
     * @return \App\Modules\SmartAudit\OldNew
     */
    protected function getDeletedEventAttributes(): OldNew
    {
        $old = [];

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $old[$attribute] = $value;
            }
        }

        return new OldNew($old, []);
    }
}
