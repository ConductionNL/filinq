/* eslint-disable no-console */
import { setActivePinia, createPinia } from 'pinia'

import { useAnonymizationStore } from './anonymization.js'
import axios from '@nextcloud/axios'

// The store reaches for these Nextcloud helpers and the file-viewer service
// at module load / inside actions. Mock them so the store can be exercised
// in isolation — only `axios.patch` / `axios.post` matter for these tests.
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
	generateRemoteUrl: (path) => path,
}))
jest.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'tester' }),
}))
jest.mock('../../services/fileViewerService.js', () => ({
	extractDocumentText: jest.fn(),
}))

/**
 * Build a minimal `extracted` queue entry with one entity whose decision
 * defaults mirror what `decorateEntities` produces: `_decisionBases` is a
 * copy of `bases` and `_decisionSkip` mirrors `skipAnonymization`. This is
 * the shape the sidebar holds when the user never edits a card.
 *
 * @param {object} overrides Per-entity field overrides.
 * @return {object} Queue entry ready for `anonymiseEntry`.
 */
function makeEntry(overrides = {}) {
	const bases = overrides.bases ?? null
	return {
		id: 'file-1',
		name: 'doc.pdf',
		status: 'extracted',
		fileId: 42,
		entities: [
			{
				type: 'PERSON',
				value: 'Claudia Fischer',
				confidence: 0.9,
				included: true,
				relationIds: [101],
				bases,
				_decisionBases: Array.isArray(bases) ? [...bases] : [],
				skipAnonymization: false,
				_decisionSkip: false,
				_patchError: null,
				...overrides,
			},
		],
	}
}

describe('anonymiseEntry — PATCH suppression when nothing changed', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		jest.clearAllMocks()
		// Every run ends in the anonymise POST; give it a usable response.
		axios.post.mockResolvedValue({
			data: {
				anonymizedFileId: 99,
				anonymizedFileName: 'doc-anon.pdf',
				anonymizedFilePath: '/files/doc-anon.pdf',
				replacementCount: 1,
			},
		})
	})

	it('sends no entity-relation PATCH when grondslagen are untouched (no backend bases)', async () => {
		const store = useAnonymizationStore()
		const entry = makeEntry({ bases: null })

		await store.anonymiseEntry(entry)

		expect(axios.patch).not.toHaveBeenCalled()
		expect(axios.post).toHaveBeenCalledTimes(1)
		expect(entry.status).toBe('completed')
	})

	it('sends no PATCH when backend bases exist but the user changed nothing', async () => {
		const store = useAnonymizationStore()
		// _decisionBases is a value-copy of bases — JSON compare must match.
		const entry = makeEntry({ bases: ['persoonsgegevens'] })

		await store.anonymiseEntry(entry)

		expect(axios.patch).not.toHaveBeenCalled()
		expect(axios.post).toHaveBeenCalledTimes(1)
	})

	it('anonymises with the default-included entities when grondslagen are off', async () => {
		// Grondslagen UIT: the cards are read-only, the user touches nothing,
		// so the anonymise call must still carry the default-included entity.
		const store = useAnonymizationStore()
		const entry = makeEntry({ bases: null })

		await store.anonymiseEntry(entry)

		expect(axios.patch).not.toHaveBeenCalled()
		expect(axios.post).toHaveBeenCalledTimes(1)
		expect(axios.post).toHaveBeenCalledWith(
			'/apps/docudesk/api/anonymization/anonymize/42',
			{ entities: [{ type: 'PERSON', value: 'Claudia Fischer', confidence: 0.9 }], scope: 'document' },
		)
		expect(entry.status).toBe('completed')
		expect(entry.anonymizedFilePath).toBe('/files/doc-anon.pdf')
	})

	it('omits entities the review excluded from the anonymise payload', async () => {
		// Even with grondslagen off the inclusion default is `true`; an entity
		// explicitly de-selected (included=false) must drop out of the payload.
		const store = useAnonymizationStore()
		const entry = makeEntry({ bases: null, included: false })

		await store.anonymiseEntry(entry)

		expect(axios.post).toHaveBeenCalledWith(
			'/apps/docudesk/api/anonymization/anonymize/42',
			{ entities: [], scope: 'document' },
		)
	})

	it('PATCHes once per relation when the user edits the grondslagen', async () => {
		axios.patch.mockResolvedValue({ data: {} })
		const store = useAnonymizationStore()
		const entry = makeEntry({
			bases: ['persoonsgegevens'],
			// User picked an extra basis via the dropdown (grondslagen on).
			_decisionBases: ['persoonsgegevens', 'strafrechtelijk'],
		})

		await store.anonymiseEntry(entry)

		expect(axios.patch).toHaveBeenCalledTimes(1)
		expect(axios.patch).toHaveBeenCalledWith(
			'/apps/openregister/api/entity-relations/101',
			{ bases: ['persoonsgegevens', 'strafrechtelijk'], skipAnonymization: false },
		)
		expect(axios.post).toHaveBeenCalledTimes(1)
	})
})

