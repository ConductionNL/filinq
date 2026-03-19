import Vue from 'vue'
import Router from 'vue-router'
import { generateUrl } from '@nextcloud/router'
import Dashboard from '../views/dashboard/DashboardIndex.vue'
import AnonymizationIndex from '../views/anonymization/AnonymizationIndex.vue'
import ConsentIndex from '../views/consent/ConsentIndex.vue'
import ConsentDetail from '../views/consent/ConsentDetail.vue'

Vue.use(Router)

export default new Router(
        {
            mode: 'history',
            base: generateUrl('/apps/docudesk'),
            routes: [
            { path: '/', name: 'Dashboard', component: Dashboard },
            { path: '/anonymization', name: 'Anonymization', component: AnonymizationIndex },
            { path: '/consent', name: 'Consent', component: ConsentIndex },
            { path: '/consent/:id', name: 'ConsentDetail', component: ConsentDetail, props: route => ({ consentId: route.params.id }) },
            { path: '*', redirect: '/' },
            ],
}
        )
