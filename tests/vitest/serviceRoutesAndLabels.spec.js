/**
 * The service calls and labels no other spec reaches: the route each call
 * goes to, what a refusal turns into, and the words a code is shown as.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization of thin HTTP wrappers and label maps
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { fetchBatch, reasonLabel } from '../../src/services/bulkSend.js'
import { contractParties, listTemplates, suggestTerms } from '../../src/services/contracts.js'
import { failureText, loadSettings } from '../../src/services/emailIngestion.js'
import { entityTypeLabel } from '../../src/services/entityTypes.js'
import { addScope, createCase, holdTypeOptions, retryCase } from '../../src/services/legalHolds.js'
import {
	getPublication,
	listCategories,
	listPublications,
	publicationStep,
	startPublication,
} from '../../src/services/publications.js'
import { refusalMessage, sanitizationStatus } from '../../src/services/sanitization.js'
import {
	createRequest,
	fetchCertificate,
	listRequests,
	outcomeLabel,
	parseLines,
	statusLabel,
} from '../../src/services/subjectErasures.js'

const http = vi.hoisted(() => ({ calls: [], answer: () => Promise.resolve({ data: {} }) }))
vi.mock('@nextcloud/axios', () => {
	const verb = (name) => (url, body) => {
		http.calls.push([name, url, body])
		return http.answer()
	}
	return { default: { get: verb('get'), post: verb('post'), put: verb('put'), delete: verb('delete') } }
})
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars = {}) => text.replace(/{(\w+)}/g, (match, key) => (key in vars ? String(vars[key]) : match)),
	translatePlural: (app, one, many, count) => (count === 1 ? one : many),
}))

/**
 * A rejected request with the given status and body.
 *
 * @param {number} status The HTTP status.
 * @param {object} data The response body.
 * @return {Function} The answer.
 */
function refused(status, data = {}) {
	return () => Promise.reject({ response: { status, data } })
}

beforeEach(() => {
	http.calls.length = 0
	http.answer = () => Promise.resolve({ data: {} })
})

describe('publications', () => {
	it('lists, reads and starts publications on their routes', async () => {
		http.answer = () => Promise.resolve({ data: { id: 'p 1' } })

		expect(await listPublications()).toEqual({ id: 'p 1' })
		await getPublication('p 1')
		await startPublication(812200)

		expect(http.calls).toEqual([
			['get', '/apps/filinq/api/publications', undefined],
			['get', '/apps/filinq/api/publications/p%201', undefined],
			['post', '/apps/filinq/api/publications', { documentFileRef: '812200', subjectType: 'document' }],
		])
	})

	it('puts the metadata step and posts every other step', async () => {
		await publicationStep('p1', 'metadata', { title: 'Besluit' })
		await publicationStep('p1', 'publish')

		expect(http.calls).toEqual([
			['put', '/apps/filinq/api/publications/p1/metadata', { title: 'Besluit' }],
			['post', '/apps/filinq/api/publications/p1/publish', {}],
		])
	})

	it('answers the categories, or none when the body has no results', async () => {
		http.answer = () => Promise.resolve({ data: { results: [{ id: 'c1' }] } })
		expect(await listCategories()).toEqual([{ id: 'c1' }])

		http.answer = () => Promise.resolve({ data: {} })
		expect(await listCategories()).toEqual([])
	})
})

describe('subject erasures', () => {
	it('lists, creates and fetches the certificate on their routes', async () => {
		http.answer = () => Promise.resolve({ data: { uuid: 'e1' } })

		expect(await listRequests()).toEqual({ ok: true, data: { uuid: 'e1' } })
		await createRequest({ subject: 'Jan Jansen' })
		await fetchCertificate('e 1')

		expect(http.calls).toEqual([
			['get', '/apps/filinq/api/subject-erasures', undefined],
			['post', '/apps/filinq/api/subject-erasures', { subject: 'Jan Jansen' }],
			['get', '/apps/filinq/api/subject-erasures/e%201/certificate', undefined],
		])
	})

	it('falls back to its own refusal without a server message or status', async () => {
		http.answer = () => Promise.reject(new Error('Network Error'))

		expect(await listRequests()).toEqual({
			ok: false,
			status: 0,
			error: 'The erasure step failed. Nothing further was changed. Try again later.',
		})
	})

	it('splits pasted identifiers into trimmed lines and ignores empty input', () => {
		expect(parseLines(' Jan Jansen \n\n jan@voorbeeld.example\n')).toEqual(['Jan Jansen', 'jan@voorbeeld.example'])
		expect(parseLines(null)).toEqual([])
	})

	it('names each status and outcome, and shows an unknown one as it is', () => {
		expect(statusLabel('partially_completed')).toBe('Stopped part way, can be resumed')
		expect(statusLabel('received')).toBe('Received, no preview yet')
		expect(statusLabel('archived')).toBe('archived')
		expect(outcomeLabel('excluded')).toBe('Left in place')
		expect(outcomeLabel('failed')).toBe('Not erased')
		expect(outcomeLabel('mystery')).toBe('mystery')
	})
})

