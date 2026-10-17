import Dashboard from './pages/Dashboard.vue';
import RecordsIndex from './pages/records/Index.vue';
import RecordsShow from './pages/records/Show.vue';
import GovernanceWidget from './components/GovernanceWidget.vue';
import GovernancePanelFieldtype from './components/fieldtypes/GovernancePanelFieldtype.vue';

Statamic.booting(() => {
    Statamic.$inertia.register('content-governance::Dashboard', Dashboard);
    Statamic.$inertia.register('content-governance::Records/Index', RecordsIndex);
    Statamic.$inertia.register('content-governance::Records/Show', RecordsShow);

    Statamic.$components.register('content-governance-widget', GovernanceWidget);
    Statamic.$components.register('governance_panel-fieldtype', GovernancePanelFieldtype);
});
