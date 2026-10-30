/**
 * Folder anonymise writes only the files a person checked, and names the rest.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import { createPinia, setActivePinia } from 'pinia'
import { reviewSignature } from '../../services/reviewMark.js'
import { useFolderAnonymizationStore } from './folderAnonymization.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: {
		patch: jest.fn(),
		post: jest.fn(),
		get: jest.fn(),
	},
}))
jest.mock('@nextcloud/router', () => ({
	generateUrl: (path) => path,
}))

/**
 * A folder batch in review: two extracted files, one consolidated entity.
 *
 * @param {object} store The folder store.
 * @return {void}
 */
function seedReview(store) {
	store.batchId = 'b-1'
	store.batchStatus = 'review'
	store.files = [
		{ fileId: 1, fileName: 'a.pdf', status: 'extracted' },
		{ fileId: 2, fileName: 'b.pdf', status: 'extracted' },
		{ fileId: 3, fileName: 'c.pdf', status: 'error' },
	]
	store.entities = [
		{
			type: 'PERSON',
			value: 'Jan de Wit',
			highestConfidence: 0.9,
			included: true,
			relationIds: [11],
			_decisionBases: [],
			_decisionSkip: false,
		},
	]
}

// @spec openspec/changes/archive/2026-10-30-anonymise-asks-for-the-review-mark/tasks.md#task-4
describe('folder anonymise: the human check per file', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		jest.clearAllMocks()
		axios.post.mockReset()
	})

	it('sends nothing and asks for a check when no file was checked', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)

		await store.anonymizeFolder()

		expect(axios.post).not.toHaveBeenCalled()
		expect(store.reviewRequired).toBe(true)
		expect(store.unreviewedFiles.map((f) => f.fileId)).toEqual([1, 2])
	})

	it('anonymises the checked file only and lists the unchecked one', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		axios.post.mockImplementation((url) => {
			if (url.endsWith('/checked')) {
				return Promise.resolve({
					data: { checkedBy: 'ingrid', checkedAt: '2026-10-16T10:00:00+00:00' },
				})
			}
			return Promise.resolve({ data: { anonymizedFileId: 99 } })
		})

		await store.markFileChecked(store.files[0])
		await store.anonymizeFolder()

		const urls = axios.post.mock.calls.map(([url]) => url)
		expect(urls).toEqual([
			'/apps/filinq/api/redaction/documents/1/checked',
			'/apps/filinq/api/anonymization/anonymize/1',
		])
		const [, checkBody] = axios.post.mock.calls[0]
		const [, anonBody] = axios.post.mock.calls[1]
		expect(checkBody.entities).toEqual(anonBody.entities)
		expect(store.skippedUnreviewed).toEqual([{ fileId: 2, fileName: 'b.pdf' }])
		expect(store.files[0].status).toBe('completed')
		expect(store.files[1].status).toBe('extracted')
	})

	it('drops a check when the entity selection changes afterwards', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		axios.post.mockResolvedValue({
			data: { checkedBy: 'ingrid', checkedAt: '2026-10-16T10:00:00+00:00' },
		})

		await store.markFileChecked(store.files[0])
		expect(store.reviewedFiles.map((f) => f.fileId)).toEqual([1])

		store.toggleEntity(0)

		expect(store.reviewedFiles).toEqual([])
	})
})

// @spec openspec/specs/folder-analysis-anonymization/spec.md
describe('folder anonymise: skip decisions go through filinq', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		jest.clearAllMocks()
		axios.post.mockReset()
		axios.patch.mockReset()
	})

	it('sends a skip to the filinq relation route, never straight to OpenRegister', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		store.entities[0]._decisionSkip = true
		axios.patch.mockResolvedValue({ data: { status: 'ok' } })
		axios.post.mockImplementation((url) => Promise.resolve({
			data: url.endsWith('/checked')
				? { checkedBy: 'ingrid', checkedAt: '2026-10-16T10:00:00+00:00' }
				: { anonymizedFileId: 99 },
		}))

		await store.markFileChecked(store.files[0])
		await store.anonymizeFolder()

		const urls = axios.patch.mock.calls.map(([url]) => url)
		expect(urls).toEqual(['/apps/filinq/api/anonymization/relations/11'])
		expect(axios.patch.mock.calls[0][1]).toEqual({ bases: [], skipAnonymization: true })
	})

	it('keeps the refusal of a prohibited skip on the entity', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		store.entities[0]._decisionSkip = true
		axios.patch.mockRejectedValue({ response: { data: { error: 'Entity is on the publication prohibition list; skipping is not allowed.' } } })
		axios.post.mockImplementation((url) => Promise.resolve({
			data: url.endsWith('/checked')
				? { checkedBy: 'ingrid', checkedAt: '2026-10-16T10:00:00+00:00' }
				: { anonymizedFileId: 99 },
		}))

		await store.markFileChecked(store.files[0])
		await store.anonymizeFolder()

		expect(store.entities[0]._patchError).toContain('prohibition list')
	})
})

/**
 * Give every extracted file of the store a check over the current selection.
 *
 * @param {object} store The folder store.
 * @return {void}
 */
function checkAll(store) {
	const signature = reviewSignature(
		store.entities
			.filter((e) => e.included)
			.map((e) => ({ type: e.type, value: e.value, confidence: e.highestConfidence })),
	)
	for (const file of store.files) {
		if (file.status === 'extracted') {
			file.reviewMark = { checkedBy: 'ingrid', checkedAt: '2026-10-16T10:00:00+00:00', signature }
		}
	}
}

