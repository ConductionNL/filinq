/**
 * The mail archive import page's calls, labels, progress and mapping.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#4-1
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	canCancel,
	cancelImport,
	countsText,
	failureText,
	formatLabel,
	listImports,
	loadDossierOptions,
	mapFolders,
	mappingPayload,
	progressPercent,
	startImport,
	statusColorMap,
	STATUSES,
	statusLabel,
} from '../../src/services/mailArchiveImport.js'

const http = vi.hoisted(() => ({ calls: [], answer: () => Promise.resolve({ data: {} }) }))
vi.mock('@nextcloud/axios', () => {
	const verb = (name) => (url, body) => {
		http.calls.push([name, url, body])
		return http.answer()
	}
	return { default: { get: verb('get'), post: verb('post') } }
})
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) => text.replace(/{(\w+)}/g, (match, key) => (key in vars ? String(vars[key]) : match)),
}))

describe('mail archive import service', () => {
	beforeEach(() => {
		http.calls.length = 0
		http.answer = () => Promise.resolve({ data: {} })
	})

	it('starts, lists, maps and cancels on the import routes', async () => {
		await startImport('42')
		await listImports()
		await mapFolders('a/b', [{ archiveFolder: 'Inbox', dossier: '' }])
		await cancelImport('a/b')

		expect(http.calls).toEqual([
			['post', '/apps/filinq/api/mail-archive-imports', { fileId: 42 }],
			['get', '/apps/filinq/api/mail-archive-imports', undefined],
			['post', '/apps/filinq/api/mail-archive-imports/a%2Fb/start', { folderMap: [{ archiveFolder: 'Inbox', dossier: '' }] }],
			['post', '/apps/filinq/api/mail-archive-imports/a%2Fb/cancel', undefined],
		])
	})

	it('keeps a refused dossier as a 403 the page can name', async () => {
		http.answer = () => Promise.reject({ response: { status: 403, data: { error: 'dossier_refused' } } })

		expect(await mapFolders('x', [])).toEqual({ ok: false, status: 403, error: 'dossier_refused' })
	})

	it('sends every folder, with the picked dossier or the inbox', () => {
		const folders = [{ archiveFolder: 'Inbox' }, { archiveFolder: 'Projecten/Brug' }, { archiveFolder: 'Verzonden' }]

		expect(mappingPayload(folders, { 'Projecten/Brug': { id: 'd-1', label: 'Brug' }, Verzonden: ' d-2 ', Inbox: null })).toEqual([
			{ archiveFolder: 'Inbox', dossier: '' },
			{ archiveFolder: 'Projecten/Brug', dossier: 'd-1' },
			{ archiveFolder: 'Verzonden', dossier: 'd-2' },
		])
	})

	it('reads the dossiers as options, and none when they cannot be read', async () => {
		http.answer = () => Promise.resolve({ data: { results: [{ '@self': { id: 'u-1' }, name: 'Vervanging brug Zuid' }, { uuid: 'u-2' }] } })
		expect(await loadDossierOptions()).toEqual([
			{ id: 'u-1', label: 'Vervanging brug Zuid' },
			{ id: 'u-2', label: 'Unnamed dossier' },
		])
		expect(http.calls[0][1]).toBe('/apps/openregister/api/objects/filinq/dossier')

		http.answer = () => Promise.reject({ response: { status: 403 } })
		expect(await loadDossierOptions()).toEqual([])
	})

	it('measures progress over everything handled, capped at 100', () => {
		expect(progressPercent({ found: 0 })).toBe(0)
		expect(progressPercent()).toBe(0)
		expect(progressPercent({ found: 8, filed: 1, toInbox: 1, skippedDuplicate: 1, failed: 0 })).toBe(37)
		expect(progressPercent({ found: 2, filed: 3 })).toBe(100)
	})

	it('writes the counts as one line', () => {
		expect(countsText({ found: 6, filed: 2, toInbox: 3, skippedDuplicate: 0, failed: 1 })).toBe(
			'6 found: 2 filed, 3 to the intake inbox, 0 already there, 1 failed',
		)
	})

	it('labels every status with a colour, and cancels only what has not ended', () => {
		const colours = statusColorMap()
		for (const status of STATUSES) {
			expect(colours[statusLabel(status)]).toBeTruthy()
		}
		expect(colours[statusLabel('failed')]).toBe('error')
		expect(STATUSES.filter((status) => canCancel({ status }))).toEqual(['received', 'mapping', 'importing'])
		expect(statusLabel('odd')).toBe('odd')
	})

	it('names the formats and the failure reasons', () => {
		expect(formatLabel('pst')).toBe('Outlook PST')
		expect(formatLabel('rar')).toBe('')
		expect(failureText('unsupported-format')).toContain('.msg')
		expect(failureText('unparseable')).toBe('This message could not be read as an email.')
		expect(failureText('weird')).toBe('weird')
		expect(failureText('')).toBe('Unknown reason')
	})
})
