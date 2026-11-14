/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Model filling's calls go to the routes FieldFillController answers, and
 * the intake worklist offers "Fill fields from document" only when the
 * feature is available and the document sits on a record (REQ-EMF-003,
 * REQ-EMF-004).
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */

import { readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	decideFields,
	fetchFieldFillStatus,
	idOf,
	listFieldProfiles,
	saveFieldProfile,
	setFieldFillEnabled,
	suggestFields,
} from '../../src/services/fieldFill.js'

const calls = []

vi.mock('@nextcloud/router', () => ({
	generateUrl: (url, params = {}) => url.replace(/\{(\w+)\}/g, (m, key) => params[key] ?? m),
}))
vi.mock('@nextcloud/axios', () => {
	const answer = (method) => (url, body) => {
		calls.push([method, url, body])
		return Promise.resolve({ data: url.endsWith('field-profiles') && method === 'get' ? { results: [{ id: 'p1' }] } : { ok: method } })
	}
	return { default: { get: answer('get'), post: answer('post'), put: answer('put') } }
})

beforeEach(() => {
	calls.length = 0
})

describe('the model filling calls', () => {
	it('reads and switches the status', async () => {
		await fetchFieldFillStatus()
		await setFieldFillEnabled(true)
		expect(calls).toEqual([
			['get', '/apps/filinq/api/extraction/fields/status', undefined],
			['put', '/apps/filinq/api/extraction/fields/status', { enabled: true }],
		])
	})

	it('asks for a suggestion and sends the decisions', async () => {
		await suggestFields({ fileId: 12, register: 'subsidie', schema: 'aanvraag', objectId: 'a-1', extra: 'dropped' })
		await decideFields('s-1', { applicant: { decision: 'accept' } })
		expect(calls).toEqual([
			['post', '/apps/filinq/api/extraction/fields', { fileId: 12, register: 'subsidie', schema: 'aanvraag', objectId: 'a-1' }],
			['post', '/apps/filinq/api/extraction/fields/s-1/decide', { decisions: { applicant: { decision: 'accept' } } }],
		])
	})

	it('lists, creates and updates the profiles', async () => {
		expect(await listFieldProfiles()).toEqual([{ id: 'p1' }])
		await saveFieldProfile({ schema: 'dossier' })
		await saveFieldProfile({ schema: 'dossier' }, 'p1')
		expect(calls.map((c) => [c[0], c[1]])).toEqual([
			['get', '/apps/filinq/api/extraction/field-profiles'],
			['post', '/apps/filinq/api/extraction/field-profiles'],
			['put', '/apps/filinq/api/extraction/field-profiles/p1'],
		])
	})

	it('knows a stored object by its uuid, id or @self id', () => {
		expect(idOf({ uuid: 'u', id: 'i' })).toBe('u')
		expect(idOf({ id: 'i' })).toBe('i')
		expect(idOf({ '@self': { id: 's' } })).toBe('s')
		expect(idOf(null)).toBe('')
	})

	it('call the routes the controller declares', () => {
		const routes = readFileSync('appinfo/routes.php', 'utf8')
		for (const route of ["'api/extraction/fields', 'verb' => 'POST'", "'api/extraction/fields/status', 'verb' => 'GET'", "'api/extraction/fields/status', 'verb' => 'PUT'", "'api/extraction/fields/{id}/decide', 'verb' => 'POST'", "'api/extraction/field-profiles', 'verb' => 'GET'"]) {
			expect(routes).toContain(route)
		}
	})
})

describe('the intake worklist', () => {
	const page = readFileSync('src/views/intake/IntakeIndex.vue', 'utf8')

	it('offers the action only when available and on a record', () => {
		expect(page).toMatch(/v-if="canFill\(row\)"/)
		expect(page).toMatch(/this\.fieldFillAvailable && this\.mode === 'waiting' && Boolean\(row\?\.file\)/)
		expect(page).toMatch(/target\?\.register && target\?\.schema && target\?\.id/)
		expect(page).toMatch(/t\('filinq', 'Fill fields from document'\)/)
	})

	it('offers the fields after an assign, quietly when the schema has no profile', () => {
		expect(page).toMatch(/await this\.fill\(document, target, true\)/)
		expect(page).toMatch(/offered && \[409, 503\]\.includes\(error\?\.response\?\.status\)/)
		expect(page).toMatch(/<FieldSuggestionDialog/)
	})
})
