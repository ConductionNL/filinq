/**
 * Golden: the dossier status labels and badge colours both dossier views
 * show, recorded before they moved to one module.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of the dossier status labels
 */

import { join } from 'node:path'
import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => '[t] ' + text,
	translatePlural: (app, one, many) => '[n] ' + one + '|' + many,
}))
vi.mock('@nextcloud/axios', () => ({ default: { get: async () => ({ data: {} }) } }))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
vi.mock('@nextcloud/dialogs', () => ({ showError: () => {}, showSuccess: () => {} }))
vi.mock('../../src/store/store.js', () => ({ dossierStore: {} }))

const STATUSES = ['open', 'in-review', 'processed', 'published', 'closed', '', undefined, 'archived']

describe('dossier status golden', () => {
	for (const view of ['DossierDetail', 'DossierIndex']) {
		it(view, async () => {
			const options = await loadSfcOptions(
				join(import.meta.dirname, '../../src/views/dossier/' + view + '.vue'),
			)
			const vm = createInstance(options, { route: { params: { id: 'd1' }, query: {} } })
			golden('dossierStatus-' + view, {
				labels: STATUSES.map((status) => [String(status), vm.statusLabel(status)]),
				colors: vm.statusColorMap,
			})
		})
	}
})
