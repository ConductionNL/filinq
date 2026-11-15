/**
 * The anonymisation store's anonymise and mark-checked actions: the review
 * gate, the decision PATCHes sent first, the anonymise payload, how the
 * result or failure lands on the entry, and the upload queue's gate.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { reviewSignature } from '../../src/services/reviewMark.js'
import { useAnonymizationStore } from '../../src/store/modules/anonymization.js'

const calls = []
let fail = {}
let anonymizeAnswer = {}

vi.mock('@nextcloud/axios', () => {
	const axios = vi.fn(async (config) => {
		calls.push([config.method, config.url])
		if (fail[config.method]) {
			throw fail[config.method]
		}
		return { data: {} }
	})
	axios.patch = vi.fn(async (url, body) => {
		calls.push(['PATCH', url, body])
		if (fail.PATCH) {
			throw fail.PATCH
		}
		return { status: 200, data: {} }
	})
	axios.post = vi.fn(async (url, body) => {
		calls.push(['POST', url, body])
		if (url.endsWith('/checked')) {
			if (fail.CHECKED) {
				throw fail.CHECKED
			}
			return { data: { checkedBy: 'alice', checkedAt: '2026-11-13T10:00:00+00:00' } }
		}
		if (fail.POST) {
			throw fail.POST
		}
		return { data: anonymizeAnswer }
	})
	return { default: axios }
})

/**
 * An HTTP error shaped like axios's.
 *
 * @param {number} status The HTTP status.
 * @param {object} data The response body.
 * @return {Error}
 */
function httpError(status, data) {
	const err = new Error('HTTP ' + status)
	err.response = { status, data }
	return err
}

/**
 * An extracted entry whose review mark matches its included entities.
 *
 * @param {Array<object>} entities The entities.
 * @return {object}
 */
function checkedEntry(entities) {
	const included = entities.filter((e) => e.included !== false)
	return {
		name: 'brief.pdf',
		fileId: 12,
		status: 'extracted',
		entities,
		reviewMark: { checkedBy: 'alice', checkedAt: 'x', signature: reviewSignature(included) },
	}
}

