/* eslint-disable no-console */
import { setActivePinia, createPinia } from 'pinia'

import { useNavigationStore } from './navigation'

describe('Navigation Store', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('set current selected view correctly', () => {
		const store = useNavigationStore()

		store.setSelected('dashboard')
		expect(store.selected).toBe('dashboard')

		store.setSelected('consent')
		expect(store.selected).toBe('consent')

		store.setSelected('anonymization')
		expect(store.selected).toBe('anonymization')
	})

	it('set modal correctly', () => {
		const store = useNavigationStore()

		store.setModal('editPublication')
		expect(store.modal).toBe('editPublication')

		store.setModal('editCatalogi')
		expect(store.modal).toBe('editCatalogi')
	})

	it('set dialog correctly', () => {
		const store = useNavigationStore()

		store.setDialog('deletePublication')
		expect(store.dialog).toBe('deletePublication')

		store.setDialog('deleteCatalogi')
		expect(store.dialog).toBe('deleteCatalogi')
	})
})
