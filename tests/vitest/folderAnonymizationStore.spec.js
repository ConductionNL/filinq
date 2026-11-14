// @vitest-environment jsdom
/**
 * The folder anonymisation store outside its anonymise run: starting a
 * folder batch, polling it, loading the consolidated entities, the review
 * controls, the dossier and its grondslagen report, and the derived getters.
 *
 * Only HTTP is mocked (@nextcloud/axios); the router resolves through the
 * shared stub and the review-mark service runs for real over the mocked
 * HTTP.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { WOO_BASES } from '../../src/constants/grondslagen.js'
import { reviewSignature } from '../../src/services/reviewMark.js'
import { useFolderAnonymizationStore } from '../../src/store/modules/folderAnonymization.js'

vi.mock('@nextcloud/axios', () => ({
	default: { get: vi.fn(), post: vi.fn(), patch: vi.fn() },
}))

/**
 * An HTTP error carrying a server message.
 *
 * @param {string} message The server's error text.
 * @return {Error}
 */
function serverError(message) {
	return Object.assign(new Error('Request failed'), {
		response: { data: { error: message } },
	})
}

beforeEach(() => {
	setActivePinia(createPinia())
	vi.mocked(axios.get).mockReset()
	vi.mocked(axios.post).mockReset()
	vi.mocked(axios.patch).mockReset()
})

afterEach(() => {
	vi.useRealTimers()
})

describe('startFolderBatch', () => {
	it('stores the batch, defaults the dossier name to the folder basename and starts polling', async () => {
		vi.useFakeTimers()
		vi.mocked(axios.post).mockResolvedValue({
			data: { batchId: 'b-1', folderId: 77, files: [{ fileId: 1 }, { fileId: 2 }], fileCount: 5 },
		})
		const store = useFolderAnonymizationStore()

		await store.startFolderBatch('/Projects/Woo request 12/')

		expect(axios.post).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/anonymization/batch/folder',
			{ folderPath: '/Projects/Woo request 12/' },
		)
		expect(store.batchId).toBe('b-1')
		expect(store.folderId).toBe(77)
		expect(store.files).toEqual([{ fileId: 1 }, { fileId: 2 }])
		expect(store.totalFiles).toBe(5)
		expect(store.batchStatus).toBe('extracting')
		expect(store.dossier.name).toBe('Woo request 12')
		expect(store.processing).toBe(false)
		expect(store.isActive).toBe(true)
		expect(store.pollTimer).not.toBeNull()
		store.stopPolling()
	})

	it('falls back to the file count of the list and keeps a dossier name already typed', async () => {
		vi.useFakeTimers()
		vi.mocked(axios.post).mockResolvedValue({ data: { batchId: 'b-2', files: [{ fileId: 3 }] } })
		const store = useFolderAnonymizationStore()
		store.dossier.name = 'My dossier'

		await store.startFolderBatch('/Inbox')

		expect(store.folderId).toBeNull()
		expect(store.totalFiles).toBe(1)
		expect(store.dossier.name).toBe('My dossier')
		store.stopPolling()
	})

	it('records the server error and marks the batch errored', async () => {
		vi.mocked(axios.post).mockRejectedValue(serverError('Folder not found'))
		const store = useFolderAnonymizationStore()

		await store.startFolderBatch('/Missing')

		expect(store.error).toBe('Folder not found')
		expect(store.batchStatus).toBe('error')
		expect(store.processing).toBe(false)
		expect(store.pollTimer).toBeNull()
	})
})