describe('legal holds', () => {
	it('creates, scopes and retries a case on its routes', async () => {
		await createCase({ name: 'Zaak 12' })
		await addScope('h 1', { fileIds: [1, 2] })
		await retryCase('h 1')

		expect(http.calls).toEqual([
			['post', '/apps/filinq/api/legal-holds', { name: 'Zaak 12' }],
			['post', '/apps/filinq/api/legal-holds/h%201/scope', { fileIds: [1, 2] }],
			['post', '/apps/filinq/api/legal-holds/h%201/retry', undefined],
		])
	})

	it('offers the four hold types', () => {
		expect(holdTypeOptions().map((o) => o.id)).toEqual(['litigation', 'audit', 'woo-appeal', 'other'])
	})
})

describe('sanitization', () => {
	it('names an unsupported type and any other failure', () => {
		expect(refusalMessage(415)).toBe('Only Word and OpenDocument text files can be sanitized.')
		expect(refusalMessage(500)).toBe('The document could not be sanitized. Try again later.')
		expect(refusalMessage(415, 'encrypted')).toBe('This document is encrypted. Remove the password and try again.')
	})

	it('turns a refused status read into the message for its status', async () => {
		http.answer = refused(404)
		expect(await sanitizationStatus(7)).toEqual({ ok: false, status: 404, error: 'This file is not there, or you cannot open it.' })

		http.answer = () => Promise.reject(new Error('Network Error'))
		expect(await sanitizationStatus(7)).toEqual({ ok: false, status: 0, error: 'The document could not be sanitized. Try again later.' })
	})
})

describe('entity types', () => {
	it('shows a type in capitals, and nothing for an empty one', () => {
		expect(entityTypeLabel(' person ')).toBe('PERSON')
		expect(entityTypeLabel('')).toBe('')
		expect(entityTypeLabel(null)).toBe('')
	})
})

describe('email ingestion', () => {
	it('explains each failure reason, and shows an unknown one as it is', () => {
		expect(failureText('unparseable')).toBe('This file could not be read as an email. Export it again as .eml.')
		expect(failureText('dossier-folder-unavailable')).toBe('The dossier of this inbox has no folder that can be reached. Check the inbox mapping.')
		expect(failureText('filing-failed')).toBe('The email could not be moved into the dossier folder.')
		expect(failureText('quota')).toBe('quota')
		expect(failureText('')).toBe('Unknown reason')
	})

	it('loads the settings from their route', async () => {
		http.answer = () => Promise.resolve({ data: { inboxes: [] } })

		const result = await loadSettings()

		expect(http.calls[0]).toEqual(['get', '/apps/filinq/api/email-ingestion/settings', undefined])
		expect(result.ok).toBe(true)
	})
})

describe('bulk send', () => {
	it('fetches one batch', async () => {
		http.answer = () => Promise.resolve({ data: { id: 'b1', rows: 3 } })

		expect(await fetchBatch('b1')).toEqual({ id: 'b1', rows: 3 })
		expect(http.calls[0]).toEqual(['get', '/apps/filinq/api/signing/batches/b1', undefined])
	})

	it('names each rejection, and shows an unknown reason as it is', () => {
		expect(reasonLabel({ reason: 'no-recipient' })).toBe('No e-mail address or user')
		expect(reasonLabel({ reason: 'unknown-user', detail: 'piet' })).toBe('No user piet on this Nextcloud')
		expect(reasonLabel({ reason: 'over-quota' })).toBe('over-quota')
	})
})

describe('contracts', () => {
	it('asks for suggestions and parties on the contract\'s routes', async () => {
		await suggestTerms('c 1')
		await contractParties('c 1')

		expect(http.calls).toEqual([
			['post', '/apps/filinq/api/contracts/c%201/suggestions', undefined],
			['get', '/apps/filinq/api/contracts/c%201/parties', undefined],
		])
	})

	it('lists templates from a bare list or a results envelope, and passes a refusal on', async () => {
		http.answer = () => Promise.resolve({ data: [{ id: 't1' }] })
		expect(await listTemplates()).toEqual({ ok: true, data: [{ id: 't1' }] })
		expect(http.calls[0][1]).toBe('/apps/openregister/api/objects/filinq/template')

		http.answer = () => Promise.resolve({ data: { results: [{ id: 't2' }] } })
		expect((await listTemplates()).data).toEqual([{ id: 't2' }])

		http.answer = () => Promise.resolve({ data: {} })
		expect((await listTemplates()).data).toEqual([])

		http.answer = refused(503)
		const refusal = await listTemplates()
		expect(refusal.ok).toBe(false)
		expect(refusal.status).toBe(503)
		expect(refusal.data).toBeUndefined()
	})
})
