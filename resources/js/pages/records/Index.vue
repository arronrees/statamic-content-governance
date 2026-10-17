<script setup>
import { Head, Link } from '@statamic/cms/inertia';
import { Alert, Badge, DropdownItem, Header, Listing } from '@statamic/cms/ui';
import { formatDate, statusColours } from '../../status.js';

defineProps({
    proRequired: Boolean,
    columns: Array,
    filters: Array,
    actionUrl: String,
});
</script>

<template>
    <Head :title="__('Review')" />

    <div class="max-w-full mx-auto">
        <Header :title="__('Review')" icon="clipboard-check" />

        <Alert v-if="proRequired" variant="warning" :heading="__('Content Governance needs Statamic Pro')">
            {{ __('Owners and review permissions depend on user roles, which are a Pro feature. Upgrade to Pro before relying on governance.') }}
        </Alert>

        <Listing
            :url="cp_url('governance/records')"
            :columns
            :filters
            :action-url
            sort-column="next_action_at"
            sort-direction="asc"
            preferences-prefix="governance.records"
            push-query
        >
            <template #cell-title="{ row }">
                <div class="flex flex-wrap items-center gap-2" :class="{ 'ps-6': row.is_localisation }">
                    <Link :href="row.detail_url" v-text="row.title" />
                    <Badge v-if="!row.published" size="sm" :text="__('Unpublished')" />
                    <Badge v-for="flag in row.flags" :key="flag" size="sm" color="violet" :text="flag" />
                </div>
            </template>

            <template #cell-status="{ row }">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge :color="statusColours[row.status]" :text="row.status_label" />
                    <Badge
                        v-if="row.group_status && row.group_status !== row.status"
                        size="sm"
                        :color="statusColours[row.group_status]"
                        :text="__('Localisations: :status', { status: row.group_status_label })"
                    />
                </div>
            </template>

            <template #cell-owner="{ row }">
                {{ row.owner || '—' }}<template v-if="row.owner_is_fallback"> ({{ __('fallback') }})</template>
            </template>

            <template #cell-next_review_at="{ value }">{{ formatDate(value) }}</template>
            <template #cell-expires_at="{ value }">{{ formatDate(value) }}</template>
            <template #cell-next_action_at="{ value }">{{ formatDate(value) }}</template>

            <template #prepended-row-actions="{ row }">
                <DropdownItem :text="__('View details')" :href="row.detail_url" icon="eye" />
                <DropdownItem v-if="row.entry_url" :text="__('Open entry')" :href="row.entry_url" icon="edit" />
            </template>
        </Listing>
    </div>
</template>
