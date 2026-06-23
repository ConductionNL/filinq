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
			{ entities: [{ type: 'PERSON', value: 'Claudia Fischer', confidence: 0.9 }] },
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
			{ entities: [] },
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
