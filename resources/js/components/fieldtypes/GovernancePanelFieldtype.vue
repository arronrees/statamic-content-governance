<script setup>
import { Fieldtype, ItemActions } from '@statamic/cms';
import { Link } from '@statamic/cms/inertia';
import { Badge, Button, Description } from '@statamic/cms/ui';
import { computed } from 'vue';
import { formatDate, statusColours } from '../../status.js';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, updateMeta } = Fieldtype.use(emit, props);
defineExpose(expose);

const record = computed(() => props.meta.record);

function actionCompleted(successful, response) {
    if (!successful) return;

    Statamic.$toast.success(response.message || __('Review recorded'));

    if (response.data) {
        updateMeta({ ...props.meta, ...response.data });
    }
}
</script>

<template>
    <Description v-if="!record" :text="__('Governance starts once this entry has been saved.')" />

    <div v-else class="space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <Badge :color="statusColours[record.status]" :text="record.status_label" />
            <Badge v-for="flag in record.flags" :key="flag" size="sm" color="violet" :text="flag" />
        </div>

        <dl class="grid grid-cols-2 gap-1 text-sm">
            <dt>{{ __('Owner') }}</dt>
            <dd>{{ record.owner || __('Nobody') }}<template v-if="record.owner_is_fallback"> ({{ __('fallback') }})</template></dd>
            <dt>{{ __('Next review') }}</dt>
            <dd>{{ formatDate(record.next_review_at) }}</dd>
            <dt>{{ __('Expires') }}</dt>
            <dd>{{ formatDate(record.expires_at) }}</dd>
        </dl>

        <div class="flex flex-wrap items-center gap-2">
            <ItemActions
                :url="meta.actionUrl"
                :actions="meta.actions"
                :item="record.id"
                :context="{ view: 'form' }"
                @completed="actionCompleted"
                v-slot="{ actions }"
            >
                <Button
                    v-for="action in actions"
                    :key="action.handle"
                    size="sm"
                    :text="__('Review')"
                    :icon="action.icon"
                    @click="action.run"
                />
            </ItemActions>
            <Link :href="record.detail_url" class="text-sm">{{ __('Details') }}</Link>
        </div>
    </div>
</template>
