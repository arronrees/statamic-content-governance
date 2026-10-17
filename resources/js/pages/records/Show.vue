<script setup>
import { Head, Link, router } from '@statamic/cms/inertia';
import { ItemActions } from '@statamic/cms';
import {
    Alert,
    Badge,
    Button,
    Card,
    Dropdown,
    DropdownItem,
    DropdownMenu,
    Header,
    Heading,
    Panel,
    PanelHeader,
    Subheading,
} from '@statamic/cms/ui';
import { formatDate, statusColours } from '../../status.js';

const props = defineProps({
    record: Object,
    entry: Object,
    rule: Object,
    localisations: Array,
    timeline: Array,
    actions: Array,
    actionUrl: String,
    reviewUrl: String,
});

function actionCompleted(successful, response) {
    if (!successful) return;

    Statamic.$toast.success(response.message || __('Action completed'));
    router.reload();
}
</script>

<template>
    <Head :title="record.title" />

    <div class="max-w-5xl mx-auto space-y-6">
        <Header :title="record.title" icon="clipboard-check">
            <ItemActions
                :url="actionUrl"
                :actions="actions"
                :item="record.id"
                @completed="actionCompleted"
                v-slot="{ actions: itemActions }"
            >
                <Dropdown v-if="itemActions.length">
                    <template #trigger>
                        <Button icon="dots" variant="ghost" :aria-label="__('Actions')" />
                    </template>
                    <DropdownMenu>
                        <DropdownItem
                            v-for="action in itemActions"
                            :key="action.handle"
                            :text="__(action.title)"
                            :icon="action.icon"
                            @click="action.run"
                        />
                    </DropdownMenu>
                </Dropdown>
            </ItemActions>

            <Button v-if="entry.edit_url" :href="entry.edit_url" :text="__('Open entry')" icon="edit" />
        </Header>

        <Alert v-if="entry.has_unpublished_changes" variant="warning" :heading="__('This entry has unpublished changes')">
            {{ __('A working copy is waiting to be published. Reviewing confirms the live version, not the draft.') }}
        </Alert>

        <Alert v-if="!entry.exists" variant="error" :heading="__('The entry no longer exists')" />

        <div class="grid gap-6 md:grid-cols-2">
            <Panel>
                <PanelHeader><Heading :text="__('Status')" /></PanelHeader>
                <Card class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge :color="statusColours[record.status]" :text="record.status_label" />
                        <Badge v-if="!entry.published" :text="__('Unpublished')" />
                        <Badge v-for="flag in record.flags" :key="flag" color="violet" :text="flag" />
                    </div>
                    <dl class="grid grid-cols-2 gap-2 text-sm">
                        <dt>{{ __('Owner') }}</dt>
                        <dd>{{ record.owner || __('Nobody') }}<template v-if="record.owner_is_fallback"> ({{ __('fallback') }})</template></dd>
                        <dt>{{ __('Site') }}</dt>
                        <dd>{{ record.site }}</dd>
                        <dt>{{ __('Last reviewed') }}</dt>
                        <dd>{{ formatDate(record.last_reviewed_at) }}<template v-if="record.last_reviewed_by"> · {{ record.last_reviewed_by }}</template></dd>
                        <dt>{{ __('Next review') }}</dt>
                        <dd>{{ formatDate(record.next_review_at) }}</dd>
                        <dt>{{ __('Expires') }}</dt>
                        <dd>{{ formatDate(record.expires_at) }}</dd>
                    </dl>
                </Card>
            </Panel>

            <Panel>
                <PanelHeader><Heading :text="__('Rule in effect')" /></PanelHeader>
                <Card>
                    <dl class="grid grid-cols-2 gap-2 text-sm">
                        <dt>{{ __('Review every') }}</dt>
                        <dd class="flex items-center gap-2">
                            {{ __(':count days', { count: rule.review_interval_days }) }}
                            <Badge v-if="rule.review_interval_overridden" size="sm" color="amber" :text="__('Overridden')" />
                        </dd>
                        <dt>{{ __('Due window') }}</dt>
                        <dd>{{ __(':count days', { count: rule.due_window_days }) }}</dd>
                        <dt>{{ __('Expiry') }}</dt>
                        <dd>{{ rule.expiry_enabled ? __('On') : __('Off') }}</dd>
                        <template v-if="rule.expiry_enabled">
                            <dt>{{ __('Warning') }}</dt>
                            <dd>{{ __(':count days before', { count: rule.expiry_warning_days }) }}</dd>
                        </template>
                    </dl>
                </Card>
            </Panel>
        </div>

        <Panel v-if="localisations.length">
            <PanelHeader><Heading :text="__('Localisations')" /></PanelHeader>
            <Card class="space-y-2">
                <div v-for="localisation in localisations" :key="localisation.id" class="flex items-center justify-between gap-2">
                    <Link :href="localisation.detail_url">{{ localisation.site }}</Link>
                    <Badge :color="statusColours[localisation.status]" :text="localisation.status_label" />
                </div>
            </Card>
        </Panel>

        <Panel>
            <PanelHeader><Heading :text="__('History')" /></PanelHeader>
            <Card class="space-y-4">
                <div v-for="event in timeline" :key="event.id" class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <Heading size="sm" :text="event.label" />
                        <Badge v-if="event.outcome" size="sm" :text="event.outcome" />
                    </div>
                    <Subheading :text="`${event.actor} · ${formatDate(event.at)}`" />
                    <p v-if="event.note" class="text-sm" v-text="event.note" />
                    <p v-if="event.revision_id && entry.edit_url" class="text-sm">
                        <a :href="entry.edit_url">{{ __('Revision :id', { id: event.revision_id.split('/').pop() }) }}</a>
                    </p>
                </div>
            </Card>
        </Panel>
    </div>
</template>
