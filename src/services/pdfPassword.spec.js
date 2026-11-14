/**
 * Tests for removing the password of a PDF (intake-pdf-password-removal, task 2.1).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import { mayRemovePassword, removeIntakePassword, removePassword, removePasswordError } from './pdfPassword.js'

jest.mock('@nextcloud/axios', () => ({ post: jest.fn() }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url, params = {}) => `/index.php${url.replace(/\{(\w+)\}/g, (m, k) => encodeURIComponent(params[k]))}` }))
jest.mock('@nextcloud/l10n', () => ({ translate: (_app, text) => text }))

describe('removePassword', () => {
	beforeEach(() => axios.post.mockReset())

	it('posts the password in the body to the file\'s route and answers the copy', async () => {
		const copy = { fileId: 43, name: 'Brief (unlocked).pdf', validationStatus: 'passed', validationFindings: [] }
		axios.post.mockResolvedValue({ data: copy })

		await expect(removePassword(42, 'geheim')).resolves.toEqual(copy)
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/documents/42/remove-password', { password: 'geheim' })
	})

	it('answers an empty object when the server sends no body', async () => {
		axios.post.mockResolvedValue({})

		await expect(removePassword(42, 'x')).resolves.toEqual({})
	})
})

describe('removePasswordError', () => {
	it('shows the server\'s own message first', () => {
		expect(removePasswordError({ response: { status: 422, data: { error: 'Het wachtwoord klopt niet' } } })).toBe('Het wachtwoord klopt niet')
	})

	it('names the refusal by status when the server sent no message', () => {
		expect(removePasswordError({ response: { status: 422 } })).toBe('The password is not correct')
		expect(removePasswordError({ response: { status: 409 } })).toBe('This document is final and cannot be changed')
		expect(removePasswordError({ response: { status: 503 } })).toBe('qpdf is not installed on this server')
	})

	it('falls back to a general sentence for anything else', () => {
		expect(removePasswordError(new Error('offline'))).toBe('The password could not be removed from this PDF.')
	})
})

describe('removeIntakePassword', () => {
	beforeEach(() => axios.post.mockReset())

	it('posts the password in the body to the intake document\'s route and answers the copy and the document', async () => {
		const answer = { fileId: 77, name: 'scan (unlocked).pdf', intakeDocument: { file: 77, lockedOriginal: 41 } }
		axios.post.mockResolvedValue({ data: answer })

		await expect(removeIntakePassword('doc 1', 'geheim')).resolves.toEqual(answer)
		expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/intake/documents/doc%201/remove-password', { password: 'geheim' })
	})
})

describe('mayRemovePassword', () => {
	it('offers the action on a PDF whose password was not removed yet', () => {
		expect(mayRemovePassword({ fileName: 'scan.PDF' })).toBe(true)
		expect(mayRemovePassword({ fileName: 'scan.pdf', lockedOriginal: 0 })).toBe(true)
	})

	it('does not offer it on another format, an unlocked document or a row without a name', () => {
		expect(mayRemovePassword({ fileName: 'brief.docx' })).toBe(false)
		expect(mayRemovePassword({ fileName: 'scan.pdf', lockedOriginal: 41 })).toBe(false)
		expect(mayRemovePassword({})).toBe(false)
		expect(mayRemovePassword(null)).toBe(false)
	})
})
