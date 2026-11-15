/**
 * Golden: what fetchSettings does with every response shape, recorded on the
 * implementation with the explicit null checks.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/admin-settings/spec.md#requirement-settings-rest-api-req-set-06
 */

import { createPinia, setActivePinia } from 'pinia'
import { afterEach, describe, it, vi } from 'vitest'
import { useSettingsStore } from '../../src/store/modules/settings.js'
import { golden } from './golden/golden.js'

const FLAGS = {
	absent: undefined,
	null: null,
	true: true,
	false: false,
	zero: 0,
	one: 1,
	empty: '',
	text: 'yes',
	object: { on: true },
}
const CONFIGS = {
	absent: undefined,
	null: null,
	zero: 0,
	empty: '',
	object: { foo: 'bar' },
}

afterEach(() => {
	vi.restoreAllMocks()
	delete globalThis.fetch
	delete globalThis.OC
})

/**
 * Run fetchSettings once against a fetch double and record what it did.
 *
 * @param {Function} fetchDouble The fetch implementation for this run.
 * @return {Promise<object>}
 */
async function run(fetchDouble) {
	setActivePinia(createPinia())
	globalThis.OC = { requestToken: 'token-1' }
	globalThis.fetch = vi.fn(fetchDouble)
	const errors = vi.spyOn(console, 'error').mockImplementation(() => {})
	const store = useSettingsStore()
	store.$patch({
		config: 'previous',
		openRegisters: 'previous',
		isAdmin: 'previous',
		error: 'previous',
		initialized: 'previous',
	})
	const pending = store.fetchSettings()
	const during = { loading: store.loading, error: store.error }
	const result = await pending
	const consoleErrors = errors.mock.calls.map((call) => [call[0], String(call[1])])
	errors.mockRestore()
	return {
		result: result === undefined ? '(undefined)' : result,
		during,
		after: {
			config: store.config,
			openRegisters: store.openRegisters,
			isAdmin: store.isAdmin,
			loading: store.loading,
			error: store.error,
			initialized: store.initialized,
		},
		fetchCalls: globalThis.fetch.mock.calls,
		consoleErrors,
	}
}

/**
 * A fetch double that answers OK with the given body.
 *
 * @param {*} body The JSON body.
 * @return {Function}
 */
function answering(body) {
	return async () => ({ ok: true, statusText: 'OK', json: async () => body })
}

describe('fetchSettings golden', () => {
	it('gives the recorded result for every flag value', async () => {
		const rows = {}
		for (const [name, value] of Object.entries(FLAGS)) {
			rows['openRegisters ' + name] = await run(
				answering({ config: { a: 1 }, openRegisters: value, isAdmin: true }),
			)
			rows['isAdmin ' + name] = await run(
				answering({ config: { a: 1 }, openRegisters: true, isAdmin: value }),
			)
		}
		golden('fetchSettings-flags', rows)
	})

	it('gives the recorded result for every config envelope', async () => {
		const rows = {}
		for (const [name, value] of Object.entries(CONFIGS)) {
			rows['config ' + name] = await run(
				answering({ config: value, openRegisters: false, extra: 'x' }),
			)
		}
		golden('fetchSettings-config', rows)
	})

	it('gives the recorded result for every failure', async () => {
		golden('fetchSettings-failures', {
			notOk: await run(async () => ({
				ok: false,
				statusText: 'Forbidden',
				json: async () => ({ config: { a: 1 } }),
			})),
			rejected: await run(async () => {
				throw new TypeError('network down')
			}),
			badJson: await run(async () => ({
				ok: true,
				statusText: 'OK',
				json: async () => {
					throw new SyntaxError('bad json')
				},
			})),
			nullBody: await run(answering(null)),
			emptyBody: await run(answering({})),
		})
	})
})
