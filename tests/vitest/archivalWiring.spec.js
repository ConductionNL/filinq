/**
 * The archival wiring: which ids the action writes, what the panel reports,
 * and that a write keeps OpenRegister's other archival settings.
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const http = vi.hoisted(() => ({ calls: [], answer: () => Promise.resolve({ data: {} }) }))

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url) => {
			http.calls.push(['get', url])
			return http.answer()
		},
		put: (url, body) => {
			http.calls.push(['put', url, body])
			return http.answer()
		},
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))

const { currentOwner, loadArchivalSettings, wireArchivalRegister, wiringFor, wiringState } = await import('../../src/services/archivalWiring.js')

const REGISTERS = [
	{ id: 3, slug: 'opencatalogi', title: 'OpenCatalogi', schemas: [] },
	{ id: 7, slug: 'filinq', title: 'Filinq', schemas: [{ id: 41, slug: 'selectielijstEntry' }, { id: 42, slug: 'destructionList' }, 43] },
]

const WIRED = { selectielijstRegister: 7, selectielijstSchema: 41, destructionListRegister: 7, destructionListSchema: 42, archivalRegister: 7 }

describe('wiringFor', () => {
	it('points every setting at filinq\'s register and its two schemas', () => {
		expect(wiringFor(REGISTERS)).toEqual(WIRED)
	})

	it('is null when filinq\'s register or one of the schemas is not there', () => {
		expect(wiringFor([])).toBeNull()
		expect(wiringFor(null)).toBeNull()
		expect(wiringFor([{ id: 7, slug: 'filinq', schemas: [{ id: 41, slug: 'selectielijstEntry' }] }])).toBeNull()
		expect(wiringFor([{ id: 'x', slug: 'filinq', schemas: REGISTERS[1].schemas }])).toBeNull()
	})
})

describe('wiringState', () => {
	it('is unset when OpenRegister has none of the five', () => {
		expect(wiringState({ destructionBatchSize: 50, selectielijstRegister: null }, WIRED)).toBe('unset')
		expect(wiringState(null, WIRED)).toBe('unset')
	})

	it('is wired when all five match, also when OpenRegister stores them as text', () => {
		expect(wiringState({ ...WIRED, selectielijstSchema: '41' }, WIRED)).toBe('wired')
	})

	it('is elsewhere when any setting points at something else, or filinq cannot be wired', () => {
		expect(wiringState({ ...WIRED, selectielijstRegister: 3 }, WIRED)).toBe('elsewhere')
		expect(wiringState({ archivalRegister: 7 }, WIRED)).toBe('elsewhere')
		expect(wiringState(WIRED, null)).toBe('elsewhere')
	})
})

describe('currentOwner', () => {
	it('names the register the selectielijst points at', () => {
		expect(currentOwner({ selectielijstRegister: 3 }, REGISTERS)).toBe('OpenCatalogi')
		expect(currentOwner({ selectielijstRegister: 9 }, REGISTERS)).toBe('9')
		expect(currentOwner({ selectielijstRegister: 7 }, [{ id: 7, slug: 'filinq' }])).toBe('filinq')
		expect(currentOwner({}, REGISTERS)).toBe('')
	})
})

describe('the requests', () => {
	beforeEach(() => {
		http.calls.length = 0
		http.answer = () => Promise.resolve({ data: { ok: true } })
	})

	it('reads OpenRegister\'s archival settings', async () => {
		expect(await loadArchivalSettings()).toEqual({ ok: true, data: { ok: true } })
		expect(http.calls).toEqual([['get', '/apps/openregister/api/settings/archival']])
	})

	it('writes the five values and sends the other settings back as they were', async () => {
		await wireArchivalRegister({ destructionBatchSize: 25, selectielijstRegister: 3 }, WIRED)

		expect(http.calls).toEqual([['put', '/apps/openregister/api/settings/archival', { destructionBatchSize: 25, ...WIRED }]])
	})

	it('answers the refusal when OpenRegister refuses', async () => {
		http.answer = () => Promise.reject(Object.assign(new Error('403'), { response: { status: 403, data: { error: 'admin only' } } }))

		expect(await wireArchivalRegister(null, WIRED)).toEqual({ ok: false, status: 403, error: 'admin only' })
	})
})
