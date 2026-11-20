/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The finality rule section (finish-half-built-capabilities 3.2): it sends
 * a complete declaration to POST /api/document-finality-rules with the
 * states as a list, shows what the server stored, and shows the server's
 * own refusal.
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import DocumentFinalityRuleSettings, {
	parseStates,
} from './DocumentFinalityRuleSettings.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { post: jest.fn() },
}))
jest.mock('@nextcloud/dialogs', () => ({ showError: jest.fn(), showSuccess: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/vue', () => ({
	NcButton: {},
	NcSettingsSection: {},
	NcTextField: {},
}))

const URL = '/index.php/apps/filinq/api/document-finality-rules'

beforeEach(() => {
	jest.clearAllMocks()
})

test('the states are split on commas, trimmed, without empty ones or repeats', () => {
	expect(parseStates(' besloten, gearchiveerd ,,besloten ')).toEqual([
		'besloten',
		'gearchiveerd',
	])
	expect(parseStates('')).toEqual([])
	expect(parseStates(undefined)).toEqual([])
})

test('the rule can only be declared with an app, a record type and a state', async () => {
	const page = await renderStubbed(DocumentFinalityRuleSettings)

	expect(page.vm.complete).toBe(false)
	page.vm.form.declaringApp = 'dossiq'
	page.vm.form.typeReference = 'bezwaar'
	expect(page.vm.complete).toBe(false)
	page.vm.form.finalStates = ' , '
	expect(page.vm.complete).toBe(false)
	page.vm.form.finalStates = 'besloten'
	expect(page.vm.complete).toBe(true)
})

test('a declaration is posted with the states as a list and the stored rule is shown', async () => {
	axios.post.mockResolvedValue({
		data: {
			rule: {
				declaringApp: 'dossiq',
				typeReference: 'bezwaar',
				finalStates: ['besloten', 'gearchiveerd'],
			},
		},
	})
	const page = await renderStubbed(DocumentFinalityRuleSettings)
	Object.assign(page.vm.form, {
		declaringApp: ' dossiq ',
		typeReference: 'bezwaar',
		finalStates: 'besloten, gearchiveerd',
		documentRole: '',
		reasonTemplate: ' Besluit genomen ',
	})

	await page.vm.save()

	expect(axios.post).toHaveBeenCalledWith(URL, {
		declaringApp: 'dossiq',
		typeReference: 'bezwaar',
		finalStates: ['besloten', 'gearchiveerd'],
		documentRole: '',
		reasonTemplate: 'Besluit genomen',
	})
	expect(page.vm.stored.finalStates).toEqual(['besloten', 'gearchiveerd'])
	expect(page.vm.saving).toBe(false)
	expect(showSuccess).toHaveBeenCalledWith('Finality rule stored')
	expect(showError).not.toHaveBeenCalled()
})

test('a refusal shows the server message, or a fallback without one', async () => {
	axios.post.mockRejectedValueOnce({
		response: {
			data: {
				error: 'Declaring which states make a document final needs the final-document administrator right.',
			},
		},
	})
	const page = await renderStubbed(DocumentFinalityRuleSettings)
	Object.assign(page.vm.form, {
		declaringApp: 'dossiq',
		typeReference: 'bezwaar',
		finalStates: 'besloten',
	})

	await page.vm.save()
	expect(showError).toHaveBeenLastCalledWith(
		'Declaring which states make a document final needs the final-document administrator right.',
	)
	expect(page.vm.stored).toBeNull()

	axios.post.mockRejectedValueOnce(new Error('network'))
	await page.vm.save()
	expect(showError).toHaveBeenLastCalledWith('Could not store the declaration')
	expect(showSuccess).not.toHaveBeenCalled()
	expect(page.vm.saving).toBe(false)
})
