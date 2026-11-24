/**
 * A dossier transferred to the e-depot is read only on its page: no status
 * change, no rename, no document added or removed, each refusal said in
 * words. OpenRegister refuses the write itself with 409 OBJECT_TRANSFERRED;
 * the page does not offer what would be refused.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-transfer-to-archive-state-for-permanent-records-req-ddare-006
 */

import { readFileSync } from 'node:fs'
import { join } from 'node:path'
import { describe, expect, it, vi } from 'vitest'
import { loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('../../src/store/store.js', () => ({
	fileViewerStore: {},
	dossierStore: {},
}))
vi.mock('@nextcloud/dialogs', () => ({ showError: () => {} }))
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
	translatePlural: (app, one) => one,
}))
vi.mock('@nextcloud/axios', () => ({ default: {} }))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))

const PAGE = join(import.meta.dirname, '../../src/views/dossier/DossierDetail.vue')

describe('a transferred dossier', () => {
	it('says why removing a document is switched off', async () => {
		const { methods } = await loadSfcOptions(PAGE)

		expect(
			methods.removeTitle.call({ transferred: true, underLegalHold: true }),
		).toBe('This dossier was transferred to the e-depot and is read only.')
		expect(
			methods.removeTitle.call({ transferred: false, underLegalHold: true }),
		).toBe('This dossier is under a legal hold.')
		expect(
			methods.removeTitle.call({ transferred: false, underLegalHold: false }),
		).toBe('')
	})

	it('does not start a rename', async () => {
		const { methods } = await loadSfcOptions(PAGE)
		const page = {
			transferred: true,
			renaming: false,
			draftName: '',
			dossierStore: { dossier: { name: 'Beroep 2026' } },
			$nextTick: vi.fn(),
		}

		methods.startRename.call(page)
		expect(page.renaming).toBe(false)
		expect(page.draftName).toBe('')

		page.transferred = false
		methods.startRename.call(page)
		expect(page.renaming).toBe(true)
		expect(page.draftName).toBe('Beroep 2026')
	})

	it('hears the transfer from the MDTO panel and switches every change off', async () => {
		const { data } = await loadSfcOptions(PAGE)
		const page = readFileSync(PAGE, 'utf8')
		const template = page.slice(0, page.indexOf('<script>'))

		expect(data.call({}).transferred).toBe(false)
		expect(template).toContain('@transferred="transferred = $event"')
		expect(template).toContain(':disabled="dossierStore.saving || transferred"')
		expect(template).toMatch(/:disabled="transferred"\s+@click="addDocument"/)
		expect(template).toContain(':disabled="underLegalHold || transferred"')
	})
})
