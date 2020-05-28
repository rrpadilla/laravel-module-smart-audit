# Smart Audit

This module is based on the [laravel auditing package](https://github.com/owen-it/laravel-auditing).

Smart Audit module allows you to keep a history of model changes by simply using a trait. Retrieving the audited data is straightforward, making it possible to display it in various ways.

Under the hood this module is using the **getDirty()** method to get the attributes that have been changed.

**THIS IS NOT A PACKAGE, IT'S A MODULE WITH A GROUP OF REUSABLE CLASES**.

![laravel-module-smart-audit](https://user-images.githubusercontent.com/6921286/83082124-610b5700-a037-11ea-9fc5-216051b921a6.png)

## Support

This module should support any version of Laravel >= 7.x.

## Installation

- Download this repository.
- Create a new directory **app/Modules** inside your **app**.
- Copy and paste the directory **SmartAudit** inside **app/Modules**.
- Copy and rename the migration file **2020_05_25_231102_create_audits_table.php** inside **database/migrations**.
- Run: **composer dump-autoload**
- Run migration: **php artisan migrate**. This will create the **audits** table in the database.

## Model Setup

Setting up a model for auditing could not be simpler. Just use the **App\Modules\SmartAudit\AuditableTrait** trait in the model you wish to audit and implement the **App\Modules\SmartAudit\Contracts\Auditable** interface.

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Modules\SmartAudit\AuditableTrait;
use App\Modules\SmartAudit\Contracts\Auditable;

class Reservation extends Model implements Auditable
{
    use AuditableTrait;

    // ...
}
```

## Getting Audits

Audit records can be fetched very easily, via Eloquent relations.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Modules\SmartAudit\SmartAudit;
use Illuminate\Http\Request;

class ReservationAuditsController extends Controller
{
    public function index(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);

        $this->authorize('viewAudits', Reservation::class);
        
        // Paginate the associated audits
        $audits = $reservation->audits()->with('user')->latest()->paginate(15);

        return view('reservations.audits.index', [
            'reservation' => $reservation,
            'audits' => $audits,
        ]);
    }

    public function show(Request $request, $id, $auditId)
    {
        $this->authorize('viewAudits', Reservation::class);

        $reservation = Reservation::findOrFail($id);

        $audit = $reservation->audits()->findOrFail($auditId);
        
        // This object will have access to the OLD and NEW Auditable models. 
        // In this case could be a Reservation instance.
        $smartAudit = new SmartAudit($audit);
        
        return view('reservations.audits.show', [
            'reservation' => $reservation,
            'audit' => $audit,
            'smartAudit' => $smartAudit,
        ]);
    }
}

```

## Add a method "displayAuditField" to the Auditable model to handle how you want to display every field.

This is very important when your model is using Value Object Casting. See [laravel documentation](https://laravel.com/docs/7.x/eloquent-mutators#custom-casts).
This is very usefull when you want to render boolean as yes/no or print a date with a custom format. See example bellow to get a better idea.

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Modules\SmartAudit\AuditableTrait;
use App\Modules\SmartAudit\Contracts\Auditable;

class Reservation extends Model implements Auditable
{
    use AuditableTrait;
    
    public function displayAuditField($field)
    {
        $resut = $this->{$field};
        switch ($field) {
            // boolean
            case 'active':
                $resut = $this->{$field} ? __('yes') : __('no');
                break;

            // dates
            case 'created_at':
            case 'updated_at':
            case 'deleted_at':
                $resut = optional($this->{$field})->format('F j, Y g:i A');
                break;

            // decimal
            case 'price':
                $resut = '$ ' . number_format($this->{$field}, 2);
                break;

            // objects
            case 'created_by':
                $resut = $this->creator ? optional($this->creator)->name : $this->{$field};
                break;
            case 'agency_id':
                $resut = $this->agency ? optional($this->agency)->name : $this->{$field};
                break;

            case 'address':
                $resut = optional($this->address)->displayAsAuditField();
                break;
        }

        return $resut;
    }

    // ...
}
```

## View

Using a reusable partial **reservations.audits.partial.audit**

```php
<?php
// This object will have access to the OLD and NEW Auditable models. 
// In this case could be a Reservation instance.
$smartAudit = new \App\Modules\SmartAudit\SmartAudit($audit);
?>
@if (count($smartAudit->modifiedKeys))
    <!-- Card -->
    <div class="card">
        <div class="card-header">
            <span>
                {{ Str::ucfirst(__("{$audit->event} by")) }}&nbsp;
                <b>{{ $audit->user ? $audit->user->name : __('Unknown') }}</b>&nbsp;
                <small class="text-muted">
                    - {{ $audit->created_at->format('F j, Y g:i A') }}
                </small>
                <small class="text-muted">
                    ({{ $audit->created_at->diffForHumans() }})
                </small>
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 150px;">{{ __('Attribute') }}</th>
                            <th>
                                <div class="row">
                                    <div class="col-6">{{ __('Old') }}</div>
                                    <div class="col-6">{{ __('New') }}</div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($smartAudit->modifiedKeys as $attribute)
                            <tr>
                                <td>
                                    <strong>@lang('reservation.field.'.$attribute)</strong>
                                </td>
                                <td>
                                    <table class="w-100">
                                        <td style="width: 50%; background: #ffe9e9;">
                                            {{ optional($smartAudit->oldModel)->displayAuditField($attribute) }}
                                        </td>
                                        <td style="background: #e9ffe9;">
                                            {{ optional($smartAudit->newModel)->displayAuditField($attribute) }}
                                        </td>
                                    </table>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Card -->
    <div class="card">
        <div class="card-header">{{ __('Metadata') }}</div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <th style="width: 150px;">
                        {{ __('Meta') }}
                    </th>
                    <th>
                        {{ __('Info') }}
                    </th>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            {{ __('IP address') }}
                        </td>
                        <td>
                            {{ $audit->ip_address }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            {{ __('User agent') }}
                        </td>
                        <td>
                            {{ $audit->user_agent }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            {{ __('URL') }}
                        </td>
                        <td>
                            {{ $audit->url }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endif
```

### Feel free to open a ticket if you have any question.

