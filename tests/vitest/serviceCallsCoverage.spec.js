/**
 * The service calls the existing specs leave out: the wizard's load, delete
 * and lookup calls with their refusals, a blob error body that is not JSON,
 * the question form's optional fields, the same-origin fetch helpers, the
 * contract date, the validator status, the format labels and the envelope read.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

const calls = []
let reply = () => Promise.resolve({ data: {} })
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url, config) => {
			calls.push(['get', url, config])
			return reply()
		},
		post: (url, body, config) => {
			calls.push(['post', url, body, config])
			return reply()
		},
		delete: (url) => {
			calls.push(['delete', url])
			return reply()
		},
	},
}))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (path) => path,
	generateRemoteUrl: (service) => `http://localhost/remote.php/${service}`,
}))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => `t:${text}` }))

const wizard = await import('../../src/services/wizard.js')
const files = await import('../../src/services/fileViewerService.js')
const { todayIso } = await import('../../src/services/contractPipeline.js')
const { fetchValidatorStatus } = await import('../../src/services/conformance.js')
const { formatLabel } = await import('../../src/services/formatMatrix.js')
const { fetchEnvelope } = await import('../../src/services/signingEnvelope.js')

const refuse = (status, data) => () => Promise.reject({ response: { status, data } })

beforeEach(() => {
	calls.length = 0
	reply = () => Promise.resolve({ data: {} })
})

afterEach(() => {
	vi.useRealTimers()
})

describe('wizard calls', () => {
	it('loads a template wizard by its encoded id', async () => {
		reply = () => Promise.resolve({ data: { wizard: { uuid: 'w1' } } })

		const result = await wizard.loadTemplateWizard('tpl/1')

		expect(calls).toEqual([['get', '/apps/filinq/api/templates/tpl%2F1/wizard', undefined]])
		expect(result).toEqual({ ok: true, data: { wizard: { uuid: 'w1' } } })
	})

	it('deletes a wizard by its encoded id', async () => {
		const result = await wizard.deleteWizard('w 1')

		expect(calls).toEqual([['delete', '/apps/filinq/api/wizards/w%201']])
		expect(result.ok).toBe(true)
	})

	it('looks up wizards for a register and schema', async () => {
		reply = () => Promise.resolve({ data: { results: [{ uuid: 'w1' }] } })

		const result = await wizard.listWizardsFor('filinq', 'dossier')

		expect(calls).toEqual([
			['get', '/apps/filinq/api/wizards', { params: { register: 'filinq', schema: 'dossier' } }],
		])
		expect(result.data.results).toEqual([{ uuid: 'w1' }])
	})

	it('answers a refusal with its status, error and field errors', async () => {
		reply = refuse(422, { error: 'Invalid wizard', errors: { key: 'taken' } })

		expect(await wizard.deleteWizard('w1')).toEqual({
			ok: false,
			status: 422,
			error: 'Invalid wizard',
			errors: { key: 'taken' },
		})
	})

	it('answers a refusal without a response as status 0 and no field errors', async () => {
		reply = () => Promise.reject(new Error('offline'))

		expect(await wizard.loadTemplateWizard('t')).toEqual({ ok: false, status: 0, error: '', errors: {} })
	})

	it('reads a generate refusal whose blob body is not JSON as empty', async () => {
		reply = refuse(500, { text: () => Promise.resolve('<html>oops</html>') })

		const result = await wizard.generateWithWizard('tpl-1', { questions: [] }, {})

		expect(result).toEqual({ ok: false, status: 500, error: '', errors: {} })
	})
})

describe('formToQuestion optional fields', () => {
	const base = {
		key: ' k ',
		label: ' L ',
		helpText: '',
		type: 'text',
		required: false,
		choicesText: '',
		register: '',
		schema: '',
		mapsTo: '',
		conditionKey: null,
		conditionOperator: 'equals',
		conditionValue: '',
	}

	it('keeps trimmed help text', () => {
		expect(wizard.formToQuestion({ ...base, helpText: '  Hint ' })).toEqual({
			key: 'k',
			label: 'L',
			type: 'text',
			required: false,
			helpText: 'Hint',
		})
	})

	it('keeps register and schema for a register object, not mapsTo', () => {
		expect(
			wizard.formToQuestion({ ...base, type: 'registerObject', register: ' filinq ', schema: ' dossier ', mapsTo: 'a.b' }),
		).toEqual({ key: 'k', label: 'L', type: 'registerObject', required: false, register: 'filinq', schema: 'dossier' })
	})

	it('keeps the value of a condition that compares, not of one that asks for an answer', () => {
		expect(
			wizard.formToQuestion({ ...base, conditionKey: 'q1', conditionOperator: 'notEquals', conditionValue: 'nee' }).condition,
		).toEqual({ questionKey: 'q1', operator: 'notEquals', value: 'nee' })
		expect(
			wizard.formToQuestion({ ...base, conditionKey: 'q1', conditionOperator: 'answered', conditionValue: 'x' }).condition,
		).toEqual({ questionKey: 'q1', operator: 'answered' })
	})
})

describe('same-origin fetch helpers', () => {
	it('fetches a URL as an array buffer', async () => {
		reply = () => Promise.resolve({ data: 'buffer' })

		expect(await files.fetchUrlAsArrayBuffer('/apps/filinq/eml/1')).toBe('buffer')
		expect(calls).toEqual([['get', '/apps/filinq/eml/1', { responseType: 'arraybuffer' }]])
	})

	it('fetches a URL as text', async () => {
		reply = () => Promise.resolve({ data: 'hello' })

		expect(await files.fetchUrlAsText('/apps/filinq/x.txt')).toBe('hello')
		expect(calls).toEqual([['get', '/apps/filinq/x.txt', { responseType: 'text' }]])
	})

	it('treats an empty path as the user root', () => {
		expect(files.buildWebdavUrl('')).toBe('http://localhost/remote.php/dav/files/admin/')
	})
})

describe('todayIso', () => {
	it('is the local date, zero-padded', () => {
		vi.useFakeTimers()
		vi.setSystemTime(new Date(2026, 0, 5, 12, 0, 0))

		expect(todayIso()).toBe('2026-01-05')
	})
})

describe('fetchValidatorStatus', () => {
	it('answers booleans and the version', async () => {
		reply = () => Promise.resolve({ data: { enabled: 1, available: 0, version: '1.24' } })

		expect(await fetchValidatorStatus()).toEqual({ enabled: true, available: false, version: '1.24' })
		expect(calls[0][1]).toBe('/apps/filinq/api/validation/conformance-status')
	})

	it('answers an empty version when the server sends none', async () => {
		reply = () => Promise.resolve({ data: {} })

		expect(await fetchValidatorStatus()).toEqual({ enabled: false, available: false, version: '' })
	})
})

describe('formatLabel', () => {
	it('labels each known format and passes an unknown one through', () => {
		expect(formatLabel('pdf')).toBe('t:PDF')
		expect(formatLabel('odf')).toBe('t:ODT (editable)')
		expect(formatLabel('html')).toBe('t:HTML')
		expect(formatLabel('email')).toBe('t:Email body')
		expect(formatLabel('rtf')).toBe('rtf')
	})
})

describe('fetchEnvelope', () => {
	it('reads the envelope by id', async () => {
		reply = () => Promise.resolve({ data: { id: 'env-1', members: [] } })

		expect(await fetchEnvelope('env-1')).toEqual({ id: 'env-1', members: [] })
		expect(calls).toEqual([['get', '/apps/filinq/api/signing/envelopes/env-1', undefined]])
	})
})
