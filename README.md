# Content Governance

Keep track of whether your Statamic content is still owned, reviewed and in date.

Every entry in a governed collection gets an owner, a review date and an optional expiry date. Owners are reminded when content is due or expiring, reviewers attest to it from the Control Panel, and everything is recorded in an audit history. Governance data is never written into your entries.

## Requirements

- Statamic 6.34+ **Pro** (owners and review permissions rely on user roles)
- PHP 8.3+, Laravel 12 or 13
- A running scheduler. A queue worker is recommended.

## Installation

1. **Install the addon**

   ```bash
   composer require arronrees/content-governance
   ```

2. **Publish the config** to `config/statamic/governance.php`. This is where storage and notifications are set. Until it's published, the defaults apply.

   ```bash
   php artisan vendor:publish --tag=governance-config
   ```

3. **Choose a storage driver.** `file` (the default) suits most sites. Above about **5,000 governed entries** (localisations count separately), use `eloquent`:

   ```env
   GOVERNANCE_DRIVER=eloquent
   ```

   ```bash
   php artisan migrate
   ```

4. **Run the scheduler.** An hourly sweep moves content between statuses, and the digest is scheduled too:

   ```bash
   * * * * * cd /path-to-your-site && php artisan schedule:run >> /dev/null 2>&1
   ```

5. **Run a queue worker** on larger sites. Notifications are queued, and so is the backfill when a collection is enabled. On the default `sync` connection, enabling a large collection runs the backfill inside the settings save and can time out. If it does, run `php please governance:sync --queue`.

6. **Configure it** under **Governance → Settings**. Add a row on the **Collections** tab for each collection to govern, and set a fallback owner on **General**. Existing entries get records automatically.

7. **Give roles the [permissions](#permissions)** they need.

## How it works

| Status | When |
| --- | --- |
| **Current** | Reviewed and in date |
| **Due** | The review date is within the collection's due window |
| **Overdue** | The review date has passed |
| **Expired** | The expiry date has passed (wins over everything else) |

- **Dates are whole days** in the site's timezone. A review date of 30 September is overdue from the start of 1 October.
- **Clocks start on first publish**, so a long-running draft doesn't go live overdue. Scheduled entries start when they go live. An expiry set on a draft is kept.
- **A review is an explicit attestation**: *Confirmed accurate* or *Needs changes*, with an optional note. It moves the next review date one interval on. Review from the Review list, the Detail page, the entry's Governance panel, or the "Mark reviewed" entry action. Editing is never a review, though a collection can have edits restart the clock.
- **Expired content stays live.** It shows as Expired, its owner is notified, and `ContentExpired` fires so you can act on it. Governance never unpublishes anything.
- **Owners** come from the origin entry (for localisations, if allowed), then the collection's setting (author, a set person, or nobody), then the fallback owner. Unowned content is shown and notified as the fallback owner's. A deleted user's content passes to the fallback owner and is flagged *orphaned*.
- **Unpublished content** keeps its dates, but reminders and digests leave it out.

## Permissions

| Permission | Allows |
| --- | --- |
| View governance | The Dashboard, Review list, Detail pages, widget and the entry panel |
| Review own governed content | Reviewing content you own, plus unowned content if you're the fallback owner |
| Review all governed content | Reviewing anything |
| Manage governance | Reassigning owners, rescheduling, changing expiry dates |
| Configure governance | The settings screen |

Statamic's collection permissions apply too: people only see governance for collections whose entries they can view. Owner pickers only offer people who can review. Statamic's own "Configure addons" permission also opens the settings screen.

## Configuration

```php
'driver' => env('GOVERNANCE_DRIVER', 'file'),

'records_path' => base_path('content/governance'),         // file driver
'audit_path' => storage_path('statamic/governance/audit'), // file driver: must survive deploys

'notifications' => [
    'enabled' => env('GOVERNANCE_NOTIFICATIONS', true),
    'channels' => ['mail'],
    'delivery' => [                 // instant | digest | off
        'due' => 'digest',
        'overdue' => 'digest',
        'expiring' => 'digest',
        'expired' => 'instant',
    ],
    'digest' => [
        'time' => '08:00',          // the site's display timezone
        'days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
    ],
],
```

- **Mail** goes to the owner, or the fallback owner. The digest is one email per person listing everything still outstanding. Turning notifications off stops mail, but [events](#events) still fire.
- **Timezones:** sites use `statamic.system.display_timezone` unless you add a `timezone` attribute to a site in `resources/sites.yaml`, e.g. `attributes: { timezone: Pacific/Auckland }`.
- **Git:** `content/governance` changes on every review and sweep. Commit it to keep records in step between environments, or add `/content/governance` to `.gitignore` to keep them per environment.
- **Switching drivers** is never automatic. Run `php please governance:migrate-driver file eloquent`, then change `GOVERNANCE_DRIVER`. It copies everything, refuses a target that already has data, and leaves the source in place.

## Dashboard widget

Add to `widgets` in `config/statamic/cp.php`:

```php
['type' => 'content_governance', 'scope' => 'mine', 'limit' => 5, 'statuses' => ['due', 'overdue', 'expired']],
```

`scope` is `mine` or `all`. The widget is hidden from anyone without "View governance".

## Events

In `Arronrees\ContentGovernance\Events`, each carrying `$event->record`: `GovernanceRecordCreated`, `ReviewDue`, `ReviewOverdue`, `ContentExpiring`, `ContentExpired`, `ContentReviewed`, `ReviewRescheduled`, `OwnerChanged`, `RecordOrphaned`.

## Commands

| Command | |
| --- | --- |
| `php please governance:sync` | Create missing records and remove ones whose entries were deleted outside Statamic. `--collection=`, `--queue` |
| `php please governance:sweep` | Update statuses (runs hourly) |
| `php please governance:digest` | Send the digest now |
| `php please governance:migrate-driver {from} {to}` | Copy records and history between drivers |

The Dashboard warns if the sweep hasn't run for two hours. A cache clear resets this until the next run.

## Support

Email [info@arronrees.co.uk](mailto:info@arronrees.co.uk) with your Statamic version, addon version and storage driver. Release notes are in the [changelog](CHANGELOG.md).

## Licence

Commercial software. A licence from the [Statamic Marketplace](https://statamic.com/marketplace) covers one production site. See [LICENSE.md](LICENSE.md).
