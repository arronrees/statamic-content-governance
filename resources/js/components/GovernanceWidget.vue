<script setup>
import { Link } from '@statamic/cms/inertia';
import { Badge, Widget } from '@statamic/cms/ui';
import { formatDate, statusColours } from '../status.js';

defineProps({
    title: String,
    counts: Object,
    items: Array,
    reviewUrl: String,
    scheduler: Object,
});
</script>

<template>
    <Widget :title="title" icon="clipboard-check" :href="reviewUrl">
        <div class="px-4 py-3 space-y-3">
            <p v-if="scheduler.stale" class="text-sm">{{ __('The governance sweep hasn\'t run recently.') }}</p>

            <div class="flex flex-wrap gap-2">
                <Badge v-for="(count, status) in counts" :key="status" :color="statusColours[status]" :text="`${count.label}: ${count.count}`" />
            </div>

            <div v-for="item in items" :key="item.id" class="flex items-center justify-between gap-2 text-sm">
                <Link :href="item.detail_url">{{ item.title }}</Link>
                <span>{{ formatDate(item.next_action_at) }}</span>
            </div>

            <p v-if="!items.length" class="text-sm">{{ __('Nothing needs attention.') }}</p>
        </div>
    </Widget>
</template>
