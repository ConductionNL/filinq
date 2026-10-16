/**
 * Folder anonymise writes only the files a person checked, and names the rest.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import { createPinia, setActivePinia } from 'pinia'
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

// @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-4
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