describe('anonymization store anonymiseEntry', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useAnonymizationStore()
		store.loadAnonymizedEntities = vi.fn(async () => ({ entities: [{ entityId: 1, type: 'PERSON' }] }))
		calls.length = 0
		fail = {}
		anonymizeAnswer = {
			anonymizedFileId: 99,
			anonymizedFileName: 'brief (geanonimiseerd).pdf',
			anonymizedFilePath: '/DocuDesk/brief (geanonimiseerd).pdf',
			replacementCount: 4,
		}
		vi.spyOn(console, 'error').mockImplementation(() => {})
	})

	it('does nothing unless the entry is extracted', async () => {
		const entry = { ...checkedEntry([]), status: 'queued' }

		await store.anonymiseEntry(entry)

		expect(entry.status).toBe('queued')
		expect(calls).toEqual([])
	})

	it('an unchecked entry is sent back for review without any request', async () => {
		const entry = checkedEntry([{ type: 'PERSON', value: 'Jan' }])
		entry.reviewMark = { signature: 'stale' }

		await store.anonymiseEntry(entry)

		expect(entry.reviewRequired).toBe(true)
		expect(entry.status).toBe('extracted')
		expect(calls).toEqual([])
	})

	it('sends changed decisions, then anonymises the included entities, and records the result', async () => {
		const changed = { type: 'PERSON', value: 'Jan', confidence: 0.9, relationIds: [1, 2], bases: [], _decisionBases: ['5.1.2e'], _decisionSkip: false }
		const unchanged = { type: 'EMAIL', value: 'a@b.nl', confidence: 0.8, relationId: 3, bases: ['x'], _decisionBases: ['x'] }
		const skipped = { type: 'LOC', value: 'Utrecht', included: false, relationId: 4, skipAnonymization: false, _decisionSkip: true }
		const entry = checkedEntry([changed, unchanged, skipped])

		await store.anonymiseEntry(entry, { appendBasisSummary: true, outputFormat: 'pdf', reversible: true })

		expect(calls).toEqual([
			['PATCH', '/index.php/apps/filinq/api/anonymization/relations/1', { bases: ['5.1.2e'], skipAnonymization: false }],
			['PATCH', '/index.php/apps/filinq/api/anonymization/relations/2', { bases: ['5.1.2e'], skipAnonymization: false }],
			['PATCH', '/index.php/apps/filinq/api/anonymization/relations/4', { bases: [], skipAnonymization: true }],
			['POST', '/index.php/apps/filinq/api/anonymization/anonymize/12', {
				entities: [
					{ type: 'EMAIL', value: 'a@b.nl', confidence: 0.8 },
					{ type: 'PERSON', value: 'Jan', confidence: 0.9 },
				],
				scope: 'document',
				appendBasisSummary: true,
				outputFormat: 'pdf',
				reversible: true,
			}],
		])
		expect(changed.bases).toEqual(['5.1.2e'])
		expect(skipped.skipAnonymization).toBe(true)
		expect(entry).toMatchObject({
			status: 'completed',
			reviewRequired: false,
			anonymizedFileId: 99,
			replacementCount: 4,
			complete: true,
			residualCount: 0,
			residualEntities: [],
			pseudonymisation: null,
			reanonymize: false,
			resolvedEntities: [{ entityId: 1, type: 'PERSON' }],
		})
		expect(store.loadAnonymizedEntities).toHaveBeenCalledWith(
			{ fileId: 99, fileName: 'brief (geanonimiseerd).pdf', path: '/DocuDesk/brief (geanonimiseerd).pdf' },
			{ push: false },
		)
	})

	it('leaves the basis summary out without an output format, and keeps an incomplete result', async () => {
		anonymizeAnswer = { anonymizedFileId: 5, complete: false, residualCount: 2, residualEntities: ['Jan'] }
		store.loadAnonymizedEntities = vi.fn(async () => ({ entities: [] }))
		const entry = checkedEntry([])

		await store.anonymiseEntry(entry, { appendBasisSummary: true })

		expect(calls).toEqual([
			['POST', '/index.php/apps/filinq/api/anonymization/anonymize/12', { entities: [], scope: 'document' }],
		])
		expect(entry).toMatchObject({ status: 'completed', complete: false, residualCount: 2, residualEntities: ['Jan'], replacementCount: 0 })
		expect(entry.resolvedEntities).toBeUndefined()
	})

	it('a failed decision PATCH is recorded on the entity and anonymising goes on', async () => {
		fail.PATCH = httpError(403, { error: 'Locked' })
		const entity = { type: 'PERSON', value: 'Jan', relationId: 1, bases: [], _decisionBases: ['x'] }
		const entry = checkedEntry([entity])

		await store.anonymiseEntry(entry)

		expect(entity._patchError).toBe('Locked')
		expect(entity.bases).toEqual([])
		expect(entry.status).toBe('completed')
	})

	it('a marker lookup that throws still completes the entry', async () => {
		store.loadAnonymizedEntities = vi.fn(async () => {
			throw new Error('no text')
		})
		const entry = checkedEntry([])

		await store.anonymiseEntry(entry)

		expect(entry.status).toBe('completed')
		expect(console.error).toHaveBeenCalled()
	})

	it('a refusal that asks for a review sends the entry back to review', async () => {
		fail.POST = httpError(409, { error: 'Review first', reviewRequired: true })
		const entry = checkedEntry([])

		await store.anonymiseEntry(entry)

		expect(entry).toMatchObject({ status: 'extracted', reviewRequired: true, reviewMark: null, error: 'Review first' })
	})

	it('any other failure marks the entry as failed with the message', async () => {
		fail.POST = new Error('Network Error')
		const entry = checkedEntry([])

		await store.anonymiseEntry(entry)

		expect(entry).toMatchObject({ status: 'error', error: 'Network Error' })
	})
})

