import { createPinia, setActivePinia } from 'pinia'
import { useNavigationStore } from './navigation'

describe('Navigation Store', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
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
