/**
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * 5-kind component registry (hydra ADR-036).
 *
 * All custom page components referenced in src/manifest.json must be
 * registered here so CnPageRenderer can resolve them at render time.
 *
 * Kinds:
 *   pages       — type:"custom" page views (page.component)
 *   headers     — optional per-page header component overrides
 *   actions     — optional per-page actions bar overrides
 *   sidebarTabs — custom sidebar tab components
 *   cells       — custom cell renderer formatters
 */

import ConsentIndex from './views/consent/ConsentIndex.vue'
import ConsentDetail from './views/consent/ConsentDetail.vue'
import AnonymizationIndex from './views/anonymization/AnonymizationIndex.vue'
import FolderAnonymizationView from './views/anonymization/FolderAnonymizationView.vue'
import TemplateIndex from './views/templates/TemplateIndex.vue'
import TemplateDetail from './views/templates/TemplateDetail.vue'
import SigningRequestList from './views/signing/SigningRequestList.vue'
import SigningRequestDetail from './views/signing/SigningRequestDetail.vue'
import PrintPreview from './components/PrintPreview.vue'

export default {
	pages: {
		ConsentIndex,
		ConsentDetail,
		AnonymizationIndex,
		FolderAnonymizationView,
		TemplateIndex,
		TemplateDetail,
		SigningRequestList,
		SigningRequestDetail,
		PrintPreview,
	},
	headers: {},
	actions: {},
	sidebarTabs: {},
	cells: {},
}
