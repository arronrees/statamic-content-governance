<?php

namespace Arronrees\ContentGovernance\Drivers\Eloquent;

use Illuminate\Database\Eloquent\Model;

class GovernanceRecordModel extends Model
{
    protected $table = 'governance_records';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'flags' => 'array',
        'review_interval_days' => 'integer',
    ];
}
