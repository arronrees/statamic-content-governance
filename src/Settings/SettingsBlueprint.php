<?php

namespace Arronrees\ContentGovernance\Settings;

use Arronrees\ContentGovernance\Query\Scopes\GovernanceReviewers;
use Arronrees\ContentGovernance\Rules\OwnerStrategy;
use Arronrees\ContentGovernance\Rules\Reviewer;
use Arronrees\ContentGovernance\Rules\UniqueCollectionRows;

class SettingsBlueprint
{
    public static function contents(): array
    {
        return [
            'tabs' => [
                'general' => [
                    'display' => __('General'),
                    'sections' => [[
                        'fields' => [
                            self::field('fallback_owner', [
                                'type' => 'users',
                                'display' => __('Fallback owner'),
                                'instructions' => __('Owns content when nobody else can: no author, a deleted owner, or an unowned collection. Also receives unowned content\'s notifications.'),
                                'max_items' => 1,
                                'mode' => 'select',
                                'query_scopes' => [GovernanceReviewers::handle()],
                                'validate' => ['new '.Reviewer::class],
                            ]),
                            self::field('default_due_window_days', [
                                'type' => 'integer',
                                'display' => __('Default due window (days)'),
                                'instructions' => __('How long before the review date content counts as due, for collections that don\'t set their own.'),
                                'default' => 14,
                                'validate' => 'nullable|integer|min:0',
                            ]),
                            self::field('mail_from_address', [
                                'type' => 'text',
                                'input_type' => 'email',
                                'display' => __('Send notifications from'),
                                'instructions' => __('Leave empty to use the site\'s mail settings. Which notifications go out, and when, is set in `config/statamic/governance.php`.'),
                                'width' => 50,
                                'validate' => 'nullable|email',
                            ]),
                            self::field('mail_from_name', [
                                'type' => 'text',
                                'display' => __('Sender name'),
                                'width' => 50,
                            ]),
                        ],
                    ]],
                ],
                'collections' => [
                    'display' => __('Collections'),
                    'sections' => [[
                        'fields' => [
                            self::field('collections', [
                                'type' => 'grid',
                                'display' => __('Governed collections'),
                                'instructions' => __('One row per collection. Only enabled collections are governed.'),
                                'mode' => 'stacked',
                                'add_row' => __('Add collection'),
                                'validate' => ['new '.UniqueCollectionRows::class],
                                'fields' => self::ruleFields(),
                            ]),
                        ],
                    ]],
                ],
            ],
        ];
    }

    private static function ruleFields(): array
    {
        $expiry = ['expiry_enabled' => true];

        return [
            self::field('collection', ['type' => 'collections', 'display' => __('Collection'), 'max_items' => 1, 'mode' => 'select', 'validate' => 'required', 'width' => 50]),
            self::field('enabled', ['type' => 'toggle', 'display' => __('Governed'), 'default' => true, 'width' => 50]),
            self::field('review_interval_days', ['type' => 'integer', 'display' => __('Review every (days)'), 'default' => 180, 'validate' => 'required|integer|min:1', 'width' => 50]),
            self::field('due_window_days', ['type' => 'integer', 'display' => __('Due window (days)'), 'instructions' => __('Empty uses the general default.'), 'validate' => 'nullable|integer|min:0', 'width' => 50]),
            self::field('default_owner_strategy', [
                'type' => 'button_group',
                'display' => __('Default owner'),
                'options' => [
                    OwnerStrategy::Author->value => __('Entry author'),
                    OwnerStrategy::CollectionDefault->value => __('A set person'),
                    OwnerStrategy::None->value => __('Nobody'),
                ],
                'default' => OwnerStrategy::Author->value,
                'width' => 50,
            ]),
            self::field('default_owner_id', [
                'type' => 'users',
                'display' => __('Owner'),
                'max_items' => 1,
                'mode' => 'select',
                'query_scopes' => [GovernanceReviewers::handle()],
                'validate' => ['new '.Reviewer::class],
                'width' => 50,
            ]),
            self::field('localisations_inherit_owner', ['type' => 'toggle', 'display' => __('Localisations inherit the owner'), 'default' => true, 'width' => 50]),
            self::field('edit_resets_review', ['type' => 'toggle', 'display' => __('Editing restarts the review clock'), 'instructions' => __('Off: edits are logged but the review date stays. Never counts as a review either way.'), 'default' => false, 'width' => 50]),
            self::field('expiry_enabled', ['type' => 'toggle', 'display' => __('Content expires'), 'default' => false, 'width' => 50]),
            self::field('default_expiry_days', ['type' => 'integer', 'display' => __('Expire after (days)'), 'instructions' => __('From first publish. Empty means no default expiry.'), 'validate' => 'nullable|integer|min:1', 'if' => $expiry, 'width' => 50]),
            self::field('expiry_warning_days', ['type' => 'integer', 'display' => __('Warn before expiry (days)'), 'default' => 14, 'validate' => 'nullable|integer|min:0', 'if' => $expiry, 'width' => 50]),
        ];
    }

    private static function field(string $handle, array $field): array
    {
        return ['handle' => $handle, 'field' => $field];
    }
}
