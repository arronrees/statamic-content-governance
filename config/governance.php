<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage driver
    |--------------------------------------------------------------------------
    |
    | Where governance records and the audit log live. "file" keeps records in
    | the Stache as YAML and the audit log as JSONL. "eloquent" uses database
    | tables. Switching is never automatic: run governance:migrate-driver.
    |
    | Supported: "file", "eloquent"
    |
    */

    'driver' => env('GOVERNANCE_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | File driver paths
    |--------------------------------------------------------------------------
    |
    | Records are stored per site and collection beneath the records path.
    | The audit log is append-only and must live on persistent storage, so
    | on ephemeral hosts point it somewhere that survives a deploy.
    |
    */

    'records_path' => base_path('content/governance'),

    'audit_path' => storage_path('statamic/governance/audit'),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Who gets told what, and when. Mail goes to each record's owner, or the
    | fallback owner when a record has none. Turning notifications off stops
    | all mail but domain events still fire, so your own listeners work.
    |
    | Delivery per type: "instant" (as it happens), "digest" (in the next
    | digest) or "off". Digest time is in the site's display timezone.
    |
    */

    'notifications' => [

        'enabled' => env('GOVERNANCE_NOTIFICATIONS', true),

        'channels' => ['mail'],

        'delivery' => [
            'due' => 'digest',
            'overdue' => 'digest',
            'expiring' => 'digest',
            'expired' => 'instant',
        ],

        'digest' => [
            'time' => '08:00',
            'days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
        ],

    ],

];
