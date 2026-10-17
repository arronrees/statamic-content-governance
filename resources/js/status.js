import { DateFormatter } from '@statamic/cms';

export const statusColours = {
    current: 'green',
    due: 'amber',
    overdue: 'orange',
    expired: 'red',
};

export function formatDate(value) {
    return value ? DateFormatter.format(value, 'date') : '—';
}
