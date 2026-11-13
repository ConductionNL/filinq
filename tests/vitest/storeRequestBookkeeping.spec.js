/**
 * The loading and error bookkeeping every store action shares: the error is
 * cleared first and set from a failure (the server's text first when asked),
 * a failure is logged and rethrown or answered with the fallback, and the
 * loading flag is up only while the request runs.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/filinq-store-migration/spec.md
 */

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { quietRequest, storeRequest } from '../../src/store/storeRequest.js'

let consoleError

beforeEach(() => {
	consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
})

afterEach(() => {
	consoleError.mockRestore()
})

/**
 * @param {string} message the error message
 * @param {string} [serverText] the server's error text
 * @return {Error} a failure as axios gives it
 */
function failure(message, serverText) {
	const err = new Error(message)
	if (serverText !== undefined) {
		err.response = { data: { error: serverText } }
	}
	return err
}

describe('quietRequest', () => {
	it('clears an old error and returns what the request gives', async () => {
		const store = { error: 'old' }
		const run = vi.fn(async () => {
			expect(store.error).toBeNull()
			return { id: 7 }
		})
		await expect(quietRequest(store, { run, failure: 'Could not load' })).resolves.toEqual({ id: 7 })
		expect(run).toHaveBeenCalledTimes(1)
		expect(store.error).toBeNull()
		expect(consoleError).not.toHaveBeenCalled()
	})

	it('logs a failure, keeps its message and answers with the fallback', async () => {
		const store = { error: null }
		const err = failure('Network Error', 'Dossier is locked')
		const result = await quietRequest(store, { run: () => Promise.reject(err), failure: 'Could not load dossier', fallback: [] })
		expect(result).toEqual([])
		expect(store.error).toBe('Network Error')
		expect(consoleError).toHaveBeenCalledWith('Could not load dossier', err)
	})

	it('answers undefined without a fallback', async () => {
		const store = {}
		await expect(quietRequest(store, { run: () => Promise.reject(failure('boom')), failure: 'x' })).resolves.toBeUndefined()
		expect(store.error).toBe('boom')
	})

	it('prefers the server text when asked, and falls back to the message without one', async () => {
		const withText = {}
		await quietRequest(withText, { run: () => Promise.reject(failure('Request failed with status code 409', 'Dossier is locked')), failure: 'x', serverError: true })
		expect(withText.error).toBe('Dossier is locked')
		const withoutText = {}
		await quietRequest(withoutText, { run: () => Promise.reject(failure('Request failed with status code 500')), failure: 'x', serverError: true })
		expect(withoutText.error).toBe('Request failed with status code 500')
		const emptyText = {}
		await quietRequest(emptyText, { run: () => Promise.reject(failure('Timeout', '')), failure: 'x', serverError: true })
		expect(emptyText.error).toBe('Timeout')
	})

	it('rethrows the failure when asked, after setting the error', async () => {
		const store = {}
		const err = failure('Forbidden', 'Not your dossier')
		await expect(quietRequest(store, { run: () => Promise.reject(err), failure: 'Could not save', rethrow: true, serverError: true, fallback: 'unused' })).rejects.toBe(err)
		expect(store.error).toBe('Not your dossier')
		expect(consoleError).toHaveBeenCalledWith('Could not save', err)
	})
})

describe('storeRequest', () => {
	it('raises the loading flag while the request runs and lowers it after', async () => {
		const store = { loading: false, error: 'old' }
		const result = await storeRequest(store, {
			run: async () => {
				expect([store.loading, store.error]).toEqual([true, null])
				return 'done'
			},
			failure: 'x',
		})
		expect(result).toBe('done')
		expect(store.loading).toBe(false)
	})

	it('uses the named flag instead of loading', async () => {
		const store = { loading: false, saving: false }
		await storeRequest(store, {
			flag: 'saving',
			run: async () => {
				expect([store.saving, store.loading]).toEqual([true, false])
			},
			failure: 'x',
		})
		expect(store.saving).toBe(false)
	})

	it('lowers the flag after a failure, with the fallback and the error set', async () => {
		const store = { loading: false }
		const result = await storeRequest(store, { run: () => Promise.reject(failure('boom')), failure: 'Could not list', fallback: null })
		expect([result, store.loading, store.error]).toEqual([null, false, 'boom'])
	})

	it('lowers the flag after a rethrown failure', async () => {
		const store = { busy: false }
		const err = failure('boom')
		await expect(storeRequest(store, { flag: 'busy', run: () => Promise.reject(err), failure: 'x', rethrow: true })).rejects.toBe(err)
		expect([store.busy, store.error]).toEqual([false, 'boom'])
	})
})
