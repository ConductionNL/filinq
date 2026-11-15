/**
 * The navigation (ui) store: which modal and dialog are open, and the
 * one-shot hand-over of transfer data between them.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useNavigationStore } from '../../src/store/modules/navigation.ts'

beforeEach(() => {
	setActivePinia(createPinia())
})

describe('navigation store', () => {
	it('starts with nothing open', () => {
		const store = useNavigationStore()

		expect(store.modal).toBeNull()
		expect(store.dialog).toBeNull()
		expect(store.transferData).toBeNull()
	})

	it('opens and closes a modal and a dialog independently', () => {
		const store = useNavigationStore()

		store.setModal('uploadDocument')
		store.setDialog('deleteDocument')
		expect(store.modal).toBe('uploadDocument')
		expect(store.dialog).toBe('deleteDocument')

		store.setModal(null)
		expect(store.modal).toBeNull()
		expect(store.dialog).toBe('deleteDocument')
	})

	it('hands transfer data over exactly once', () => {
		const store = useNavigationStore()

		store.setTransferData('file-42')

		expect(store.getTransferData()).toBe('file-42')
		expect(store.transferData).toBeNull()
		expect(store.getTransferData()).toBeNull()
	})
})
