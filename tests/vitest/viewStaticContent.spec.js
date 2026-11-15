/**
 * Golden: the fixed content three views build for themselves — the
 * dashboard layout, the consent detail's option lists and the settings
 * page's processing activities — recorded before that content moved out
 * of the units that built it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of fixed view content
 */

import { join } from 'node:path'
import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'
import { loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => '[t] ' + text,
	translatePlural: (app, one, many) => '[n] ' + one + '|' + many,
}))
vi.mock('@nextcloud/axios', () => ({ default: { get: async () => ({ data: {} }), post: async () => ({ data: {} }) } }))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => url, generateOcsUrl: (url) => url }))
vi.mock('@nextcloud/dialogs', () => ({ showError: () => {}, showSuccess: () => {} }))
vi.mock('../../src/store/store.js', () => ({ consentStore: {}, settingsStore: {}, anonymizationStore: {}, fileViewerStore: {}, myDocumentsStore: {} }))

/**
 * The options of one view.
 *
 * @param {string} path The view, relative to src/views.
 * @return {Promise<object>} Its options.
 */
async function view(path) {
	return loadSfcOptions(join(import.meta.dirname, '../../src/views', path))
}

describe('fixed view content golden', () => {
	it('the dashboard layout', async () => {
		const options = await view('dashboard/DashboardIndex.vue')
		golden('viewStaticContent-dashboard-data', options.data.call({}))
	})

	it('the consent detail options', async () => {
		const options = await view('consent/ConsentDetail.vue')
		golden('viewStaticContent-consent-detail-data', options.data.call({}))
	})

	it('the processing activities', async () => {
		// The settings page uses Nextcloud's global t().
		globalThis.t = (app, text) => '[t] ' + text
		const options = await view('settings/Settings.vue')
		golden('viewStaticContent-processing-activities', options.computed.processingActivities.call({}))
	})
})
