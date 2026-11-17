/**
 * The draft calls (generate-draft-and-attended-edit): opening a generation
 * for editing goes to the app's route, saving moves the expiry 30 days on and
 * writes back only the draft's own fields, a wizard run is saved in stage
 * answering with its answers and step, a draft continues in the wizard or the
 * editor by its stage, and finishing reads a download, an HTML answer or the
 * server's refusal.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	createDraft,
	discardDraft,
	draftId,
	expiresAt,
	finishDraft,
	hasPlainLanguage,
	isOpen,
	loadDraft,
	resumeRoute,
	saveDraft,
	saveWizardDraft,
	templateEditability,
} from '../../src/services/drafts.js'

const http = vi.hoisted(() => ({ calls: [], answer: null }))

vi.mock('@nextcloud/axios', () => {
	const call = (verb) => (url, body, config) => {
		http.calls.push([verb, url, body, config])
		return http.answer instanceof Error ? Promise.reject(http.answer) : Promise.resolve(http.answer)
	}
	return { default: { get: call('get'), post: call('post'), put: call('put'), delete: call('delete') } }
})

const NOW = new Date('2026-11-14T10:00:00.000Z')
const IN_30_DAYS = '2026-12-14T10:00:00.000Z'
const OBJECTS = '/index.php/apps/openregister/api/objects/filinq/documentDraft'

/**
 * A refusal as axios rejects it.
 *
 * @param {number} status The HTTP status.
 * @param {object|Blob} data The body.
 * @return {Error}
 */
function refused(status, data) {
	return Object.assign(new Error('refused'), { response: { status, data } })
}

beforeEach(() => {
	http.calls = []
	http.answer = { data: {} }
})

describe('the draft calls', () => {
	it('opens a generation for editing through the app route', async () => {
		http.answer = { data: { id: 'd1', stage: 'editing', html: '<p>Beste Jan</p>' } }

		const result = await createDraft({
			templateId: 't1',
			dataRefs: [{ register: 'brp', schema: 'persoon', id: 'p1' }],
			options: { format: 'pdf' },
			origin: 'correspondence',
		})

		expect(result).toEqual({ ok: true, data: { id: 'd1', stage: 'editing', html: '<p>Beste Jan</p>' } })
		expect(http.calls).toEqual([[
			'post',
			'/index.php/apps/filinq/api/documents/drafts',
			{ templateId: 't1', dataRefs: [{ register: 'brp', schema: 'persoon', id: 'p1' }], options: { format: 'pdf' }, origin: 'correspondence' },
			undefined,
		]])
	})

	it('gives the plain-language refusal back as it came', async () => {
		http.answer = refused(409, { error: 'This template has a plain-language version. Generate it directly.' })

		expect(await createDraft({ templateId: 't1' })).toEqual({
			ok: false,
			status: 409,
			error: 'This template has a plain-language version. Generate it directly.',
		})
	})

	it('saves only the draft fields and moves the expiry 30 days on', async () => {
		const draft = { id: 'd1', '@self': { id: 'd1', owner: 'anna' }, uuid: 'd1', stage: 'editing', html: '<p>Oud</p>', templateId: 't1', finishedDocumentId: null, expiresAt: '2026-11-20T00:00:00Z' }

		await saveDraft(draft, { html: '<p>Nieuw</p>' }, NOW)

		expect(http.calls).toEqual([[
			'put',
			OBJECTS + '/d1',
			{ stage: 'editing', html: '<p>Nieuw</p>', templateId: 't1', expiresAt: IN_30_DAYS },
			undefined,
		]])
	})

	it('reads and discards one draft by its object URL', async () => {
		await loadDraft('d 1')
		await discardDraft('d1')

		expect(http.calls.map(([verb, url]) => [verb, url])).toEqual([
			['get', OBJECTS + '/d%201'],
			['delete', OBJECTS + '/d1'],
		])
	})

	it('reads another clerk\'s draft as not found', async () => {
		http.answer = refused(404, { error: 'Not found' })

		expect(await loadDraft('d9')).toEqual({ ok: false, status: 404, error: 'Not found' })
	})

	it('saves a new wizard run in stage answering, and an existing one in place', async () => {
		const wizard = { uuid: 'w1' }
		await saveWizardDraft({ templateId: 't1', templateName: 'Brief', wizard, answers: { naam: 'Jan' }, step: 3, format: 'odf' }, NOW)
		await saveWizardDraft({ draft: { id: 'd2', stage: 'answering', templateId: 't1' }, templateId: 't1', wizard, answers: { naam: 'Jan', datum: '2026-11-01' }, step: -2 }, NOW)

		expect(http.calls[0]).toEqual([
			'post',
			OBJECTS,
			{ origin: 'wizard', stage: 'answering', templateId: 't1', templateName: 'Brief', wizardId: 'w1', wizardAnswers: { naam: 'Jan' }, wizardStep: 3, format: 'odf', expiresAt: IN_30_DAYS },
			undefined,
		])
		expect(http.calls[1]).toEqual([
			'put',
			OBJECTS + '/d2',
			{ stage: 'answering', templateId: 't1', wizardId: 'w1', wizardAnswers: { naam: 'Jan', datum: '2026-11-01' }, wizardStep: 0, format: 'pdf', expiresAt: IN_30_DAYS },
			undefined,
		])
	})

	it('continues a draft in the wizard or the editor by its stage', () => {
		expect(resumeRoute({ id: 'd2', stage: 'answering', templateId: 't1' })).toEqual({ name: 'WizardRunner', params: { id: 't1' }, query: { draft: 'd2' } })
		expect(resumeRoute({ '@self': { id: 'd3' }, stage: 'editing' })).toEqual({ name: 'DraftEditor', params: { id: 'd3' } })
		expect(draftId({ uuid: 'u1' })).toBe('u1')
		expect(draftId(null)).toBe('')
		expect([isOpen({ stage: 'answering' }), isOpen({ stage: 'editing' }), isOpen({ stage: 'finished' }), isOpen(null)]).toEqual([true, true, false, false])
	})

	it('expires 30 days after a save', () => {
		expect(expiresAt(NOW)).toBe(IN_30_DAYS)
	})
})

