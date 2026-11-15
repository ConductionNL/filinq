/**
 * The store barrel: the stores it builds on the app's Pinia, and
 * initializeStores, which fetches the settings, points the lib object store
 * at OpenRegister and registers the seven object types, once.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/filinq-store-migration/spec.md
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const lib = vi.hoisted(() => ({
	objectStore: null,
	createObjectStore: null,
	useObjectStore: null,
}))
const settings = vi.hoisted(() => ({ fetchSettings: null, useSettingsStore: null }))
const made = vi.hoisted(() => [])

vi.mock('@conduction/nextcloud-vue', () => ({ createObjectStore: (...args) => lib.createObjectStore(...args) }))
vi.mock('../../src/pinia.js', () => ({ default: { id: 'filinq-pinia' } }))

/**
 * @param {string} name the store
 * @return {object} a module whose use function records the Pinia it was given
 */
function storeModule(name) {
	const exportName = 'use' + name + 'Store'
	return { [exportName]: (pinia) => { made.push([name, pinia]); return { name } } }
}

vi.mock('../../src/store/modules/anonymization.js', () => storeModule('Anonymization'))
vi.mock('../../src/store/modules/consent.js', () => storeModule('Consent'))
vi.mock('../../src/store/modules/customDictionary.js', () => storeModule('CustomDictionary'))
vi.mock('../../src/store/modules/dossier.js', () => storeModule('Dossier'))
vi.mock('../../src/store/modules/fileViewer.js', () => storeModule('FileViewer'))
vi.mock('../../src/store/modules/folderAnonymization.js', () => storeModule('FolderAnonymization'))
vi.mock('../../src/store/modules/myDocuments.js', () => storeModule('MyDocuments'))
vi.mock('../../src/store/modules/navigation.ts', () => storeModule('Navigation'))
vi.mock('../../src/store/modules/prohibition.js', () => storeModule('Prohibition'))
vi.mock('../../src/store/modules/standingConsent.js', () => storeModule('StandingConsent'))
vi.mock('../../src/store/modules/settings.js', () => ({ useSettingsStore: (...args) => settings.useSettingsStore(...args) }))

beforeEach(() => {
	vi.resetModules()
	made.length = 0
	lib.objectStore = { configure: vi.fn(), registerObjectType: vi.fn() }
	lib.useObjectStore = vi.fn(() => lib.objectStore)
	lib.createObjectStore = vi.fn(() => lib.useObjectStore)
	settings.fetchSettings = vi.fn(async () => {})
	settings.useSettingsStore = vi.fn(() => ({ fetchSettings: settings.fetchSettings }))
})

/**
 * @return {Promise<object>} a fresh copy of the store barrel
 */
async function barrel() {
	return import('../../src/store/store.js')
}

describe('the store barrel', () => {
	it('creates the lib object store under the filinq id and builds every legacy store on the app Pinia', async () => {
		const store = await barrel()
		expect(lib.createObjectStore).toHaveBeenCalledWith('filinq-objects')
		expect(store.useObjectStore).toBe(lib.useObjectStore)
		expect(made).toEqual([
			['Navigation', { id: 'filinq-pinia' }],
			['Consent', { id: 'filinq-pinia' }],
			['Anonymization', { id: 'filinq-pinia' }],
			['FolderAnonymization', { id: 'filinq-pinia' }],
			['MyDocuments', { id: 'filinq-pinia' }],
			['FileViewer', { id: 'filinq-pinia' }],
			['Prohibition', { id: 'filinq-pinia' }],
			['StandingConsent', { id: 'filinq-pinia' }],
			['CustomDictionary', { id: 'filinq-pinia' }],
			['Dossier', { id: 'filinq-pinia' }],
		])
		expect([store.navigationStore, store.dossierStore, store.customDictionaryStore]).toEqual([{ name: 'Navigation' }, { name: 'Dossier' }, { name: 'CustomDictionary' }])
		expect(typeof store.useSettingsStore).toBe('function')
	})

	it('fetches the settings, then configures OpenRegister and registers the seven object types', async () => {
		const order = []
		settings.fetchSettings = vi.fn(async () => { order.push('fetchSettings') })
		lib.objectStore.configure = vi.fn(() => order.push('configure'))
		const { initializeStores } = await barrel()

		await expect(initializeStores()).resolves.toBe(lib.objectStore)

		expect(lib.useObjectStore).toHaveBeenCalledWith({ id: 'filinq-pinia' })
		expect(settings.useSettingsStore).toHaveBeenCalledWith({ id: 'filinq-pinia' })
		expect(order).toEqual(['fetchSettings', 'configure'])
		expect(lib.objectStore.configure).toHaveBeenCalledWith({ baseUrl: '/index.php/apps/openregister/api/objects' })
		expect(lib.objectStore.registerObjectType.mock.calls).toEqual([
			['consent', 'publicationConsent', 'consent'],
			['signing-request', 'signingRequest', 'signing'],
			['signer-record', 'signerRecord', 'signing'],
			['signing-audit-entry', 'signingAuditEntry', 'signing'],
			['template', 'template', 'templates'],
			['correspondence', 'correspondence', 'document'],
			['huisstijl', 'huisstijl', 'document'],
		])
	})

	it('does the setup once: a second call returns the store without fetching or registering again', async () => {
		const { initializeStores } = await barrel()
		await initializeStores()
		const again = await initializeStores()
		expect(again).toBe(lib.objectStore)
		expect(settings.fetchSettings).toHaveBeenCalledTimes(1)
		expect(lib.objectStore.configure).toHaveBeenCalledTimes(1)
		expect(lib.objectStore.registerObjectType).toHaveBeenCalledTimes(7)
	})

	it('stays uninitialised when the settings fetch fails, so the next call tries again', async () => {
		settings.fetchSettings = vi.fn().mockRejectedValueOnce(new Error('offline')).mockResolvedValueOnce()
		const { initializeStores } = await barrel()
		await expect(initializeStores()).rejects.toThrow('offline')
		expect(lib.objectStore.configure).not.toHaveBeenCalled()
		await initializeStores()
		expect(settings.fetchSettings).toHaveBeenCalledTimes(2)
		expect(lib.objectStore.registerObjectType).toHaveBeenCalledTimes(7)
	})
})