describe('polling', () => {
	it('polls the status endpoint every three seconds until stopped', async () => {
		vi.useFakeTimers()
		vi.mocked(axios.get).mockResolvedValue({ data: { batchStatus: 'extracting', progress: 40 } })
		const store = useFolderAnonymizationStore()
		store.batchId = 'b-3'

		store.startPolling()
		await vi.advanceTimersByTimeAsync(3000)
		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/anonymization/batch/b-3/status')
		expect(store.progress).toBe(40)

		store.stopPolling()
		expect(store.pollTimer).toBeNull()
		await vi.advanceTimersByTimeAsync(9000)
		expect(axios.get).toHaveBeenCalledTimes(1)
	})

	it('does nothing without a batch', async () => {
		const store = useFolderAnonymizationStore()

		await store.pollStatus()

		expect(axios.get).not.toHaveBeenCalled()
	})

	it('keeps the previous files and totals when the status omits them', async () => {
		vi.mocked(axios.get).mockResolvedValue({ data: { batchStatus: 'extracting' } })
		const store = useFolderAnonymizationStore()
		store.batchId = 'b-4'
		store.files = [{ fileId: 9 }]
		store.totalFiles = 3
		store.progress = 50

		await store.pollStatus()

		expect(store.files).toEqual([{ fileId: 9 }])
		expect(store.totalFiles).toBe(3)
		expect(store.progress).toBe(0)
	})

	it('stops polling and loads the entities once the batch reaches review', async () => {
		vi.useFakeTimers()
		vi.mocked(axios.get)
			.mockResolvedValueOnce({ data: { batchStatus: 'review', files: [{ fileId: 1, status: 'extracted' }], progress: 100, totalFiles: 1 } })
			.mockResolvedValueOnce({ data: { entities: [{ type: 'PERSON', value: 'Jan' }], entityCount: 1 } })
		const store = useFolderAnonymizationStore()
		store.batchId = 'b-5'
		store.startPolling()

		await store.pollStatus()

		expect(store.pollTimer).toBeNull()
		expect(axios.get).toHaveBeenLastCalledWith('/index.php/apps/filinq/api/anonymization/batch/b-5/entities')
		expect(store.batchStatus).toBe('review')
		expect(store.entities).toHaveLength(1)
		expect(store.totalEntities).toBe(1)
	})

	it('records a failed poll', async () => {
		vi.mocked(axios.get).mockRejectedValue(new Error('Network down'))
		const store = useFolderAnonymizationStore()
		store.batchId = 'b-6'

		await store.pollStatus()

		expect(store.error).toBe('Network down')
	})
})

describe('fetchEntities', () => {
	it('adds the confidence filter and seeds the per-entity review state', async () => {
		vi.mocked(axios.get).mockResolvedValue({
			data: { entities: [{ type: 'EMAIL', value: 'a@b.nl', relationIds: [4] }], entityCount: 1 },
		})
		const store = useFolderAnonymizationStore()
		store.batchId = 'b-7'
		store.minConfidence = 0.6

		await store.fetchEntities()

		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/filinq/api/anonymization/batch/b-7/entities?minConfidence=0.6')
		expect(store.entities).toEqual([{
			type: 'EMAIL',
			value: 'a@b.nl',
			relationIds: [4],
			included: true,
			_decisionBases: [],
			_decisionSkip: false,
			_patchError: null,
		}])
		expect(store.selectedEntityCount).toBe(1)
	})

	it('treats a missing list as no entities', async () => {
		vi.mocked(axios.get).mockResolvedValue({ data: {} })
		const store = useFolderAnonymizationStore()
		store.batchId = 'b-8'

		await store.fetchEntities()

		expect(store.entities).toEqual([])
		expect(store.totalEntities).toBe(0)
	})

	it('records the server error', async () => {
		vi.mocked(axios.get).mockRejectedValue(serverError('Batch expired'))
		const store = useFolderAnonymizationStore()
		store.batchId = 'b-9'

		await store.fetchEntities()

		expect(store.error).toBe('Batch expired')
	})
})

