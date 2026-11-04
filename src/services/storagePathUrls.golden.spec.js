/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the storage path links: the Files app link and the WebDAV
 * download URL the dashboard widgets and the file viewer sidebar build from a
 * Nextcloud-internal storage path. The router is replaced by a recorder, so
 * the golden pins the exact route and parameters each method passes.
 * RECORD_GOLDEN=1 rewrites the golden file.
 */

import fs from 'fs'
import path from 'path'
import FileViewerSidebar from '../sidebars/FileViewerSidebar.vue'
import AnonymizationDashboardWidget from '../views/widgets/AnonymizationDashboardWidget.vue'
import FileEntitiesDashboardWidget from '../views/widgets/FileEntitiesDashboardWidget.vue'

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
jest.mock('vue-material-design-icons/KeyVariant.vue', () => ({}))
jest.mock('vue-material-design-icons/Plus.vue', () => ({}))
jest.mock('vue-material-design-icons/ShieldLockOutline.vue', () => ({}))
jest.mock('vue-material-design-icons/ShieldRefreshOutline.vue', () => ({}))
jest.mock('../dialogs/DossierNameDialog.vue', () => ({}))
jest.mock('../components/ClassificationCard.vue', () => ({}))
jest.mock('../components/DocumentLeafTabs.vue', () => ({}))
jest.mock('../components/DdEntityCard.vue', () => ({}))
jest.mock('../components/DdRemovedEntitiesList.vue', () => ({}))
jest.mock('../components/DdSearchBar.vue', () => ({}))
jest.mock('../components/DdToggle.vue', () => ({}))
jest.mock('../components/ReviewMarkCard.vue', () => ({}))
jest.mock('../dialogs/ProhibitionBlockedDialog.vue', () => ({}))
jest.mock('../dialogs/RestoreOriginalDialog.vue', () => ({}))

const GOLDEN = path.join(__dirname, '../../tests/jest/golden/storage-path-urls.json')
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

const PATHS = [
	'/admin/files/report.docx',
	'/admin/files/Dossier A/sub dir/b c?.pdf',
	'/admin/files/#1 & more/x=y.txt',
	'admin/files/top.txt',
	'/admin/files/',
	'/admin/files',
	'files/only.txt',
	'files',
	'/u/files/files/nested/files/x.txt',
	'/appdata_x/preview/a.png',
	'/admin/Files/case.txt',
	'/admin/files//double//slash.txt',
	'/admin/files/dir/',
	'/üser/files/ünïcode %20 name.odt',
	'plain-name.docx',
	'',
	'/',
]

const SIDEBAR_EXTRA = [null, undefined]

const METHODS = {
	'AnonymizationDashboardWidget.fileLink': (p) =>
		AnonymizationDashboardWidget.methods.fileLink.call({}, p),
	'AnonymizationDashboardWidget.downloadUrl': (p) =>
		AnonymizationDashboardWidget.methods.downloadUrl.call({}, p),
	'FileEntitiesDashboardWidget.fileLink': (p) =>
		FileEntitiesDashboardWidget.methods.fileLink.call({}, p),
	'FileViewerSidebar.downloadUrl': (p) =>
		FileViewerSidebar.computed.downloadUrl.call({
			entry: { anonymizedFilePath: p },
		}),
	'FileViewerSidebar.downloadUrl(no entry)': () =>
		FileViewerSidebar.computed.downloadUrl.call({ entry: null }),
	'FileViewerSidebar.downloadUrlFor': (p) =>
		FileViewerSidebar.methods.downloadUrlFor.call({}, p),
}

const SIDEBAR = new Set([
	'FileViewerSidebar.downloadUrl',
	'FileViewerSidebar.downloadUrlFor',
])

/**
 * The method's result, or the error it throws.
 *
 * @param {Function} call The method.
 * @param {*} input The storage path.
 * @return {object} `{ out }` or `{ throws }`.
 */
function outcome(call, input) {
	try {
		return { out: call(input) }
	} catch (e) {
		return { throws: String(e && e.name) }
	}
}

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('storage path links golden', () => {
	for (const [name, call] of Object.entries(METHODS)) {
		const inputs = SIDEBAR.has(name) ? [...PATHS, ...SIDEBAR_EXTRA] : PATHS
		it(`${name} answers as recorded`, () => {
			const actual = inputs.map((p) => [String(p), outcome(call, p)])
			if (RECORD) {
				golden[name] = actual
			} else {
				expect(actual).toEqual(golden[name])
			}
		})
	}
})
