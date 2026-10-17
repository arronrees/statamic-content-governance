<?php

namespace Arronrees\ContentGovernance\Drivers\Eloquent;

use Illuminate\Database\Eloquent\Model;

class AuditEventModel extends Model
{
    protected $table = 'governance_audit_events';

    protected $primaryKey = 'sequence';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}