describe('anonymiseEntry — grondslagen summary flags', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		jest.clearAllMocks()
		axios.post.mockResolvedValue({
			data: {
				anonymizedFileId: 99,
				anonymizedFileName: 'doc-anon.pdf',
				anonymizedFilePath: '/files/doc-anon.pdf',
			},
		})
	})

	it('appends both flags when summary + format are supplied (grondslagen on)', async () => {
		const store = useAnonymizationStore()
		const entry = makeEntry({ bases: null })

		await store.anonymiseEntry(entry, { appendBasisSummary: true, outputFormat: 'pdf' })

		expect(axios.post).toHaveBeenCalledWith(
			'/apps/docudesk/api/anonymization/anonymize/42',
			{
				entities: [{ type: 'PERSON', value: 'Claudia Fischer', confidence: 0.9 }],
				appendBasisSummary: true,
				outputFormat: 'pdf',
			},
		)
	})

	it('omits both flags when no options are given (grondslagen off)', async () => {
		const store = useAnonymizationStore()
		const entry = makeEntry({ bases: null })

		await store.anonymiseEntry(entry)

		const payload = axios.post.mock.calls[0][1]
		expect(payload.appendBasisSummary).toBeUndefined()
		expect(payload.outputFormat).toBeUndefined()
	})

	it('omits both flags when only one of the pair is supplied', async () => {
		// The backend needs appendBasisSummary AND outputFormat together;
		// a lone flag is a silent no-op, so the store must not send it alone.
		const store = useAnonymizationStore()
		const entry = makeEntry({ bases: null })

		await store.anonymiseEntry(entry, { appendBasisSummary: true })

		const payload = axios.post.mock.calls[0][1]
		expect(payload.appendBasisSummary).toBeUndefined()
		expect(payload.outputFormat).toBeUndefined()
	})
})

