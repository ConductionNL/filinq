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
