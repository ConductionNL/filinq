/**
 * The download-all-files leaf: the preflight it reads, the warning it derives,
 * and what it shows after the archive request — success, refusal, unreadable
 * or unreachable.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude unit test of the download-all-files leaf logic
 */

import { join } from 'node:path'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => '[t] ' + text,
}))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (url, params = {}) => '/index.php' + url.replace(/\{(\w+)\}/g, (_, k) => String(params[k])),
}))

const calls = []

/**
 * A leaf on one host object.
 *
 * @param {object} props Props to set on top.
 * @return {Promise<object>} The instance.
 */
async function leaf(props = {}) {
	const options = await loadSfcOptions(join(import.meta.dirname, '../../src/integrations/CnFilinqDownloadAllFilesWidget.vue'))
	const vm = createInstance(options, { props: { register: 'zaken', schema: 'zaak', objectId: 'z-1', ...props } })
	vm.requestToken = () => 'token-1'
	return vm
}

/**
 * Answer every fetch with one response.
 *
 * @param {number} status The HTTP status.
 * @param {*} body The JSON body, or an Error to fail the JSON read.
 * @return {void}
 */
function answerWith(status, body) {
	globalThis.fetch = vi.fn(async (url, init) => {
		calls.push({ url, init })
		return {
			ok: status >= 200 && status < 300,
			status,
			json: async () => {
				if (body instanceof Error) {
					throw body
				}
				return body
			},
		}
	})
}

afterEach(() => {
	calls.length = 0
	delete globalThis.fetch
})

describe('download-all-files leaf: preflight', () => {
	it('starts loading, without a warning or a result', async () => {
		const vm = await leaf()
		expect(vm.loading).toBe(true)
		expect(vm.busy).toBe(false)
		expect(vm.result).toBeNull()
		expect(vm.warn).toBe(false)
		expect(vm.leftOut).toEqual([])
	})

	it('reads the preflight for the host object when mounted', async () => {
		const preflight = { files: 4, bytes: 3145728, ceilingBytes: 1048576, exceedsCeiling: true }
		answerWith(200, preflight)
		const vm = await leaf()
		await vm.$hook('mounted')
		await vi.waitFor(() => expect(vm.loading).toBe(false))
		expect(calls).toHaveLength(1)
		expect(calls[0].url).toBe('/index.php/apps/filinq/api/case-archive/preflight?register=zaken&schema=zaak&id=z-1')
		expect(calls[0].init).toEqual({ headers: { requesttoken: 'token-1', Accept: 'application/json' } })
		expect(vm.preflight).toEqual(preflight)
		expect(vm.warn).toBe(true)
		expect(vm.error).toBe(false)
	})

	it('does not warn when the files fit under the ceiling', async () => {
		answerWith(200, { files: 1, bytes: 10, ceilingBytes: 100, exceedsCeiling: false })
		const vm = await leaf()
		await vm.load()
		expect(vm.warn).toBe(false)
	})

	it('marks an error and keeps the empty preflight on a refusal', async () => {
		answerWith(403, { error: 'no' })
		const vm = await leaf()
		await vm.load()
		expect(vm.error).toBe(true)
		expect(vm.loading).toBe(false)
		expect(vm.preflight.files).toBe(0)
	})

	it('asks nothing without a host object', async () => {
		answerWith(200, {})
		const vm = await leaf({ objectId: '' })
		await vm.load()
		expect(calls).toHaveLength(0)
		expect(vm.loading).toBe(false)
	})
})

describe('download-all-files leaf: download', () => {
	it('posts the manifest request and reads the archive back', async () => {
		answerWith(200, {
			included: [{ name: 'a.pdf' }, { name: 'b.pdf' }],
			excluded: [
				{ name: 'big.zip', reason: 'ceiling' },
				{ name: 'secret.pdf', reason: 'permission' },
				{ name: 'odd.txt', reason: 'quarantined' },
			],
			archive: { fileId: 42 },
		})
		const vm = await leaf()
		await vm.download()
		expect(calls[0].url).toBe('/index.php/apps/filinq/api/case-archive/manifest?register=zaken&schema=zaak&id=z-1')
		expect(calls[0].init.method).toBe('POST')
		expect(vm.busy).toBe(false)
		expect(vm.result).toEqual({
			failed: false,
			included: 2,
			excluded: { ceiling: ['big.zip'], permission: ['secret.pdf'], missing: [], other: ['odd.txt'] },
			link: '/index.php/f/42',
		})
	})

	it('groups only the non-empty reasons, each under its sentence', async () => {
		answerWith(200, {
			included: [],
			excluded: [{ name: 'gone.pdf', reason: 'missing' }, { name: 'x', reason: 'other' }],
			archive: { fileId: 7 },
		})
		const vm = await leaf()
		await vm.download()
		expect(vm.leftOut).toEqual([
			{ reason: 'missing', label: '[t] Left out because the file no longer exists', names: ['gone.pdf'] },
			{ reason: 'other', label: '[t] Left out for another reason', names: ['x'] },
		])
	})

	it('gives no link and counts nothing when no archive was written', async () => {
		answerWith(200, { included: 'not-a-list' })
		const vm = await leaf()
		await vm.download()
		expect(vm.result.failed).toBe(false)
		expect(vm.result.included).toBe(0)
		expect(vm.result.link).toBe('')
		expect(vm.leftOut).toEqual([])
	})

	it('shows the server reason on a refusal and lists nothing left out', async () => {
		answerWith(422, { error: 'Too large' })
		const vm = await leaf()
		await vm.download()
		expect(vm.result).toEqual({ failed: true, reason: 'Too large' })
		expect(vm.leftOut).toEqual([])
		expect(vm.busy).toBe(false)
	})

	it('falls back to a generic reason when the refusal is unreadable', async () => {
		answerWith(500, new Error('html'))
		const vm = await leaf()
		await vm.download()
		expect(vm.result).toEqual({ failed: true, reason: '[t] no reason was given' })
	})

	it('says the server could not be reached when the request throws', async () => {
		globalThis.fetch = vi.fn(async () => {
			throw new Error('offline')
		})
		const vm = await leaf()
		await vm.download()
		expect(vm.result).toEqual({ failed: true, reason: '[t] the server could not be reached' })
		expect(vm.busy).toBe(false)
	})

	it('formats sizes in megabytes', async () => {
		const vm = await leaf()
		expect(vm.size(1572864)).toBe('1.5')
		expect(vm.size(undefined)).toBe('0.0')
	})
})
