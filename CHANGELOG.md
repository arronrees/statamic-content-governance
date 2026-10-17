# Changelog

## 1.0.2 - 2026-10-17

- The Dashboard's status cards now sit three across on larger screens, instead of being stuck at two at every width.

## 1.0.1 - 2026-10-17

- Collections with expiry turned off no longer offer "Set or clear expiry", show expiry dates, or list content under "Expiring in the next 30 days". A stored date is kept and comes back if expiry is turned on again.
- Day counts on the Detail page are pluralised properly ("1 day", not "1 days").

## 1.0.0 - 2026-10-17

First release.

- Owners for every governed entry: from the author, a set person, or a fallback owner.
- Review dates with a due window, and expiry dates with a warning period. Expired content is reported, never unpublished.
- Review list with filters and bulk actions: mark reviewed, reschedule, reassign owner, set or clear expiry, review all localisations.
- Detail page per entry with its rule, localisations and full audit history.
- Dashboard with status counts, a per-collection breakdown, content expiring soon and recent activity, plus a dashboard widget.
- Governance panel and "Mark reviewed" action on the entry form and entry listings.
- Email reminders, instant or in a daily digest, configurable per type.
- Flat-file or database storage, with a command to migrate between them.
- Multisite aware, with per-site timezones.
- Requires Statamic 6.34+ Pro, PHP 8.3+, Laravel 12 or 13.
