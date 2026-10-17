<?php

namespace Arronrees\ContentGovernance\Support;

use Statamic\Facades\Site;
use Statamic\Statamic;

class SiteTimezone
{
    public function for(string $site): string
    {
        return Site::get($site)?->attribute('timezone') ?? Statamic::displayTimezone();
    }
}
