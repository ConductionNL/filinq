/**
 * The thin HTTP clients for intake, finality, versions and Woo bases: each
 * hits the right URL with the right verb and body, and normalises an answer
 * whose shape it cannot trust.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const http = vi.hoisted(() => ({ calls: [], answer: () => Promise.resolve({ data: {} }) }))

vi.mock('@nextcloud/axios', () => {
	const record = (method) => (url, body) => {
		http.calls.push([method, url, body])
		return http.answer()
	}
	return { default: { get: record('get'), post: record('post'), delete: record('delete') } }
})
vi.mock('@nextcloud/router', () => ({
	generateUrl: (path, vars = {}) => path.replace(/\{(\w+)\}/g, (whole, key) => (key in vars ? String(vars[key]) : whole)),
}))

const intake = await import('../../src/services/intakeService.js')
const finality = await import('../../src/services/finalDocumentService.js')
const versions = await import('../../src/services/versionService.js')
const { fetchBaseOptions } = await import('../../src/services/bases.js')
const { WOO_BASES } = await import('../../src/constants/grondslagen.js')

/**
 * Answer every request with this data.
 *
 * @param {*} data The response data.
 * @return {void}
 */
function respond(data) {
	http.answer = () => Promise.resolve({ data })
}

beforeEach(() => {
	http.calls.length = 0
	respond({})
})

describe('intakeService', () => {
	it('reads the waiting inbox with its notification reach', async () => {
		respond({ results: [{ uuid: 'a' }], notificationReach: { reached: 2 } })

		expect(await intake.fetchWaitingInbox()).toEqual({ results: [{ uuid: 'a' }], notificationReach: { reached: 2 } })
		expect(http.calls).toEqual([['get', '/apps/filinq/api/intake/documents', undefined]])
	})

	it('treats a malformed inbox answer as empty, without a reach', async () => {
		respond({ results: 'nope' })
		expect(await intake.fetchWaitingInbox()).toEqual({ results: [], notificationReach: null })

		respond(null)
		expect(await intake.fetchWaitingInbox()).toEqual({ results: [], notificationReach: null })
	})

	it('lists waiting and detached documents, empty when the answer is not a list', async () => {
		respond({ results: [{ uuid: 'w' }] })
		expect(await intake.listWaitingDocuments()).toEqual([{ uuid: 'w' }])
		expect(await intake.listDetachedDocuments()).toEqual([{ uuid: 'w' }])

		respond({})
		expect(await intake.listWaitingDocuments()).toEqual([])
		expect(await intake.listDetachedDocuments()).toEqual([])

		expect(http.calls.map(([method, url]) => method + ' ' + url)).toEqual([
			'get /apps/filinq/api/intake/documents',
			'get /apps/filinq/api/intake/detached',
			'get /apps/filinq/api/intake/documents',
			'get /apps/filinq/api/intake/detached',
		])
	})

	it('assigns a document to a target, sending empty strings for missing parts', async () => {
		respond({ ok: true })

		expect(await intake.assignIntakeDocument('doc-1', { register: 'r', schema: 's', id: 'o-1' })).toEqual({ ok: true })
		await intake.assignIntakeDocument('doc-2', { register: 'r' })
		await intake.assignIntakeDocument('doc-3', null)

		expect(http.calls).toEqual([
			['post', '/apps/filinq/api/intake/documents/doc-1/assign', { register: 'r', schema: 's', id: 'o-1' }],
			['post', '/apps/filinq/api/intake/documents/doc-2/assign', { register: 'r', schema: '', id: '' }],
			['post', '/apps/filinq/api/intake/documents/doc-3/assign', { register: '', schema: '', id: '' }],
		])
	})

	it('rejects, detaches and decides with the given bodies', async () => {
		respond({ done: 1 })

		expect(await intake.rejectIntakeDocument('doc-1', 'Spam')).toEqual({ done: 1 })
		await intake.detachDocument(42, 'Wrong case')
		await intake.detachDocument(43, 'Duplicate', 'scan.pdf')
		await intake.decidePartySuggestion({ uuid: 'doc-1', accept: true })

		expect(http.calls).toEqual([
			['post', '/apps/filinq/api/intake/documents/doc-1/reject', { reason: 'Spam' }],
			['post', '/apps/filinq/api/intake/documents/detach', { fileId: 42, reason: 'Wrong case', documentName: '' }],
			['post', '/apps/filinq/api/intake/documents/detach', { fileId: 43, reason: 'Duplicate', documentName: 'scan.pdf' }],
			['post', '/apps/filinq/api/intake/party-decisions', { uuid: 'doc-1', accept: true }],
		])
	})

	it('lets a failed request reach the caller', async () => {
		http.answer = () => Promise.reject(new Error('403'))

		await expect(intake.rejectIntakeDocument('doc-1', 'x')).rejects.toThrow('403')
	})
})

