import Vue from 'vue'
import Router from 'vue-router'
import { generateUrl } from '@nextcloud/router'
import Dashboard from '../views/dashboard/DashboardIndex.vue'
import AnonymizationIndex from '../views/anonymization/AnonymizationIndex.vue'
import ConsentIndex from '../views/consent/ConsentIndex.vue'
import ConsentDetail from '../views/consent/ConsentDetail.vue'
import PrintPreview from '../components/PrintPreview.vue'
import TemplateIndex from '../views/templates/TemplateIndex.vue'
import TemplateDetail from '../views/templates/TemplateDetail.vue'
import SigningRequestList from '../views/signing/SigningRequestList.vue'
import SigningRequestDetail from '../views/signing/SigningRequestDetail.vue'
import SigningRequestForm from '../views/signing/SigningRequestForm.vue'
import BulkSigningPanel from '../views/signing/BulkSigningPanel.vue'
import SignatureVerification from '../views/signing/SignatureVerification.vue'

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
			{ path: '/print-preview/:templateId?', name: 'PrintPreview', component: PrintPreview, props: route => ({ templateId: route.params.templateId || '' }) },
			{ path: '/templates', name: 'Templates', component: TemplateIndex },
			{ path: '/templates/:id', name: 'TemplateDetail', component: TemplateDetail, props: route => ({ templateId: route.params.id }) },
			{ path: '/signing', name: 'Signing', component: SigningRequestList },
			{ path: '/signing/new', name: 'SigningCreate', component: SigningRequestForm },
			{ path: '/signing/bulk', name: 'BulkSigning', component: BulkSigningPanel },
			{ path: '/signing/verify', name: 'SignatureVerification', component: SignatureVerification },
			{ path: '/signing/:id', name: 'SigningDetail', component: SigningRequestDetail, props: route => ({ requestId: route.params.id }) },
			{ path: '*', redirect: '/' },
		],
	},
)
