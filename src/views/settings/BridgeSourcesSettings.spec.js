/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The bridge status panel: each source with its vendor and health, the
 * error of a failing one, and an empty state that points at OpenConnector.
 *
 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-1
 */

import axios from '@nextcloud/axios'
import BridgeSourcesSettings from './BridgeSourcesSettings.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/axios', () => ({ __esModule: true, default: { get: jest.fn() } }))
jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => text.replace(/{(\w+)}/g, (match, key) => (vars && key in vars ? vars[key] : match)),
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@conduction/nextcloud-vue', () => ({ CnStatusBadge: { name: 'CnStatusBadge' } }))

const flush = () => new Promise((resolve) => setTimeout(resolve, 0))
let rendered

afterEach(() => {
	rendered?.unmount()
	rendered = null
	axios.get.mockReset()
})

/**
 * Mount the panel over the given sources.
 *
 * @param {Array<object>} results The sources the endpoint answers.
 * @return {Promise<object>} The rendered panel.
 */
async function mountPanel(results) {
	axios.get.mockResolvedValue({ data: { results } })
	rendered = await renderStubbed(BridgeSourcesSettings, {})
	await flush()
	await flush()
	return rendered
}

const texts = (wrapper, id) => [...wrapper.vm.$el.querySelectorAll(`[data-testid="${id}"]`)].map((n) => n.textContent.replace(/\s+/g, ' ').trim())

describe('BridgeSourcesSettings', () => {
	it('lists each source with its vendor and shows the error of a failing one', async () => {
		const wrapper = await mountPanel([
			{ uuid: 's-1', name: 'Zaken', vendor: 'Rx.Enterprise', sourceType: 'zgw-drc', lastSyncAt: '', health: 'fresh' },
			{ uuid: 's-2', name: 'Archief', vendor: 'zaaksysteem.nl', sourceType: 'stuf-zds', lastSyncAt: '', health: 'failing', lastSyncError: 'Timeout' },
		])

		const rows = texts(wrapper, 'bridge-source')
		expect(rows).toHaveLength(2)
		expect(rows[0]).toContain('Rx.Enterprise')
		expect(rows[0]).toContain('ZGW Documenten API')
		expect(rows[1]).toContain('zaaksysteem.nl')
		expect(texts(wrapper, 'bridge-source-error')).toEqual(['Timeout'])
		const badges = wrapper.stubs.filter((s) => s.name === 'CnStatusBadge').map((s) => s.attrs.label)
		expect(badges).toEqual(['Up to date', 'Failing'])
	})

	it('explains where sources come from when there are none', async () => {
		const wrapper = await mountPanel([])

		expect(texts(wrapper, 'bridge-sources-empty')[0]).toContain('OpenConnector')
		expect(texts(wrapper, 'bridge-source')).toEqual([])
	})
})
