/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAnonymizationStore } from './anonymization'
import { Anonymization } from '../../entities'

describe('Anonymization Store', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('sets anonymization item', () => {
		const store = useAnonymizationStore()
		const anonymization = { id: '1', documentTitle: 'Test Document' }
		store.setAnonymizationItem(anonymization)
		expect(store.anonymizationItem).toBeInstanceOf(Anonymization)
		expect(store.anonymizationItem.id).toBe('1')
		expect(store.anonymizationItem.documentTitle).toBe('Test Document')
	})

	it('sets anonymization list', () => {
		const store = useAnonymizationStore()
		const anonymizationList = [
			{ id: '1', documentTitle: 'Test Document 1' },
			{ id: '2', documentTitle: 'Test Document 2' },
		]
		store.setAnonymizationList(anonymizationList)
		expect(store.anonymizationList).toHaveLength(2)
		expect(store.anonymizationList[0]).toBeInstanceOf(Anonymization)
		expect(store.anonymizationList[0].id).toBe('1')
		expect(store.anonymizationList[1].id).toBe('2')
	})
})