describe('entity review controls', () => {
	/**
	 * A store holding three entities.
	 *
	 * @return {object}
	 */
	function seeded() {
		const store = useFolderAnonymizationStore()
		store.entities = [
			{ type: 'PERSON', value: 'Jan', included: true, _decisionBases: [], _decisionSkip: false, _patchError: 'old' },
			{ type: 'PERSON', value: 'Piet', included: true, _decisionBases: [], _decisionSkip: false, _patchError: 'old' },
			{ type: 'IBAN', value: 'NL01', included: false, _decisionBases: [], _decisionSkip: false, _patchError: null },
		]
		return store
	}

	it('toggles one entity and ignores an index out of range', () => {
		const store = seeded()

		store.toggleEntity(0)
		store.toggleEntity(2)
		store.toggleEntity(9)

		expect(store.entities.map((e) => e.included)).toEqual([false, true, true])
	})

	it('sets the visible entities in one go, skipping unknown indices', () => {
		const store = seeded()

		store.setVisibleEntities([0, 2, 5], false)

		expect(store.entities.map((e) => e.included)).toEqual([false, true, false])
		expect(store.selectedEntityCount).toBe(1)
	})

	it('copies the chosen bases and clears the patch error; a non-array clears them', () => {
		const store = seeded()
		const bases = ['art-5-1-2-e']

		store.setEntityBases(0, bases)
		bases.push('mutated')
		store.setEntityBases(1, 'not-an-array')
		store.setEntityBases(9, ['ignored'])

		expect(store.entities[0]._decisionBases).toEqual(['art-5-1-2-e'])
		expect(store.entities[0]._patchError).toBeNull()
		expect(store.entities[1]._decisionBases).toEqual([])
		expect(store.entities[1]._patchError).toBeNull()
	})

	it('coerces the skip flag to a boolean and clears the patch error', () => {
		const store = seeded()

		store.setEntitySkip(0, 1)
		store.setEntitySkip(1, '')
		store.setEntitySkip(9, true)

		expect(store.entities[0]._decisionSkip).toBe(true)
		expect(store.entities[0]._patchError).toBeNull()
		expect(store.entities[1]._decisionSkip).toBe(false)
	})
})

describe('markFileChecked and the review getters', () => {
	it('records the mark for the included entities and moves the file to reviewed', async () => {
		vi.mocked(axios.post).mockResolvedValue({ data: { checkedBy: 'alice', checkedAt: '2026-11-13T10:00:00Z' } })
		const store = useFolderAnonymizationStore()
		store.entities = [
			{ type: 'PERSON', value: 'Jan', highestConfidence: 0.9, included: true },
			{ type: 'IBAN', value: 'NL01', highestConfidence: 0.8, included: false },
		]
		const file = { fileId: 12, fileName: 'a.pdf', status: 'extracted', reviewMark: null }
		store.files = [file, { fileId: 13, fileName: 'b.pdf', status: 'extracting' }]
		expect(store.unreviewedFiles.map((f) => f.fileId)).toEqual([12])

		await store.markFileChecked(store.files[0])

		expect(axios.post).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/redaction/documents/12/checked',
			{ entities: [{ type: 'PERSON', value: 'Jan', confidence: 0.9 }] },
		)
		expect(store.files[0].reviewMark).toEqual({
			checkedBy: 'alice',
			checkedAt: '2026-11-13T10:00:00Z',
			signature: reviewSignature([{ type: 'PERSON', value: 'Jan' }]),
		})
		expect(store.files[0].reviewing).toBe(false)
		expect(store.reviewedFiles.map((f) => f.fileId)).toEqual([12])
		expect(store.unreviewedFiles).toEqual([])
	})

	it('keeps the server error on the file and stops reviewing', async () => {
		vi.mocked(axios.post).mockRejectedValue(serverError('Not allowed'))
		const store = useFolderAnonymizationStore()
		store.files = [{ fileId: 14, status: 'extracted' }]

		await store.markFileChecked(store.files[0])

		expect(store.files[0].reviewError).toBe('Not allowed')
		expect(store.files[0].reviewing).toBe(false)
	})

	it('ignores a missing file or one that is not extracted', async () => {
		const store = useFolderAnonymizationStore()

		await store.markFileChecked(null)
		await store.markFileChecked({ fileId: 15, status: 'extracting' })

		expect(axios.post).not.toHaveBeenCalled()
	})

	it('counts files with entities and files done extracting', () => {
		const store = useFolderAnonymizationStore()
		store.files = [
			{ status: 'extracted', entityCount: 2 },
			{ status: 'error' },
			{ status: 'extracting', entityCount: 0 },
		]

		expect(store.filesWithEntities).toBe(1)
		expect(store.extractedCount).toBe(2)
		expect(store.basesOptions).toBe(WOO_BASES)
		expect(store.hasDossier).toBe(false)
	})
})

