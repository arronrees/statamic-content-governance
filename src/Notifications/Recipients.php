<?php

namespace Arronrees\ContentGovernance\Notifications;

use Arronrees\ContentGovernance\Records\GovernanceRecord;
use Arronrees\ContentGovernance\Support\GeneralSettings;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\User as Users;

class Recipients
{
    public function __construct(private GeneralSettings $settings) {}

    public function for(GovernanceRecord $record): ?User
    {
        return $this->find($record->ownerId()) ?? $this->find($this->settings->fallbackOwnerId());
    }

    private function find(?string $userId): ?User
    {
        return $userId === null ? null : Users::find($userId);
    }
}
