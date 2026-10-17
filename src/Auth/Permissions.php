<?php

namespace Arronrees\ContentGovernance\Auth;

use Statamic\Facades\Permission;

class Permissions
{
    public const VIEW = 'view governance';

    public const REVIEW_OWN = 'review own governed content';

    public const REVIEW_ALL = 'review all governed content';

    public const MANAGE = 'manage governance';

    public const CONFIGURE = 'configure governance';

    public static function register(): void
    {
        Permission::extend(function () {
            Permission::group('content_governance', __('Content Governance'), function () {
                Permission::register(self::VIEW, function ($permission) {
                    $permission->label(__('View governance'))->children([
                        Permission::make(self::REVIEW_OWN)->label(__('Review own governed content')),
                        Permission::make(self::REVIEW_ALL)->label(__('Review all governed content')),
                        Permission::make(self::MANAGE)->label(__('Manage governance (owners, dates, bulk changes)')),
                    ]);
                });

                Permission::register(self::CONFIGURE)->label(__('Configure governance'));
            });
        });
    }
}
