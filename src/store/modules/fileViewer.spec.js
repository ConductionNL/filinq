import { setActivePinia, createPinia } from 'pinia'

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
		path: '/DocuDesk/doc.pdf',
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

describe('fileViewer store — edit mode & highlight state', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('defaults editMode to false and highlightEntities to empty', () => {
		const store = useFileViewerStore()
		expect(store.editMode).toBe(false)
		expect(store.highlightEntities).toEqual([])
	})

	it('setEditMode() coerces to a boolean', () => {
		const store = useFileViewerStore()
		store.setEditMode(1)
		expect(store.editMode).toBe(true)
		store.setEditMode('')
		expect(store.editMode).toBe(false)
	})

	it('leaving edit mode clears the pending selection', () => {
		const store = useFileViewerStore()
		store.setEditMode(true)
		store.setSelection('Kuipers')
		expect(store.selection).toBe('Kuipers')

		store.setEditMode(false)
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

	it('open() / close() reset edit mode and highlights', () => {
		const store = useFileViewerStore()
		store.setEditMode(true)
		store.setHighlightEntities([{ value: 'x', type: 'OTHER' }])

		store.open(sampleFile())
		expect(store.editMode).toBe(false)
		expect(store.highlightEntities).toEqual([])

		store.setEditMode(true)
		store.setHighlightEntities([{ value: 'x', type: 'OTHER' }])
		store.close()
		expect(store.editMode).toBe(false)
		expect(store.highlightEntities).toEqual([])
	})

	it('switching to the anonymised variant resets edit mode', () => {
		const store = useFileViewerStore()
		store.open(sampleFile())
		store.setEditMode(true)
		store.setHighlightEntities([{ value: 'x', type: 'OTHER' }])

		store.setAnonymizedVariant({ ...sampleFile(), fileId: 99 })
		expect(store.editMode).toBe(false)
		expect(store.highlightEntities).toEqual([])
	})
})
