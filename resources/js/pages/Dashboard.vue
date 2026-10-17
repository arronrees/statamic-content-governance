<script setup>
import { Head, Link, router } from '@statamic/cms/inertia';
import { Alert, Badge, Card, Header, Heading, Panel, PanelHeader, Select, Subheading } from '@statamic/cms/ui';
import { formatDate, statusColours } from '../status.js';

const props = defineProps({
    proRequired: Boolean,
    site: String,
    sites: Array,
    summary: Object,
    expiring: Array,
    activity: Array,
    scheduler: Object,
    reviewUrl: String,
    dashboardUrl: String,
});

const cards = [
    { key: 'current', label: __('Current') },
    { key: 'due', label: __('Due') },
    { key: 'overdue', label: __('Overdue') },
    { key: 'expired', label: __('Expired') },
    { key: 'orphaned', label: __('Orphaned') },
];

const siteOptions = [{ value: '', label: __('All sites') }, ...props.sites.map((site) => ({ value: site.handle, label: site.name }))];

function switchSite(site) {
    router.get(props.dashboardUrl, site ? { site } : {}, { preserveScroll: true });
}

function reviewLink(filters) {
    return `${props.reviewUrl}?filters=${btoa(JSON.stringify(filters))}`;
}

function cardLink(key) {
    return key === 'orphaned'
        ? reviewLink({ governance_flags: { flags: ['orphaned'] } })
        : reviewLink({ governance_status: { statuses: [key] } });
}
</script>

<template>
    <Head :title="__('Governance')" />

    <div class="max-w-6xl mx-auto space-y-6">
        <Header :title="__('Governance')" icon="chart-monitoring-indicator">
            <Select
                v-if="sites.length"
                :model-value="site || ''"
                :options="siteOptions"
                @update:model-value="switchSite"
            />
        </Header>

        <Alert v-if="proRequired" variant="warning" :heading="__('Content Governance needs Statamic Pro')">
            {{ __('Owners and review permissions depend on user roles, which are a Pro feature. Upgrade to Pro before relying on governance.') }}
        </Alert>

        <Alert v-if="scheduler.stale" variant="warning" :heading="__('The governance sweep hasn\'t run recently')">
            <template v-if="scheduler.last_sweep_at">
                {{ __('Last run :date. Statuses and expiry may be out of date — check the scheduler is running.', { date: formatDate(scheduler.last_sweep_at) }) }}
            </template>
            <template v-else>
                {{ __('No sweep recorded yet. Check the scheduler is running (a cache clear also resets this until the next hourly run).') }}
            </template>
        </Alert>

        <!-- Breakpoint-scoped only: the CP's own grid-cols-2 sits in a later layer and would override an unscoped base. -->
        <div class="grid gap-4 max-lg:grid-cols-2 lg:grid-cols-3">
            <Link v-for="card in cards" :key="card.key" :href="cardLink(card.key)">
                <Card class="space-y-1">
                    <Subheading :text="card.label" />
                    <div class="flex items-center gap-2">
                        <Heading size="xl" :text="String(summary.totals[card.key] ?? 0)" />
                        <Badge v-if="statusColours[card.key]" size="sm" :color="statusColours[card.key]" :text="card.label" />
                    </div>
                </Card>
            </Link>
        </div>

        <Panel>
            <PanelHeader><Heading :text="__('By collection')" /></PanelHeader>
            <Card>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-start">
                            <th class="text-start py-2">{{ __('Collection') }}</th>
                            <th v-for="card in cards.slice(0, 4)" :key="card.key" class="text-end py-2">{{ card.label }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="collection in summary.collections" :key="collection.handle">
                            <td class="py-2">
                                <Link :href="reviewLink({ governance_collection: { collections: [collection.handle] } })">{{ collection.title }}</Link>
                            </td>
                            <td v-for="card in cards.slice(0, 4)" :key="card.key" class="text-end py-2">{{ collection.counts[card.key] }}</td>
                        </tr>
                        <tr v-if="!summary.collections.length">
                            <td colspan="5" class="py-2">{{ __('No governed content yet.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </Card>
        </Panel>

        <div class="grid gap-6 md:grid-cols-2">
            <Panel>
                <PanelHeader><Heading :text="__('Expiring in the next 30 days')" /></PanelHeader>
                <Card class="space-y-2">
                    <div v-for="item in expiring" :key="item.id" class="flex items-center justify-between gap-2">
                        <Link :href="item.detail_url">{{ item.title }}</Link>
                        <span class="text-sm">{{ formatDate(item.expires_at) }}</span>
                    </div>
                    <p v-if="!expiring.length" class="text-sm">{{ __('Nothing expiring soon.') }}</p>
                </Card>
            </Panel>

            <Panel>
                <PanelHeader><Heading :text="__('Recent activity')" /></PanelHeader>
                <Card class="space-y-3">
                    <div v-for="event in activity" :key="event.id" class="space-y-0.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <Link v-if="event.detail_url" :href="event.detail_url">{{ event.title }}</Link>
                            <span v-else>{{ event.title }}</span>
                            <Badge size="sm" :text="event.label" />
                        </div>
                        <Subheading :text="`${event.actor} · ${formatDate(event.at)}`" />
                    </div>
                    <p v-if="!activity.length" class="text-sm">{{ __('No activity yet.') }}</p>
                </Card>
            </Panel>
        </div>
    </div>
</template>
