/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The detection provider settings section: it loads the settings and the
 * organisations, shows the chosen organisation's provider, saves the choice
 * with the Hermiq settings, and reports the connectivity test.
 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.2
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import DetectionProviderSettings from './DetectionProviderSettings.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), put: jest.fn(), post: jest.fn() },
}))
jest.mock('@nextcloud/dialogs', () => ({ showError: jest.fn(), showSuccess: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/vue', () => ({
	NcButton: {},
	NcCheckboxRadioSwitch: {},
	NcLoadingIcon: {},
	NcNoteCard: {},
	NcSelect: {},
	NcSettingsSection: {},
	NcTextField: {},
}))

const SETTINGS = '/index.php/apps/filinq/api/detection-provider/settings'
const ORGANISATIONS = '/index.php/apps/openregister/api/organisations'

const STORED = {
	providers: { 'org-b': 'llm' },
	endpoint: 'http://127.0.0.1:8080',
	agentflow: 'detect-entities',
	requireLocal: true,
	available: ['default', 'llm'],
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

/**
 * Answer the two loads: the settings and the organisations.
 *
 * @param {object} settings The settings answer, or an Error to refuse it.
 * @param {object} organisations The organisations answer, or an Error to refuse it.
 */
function answerLoads(settings, organisations) {
	axios.get.mockImplementation((url) => {
		const answer = url === SETTINGS ? settings : organisations
		return answer instanceof Error ? Promise.reject(answer) : Promise.resolve({ data: answer })
	})
}

/**
 * Render the section after its loads settle.
 *
 * @return {Promise<object>} The render.
 */
async function render() {
	const page = await renderStubbed(DetectionProviderSettings)
	await flush()
	await flush()
	return page
}

/**
 * The stubbed buttons, by their test id.
 *
 * @param {object} page The render.
 * @param {string} testId The data-testid.
 * @return {object} The stub.
 */
function button(page, testId) {
	return page.stubs.filter((stub) => stub.name === 'NcButton').find((stub) => stub.attrs['data-testid'] === testId)
}

beforeEach(() => {
	jest.clearAllMocks()
})

test('the settings load and the active organisation shows its own provider', async () => {
	answerLoads(STORED, {
		active: { uuid: 'org-b' },
		results: [{ uuid: 'org-a', name: 'A' }, { uuid: 'org-b', name: 'B' }],
	})

	const page = await render()

	expect(axios.get).toHaveBeenCalledWith(SETTINGS)
	expect(axios.get).toHaveBeenCalledWith(ORGANISATIONS)
	expect(page.vm.loading).toBe(false)
	expect(page.vm.organisation).toBe('org-b')
	expect(page.vm.provider).toBe('llm')
	expect(page.vm.endpoint).toBe('http://127.0.0.1:8080')
	expect(page.vm.agentflow).toBe('detect-entities')
	expect(page.vm.requireLocal).toBe(true)
	expect(page.vm.providerOptions.map((option) => option.id)).toEqual(['default', 'llm'])
	expect(showError).not.toHaveBeenCalled()
	page.unmount()
})

test('every provider picker carries an input label', async () => {
	answerLoads(STORED, { active: null, results: [{ uuid: 'org-a', name: 'A' }] })

	const page = await render()
	const selects = page.stubs.filter((stub) => stub.name === 'NcSelect')

	expect(selects.length).toBe(2)
	for (const select of selects) {
		expect(select.attrs.inputLabel).toBeTruthy()
	}
	page.unmount()
})

test('choosing another organisation shows that organisation\'s provider', async () => {
	answerLoads(STORED, { active: null, results: [{ uuid: 'org-a', name: 'A' }, { uuid: 'org-b', name: 'B' }] })
	const page = await render()
	expect(page.vm.organisation).toBe('org-a')
	expect(page.vm.provider).toBe('default')

	page.vm.organisation = 'org-b'
	await flush()

	expect(page.vm.provider).toBe('llm')
	page.unmount()
})

test('a refused settings load is reported', async () => {
	answerLoads(Object.assign(new Error('no'), { response: { status: 403, data: {} } }), { results: [] })

	const page = await render()

	expect(page.vm.loading).toBe(false)
	expect(showError).toHaveBeenCalledWith('The detection settings could not be loaded.')
	page.unmount()
})

test('without the organisation list the organisation is typed in', async () => {
	answerLoads(STORED, Object.assign(new Error('no'), { response: { status: 500, data: {} } }))

	const page = await render()

	expect(page.vm.organisations).toEqual([])
	expect(page.vm.organisation).toBe('')
	expect(page.stubs.some((stub) => stub.name === 'NcTextField' && stub.attrs.label === 'Organisation ID')).toBe(true)
	expect(showError).not.toHaveBeenCalled()
	page.unmount()
})

test('saving sends the organisation\'s provider and the trimmed Hermiq settings', async () => {
	answerLoads(STORED, { active: { uuid: 'org-a' }, results: [{ uuid: 'org-a', name: 'A' }] })
	axios.put.mockResolvedValue({ data: { ...STORED, providers: { 'org-a': 'llm', 'org-b': 'llm' } } })
	const page = await render()
	page.vm.provider = 'llm'
	page.vm.endpoint = ' http://localhost:9000 '
	page.vm.agentflow = ' flow '
	page.vm.requireLocal = false

	await page.vm.save()

	expect(axios.put).toHaveBeenCalledWith(SETTINGS, {
		organisation: 'org-a',
		provider: 'llm',
		endpoint: 'http://localhost:9000',
		agentflow: 'flow',
		requireLocal: false,
	})
	expect(page.vm.providers).toEqual({ 'org-a': 'llm', 'org-b': 'llm' })
	expect(page.vm.provider).toBe('llm')
	expect(page.vm.saving).toBe(false)
	expect(showSuccess).toHaveBeenCalledWith('Detection settings saved')
	page.unmount()
})

test('saving without an organisation leaves every organisation\'s provider alone', async () => {
	answerLoads(STORED, { results: [] })
	axios.put.mockResolvedValue({ data: STORED })
	const page = await render()

	await page.vm.save()

	expect(axios.put).toHaveBeenCalledWith(SETTINGS, {
		endpoint: 'http://127.0.0.1:8080',
		agentflow: 'detect-entities',
		requireLocal: true,
	})
	page.unmount()
})

test('an invalid save is reported and the stored settings stay', async () => {
	answerLoads(STORED, { results: [] })
	axios.put.mockRejectedValue({ response: { status: 400, data: { error: 'endpoint' } } })
	const page = await render()
	page.vm.endpoint = 'ftp://x'

	await page.vm.save()

	expect(page.vm.saving).toBe(false)
	expect(page.vm.providers).toEqual({ 'org-b': 'llm' })
	expect(showSuccess).not.toHaveBeenCalled()
	expect(showError).toHaveBeenCalledWith(
		'The detection settings were not saved: check that the endpoint is an http or https address.',
	)
	page.unmount()
})

test('a save refused otherwise says it could not be saved', async () => {
	answerLoads(STORED, { results: [] })
	axios.put.mockRejectedValue({ response: { status: 403, data: {} } })
	const page = await render()

	await page.vm.save()

	expect(showError).toHaveBeenCalledWith('The detection settings could not be saved.')
	page.unmount()
})

test('the connectivity test result is shown', async () => {
	answerLoads(STORED, { results: [] })
	axios.post.mockResolvedValue({ data: { available: false, reason: 'unreachable' } })
	const page = await render()

	button(page, 'detection-settings-test').attrs.onClick()
	await flush()

	expect(axios.post).toHaveBeenCalledWith('/index.php/apps/filinq/api/detection-provider/test')
	expect(page.vm.testResult).toEqual({ available: false, reason: 'unreachable' })
	expect(page.vm.testText).toContain('did not answer')
	expect(page.vm.testing).toBe(false)
	page.unmount()
})

test('a refused connectivity test is reported and shows no result', async () => {
	answerLoads(STORED, { results: [] })
	axios.post.mockRejectedValue({ response: { status: 403, data: {} } })
	const page = await render()

	await page.vm.test()

	expect(page.vm.testResult).toBeNull()
	expect(showError).toHaveBeenCalledWith('The connection could not be tested.')
	page.unmount()
})

test('the save button triggers a save', async () => {
	answerLoads(STORED, { results: [] })
	axios.put.mockResolvedValue({ data: STORED })
	const page = await render()

	button(page, 'detection-settings-save').attrs.onClick()
	await flush()

	expect(axios.put).toHaveBeenCalledTimes(1)
	page.unmount()
})
