import axios from '@nextcloud/axios'
/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Sending a letter by email from the correspondence store: one request to
 * the send route with the letter and the PDF choice, the sent letter kept,
 * and a refusal handed back with the server's message.
 *
 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-3
 */
import { createPinia, setActivePinia } from 'pinia'
import { sendRequest, useCorrespondenceStore } from './correspondence.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { post: jest.fn() },
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))

/**
 * A store holding one letter for one recipient.
 *
 * @return {object} The store.
 */
function letterStore() {
	const store = useCorrespondenceStore()
	store.templateId = 'tmpl-1'
	store.dataRefs = [{ register: 'brp', schema: 'persoon', id: 'p-1' }]
	store.format = 'docx'
	store.caseReference = 'Z/2026/001'
	return store
}

beforeEach(() => {
	setActivePinia(createPinia())
	axios.post.mockReset()
})

describe('sendRequest', () => {
	it('carries the letter without a format, leaving out empty ids', () => {
		expect(sendRequest(letterStore())).toEqual({
			templateId: 'tmpl-1',
			dataRefs: [{ register: 'brp', schema: 'persoon', id: 'p-1' }],
			options: { huisstijlId: undefined, caseReference: 'Z/2026/001' },
		})
	})
})

describe('sendBody', () => {
	it('is the same body as sendRequest', () => {
		const store = letterStore()
		expect(store.sendBody).toEqual(sendRequest(store))
	})
})

describe('sendByEmail', () => {
	it('sends one request to the send route and keeps the sent letter', async () => {
		axios.post.mockResolvedValue({
			data: { status: 'sent', to: 'j.jansen@example.nl', subject: 'Uitnodiging gesprek', sentAt: '2026-11-17T15:00:00+00:00', warnings: [] },
		})
		const store = letterStore()

		const result = await store.sendByEmail(true)

		expect(axios.post).toHaveBeenCalledTimes(1)
		expect(axios.post).toHaveBeenCalledWith('/apps/filinq/api/correspondence/send', {
			templateId: 'tmpl-1',
			dataRefs: [{ register: 'brp', schema: 'persoon', id: 'p-1' }],
			options: { huisstijlId: undefined, caseReference: 'Z/2026/001', attachPdf: true },
		})
		expect(result.status).toBe('sent')
		expect(store.lastSent).toEqual({ to: 'j.jansen@example.nl', subject: 'Uitnodiging gesprek', sentAt: '2026-11-17T15:00:00+00:00' })
		expect(store.loading).toBe(false)
	})

	it('rejects with the server message and keeps nothing as sent', async () => {
		axios.post.mockRejectedValue({ message: 'Request failed', response: { status: 422, data: { error: 'Deze ontvanger heeft geen e-mailadres.' } } })
		const store = letterStore()

		await expect(store.sendByEmail(false)).rejects.toThrow('Deze ontvanger heeft geen e-mailadres.')
		expect(store.lastSent).toBeNull()
		expect(store.loading).toBe(false)
	})
})
