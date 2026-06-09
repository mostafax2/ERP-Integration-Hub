import DashboardLayout from './components/Common/DashboardLayout.vue'
import DashboardPage from './pages/DashboardPage.vue'
import ConnectionsPage from './pages/ConnectionsPage.vue'
import ConnectionWizardPage from './pages/ConnectionWizardPage.vue'
import SyncProfilesPage from './pages/SyncProfilesPage.vue'
import SyncProfileEditorPage from './pages/SyncProfileEditorPage.vue'
import FieldMappingPage from './pages/FieldMappingPage.vue'
import SchedulerPage from './pages/SchedulerPage.vue'
import MonitoringPage from './pages/MonitoringPage.vue'
import LogsPage from './pages/LogsPage.vue'
import LogDetailPage from './pages/LogDetailPage.vue'
import FailedJobsPage from './pages/FailedJobsPage.vue'
import SettingsPage from './pages/SettingsPage.vue'

export default [
    {
        path: '/',
        component: DashboardLayout,
        children: [
            { path: '',                          name: 'dashboard',           component: DashboardPage },
            { path: 'connections',               name: 'connections',         component: ConnectionsPage },
            { path: 'connections/create',        name: 'connections.create',  component: ConnectionWizardPage },
            { path: 'connections/:id/edit',      name: 'connections.edit',    component: ConnectionWizardPage },
            { path: 'sync-profiles',             name: 'sync-profiles',       component: SyncProfilesPage },
            { path: 'sync-profiles/create',      name: 'sync-profiles.create', component: SyncProfileEditorPage },
            { path: 'sync-profiles/:id',         name: 'sync-profiles.edit',  component: SyncProfileEditorPage },
            { path: 'sync-profiles/:id/mapping', name: 'field-mapping',       component: FieldMappingPage },
            { path: 'scheduler',                 name: 'scheduler',           component: SchedulerPage },
            { path: 'monitoring',                name: 'monitoring',          component: MonitoringPage },
            { path: 'logs',                      name: 'logs',                component: LogsPage },
            { path: 'logs/:id',                  name: 'logs.detail',         component: LogDetailPage },
            { path: 'failed-jobs',               name: 'failed-jobs',         component: FailedJobsPage },
            { path: 'settings',                  name: 'settings',            component: SettingsPage },
        ],
    },
]
