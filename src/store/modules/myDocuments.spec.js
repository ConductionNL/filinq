import { setActivePinia, createPinia } from 'pinia'

import { useMyDocumentsStore } from './myDocuments.js'

// The store reaches for these Nextcloud helpers at module load / inside
// actions. Mock them so the getters can be exercised in isolation — these
// tests only set `documents` directly and never hit WebDAV.
jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { request: jest.fn() },
}))
jest.mock('@nextcloud/router', () => ({
	generateUrl: (path) => path,
	generateRemoteUrl: (path) => path,
}))
jest.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'tester' }),
}))

/**
 * Build a document entry as produced by fetchDocuments(). Pairing is driven
 * by anonymizationLinks, so `isAnonymized` defaults to false here.
 *
 * @param {string} fileName File name including extension.
 * @param {object} [extra] Overrides (fileId, isFolder, isAnonymized, ...).
 * @return {object} Document entry.
 */
function doc(fileName, extra = {}) {
	return {
		fileId: extra.fileId ?? Math.floor(fileName.length),
		fileName,
		mimeType: 'application/pdf',
		fileSize: 0,
		modified: 0,
		isFolder: extra.isFolder ?? false,
		isAnonymized: extra.isAnonymized ?? false,
		...extra,
	}
}

/**
 * Build an anonymizationLink record.
 *
 * @param {number} sourceFileId Source (concept) file id.
 * @param {number} anonymizedFileId Anonymized output file id.
 * @return {object} Link record.
 */
function link(sourceFileId, anonymizedFileId) {
	return { sourceFileId, anonymizedFileId }
}

describe('myDocuments store — concept/anonymized collapsing (via links)', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('hides the concept original when its linked anonymized copy is present', () => {
		const store = useMyDocumentsStore()
		store.documents = [
			doc('report.pdf', { fileId: 1 }),
			doc('report_anonymized.pdf', { fileId: 2, isAnonymized: true }),
		]
		store.anonymizationLinks = [link(1, 2)]
		const names = store.visibleDocuments.map((d) => d.fileName)
		expect(names).toEqual(['report_anonymized.pdf'])
	})

	it('keeps the concept when its anonymized output is not in the listing', () => {
		const store = useMyDocumentsStore()
		// The output (id 2) was deleted, so only the source remains — it must
		// not vanish just because a stale link exists.
		store.documents = [doc('report.pdf', { fileId: 1 })]
		store.anonymizationLinks = [link(1, 2)]
		const names = store.visibleDocuments.map((d) => d.fileName)
		expect(names).toEqual(['report.pdf'])
	})

	it('keeps a concept file that has no link', () => {
		const store = useMyDocumentsStore()
		store.documents = [
			doc('draft.pdf', { fileId: 1 }),
			doc('report_anonymized.pdf', { fileId: 2, isAnonymized: true }),
		]
		store.anonymizationLinks = [link(9, 2)]
		const names = store.visibleDocuments.map((d) => d.fileName)
		expect(names).toEqual(['draft.pdf', 'report_anonymized.pdf'])
	})

	it('always keeps folders (dossiers)', () => {
		const store = useMyDocumentsStore()
		store.documents = [
			doc('Dossier A', { fileId: 1, isFolder: true }),
			doc('report.pdf', { fileId: 2 }),
			doc('report_anonymized.pdf', { fileId: 3, isAnonymized: true }),
		]
		store.anonymizationLinks = [link(2, 3)]
		const names = store.visibleDocuments.map((d) => d.fileName)
		expect(names).toEqual(['Dossier A', 'report_anonymized.pdf'])
	})

	it('conceptFor() resolves the original of an anonymized file via the link', () => {
		const store = useMyDocumentsStore()
		const original = doc('report.pdf', { fileId: 1 })
		const anonymized = doc('report_anonymized.pdf', { fileId: 2, isAnonymized: true })
		store.documents = [original, anonymized]
		store.anonymizationLinks = [link(1, 2)]
		expect(store.conceptFor(anonymized)).toBe(original)
		expect(store.conceptFor(original)).toBeUndefined()
	})

	it('anonymizedFor() resolves the anonymized copy of a concept file via the link', () => {
		const store = useMyDocumentsStore()
		const original = doc('report.pdf', { fileId: 1 })
		const anonymized = doc('report_anonymized.pdf', { fileId: 2, isAnonymized: true })
		store.documents = [original, anonymized]
		store.anonymizationLinks = [link(1, 2)]
		expect(store.anonymizedFor(original)).toBe(anonymized)
		expect(store.anonymizedFor(anonymized)).toBeUndefined()
	})

	it('documentStats counts visible documents, not hidden originals', () => {
		const store = useMyDocumentsStore()
		store.documents = [
			doc('report.pdf', { fileId: 1 }),
			doc('report_anonymized.pdf', { fileId: 2, isAnonymized: true }),
			doc('standalone.pdf', { fileId: 3 }),
		]
		store.anonymizationLinks = [link(1, 2)]
		expect(store.documentStats.total).toBe(2)
	})
})