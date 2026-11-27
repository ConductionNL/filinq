/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The review preview marks the pages a scope decision covers
 * (image-redaction task 6.3): the sidebar pushes one full-page overlay per
 * covered page to the viewer, next to the image-region overlays of the
 * entity rows, and nothing once the document is anonymised.
 *
 * @spec openspec/changes/image-redaction/tasks.md#task-6.3
 */

import FileViewerSidebar from './FileViewerSidebar.vue'

jest.mock('@nextcloud/router', () => ({
	generateUrl: (url, params) => 'url:' + JSON.stringify([url, params ?? null]),
	generateRemoteUrl: (service) => 'remote:' + service,
}))
jest.mock('@nextcloud/l10n', () => ({
	translate: (a, s) => s,
	translatePlural: (a, s) => s,
}))
jest.mock('@nextcloud/dialogs', () => ({
	showError: () => {},
	showSuccess: () => {},
}))
jest.mock('@nextcloud/vue', () => ({}))
jest.mock('@nextcloud/auth', () => ({ getCurrentUser: () => null }))
jest.mock('../store/modules/settings.js', () => ({ useSettingsStore: () => ({ getConfig: null }) }))
jest.mock('jszip', () => ({}))
jest.mock('../store/store.js', () => ({}))
jest.mock('../store/modules/anonymization.js', () => ({
	entryIsChecked: () => false,
}))
jest.mock('../services/bases.js', () => ({}))
jest.mock('../services/entityTypes.js', () => ({
	ENTITY_TYPES: [],
	entityTypeLabel: () => '',
}))
jest.mock('../services/pseudonymisation.js', () => ({}))
jest.mock('../services/redactionAccessibility.js', () => ({}))
jest.mock('../services/sanitization.js', () => ({}))
jest.mock('../services/documentLeafTabs.js', () => ({}))
jest.mock('../assets/upload.png', () => 'upload.png')
jest.mock('vue-material-design-icons/FileDocumentOutline.vue', () => ({}))
jest.mock('vue-material-design-icons/Download.vue', () => ({}))
jest.mock('vue-material-design-icons/FileHidden.vue', () => ({}))
jest.mock('vue-material-design-icons/KeyVariant.vue', () => ({}))
jest.mock('vue-material-design-icons/Plus.vue', () => ({}))
jest.mock('vue-material-design-icons/ShieldLockOutline.vue', () => ({}))
jest.mock('vue-material-design-icons/ShieldRefreshOutline.vue', () => ({}))
jest.mock('vue-material-design-icons/CertificateOutline.vue', () => ({}))
jest.mock('../components/ClassificationCard.vue', () => ({}))
jest.mock('../components/DocumentLeafTabs.vue', () => ({}))
jest.mock('../components/DdEntityCard.vue', () => ({}))
jest.mock('../components/DdRemovedEntitiesList.vue', () => ({}))
jest.mock('../components/DdSearchBar.vue', () => ({}))
jest.mock('../components/DdToggle.vue', () => ({}))
jest.mock('../components/ReviewMarkCard.vue', () => ({}))
jest.mock('../dialogs/ProhibitionBlockedDialog.vue', () => ({}))
jest.mock('../dialogs/RestoreOriginalDialog.vue', () => ({}))
jest.mock('../dialogs/ScopeRedactionDialog.vue', () => ({}))

const regionList = FileViewerSidebar.computed.regionList

const imageRow = { type: 'BSN', origin: 'image', boxes: [{ page: 1, x: 0.1, y: 0.1, w: 0.2, h: 0.1 }] }

describe('FileViewerSidebar regionList', () => {
	it('marks pages 3 to 5 of a stored range next to the image regions', () => {
		const list = regionList.call({
			entry: { viewMode: 'review', entities: [imageRow] },
			scopeState: { decisions: [{ scope: 'pageRange', fromPage: 3, toPage: 5, bases: ['5.1.2.e'] }], pageCount: 7, pageScopesAvailable: true },
		})
		expect(list.filter((o) => o.scope !== true)).toHaveLength(1)
		expect(list.filter((o) => o.scope === true).map((o) => o.page)).toEqual([3, 4, 5])
	})

	it('marks nothing without decisions', () => {
		const list = regionList.call({
			entry: { viewMode: 'review', entities: [] },
			scopeState: { decisions: [], pageCount: 7, pageScopesAvailable: false },
		})
		expect(list).toEqual([])
	})

	it('draws no overlay on the anonymised view or without a document', () => {
		const scopeState = { decisions: [{ scope: 'page', fromPage: 1 }], pageCount: 2 }
		expect(regionList.call({ entry: { viewMode: 'anonymized', entities: [imageRow] }, scopeState })).toEqual([])
		expect(regionList.call({ entry: null, scopeState })).toEqual([])
	})
})
