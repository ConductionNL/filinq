import { createPinia, setActivePinia } from 'pinia'
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
		const anonymized = doc('report_anonymized.pdf', {
			fileId: 2,
			isAnonymized: true,
		})
		store.documents = [original, anonymized]
		store.anonymizationLinks = [link(1, 2)]
		// Strict identity against the entry the store HOLDS. Vue 3 wraps
		// anything written into reactive state in a Proxy, so `toBe(original)`
		// against the raw literal fails even though the store stored exactly
		// that object. `toEqual` would weaken this into a shape check and stop
		// it catching a copy-instead-of-reference regression, so the reference
		// is re-anchored on store.documents[0] instead.
		expect(store.conceptFor(anonymized)).toBe(store.documents[0])
		expect(store.documents[0]).toEqual(original)
		expect(store.conceptFor(original)).toBeUndefined()
	})

	it('anonymizedFor() resolves the anonymized copy of a concept file via the link', () => {
		const store = useMyDocumentsStore()
		const original = doc('report.pdf', { fileId: 1 })
		const anonymized = doc('report_anonymized.pdf', {
			fileId: 2,
			isAnonymized: true,
		})
		store.documents = [original, anonymized]
		store.anonymizationLinks = [link(1, 2)]
		expect(store.anonymizedFor(original)).toBe(store.documents[1])
		expect(store.documents[1]).toEqual(anonymized)
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

describe('myDocuments store — duplicate anonymized outputs (feat #107, dedupe)', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('hides both the concept and the superseded (older) output when a source was re-anonymized', () => {
		const store = useMyDocumentsStore()
		store.documents = [
			doc('report.pdf', { fileId: 1, modified: 10 }),
			doc('report_anonymized.pdf', {
				fileId: 2,
				isAnonymized: true,
				modified: 100,
			}),
			doc('report_anonymized (2).pdf', {
				fileId: 3,
				isAnonymized: true,
				modified: 200,
			}),
		]
		// Two runs of the same source left two outputs behind.
		store.anonymizationLinks = [link(1, 2), link(1, 3)]
		const names = store.visibleDocuments.map((d) => d.fileName)
		expect(names).toEqual(['report_anonymized (2).pdf'])
	})

	it('anonymizedFor() resolves the newest present output of a re-anonymized source', () => {
		const store = useMyDocumentsStore()
		const source = doc('report.pdf', { fileId: 1, modified: 10 })
		const oldOutput = doc('report_anonymized.pdf', {
			fileId: 2,
			isAnonymized: true,
			modified: 100,
		})
		const newOutput = doc('report_anonymized (2).pdf', {
			fileId: 3,
			isAnonymized: true,
			modified: 200,
		})
		store.documents = [source, oldOutput, newOutput]
		store.anonymizationLinks = [link(1, 2), link(1, 3)]
		// documents[2] is newOutput — the NEWER of the two outputs. Asserting
		// the reference (not just the shape) is what makes this test able to
		// fail if the getter ever returns the older output or a copy.
		expect(store.anonymizedFor(source)).toBe(store.documents[2])
		expect(store.documents[2]).toEqual(newOutput)
		expect(store.anonymizedFor(source)).not.toBe(store.documents[1])
	})

	it('ignores a degenerate self-referential link so a file is not masked as its own output', () => {
		const store = useMyDocumentsStore()
		const file = doc('report.pdf', { fileId: 5 })
		store.documents = [file]
		store.anonymizationLinks = [link(5, 5)]
		expect(store.visibleDocuments.map((d) => d.fileName)).toEqual(['report.pdf'])
		expect(store.anonymizedFor(file)).toBeUndefined()
	})
})

describe('myDocuments store — orphaned outputs after a re-upload', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('hides an anonymized output whose source is no longer present (moved to trash by a re-upload)', () => {
		const store = useMyDocumentsStore()
		// Re-upload scenario: fresh source (319/318) replaced the old sources
		// (305/304, now in trash), leaving their _anonymized outputs (316/313)
		// orphaned in the dossier.
		store.documents = [
			doc('example-anonymization.pdf', { fileId: 319, modified: 200 }),
			doc('example-anonymization-docx.docx', { fileId: 318, modified: 200 }),
			doc('example-anonymization_anonymized.pdf', {
				fileId: 316,
				isAnonymized: true,
				modified: 100,
			}),
			doc('example-anonymization-docx_anonymized.pdf', {
				fileId: 313,
				isAnonymized: true,
				modified: 100,
			}),
		]
		store.anonymizationLinks = [
			{
				sourceFileId: 305,
				anonymizedFileId: 316,
				sourceFileName: 'example-anonymization.pdf',
			},
			{
				sourceFileId: 304,
				anonymizedFileId: 313,
				sourceFileName: 'example-anonymization-docx.docx',
			},
		]
		// The fresh re-uploads are shown as concepts; the orphaned outputs are not.
		expect(store.visibleDocuments.map((d) => d.fileName).sort()).toEqual([
			'example-anonymization-docx.docx',
			'example-anonymization.pdf',
		])
		expect([...store.orphanedOutputIds].sort((a, b) => a - b)).toEqual([
			313, 316,
		])
	})

	it('keeps a standalone anonymized output (source deleted, NOT re-uploaded)', () => {
		const store = useMyDocumentsStore()
		// The source is absent and there is no fresh file under its name, so the
		// output is a legitimate standalone result — it must stay visible.
		store.documents = [
			doc('draft.pdf', { fileId: 1 }),
			doc('report_anonymized.pdf', { fileId: 2, isAnonymized: true }),
		]
		store.anonymizationLinks = [
			{ sourceFileId: 9, anonymizedFileId: 2, sourceFileName: 'report.pdf' },
		]
		expect(store.orphanedOutputIds.size).toBe(0)
		expect(store.visibleDocuments.map((d) => d.fileName)).toEqual([
			'draft.pdf',
			'report_anonymized.pdf',
		])
	})

	it('keeps a live source↔output pair (source present) untouched', () => {
		const store = useMyDocumentsStore()
		store.documents = [
			doc('report.pdf', { fileId: 302, modified: 10 }),
			doc('report_anonymized.pdf', {
				fileId: 306,
				isAnonymized: true,
				modified: 100,
			}),
		]
		store.anonymizationLinks = [link(302, 306)]
		// Source present → not an orphan; overview collapses to the output as usual.
		expect(store.orphanedOutputIds.size).toBe(0)
		expect(store.visibleDocuments.map((d) => d.fileName)).toEqual([
			'report_anonymized.pdf',
		])
	})

	it('keeps a standalone output when an unrelated file shares the source name but predates it', () => {
		const store = useMyDocumentsStore()
		// The source (id 9) was deleted, leaving a legitimate standalone output
		// (id 2, produced at modified 100). A completely unrelated file that
		// happens to share the source name exists but is OLDER (modified 50) —
		// it cannot be the re-upload that replaced the source, so the output
		// must stay visible.
		store.documents = [
			doc('report.pdf', { fileId: 1, modified: 50 }),
			doc('report_anonymized.pdf', {
				fileId: 2,
				isAnonymized: true,
				modified: 100,
			}),
		]
		store.anonymizationLinks = [
			{ sourceFileId: 9, anonymizedFileId: 2, sourceFileName: 'report.pdf' },
		]
		expect(store.orphanedOutputIds.size).toBe(0)
		expect(store.visibleDocuments.map((d) => d.fileName).sort()).toEqual([
			'report.pdf',
			'report_anonymized.pdf',
		])
	})

	it('does not treat an anonymized output as the re-uploaded source when names collide', () => {
		const store = useMyDocumentsStore()
		// A standalone output (id 2, source 9 deleted) whose recorded source name
		// coincides with ANOTHER anonymized output present in the listing. That
		// output is not a concept re-upload, so it must not trigger a hide.
		store.documents = [
			doc('report_anonymized.pdf', {
				fileId: 2,
				isAnonymized: true,
				modified: 100,
			}),
			doc('report.pdf', { fileId: 3, isAnonymized: true, modified: 200 }),
		]
		store.anonymizationLinks = [
			{ sourceFileId: 9, anonymizedFileId: 2, sourceFileName: 'report.pdf' },
		]
		expect(store.orphanedOutputIds.size).toBe(0)
	})
})