describe('finalDocumentService', () => {
	it('reads, finalises, corrects and unfreezes on the final endpoint of the file', async () => {
		respond({ final: true })

		expect(await finality.readFinalState(7)).toEqual({ final: true })
		await finality.finaliseDocument(7, 'Signed')
		await finality.correctDocument(7, 'Typo in name')
		await finality.unfreezeDocument(7, 'Wrong version')

		expect(http.calls).toEqual([
			['get', '/apps/filinq/api/documents/7/final', undefined],
			['post', '/apps/filinq/api/documents/7/final', { reason: 'Signed' }],
			['post', '/apps/filinq/api/documents/7/final/correction', { reason: 'Typo in name' }],
			['delete', '/apps/filinq/api/documents/7/final', { data: { reason: 'Wrong version' } }],
		])
	})

	it('declares a finality rule as given', async () => {
		respond({ id: 'rule-1' })
		const rule = { trigger: 'signed', folder: '/Contracts' }

		expect(await finality.declareFinalityRule(rule)).toEqual({ id: 'rule-1' })
		expect(http.calls).toEqual([['post', '/apps/filinq/api/document-finality-rules', rule]])
	})
})

describe('versionService', () => {
	it('lists versions with a default page of 50 from the start', async () => {
		respond({ versions: [] })

		expect(await versions.listVersions(9)).toEqual({ versions: [] })
		await versions.listVersions(9, { limit: 10, offset: 20 })

		expect(http.calls).toEqual([
			['get', '/apps/filinq/api/documents/9/versions', { params: { limit: 50, offset: 0 } }],
			['get', '/apps/filinq/api/documents/9/versions', { params: { limit: 10, offset: 20 } }],
		])
	})

	it('builds the download URL and restores a version', async () => {
		respond({ restored: true })

		expect(versions.versionDownloadUrl(9, 1700000000)).toBe('/apps/filinq/api/documents/9/versions/1700000000/download')
		expect(await versions.restoreVersion(9, 1700000000)).toEqual({ restored: true })
		expect(http.calls).toEqual([['post', '/apps/filinq/api/documents/9/versions/1700000000/restore', {}]])
	})
})

describe('fetchBaseOptions', () => {
	const fallback = WOO_BASES.map((slug) => ({ label: slug, value: slug }))

	it('maps register objects to options, preferring the slug and the name', async () => {
		respond({
			results: [
				{ '@self': { slug: 'art-5-1-1-a' }, name: 'Unity of the Crown' },
				{ slug: 'art-5-1-2-e', id: 'x' },
				{ id: 'only-id', name: '' },
				{ name: 'No identity' },
				null,
			],
		})

		expect(await fetchBaseOptions()).toEqual([
			{ label: 'Unity of the Crown', value: 'art-5-1-1-a' },
			{ label: 'art-5-1-2-e', value: 'art-5-1-2-e' },
			{ label: 'only-id', value: 'only-id' },
		])
		expect(http.calls).toEqual([['get', '/apps/openregister/api/objects/filinq/base', undefined]])
	})

	it('accepts a bare list answer', async () => {
		respond([{ slug: 'b-1', name: 'Base one' }])

		expect(await fetchBaseOptions()).toEqual([{ label: 'Base one', value: 'b-1' }])
	})

	it('falls back to the statutory list when the register is empty', async () => {
		respond({ results: [] })
		expect(await fetchBaseOptions()).toEqual(fallback)

		respond({})
		expect(await fetchBaseOptions()).toEqual(fallback)
	})

	it('falls back to the statutory list when the register cannot be read', async () => {
		http.answer = () => Promise.reject(new Error('404'))

		expect(await fetchBaseOptions()).toEqual(fallback)
		expect(fallback.length).toBeGreaterThan(0)
	})
})