describe('finishing a draft', () => {
	it('reads the document as a download with its name', async () => {
		const pdf = new Blob(['%PDF'], { type: 'application/pdf' })
		http.answer = { data: pdf, headers: { 'content-type': 'application/pdf', 'content-disposition': 'attachment; filename="document.pdf"' } }

		const result = await finishDraft('d1')

		expect(result).toEqual({ ok: true, data: pdf, json: null, filename: 'document.pdf' })
		expect(http.calls).toEqual([['post', '/index.php/apps/filinq/api/documents/drafts/d1/finish', {}, { responseType: 'blob' }]])
	})

	it('reads an HTML or Files answer as JSON', async () => {
		http.answer = { data: new Blob(['{"draftId":"d1","recordId":"r1","content":"<p>x</p>"}']), headers: { 'content-type': 'application/json; charset=utf-8' } }

		expect(await finishDraft('d1')).toEqual({ ok: true, data: null, json: { draftId: 'd1', recordId: 'r1', content: '<p>x</p>' }, filename: '' })
	})

	it('reads the wizard refusal out of the blob body', async () => {
		http.answer = refused(422, new Blob(['{"error":"Answers missing","errors":{"datum":"Required"}}']))

		expect(await finishDraft('d1')).toEqual({ ok: false, status: 422, error: 'Answers missing', errors: { datum: 'Required' } })
	})

	it('survives a refusal without a readable body', async () => {
		http.answer = refused(500, new Blob(['<html>']))

		expect(await finishDraft('d1')).toEqual({ ok: false, status: 500, error: '', errors: {} })
	})
})

describe('whether a template may be opened for editing (REQ-GDA-005)', () => {
	it('refuses a template that declares a plain-language version', async () => {
		http.answer = { data: { id: 't1', plainLanguage: { requiredStatements: ['decision'] } } }
		expect(await templateEditability('t1')).toEqual({ known: true, editable: false })
		expect(http.calls[0].slice(0, 2)).toEqual(['get', '/index.php/apps/filinq/api/templates/t1'])

		http.answer = { data: { id: 't2', plainLanguage: null } }
		expect(await templateEditability('t2')).toEqual({ known: true, editable: true })
	})

	it('keeps the action when the template cannot be read, and has nothing to say without one', async () => {
		http.answer = refused(404, {})
		expect(await templateEditability('t9')).toEqual({ known: false, editable: true })
		expect(await templateEditability('')).toEqual({ known: false, editable: false })
	})

	it('reads an empty declaration as none', () => {
		expect([null, undefined, false, '', {}, []].map(hasPlainLanguage)).toEqual([false, false, false, false, false, false])
		expect([{ x: 1 }, ['a'], 'b1', true].map(hasPlainLanguage)).toEqual([true, true, true, true])
	})
})
