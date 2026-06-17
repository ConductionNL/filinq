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