describe('anonymization store markEntryChecked', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useAnonymizationStore()
		calls.length = 0
		fail = {}
	})

	it('records the review mark for the included entities', async () => {
		const entry = { fileId: 12, status: 'extracted', reviewRequired: true, error: 'old', entities: [
			{ type: 'PERSON', value: 'Jan', confidence: 1 },
			{ type: 'LOC', value: 'Utrecht', included: false },
		] }

		await store.markEntryChecked(entry)

		expect(calls).toEqual([
			['POST', '/index.php/apps/filinq/api/redaction/documents/12/checked', { entities: [{ type: 'PERSON', value: 'Jan', confidence: 1 }] }],
		])
		expect(entry.reviewMark).toEqual({
			checkedBy: 'alice',
			checkedAt: '2026-11-13T10:00:00+00:00',
			signature: reviewSignature([{ type: 'PERSON', value: 'Jan' }]),
		})
		expect(entry).toMatchObject({ reviewRequired: false, error: null, reviewing: false, reviewError: null })
	})

	it('a refused mark is shown as a review error', async () => {
		fail.CHECKED = httpError(403, { error: 'Not yours' })
		const entry = { fileId: 12, status: 'extracted', entities: [] }

		await store.markEntryChecked(entry)

		expect(entry).toMatchObject({ reviewError: 'Not yours', reviewing: false })
		expect(entry.reviewMark).toBeUndefined()
	})

	it('only extracted entries can be marked', async () => {
		await store.markEntryChecked(null)
		await store.markEntryChecked({ status: 'completed', entities: [] })

		expect(calls).toEqual([])
	})
})

describe('anonymization store dossier folders and queue', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useAnonymizationStore()
		calls.length = 0
		fail = {}
	})

	it('createDossierFolder makes the folder and accepts one that exists', async () => {
		await store.createDossierFolder('Dossier A')
		expect(calls[0][0]).toBe('MKCOL')
		expect(calls[0][1]).toMatch(/\/DocuDesk\/Dossier%20A\/$/)

		fail.MKCOL = httpError(405, {})
		await expect(store.createDossierFolder('Dossier A')).resolves.toBeUndefined()
	})

	it('createDossierFolder raises any other failure', async () => {
		fail.MKCOL = httpError(403, {})

		await expect(store.createDossierFolder('Dossier A')).rejects.toThrow('HTTP 403')
	})

	it('processQueue does not start a second run while one is processing', async () => {
		store.processing = true
		store.uploadAndExtract = vi.fn()
		store.files = [{ status: 'queued' }]

		await store.processQueue()

		expect(store.uploadAndExtract).not.toHaveBeenCalled()
		expect(store.processing).toBe(true)
	})

	it('processQueue runs only queued entries and clears the flag', async () => {
		const queued = { status: 'queued', name: 'a' }
		store.files = [{ status: 'completed', name: 'b' }, queued]
		store.uploadAndExtract = vi.fn(async (entry) => {
			expect(store.processing).toBe(true)
			entry.status = 'extracted'
		})

		await store.processQueue()

		expect(store.uploadAndExtract).toHaveBeenCalledTimes(1)
		expect(store.uploadAndExtract).toHaveBeenCalledWith(queued)
		expect(store.processing).toBe(false)
	})

	it('addFiles queues one entry per file and runs the queue', async () => {
		store.processQueue = vi.fn(async () => {})
		const file = { name: 'brief.pdf' }

		await store.addFiles([file, { name: 'nota.docx' }])

		expect(store.files.map((f) => [f.name, f.status, f.dossier, f._file])).toEqual([
			['brief.pdf', 'queued', null, file],
			['nota.docx', 'queued', null, { name: 'nota.docx' }],
		])
		expect(store.processQueue).toHaveBeenCalledTimes(1)
	})
})
