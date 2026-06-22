import Vue from 'vue'
import Router from 'vue-router'

import Dashboard from '../views/dashboard/DashboardIndex.vue'
import AnonymizationIndex from '../views/anonymization/AnonymizationIndex.vue'
import BatchAnonymizationView from '../views/anonymization/BatchAnonymizationView.vue'
import FolderAnonymizationView from '../views/anonymization/FolderAnonymizationView.vue'
import ConsentIndex from '../views/consent/ConsentIndex.vue'
import ConsentDetail from '../views/consent/ConsentDetail.vue'
import TemplateIndex from '../views/templates/TemplateIndex.vue'
import TemplateDetail from '../views/templates/TemplateDetail.vue'
import MyDocumentsIndex from '../views/myDocuments/MyDocumentsIndex.vue'
import PrintPreview from '../components/PrintPreview.vue'
import ProhibitionIndex from '../views/policy/ProhibitionIndex.vue'
import StandingConsentIndex from '../views/policy/StandingConsentIndex.vue'
import ComponentGallery from '../views/gallery/ComponentGallery.vue'

Vue.use(Router)

export default new Router(
	{
		mode: 'hash',
		routes: [
			{ path: '/', redirect: { name: 'Anonymization' } },
			{ path: '/dashboard', name: 'Dashboard', component: Dashboard },
			{ path: '/anonymization', name: 'Anonymization', component: AnonymizationIndex },
			{ path: '/anonymization/batch', name: 'BatchAnonymization', component: BatchAnonymizationView },
			{ path: '/anonymization/folder', name: 'FolderAnonymization', component: FolderAnonymizationView },
			{ path: '/consent', name: 'Consent', component: ConsentIndex },
			{ path: '/consent/:id', name: 'ConsentDetail', component: ConsentDetail, props: route => ({ consentId: route.params.id }) },
			{ path: '/policy/standing-consents', name: 'StandingConsents', component: StandingConsentIndex },
			{ path: '/policy/prohibitions', name: 'Prohibitions', component: ProhibitionIndex },
			{ path: '/templates', name: 'Templates', component: TemplateIndex },
			{ path: '/templates/new', name: 'TemplateNew', component: TemplateDetail },
			{ path: '/templates/:id', name: 'TemplateDetail', component: TemplateDetail, props: route => ({ templateId: route.params.id }) },
			{ path: '/my-documents', name: 'MyDocuments', component: MyDocumentsIndex },
			{ path: '/print-preview/:templateId?', name: 'PrintPreview', component: PrintPreview, props: route => ({ templateId: route.params.templateId || '' }) },
			{ path: '/gallery', name: 'Gallery', component: ComponentGallery },
			{ path: '*', redirect: { name: 'Anonymization' } },
		],
	},
)