describe('addManualEntity — add selected text as a new entity', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		jest.clearAllMocks()
		// Default: the manual-entities POST returns one matched relation.
		axios.post.mockResolvedValue({
			data: {
				entity: { value: 'Kuipers', type: 'PERSON', reused: false },
				relations: [{ id: 555, chunkId: 7, positionStart: 10, positionEnd: 17 }],
				matchCount: 1,
				matchesSkipped: 0,
			},
		})
		axios.patch.mockResolvedValue({ data: {} })
	})

	/**
	 * Entry with one pre-existing detected entity, so we can assert the new
	 * one is prepended (lands at index 0).
	 *
	 * @return {object}
	 */
	function entryWithOne() {
		return {
			id: 'file-1',
			name: 'doc.txt',
			status: 'extracted',
			fileId: 42,
			entities: [{ type: 'EMAIL', value: 'a@b.nl', relationIds: [101] }],
			entityCount: 1,
		}
	}

	it('POSTs the value/type and prepends the new entity to the list', async () => {
		const store = useAnonymizationStore()
		const entry = entryWithOne()

		await store.addManualEntity(entry, { value: '  Kuipers  ', type: 'PERSON' })

		expect(axios.post).toHaveBeenCalledWith(
			'/apps/openregister/api/files/42/manual-entities',
			{ value: 'Kuipers', type: 'PERSON', wholeWord: true, caseSensitive: true },
		)
		// Prepended: newest is index 0, the original detected entity follows.
		expect(entry.entities).toHaveLength(2)
		expect(entry.entities[0]).toMatchObject({
			value: 'Kuipers',
			type: 'PERSON',
			included: true,
			relationIds: [555],
		})
		expect(entry.entities[1].value).toBe('a@b.nl')
		expect(entry.entityCount).toBe(2)
	})

	it('PATCHes the chosen grondslagen onto the new relations', async () => {
		const store = useAnonymizationStore()
		const entry = entryWithOne()

		await store.addManualEntity(entry, {
			value: 'Kuipers',
			type: 'PERSON',
			bases: ['persoonsgegevens'],
		})

		expect(axios.patch).toHaveBeenCalledTimes(1)
		expect(axios.patch).toHaveBeenCalledWith(
			'/apps/openregister/api/entity-relations/555',
			{ bases: ['persoonsgegevens'], skipAnonymization: false },
		)
		// Persisted: bases === _decisionBases, so anonymiseEntry won't re-PATCH.
		expect(entry.entities[0].bases).toEqual(['persoonsgegevens'])
		expect(entry.entities[0]._decisionBases).toEqual(['persoonsgegevens'])
	})

	it('does not PATCH when no grondslagen are supplied', async () => {
		const store = useAnonymizationStore()
		await store.addManualEntity(entryWithOne(), { value: 'Kuipers', type: 'PERSON' })
		expect(axios.patch).not.toHaveBeenCalled()
	})

	it('rejects when value or type is missing', async () => {
		const store = useAnonymizationStore()
		await expect(store.addManualEntity(entryWithOne(), { value: '', type: 'PERSON' }))
			.rejects.toThrow()
		await expect(store.addManualEntity(entryWithOne(), { value: 'x', type: '' }))
			.rejects.toThrow()
		expect(axios.post).not.toHaveBeenCalled()
	})

	it('rejects when the entry has no fileId', async () => {
		const store = useAnonymizationStore()
		await expect(store.addManualEntity({ entities: [] }, { value: 'x', type: 'PERSON' }))
			.rejects.toThrow()
	})
})

