<?php

namespace Arronrees\ContentGovernance\Drivers;

use Arronrees\ContentGovernance\Contracts\AuditLogRepository;
use Arronrees\ContentGovernance\Contracts\GovernanceRecordRepository;
use Arronrees\ContentGovernance\Drivers\Eloquent\EloquentAuditLogRepository;
use Arronrees\ContentGovernance\Drivers\Eloquent\EloquentRecordRepository;
use Arronrees\ContentGovernance\Drivers\File\FileRecordRepository;
use Arronrees\ContentGovernance\Drivers\File\JsonlAuditLogRepository;
use Arronrees\ContentGovernance\Exceptions\InvalidDriver;
use Illuminate\Contracts\Container\Container;

class Drivers
{
    public const FILE = 'file';

    public const ELOQUENT = 'eloquent';

    public function __construct(private Container $app) {}

    public static function ensureValid(string $driver): void
    {
        if (! in_array($driver, [self::FILE, self::ELOQUENT], true)) {
            throw InvalidDriver::named($driver);
        }
    }

    public function records(string $driver): GovernanceRecordRepository
    {
        return match ($driver) {
            self::FILE => $this->app->make(FileRecordRepository::class),
            self::ELOQUENT => $this->app->make(EloquentRecordRepository::class),
            default => throw InvalidDriver::named($driver),
        };
    }

    public function audit(string $driver): AuditLogRepository
    {
        return match ($driver) {
            self::FILE => new JsonlAuditLogRepository(config('statamic.governance.audit_path')),
            self::ELOQUENT => new EloquentAuditLogRepository,
            default => throw InvalidDriver::named($driver),
        };
    }
}
