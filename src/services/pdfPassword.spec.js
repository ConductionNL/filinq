/**
 * Tests for removing the password of a PDF (intake-pdf-password-removal, task 2.1).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import { removePassword, removePasswordError } from './pdfPassword.js'

jest.mock('@nextcloud/axios', () => ({ post: jest.fn() }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => `/index.php${url}` }))
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
