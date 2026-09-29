/**
 * Reversible pseudonymisation in the file viewer: the status and restore
 * calls, the refusal the server answers with, and the warning after a
 * reversible run that kept no key.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.2
 */

import { readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	fetchPseudonymStatus,
	keyWarning,
	restoreOriginal,
} from '../../src/services/pseudonymisation.js'

const calls = []
let postAnswer = null
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url) => {
			calls.push(['get', url])
			return Promise.resolve({ data: { linkId: 'link-1', reversible: true, entryCount: 2, mayRestore: false } })
		},
		post: (url) => {
			calls.push(['post', url])
			return postAnswer()
		},
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))

describe('reversible pseudonymisation service', () => {
	beforeEach(() => {
		calls.length = 0
	})

	it('asks for the status of a copy, and asks nothing without one', async () => {
		expect(await fetchPseudonymStatus(0)).toBe(null)
		expect(calls).toEqual([])

		const status = await fetchPseudonymStatus(812201)
		expect(calls).toEqual([['get', '/apps/filinq/api/pseudonymisation/status/812201']])
		expect(status.mayRestore).toBe(false)
	})

	it('shows the server refusal, not a success, when the restore is refused', async () => {
		postAnswer = () => Promise.reject({ response: { status: 403, data: { error: 'You are not allowed to restore names.' } } })

		const answer = await restoreOriginal('link/1')

		expect(calls).toEqual([['post', '/apps/filinq/api/pseudonymisation/link%2F1/restore']])
		expect(answer).toEqual({ ok: false, error: 'You are not allowed to restore names.' })
	})

	it('falls back to a plain message when the server gave none', async () => {
		postAnswer = () => Promise.reject(new Error('network'))

		expect((await restoreOriginal('link-1')).error).toBe('The names could not be restored.')
	})

	it('hands back the restored copy when the restore went through', async () => {
		postAnswer = () => Promise.resolve({ data: { mode: 'copy', fileName: 'brief_restored.txt' } })

		expect(await restoreOriginal('link-1')).toEqual({ ok: true, result: { mode: 'copy', fileName: 'brief_restored.txt' } })
	})

	it('warns only after a reversible run that kept no key', () => {
		expect(keyWarning(null)).toBe('')
		expect(keyWarning({ reversible: false, previousKeyRemoved: true })).toBe('')
		expect(keyWarning({ reversible: true, keyKept: true, entryCount: 2 })).toBe('')
		expect(keyWarning({ reversible: true, keyKept: false, reason: 'no_placeholders' })).toContain('none of the replaced values')
		expect(keyWarning({ reversible: true, keyKept: false, reason: 'something new' })).toContain('could not be saved')
	})
})

describe('the restore dialog and the anonymise choice', () => {
	const sidebar = readFileSync(new URL('../../src/sidebars/FileViewerSidebar.vue', import.meta.url), 'utf8')
	const dialog = readFileSync(new URL('../../src/dialogs/RestoreOriginalDialog.vue', import.meta.url), 'utf8')
	const store = readFileSync(new URL('../../src/store/modules/anonymization.js', import.meta.url), 'utf8')

	it('says the restore is audit-logged before the user confirms', () => {
		const warning = dialog.indexOf('written to the audit trail before anything is restored')
		const confirm = dialog.indexOf('Restore and log')
		expect(warning).toBeGreaterThan(-1)
		expect(confirm).toBeGreaterThan(warning)
	})

	it('offers restore only when the server says the viewer may', () => {
		expect(sidebar).toContain('v-if="pseudonymStatus && pseudonymStatus.mayRestore"')
	})

	it('keeps irreversible as the default and sends reversible only when chosen', () => {
		expect(sidebar).toContain("anonymiseMode: 'irreversible'")
		expect(store).toContain('if (options.reversible === true) {')
		expect(store).toContain('entry.pseudonymisation = anonymizeResponse.data.pseudonymisation || null')
	})
})
