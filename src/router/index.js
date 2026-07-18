import Vue from 'vue'
import Router from 'vue-router'

import Dashboard from '../views/dashboard/DashboardIndex.vue'
import AnonymizationIndex from '../views/anonymization/AnonymizationIndex.vue'
import FolderAnonymizationView from '../views/anonymization/FolderAnonymizationView.vue'
import ConsentIndex from '../views/consent/ConsentIndex.vue'
import ConsentDetail from '../views/consent/ConsentDetail.vue'
import TemplateIndex from '../views/templates/TemplateIndex.vue'
import TemplateDetail from '../views/templates/TemplateDetail.vue'
import MyDocumentsIndex from '../views/myDocuments/MyDocumentsIndex.vue'
import PrintPreview from '../components/PrintPreview.vue'
import ProhibitionIndex from '../views/policy/ProhibitionIndex.vue'
import StandingConsentIndex from '../views/policy/StandingConsentIndex.vue'
import SigningRequestList from '../views/signing/SigningRequestList.vue'
import SigningRequestDetail from '../views/signing/SigningRequestDetail.vue'
import SigningRequestForm from '../views/signing/SigningRequestForm.vue'
import BulkSigningPanel from '../views/signing/BulkSigningPanel.vue'
import SignatureVerification from '../views/signing/SignatureVerification.vue'
import CorrespondenceIndex from '../views/correspondence/CorrespondenceIndex.vue'
import ComparisonView from '../views/comparison/ComparisonView.vue'
import ComponentGallery from '../views/gallery/ComponentGallery.vue'

Vue.use(Router)

export default new Router(
	{
		mode: 'hash',
		routes: [
			{ path: '/', redirect: { name: 'Anonymization' } },
			{ path: '/dashboard', name: 'Dashboard', component: Dashboard },
			{ path: '/anonymization', name: 'Anonymization', component: AnonymizationIndex },
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
			{ path: '/signing', name: 'SigningRequestList', component: SigningRequestList },
			{ path: '/signing/new', name: 'SigningRequestForm', component: SigningRequestForm },
			{ path: '/signing/bulk', name: 'BulkSigningPanel', component: BulkSigningPanel },
			{ path: '/signing/verify/:fileId', name: 'SignatureVerification', component: SignatureVerification, props: route => ({ fileId: route.params.fileId }) },
			{ path: '/signing/:id', name: 'SigningRequestDetail', component: SigningRequestDetail, props: route => ({ requestId: route.params.id }) },
			{ path: '/correspondence', name: 'Correspondence', component: CorrespondenceIndex },
			{ path: '/comparison', name: 'Comparison', component: ComparisonView, props: route => ({ initialLeftFileId: route.query.left || '', initialRightFileId: route.query.right || '' }) },
			{ path: '/gallery', name: 'Gallery', component: ComponentGallery },
			{ path: '*', redirect: { name: 'Anonymization' } },
		],
	},
)
