/**
 * SPDX-FileCopyrightText: 2026 Conduction / DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Unit tests for the docudesk settings Pinia store
 * (src/store/modules/settings.js): fetch envelope handling (config||data),
 * openRegisters / isAdmin flag derivation, the loading/error/initialized
 * lifecycle, and the save round-trip. global fetch + the OC.requestToken
 * global are mocked.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useSettingsStore } from '../../src/store/modules/settings.js'

beforeEach(() => {
	setActivePinia(createPinia())
	globalThis.OC = { requestToken: 'test-token' }
})

afterEach(() => {
	vi.restoreAllMocks()
	delete globalThis.fetch
	delete globalThis.OC
})

function mockFetchOnce({ ok = true, statusText = 'OK', json = {} }) {
	globalThis.fetch = vi
		.fn()
		.mockResolvedValueOnce({ ok, statusText, json: async () => json })
}

describe('docudesk settings store', () => {
	it('has sensible defaults', () => {
		const store = useSettingsStore()
		expect(store.config).toBeNull()
		expect(store.openRegisters).toBe(false)
		expect(store.isAdmin).toBe(false)
		expect(store.loading).toBe(false)
		expect(store.error).toBeNull()
		expect(store.initialized).toBe(false)
		// getters
		expect(store.isLoading).toBe(false)
		expect(store.isInitialized).toBe(false)
		expect(store.hasOpenRegisters).toBe(false)
		expect(store.getIsAdmin).toBe(false)
	})

	it('fetchSettings unwraps config and derives flags, sets initialized', async () => {
		mockFetchOnce({
			json: { config: { foo: 'bar' }, openRegisters: true, isAdmin: true },
		})
		const store = useSettingsStore()
		const result = await store.fetchSettings()
		expect(result).toEqual({ foo: 'bar' })
		expect(store.config).toEqual({ foo: 'bar' })
		expect(store.openRegisters).toBe(true)
		expect(store.isAdmin).toBe(true)
		expect(store.initialized).toBe(true)
		expect(store.loading).toBe(false)
		expect(store.error).toBeNull()
	})

	it('fetchSettings falls back to the whole payload when there is no config key', async () => {
		mockFetchOnce({ json: { foo: 'baz' } })
		const store = useSettingsStore()
		const result = await store.fetchSettings()
		expect(result).toEqual({ foo: 'baz' })
		// absent flags coerce to false
		expect(store.openRegisters).toBe(false)
		expect(store.isAdmin).toBe(false)
	})

	it('fetchSettings records the error message on a non-ok response', async () => {
		mockFetchOnce({ ok: false, statusText: 'Forbidden' })
		vi.spyOn(console, 'error').mockImplementation(() => {})
		const store = useSettingsStore()
		const result = await store.fetchSettings()
		expect(result).toBeNull()
		expect(store.error).toContain('Forbidden')
		expect(store.loading).toBe(false)
		expect(store.initialized).toBe(false)
	})

	it('saveSettings POSTs the body and stores the returned config', async () => {
		globalThis.fetch = vi.fn().mockResolvedValueOnce({
			ok: true,
			json: async () => ({ config: { saved: true } }),
		})
		const store = useSettingsStore()
		const result = await store.saveSettings({ a: 1 })
		const [url, opts] = globalThis.fetch.mock.calls[0]
		expect(url).toBe('/index.php/apps/docudesk/api/settings')
		expect(opts.method).toBe('POST')
		expect(opts.headers.requesttoken).toBe('test-token')
		expect(JSON.parse(opts.body)).toEqual({ a: 1 })
		expect(result).toEqual({ saved: true })
		expect(store.config).toEqual({ saved: true })
	})

	it('saveSettings records the error and returns null on failure', async () => {
		globalThis.fetch = vi
			.fn()
			.mockResolvedValueOnce({ ok: false, statusText: 'Boom' })
		vi.spyOn(console, 'error').mockImplementation(() => {})
		const store = useSettingsStore()
		const result = await store.saveSettings({ a: 1 })
		expect(result).toBeNull()
		expect(store.error).toContain('Boom')
		expect(store.loading).toBe(false)
	})
})