describe('anonymiseAllExtracted — batch run over a dossier', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		jest.clearAllMocks()
		axios.post.mockResolvedValue({
			data: {
				anonymizedFileId: 99,
				anonymizedFileName: 'doc-anon.pdf',
				anonymizedFilePath: '/files/doc-anon.pdf',
				replacementCount: 1,
			},
		})
	})

	/**
	 * Seed the store queue with three `extracted` entries on distinct fileIds.
	 *
	 * @param {object} store The active anonymization store.
	 * @return {void}
	 */
	function seedThree(store) {
		store.files = [
			{ ...makeEntry(), id: 'file-1', fileId: 1 },
			{ ...makeEntry(), id: 'file-2', fileId: 2 },
			{ ...makeEntry(), id: 'file-3', fileId: 3 },
		]
	}

	it('anonymises every extracted file when no scope is given', async () => {
		const store = useAnonymizationStore()
		seedThree(store)

		await store.anonymiseAllExtracted()

		expect(axios.post).toHaveBeenCalledTimes(3)
		expect(store.files.every((f) => f.status === 'completed')).toBe(true)
		expect(store.batch).toEqual({ running: false, total: 3, done: 3, failed: 0 })
	})

	it('only anonymises files whose fileId is in the scope', async () => {
		const store = useAnonymizationStore()
		seedThree(store)

		await store.anonymiseAllExtracted({ fileIds: [1, 3] })

		expect(axios.post).toHaveBeenCalledTimes(2)
		expect(store.findByFileId(2).status).toBe('extracted')
		expect(store.batch.total).toBe(2)
		expect(store.batch.done).toBe(2)
	})

	it('skips entries that are not in the extracted state', async () => {
		const store = useAnonymizationStore()
		seedThree(store)
		store.files[1].status = 'completed'

		await store.anonymiseAllExtracted()

		expect(axios.post).toHaveBeenCalledTimes(2)
		expect(store.batch.total).toBe(2)
	})

	it('counts a failed file without aborting the rest', async () => {
		const store = useAnonymizationStore()
		seedThree(store)
		// Second file's anonymise POST rejects; the run must continue.
		axios.post
			.mockResolvedValueOnce({ data: { anonymizedFileId: 99, replacementCount: 1 } })
			.mockRejectedValueOnce(new Error('boom'))
			.mockResolvedValueOnce({ data: { anonymizedFileId: 99, replacementCount: 1 } })

		await store.anonymiseAllExtracted()

		expect(store.batch.done).toBe(2)
		expect(store.batch.failed).toBe(1)
		expect(store.batch.running).toBe(false)
	})

	it('extracts dossier files the user never opened before anonymising', async () => {
		const store = useAnonymizationStore()
		// Only file 1 is in the queue (the file the user opened); files 2 and 3
		// were uploaded but never opened, so they must be extracted on the fly
		// before the batch can anonymise them.
		store.files = [{ ...makeEntry(), id: 'file-1', fileId: 1 }]

		axios.post.mockImplementation((url) => {
			if (String(url).includes('/extract/')) {
				return Promise.resolve({
					data: {
						entities: [
							{ type: 'PERSON', value: 'X', confidence: 0.9, relationIds: [1] },
						],
					},
				})
			}
			return Promise.resolve({ data: { anonymizedFileId: 99, replacementCount: 1 } })
		})

		await store.anonymiseAllExtracted({
			fileIds: [1, 2, 3],
			files: [
				{ fileId: 2, fileName: 'b.pdf', path: '/DocuDesk/D/b.pdf' },
				{ fileId: 3, fileName: 'c.pdf', path: '/DocuDesk/D/c.pdf' },
			],
		})

		expect(store.findByFileId(2)).toBeDefined()
		expect(store.findByFileId(3)).toBeDefined()
		expect(store.batch.total).toBe(3)
		expect(store.batch.done).toBe(3)
		expect(store.files.every((f) => f.status === 'completed')).toBe(true)
	})

	it('does not forward the fileIds scope as an anonymise option', async () => {
		const store = useAnonymizationStore()
		store.files = [{ ...makeEntry(), id: 'file-1', fileId: 1 }]

		await store.anonymiseAllExtracted({ fileIds: [1], appendBasisSummary: true, outputFormat: 'pdf' })

		const body = axios.post.mock.calls[0][1]
		expect(body).not.toHaveProperty('fileIds')
		expect(body.appendBasisSummary).toBe(true)
		expect(body.outputFormat).toBe('pdf')
	})
})

describe('completedInFiles — downloadable results in a dossier', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	/**
	 * Seed a mixed queue: one completed-with-result, one completed-without-path,
	 * one still extracted, one completed but outside the dossier scope.
	 *
	 * @param {object} store The active anonymization store.
	 * @return {void}
	 */
	function seedMixed(store) {
		store.files = [
			{ ...makeEntry(), id: 'a', fileId: 1, status: 'completed', anonymizedFilePath: '/files/a-anon.pdf' },
			{ ...makeEntry(), id: 'b', fileId: 2, status: 'completed', anonymizedFilePath: null },
			{ ...makeEntry(), id: 'c', fileId: 3, status: 'extracted', anonymizedFilePath: null },
			{ ...makeEntry(), id: 'd', fileId: 9, status: 'completed', anonymizedFilePath: '/files/d-anon.pdf' },
		]
	}

	it('returns only completed entries with a result path that are in scope', () => {
		const store = useAnonymizationStore()
		seedMixed(store)

		const result = store.completedInFiles([1, 2, 3])

		expect(result.map((f) => f.fileId)).toEqual([1])
	})

	it('returns an empty array when no fileIds are given', () => {
		const store = useAnonymizationStore()
		seedMixed(store)

		expect(store.completedInFiles([])).toEqual([])
	})
})