describe('createDossier', () => {
	it('refuses without a bound folder', async () => {
		const store = useFolderAnonymizationStore()

		await store.createDossier()

		expect(store.dossier.error).toBe('No folder bound yet — start the folder batch first.')
		expect(axios.post).not.toHaveBeenCalled()
	})

	it('posts the dossier bound to the folder and stores its uuid', async () => {
		vi.mocked(axios.post).mockResolvedValue({ data: { '@self': { id: 'd-1' } } })
		const store = useFolderAnonymizationStore()
		store.folderId = 77
		store.folderPath = '/Projects/Woo'
		store.dossier.description = 'Request 12'

		await store.createDossier()

		expect(axios.post).toHaveBeenCalledWith(
			'/index.php/apps/openregister/api/objects/filinq/dossier',
			{ name: '/Projects/Woo', description: 'Request 12', bases: ['art-5-1-2-e'], '@self': { folder: '77' } },
		)
		expect(store.dossier.uuid).toBe('d-1')
		expect(store.dossier.error).toBeNull()
		expect(store.dossier.creating).toBe(false)
		expect(store.hasDossier).toBe(true)
	})

	it('falls back to the default name and reads id or uuid from the response', async () => {
		vi.mocked(axios.post).mockResolvedValueOnce({ data: { uuid: 'd-2' } })
		const store = useFolderAnonymizationStore()
		store.folderId = 5
		store.dossier.bases = null

		await store.createDossier()

		expect(vi.mocked(axios.post).mock.calls[0][1]).toEqual({
			name: 'Dossier', description: '', bases: [], '@self': { folder: '5' },
		})
		expect(store.dossier.uuid).toBe('d-2')
	})

	it('reports a create without a uuid in the response', async () => {
		vi.mocked(axios.post).mockResolvedValue({ data: {} })
		const store = useFolderAnonymizationStore()
		store.folderId = 6

		await store.createDossier()

		expect(store.dossier.uuid).toBeNull()
		expect(store.dossier.error).toBe('Dossier created but UUID not found in response.')
	})

	it('records the server error', async () => {
		vi.mocked(axios.post).mockRejectedValue(serverError('Schema missing'))
		const store = useFolderAnonymizationStore()
		store.folderId = 7

		await store.createDossier()

		expect(store.dossier.error).toBe('Schema missing')
		expect(store.dossier.creating).toBe(false)
	})
})

describe('generateDossierReport', () => {
	it('refuses without a dossier', async () => {
		const store = useFolderAnonymizationStore()

		await store.generateDossierReport()

		expect(store.report.error).toBe('No dossier UUID — create a dossier first.')
		expect(axios.post).not.toHaveBeenCalled()
	})

	it('posts to the dossier report endpoint and keeps the result', async () => {
		vi.mocked(axios.post).mockResolvedValue({ data: { fileId: 99 } })
		const store = useFolderAnonymizationStore()
		store.dossier.uuid = 'd-3'

		await store.generateDossierReport()

		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/anonymization/dossier/d-3/grondslagen-pdf')
		expect(store.report).toEqual({ generating: false, result: { fileId: 99 }, error: null })
	})

	it('records the server error', async () => {
		vi.mocked(axios.post).mockRejectedValue(serverError('Render failed'))
		const store = useFolderAnonymizationStore()
		store.dossier.uuid = 'd-4'

		await store.generateDossierReport()

		expect(store.report).toEqual({ generating: false, result: null, error: 'Render failed' })
	})
})

describe('decision writes during anonymizeFolder', () => {
	it('flags an entity with a decision but no relation ids, and skips entities without one', async () => {
		vi.mocked(axios.post).mockResolvedValue({ data: { anonymizedFileId: 'x' } })
		const store = useFolderAnonymizationStore()
		const entities = [
			{ type: 'PERSON', value: 'Jan', included: true, _decisionBases: ['art-5-1-2-e'], _decisionSkip: false, relationIds: [] },
			{ type: 'PERSON', value: 'Piet', included: true, _decisionBases: [], _decisionSkip: false, relationIds: [3] },
		]
		store.entities = entities
		store.files = [{
			fileId: 20,
			status: 'extracted',
			reviewMark: { signature: reviewSignature(entities) },
		}]

		await store.anonymizeFolder()

		expect(axios.patch).not.toHaveBeenCalled()
		expect(store.entities[0]._patchError).toBe('No relation ids — decisions cannot persist.')
		expect(store.batchStatus).toBe('completed')
	})
})
