/**
 * The dictionary pages offer changes only to a maintainer the server confirms.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import { createPinia, setActivePinia } from 'pinia'
import { useCustomDictionaryStore } from './customDictionary.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn() },
}))
jest.mock('@nextcloud/router', () => ({
	generateUrl: (path) => path,
}))

// @spec openspec/changes/dictionary-actions-for-maintainers/specs/custom-dictionary-recognition/spec.md
describe('custom dictionary capabilities', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		axios.get.mockReset()
	})

	it('is read-only until the server says otherwise', () => {
		expect(useCustomDictionaryStore().canMaintain).toBe(false)
	})

	it('offers changes to a maintainer', async () => {
		axios.get.mockResolvedValue({ data: { canMaintain: true } })
		const store = useCustomDictionaryStore()

		await store.fetchCapabilities()

		expect(axios.get).toHaveBeenCalledWith('/apps/filinq/api/custom-dictionaries/capabilities')
		expect(store.canMaintain).toBe(true)
	})

	it('stays read-only for anybody else, and when the call fails', async () => {
		const store = useCustomDictionaryStore()
		axios.get.mockResolvedValueOnce({ data: { canMaintain: false } })
		await store.fetchCapabilities()
		expect(store.canMaintain).toBe(false)

		store.canMaintain = true
		axios.get.mockRejectedValueOnce(new Error('offline'))
		await store.fetchCapabilities()
		expect(store.canMaintain).toBe(false)
	})
})
