/**
 * The entity search page's service: the calls, the access check that hides the
 * menu entry, and the labels. And that the page never renders a file name the
 * server did not send (unreadable documents are a count only).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/entity-search/tasks.md#task-3.1
 */

import { readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	anonymisationLabel,
	entityDetail,
	mayUseEntitySearch,
	otherLabel,
	searchEntities,
} from '../../src/services/entitySearch.js'

const calls = []
let answer = () => Promise.resolve({ data: {} })
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url, config) => {
			calls.push(['get', url, config])
			return answer()
		},
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => text.replace(/\{(\w+)\}/g, (m, key) => (vars && key in vars ? vars[key] : m)),
}))

describe('entity search service', () => {
	beforeEach(() => {
		calls.length = 0
		answer = () => Promise.resolve({ data: {} })
	})

	it('sends only the filters that are set', async () => {
		answer = () => Promise.resolve({ data: { results: [], total: 0 } })
		await searchEntities({ query: 'de vries', type: '', category: null, limit: 25 })
		expect(calls[0]).toEqual(['get', '/apps/filinq/api/entity-search', { params: { query: 'de vries', limit: 25 } }])
	})

	it('reads the menu permission from the access route, and a refusal is no', async () => {
		answer = () => Promise.resolve({ data: { allowed: true } })
		expect(await mayUseEntitySearch()).toBe(true)
		expect(calls[0][1]).toBe('/apps/filinq/api/entity-search/access')

		answer = () => Promise.reject({ response: { status: 403, data: { error: 'no', reason: 'not_allowed' } } })
		expect(await mayUseEntitySearch()).toBe(false)

		answer = () => Promise.reject(new Error('network down'))
		expect(await mayUseEntitySearch()).toBe(false)
	})

	it('shows the server refusal and its status', async () => {
		answer = () => Promise.reject({ response: { status: 503, data: { error: 'The search could not be recorded in the processing log, so it was not run.' } } })
		const result = await entityDetail('abc/def')
		expect(calls[0][1]).toBe('/apps/filinq/api/entity-search/abc%2Fdef')
		expect(result).toEqual({ ok: false, status: 503, error: 'The search could not be recorded in the processing log, so it was not run.' })
	})

	it('labels the anonymisation state and other occurrences', () => {
		expect(anonymisationLabel('anonymised')).toBe('Anonymised copy exists')
		expect(anonymisationLabel('derivative')).toBe('This is an anonymised copy')
		expect(anonymisationLabel('none')).toBe('Not anonymised')
		expect(otherLabel('object', 2)).toBe('Register objects: 2')
		expect(otherLabel('email', 1)).toBe('Emails: 1')
	})

	it('the page shows unreadable documents as a count, never by name', () => {
		const page = readFileSync(new URL('../../src/views/entitySearch/EntitySearch.vue', import.meta.url), 'utf8')
		expect(page).toContain('detail.noAccess')
		expect(page).not.toMatch(/noAccess\s*\.\s*(name|path)/)
	})

	it('the menu entry is hidden unless the access route said yes', () => {
		const manifest = JSON.parse(readFileSync(new URL('../../src/manifest.json', import.meta.url), 'utf8'))
		const entry = manifest.menu.find((item) => item.id === 'EntitySearch')
		expect(entry.visibleIf).toEqual({ 'entitySearch.allowed': true })
		const app = readFileSync(new URL('../../src/App.vue', import.meta.url), 'utf8')
		expect(app).toContain('entitySearch: { allowed: false }')
	})
})
