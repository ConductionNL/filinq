import { createPinia, setActivePinia } from 'pinia'
import { useFileViewerStore } from './fileViewer.js'

/**
 * A minimal file descriptor as passed by the upload modal / navigation.
 *
 * @return {object}
 */
function sampleFile() {
	return {
		fileId: 42,
		fileName: 'doc.pdf',
		mimeType: 'application/pdf',
		path: '/Filinq/doc.pdf',
	}
}

describe('fileViewer store — grondslagen state', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('defaults grondslagen to true', () => {
		const store = useFileViewerStore()
		expect(store.grondslagen).toBe(true)
	})

	it('open() keeps grondslagen true when no option is passed', () => {
		const store = useFileViewerStore()
		store.open(sampleFile())
		expect(store.grondslagen).toBe(true)
	})

	it('open() takes the grondslagen option when given', () => {
		const store = useFileViewerStore()
		store.open(sampleFile(), { grondslagen: false })
		expect(store.grondslagen).toBe(false)
	})

	it('setGrondslagen() coerces to a boolean and is live-switchable', () => {
		const store = useFileViewerStore()
		store.open(sampleFile(), { grondslagen: false })

		store.setGrondslagen(true)
		expect(store.grondslagen).toBe(true)

		// Truthy/falsy inputs are coerced, never stored raw.
		store.setGrondslagen(0)
		expect(store.grondslagen).toBe(false)
		store.setGrondslagen('yes')
		expect(store.grondslagen).toBe(true)
	})

	it('close() resets grondslagen back to the default', () => {
		const store = useFileViewerStore()
		store.open(sampleFile(), { grondslagen: false })
		store.close()
		expect(store.grondslagen).toBe(true)
	})
})

describe('fileViewer store — add mode & highlight state', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('defaults addMode to false and highlightEntities to empty', () => {
		const store = useFileViewerStore()
		expect(store.addMode).toBe(false)
		expect(store.highlightEntities).toEqual([])
	})

	it('setAddMode() coerces to a boolean', () => {
		const store = useFileViewerStore()
		store.setAddMode(1)
		expect(store.addMode).toBe(true)
		store.setAddMode('')
		expect(store.addMode).toBe(false)
	})

	it('leaving add mode clears the pending selection', () => {
		const store = useFileViewerStore()
		store.setAddMode(true)
		store.setSelection('Kuipers')
		expect(store.selection).toBe('Kuipers')

		store.setAddMode(false)
		expect(store.selection).toBe('')
	})

	it('setHighlightEntities() replaces the list and guards non-arrays', () => {
		const store = useFileViewerStore()
		const list = [{ value: 'Kuipers', type: 'PERSON' }]
		store.setHighlightEntities(list)
		expect(store.highlightEntities).toEqual(list)

		store.setHighlightEntities(null)
		expect(store.highlightEntities).toEqual([])
	})

	it('open() / close() reset add mode and highlights', () => {
		const store = useFileViewerStore()
		store.setAddMode(true)
		store.setHighlightEntities([{ value: 'x', type: 'OTHER' }])

		store.open(sampleFile())
		expect(store.addMode).toBe(false)
		expect(store.highlightEntities).toEqual([])

		store.setAddMode(true)
		store.setHighlightEntities([{ value: 'x', type: 'OTHER' }])
		store.close()
		expect(store.addMode).toBe(false)
		expect(store.highlightEntities).toEqual([])
	})

	it('switching to the anonymised variant resets add mode', () => {
		const store = useFileViewerStore()
		store.open(sampleFile())
		store.setAddMode(true)
		store.setHighlightEntities([{ value: 'x', type: 'OTHER' }])

		store.setAnonymizedVariant({ ...sampleFile(), fileId: 99 })
		expect(store.addMode).toBe(false)
		expect(store.highlightEntities).toEqual([])
	})

	it('setAnonymizedVariant() switches the view to the anonymised file by default', () => {
		const store = useFileViewerStore()
		store.open(sampleFile())
		const anon = { ...sampleFile(), fileId: 99, fileName: 'doc_anonymized.pdf' }

		store.setAnonymizedVariant(anon)
		expect(store.showAnonymized).toBe(true)
		// Strict identity against what the store HOLDS, not the raw literal:
		// Vue 3 wraps anything written into reactive state in a Proxy, so
		// `Object.is(store.anonymizedFile, anon)` is false even though the
		// store stored exactly that object. See the note in
		// tests/vitest/anonymizationGetters.spec.js. Swapping to `toEqual`
		// would stop this catching a copy-instead-of-reference bug.
		expect(store.currentFile).toBe(store.anonymizedFile)
		expect(store.currentFile).toEqual(anon)
		expect(store.canToggleVariant).toBe(true)
	})

	it('setAnonymizedVariant({ show: false }) links the pair but keeps the original on screen', () => {
		const store = useFileViewerStore()
		const original = sampleFile()
		store.open(original)
		const anon = { ...sampleFile(), fileId: 99, fileName: 'doc_anonymized.pdf' }

		store.setAnonymizedVariant(anon, { show: false })
		// Linked (toggle available) but still showing the file the user opened.
		expect(store.canToggleVariant).toBe(true)
		expect(store.showAnonymized).toBe(false)
		// The store's contract is that `currentFile` is always a REFERENCE to
		// one of `originalFile` / `anonymizedFile`. Asserting that reference
		// directly is the strong form and survives Vue 3's reactive proxies
		// (see the note on the previous test); the `toEqual` lines then pin
		// down which of the two files each accessor holds.
		expect(store.currentFile).toBe(store.originalFile)
		expect(store.currentFile).not.toBe(store.anonymizedFile)
		expect(store.currentFile).toEqual(original)
		expect(store.anonymizedFile).toEqual(anon)
	})
})