// @spec openspec/specs/folder-batch-analysis/spec.md#requirement-anonymized-output-in-source-folder
describe('folder anonymise: characterization of the full run', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		jest.clearAllMocks()
		axios.post.mockReset()
		axios.patch.mockReset()
	})

	it('sends the dossier key, the summary pair and the checked entities to every file', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		store.folderId = 77
		store.appendBasisSummary = true
		checkAll(store)
		axios.post.mockResolvedValue({ data: { anonymizedFileId: 99 } })

		await store.anonymizeFolder()

		expect(axios.post.mock.calls.map(([url]) => url)).toEqual([
			'/apps/filinq/api/anonymization/anonymize/1',
			'/apps/filinq/api/anonymization/anonymize/2',
		])
		expect(axios.post.mock.calls[0][1]).toEqual({
			entities: [{ type: 'PERSON', value: 'Jan de Wit', confidence: 0.9 }],
			scope: 'dossier',
			dossierKey: '77',
			appendBasisSummary: true,
			outputFormat: 'pdf-only',
		})
	})

	it('omits the dossier key and the summary pair when neither applies', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		store.folderId = null
		store.appendBasisSummary = false
		checkAll(store)
		axios.post.mockResolvedValue({ data: { anonymizedFileId: 99 } })

		await store.anonymizeFolder()

		expect(axios.post.mock.calls[0][1]).toEqual({
			entities: [{ type: 'PERSON', value: 'Jan de Wit', confidence: 0.9 }],
			scope: 'dossier',
		})
	})

	it('copies the result onto each file and completes the batch', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		checkAll(store)
		axios.post.mockResolvedValue({
			data: { anonymizedFileId: 99, replacementCount: 4, complete: false, residualCount: 1 },
		})

		await store.anonymizeFolder()

		expect(store.files[0]).toMatchObject({
			status: 'completed',
			anonymizedFileId: 99,
			replacementCount: 4,
			complete: false,
			residualCount: 1,
			anonymizeError: null,
		})
		expect(store.progress).toBe(100)
		expect(store.batchStatus).toBe('completed')
		expect(store.error).toBeNull()
		expect(store.processing).toBe(false)
		expect(store.reviewRequired).toBe(false)
	})

	it('defaults the optional result fields', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		checkAll(store)
		axios.post.mockResolvedValue({ data: { anonymizedFileId: 5 } })

		await store.anonymizeFolder()

		expect(store.files[1]).toMatchObject({ replacementCount: 0, complete: true, residualCount: 0 })
	})

	it('completes with a warning when some files fail', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		checkAll(store)
		axios.post
			.mockResolvedValueOnce({ data: { anonymizedFileId: 99 } })
			.mockRejectedValueOnce({ response: { data: { error: 'Te groot' } } })

		await store.anonymizeFolder()

		expect(store.files[1]).toMatchObject({ status: 'error', anonymizeError: 'Te groot' })
		expect(store.progress).toBe(50)
		expect(store.batchStatus).toBe('completed')
		expect(store.error).toBe('Some files failed: Te groot')
	})

	it('fails the batch when every file fails', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		checkAll(store)
		axios.post.mockRejectedValue(new Error('offline'))

		await store.anonymizeFolder()

		expect(store.progress).toBe(0)
		expect(store.batchStatus).toBe('error')
		expect(store.error).toBe('offline')
		expect(store.processing).toBe(false)
	})

	it('patches the chosen bases onto every relation of an included entity', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		store.entities[0].relationIds = [11, 12]
		store.entities[0]._decisionBases = ['art-5-1-2-e']
		checkAll(store)
		axios.patch.mockResolvedValue({ data: {} })
		axios.post.mockResolvedValue({ data: { anonymizedFileId: 99 } })

		await store.anonymizeFolder()

		expect(axios.patch.mock.calls).toEqual([
			['/apps/filinq/api/anonymization/relations/11', { bases: ['art-5-1-2-e'], skipAnonymization: false }],
			['/apps/filinq/api/anonymization/relations/12', { bases: ['art-5-1-2-e'], skipAnonymization: false }],
		])
	})

	it('keeps patching the other relations after one fails', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		store.entities[0].relationIds = [11, 12]
		store.entities[0]._decisionSkip = true
		checkAll(store)
		axios.patch
			.mockRejectedValueOnce(new Error('first failed'))
			.mockResolvedValueOnce({ data: {} })
		axios.post.mockResolvedValue({ data: { anonymizedFileId: 99 } })

		await store.anonymizeFolder()

		expect(axios.patch).toHaveBeenCalledTimes(2)
		expect(store.entities[0]._patchError).toBe('first failed')
	})

	it('names an entity whose decision cannot persist for lack of relation ids', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		store.entities[0].relationIds = undefined
		store.entities[0]._decisionSkip = true
		checkAll(store)
		axios.post.mockResolvedValue({ data: { anonymizedFileId: 99 } })

		await store.anonymizeFolder()

		expect(axios.patch).not.toHaveBeenCalled()
		expect(store.entities[0]._patchError).toBe('No relation ids — decisions cannot persist.')
	})

	it('patches nothing for an excluded entity or one without a decision', async () => {
		const store = useFolderAnonymizationStore()
		seedReview(store)
		store.entities.push({ ...store.entities[0], value: 'Piet', included: false, _decisionSkip: true })
		checkAll(store)
		axios.post.mockResolvedValue({ data: { anonymizedFileId: 99 } })

		await store.anonymizeFolder()

		expect(axios.patch).not.toHaveBeenCalled()
		expect(axios.post.mock.calls[0][1].entities).toEqual([
			{ type: 'PERSON', value: 'Jan de Wit', confidence: 0.9 },
		])
	})
})
