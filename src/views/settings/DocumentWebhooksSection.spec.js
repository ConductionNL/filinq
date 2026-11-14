/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The document webhooks section: it lists only OpenRegister webhooks that
 * name a filinq document event, says when this OpenRegister cannot deliver
 * them, refuses to save without a secret, and adds, tests and deletes
 * receivers through OpenRegister's own webhook API.
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import DocumentWebhooksSection, { EVENTS } from './DocumentWebhooksSection.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), post: jest.fn(), delete: jest.fn() },
}))
jest.mock('@nextcloud/dialogs', () => ({ showError: jest.fn(), showSuccess: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/vue', () => ({
	NcButton: {},
	NcCheckboxRadioSwitch: {},
	NcLoadingIcon: {},
	NcNoteCard: {},
	NcPasswordField: {},
	NcSettingsSection: {},
	NcTextField: {},
}))

const BASE = '/index.php/apps/openregister/api/webhooks'
const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

const RECEIVER = {
	id: 3,
	name: 'Records system',
	url: 'https://records.example/hook',
	events: [EVENTS.signed, EVENTS.registered],
	lastSuccessAt: null,
	lastFailureAt: null,
}
const OTHER = { id: 4, name: 'Objects', url: 'https://x.example', events: ['OCA\\OpenRegister\\Event\\ObjectCreatedEvent'] }

/**
 * Mock the two load calls.
 *
 * @param {Array<object>} webhooks The listed webhooks.
 * @param {Array<object>} catalogue The event catalogue.
 * @return {void}
 */
function mockLoad(webhooks, catalogue) {
	axios.get.mockImplementation((url) => {
		if (url === BASE) {
			return Promise.resolve({ data: { results: webhooks, total: webhooks.length } })
		}
		if (url === BASE + '/events') {
			return Promise.resolve({ data: { events: catalogue } })
		}
		return Promise.resolve({ data: { ...RECEIVER, lastSuccessAt: '2026-11-13T10:00:00+00:00' } })
	})
}

/**
 * Render the section after its load settles.
 *
 * @return {Promise<object>} The render.
 */
async function render() {
	const page = await renderStubbed(DocumentWebhooksSection)
	await flush()
	return page
}

beforeEach(() => {
	jest.clearAllMocks()
})

test('only webhooks naming a document event are listed, and delivery is available when the catalogue lists one', async () => {
	mockLoad([RECEIVER, OTHER], [{ class: EVENTS.signed, name: 'Document signed' }])

	const page = await render()

	expect(axios.get).toHaveBeenCalledWith(BASE)
	expect(axios.get).toHaveBeenCalledWith(BASE + '/events')
	expect(page.vm.receivers.map((row) => row.id)).toEqual([3])
	expect(page.vm.deliveryAvailable).toBe(true)
	expect(page.html()).toContain('Document signed, Document registered')
	expect(page.html()).toContain('Nothing delivered yet')
	expect(page.html()).not.toContain('Delivery is not available on this server')
	page.unmount()
})

test('an OpenRegister without filinq events says delivery is not available and still lists receivers', async () => {
	mockLoad([RECEIVER], [{ class: 'OCA\\OpenRegister\\Event\\ObjectCreatedEvent' }])

	const page = await render()

	expect(page.vm.deliveryAvailable).toBe(false)
	expect(page.html()).toContain('Delivery is not available on this server')
	expect(page.vm.receivers).toHaveLength(1)
	page.unmount()
})

test('saving without a secret is refused and nothing is sent', async () => {
	mockLoad([], [])
	const page = await render()
	page.vm.form = { name: 'Records system', url: 'https://records.example/hook', events: [EVENTS.signed], secret: '  ' }

	await page.vm.save()

	expect(axios.post).not.toHaveBeenCalled()
	expect(page.vm.secretError).toBe('Type a secret so the receiver can check each delivery')
	page.unmount()
})

test('saving without an event is refused', async () => {
	mockLoad([], [])
	const page = await render()
	page.vm.form = { name: 'Records system', url: 'https://records.example/hook', events: [], secret: 's3cret' }

	await page.vm.save()

	expect(axios.post).not.toHaveBeenCalled()
	expect(page.vm.formError).toBe('Type a name and a URL and pick at least one event')
	page.unmount()
})

test('a complete form creates an OpenRegister webhook and lists it', async () => {
	mockLoad([], [])
	axios.post.mockResolvedValue({ data: { ...RECEIVER, id: 9 } })
	const page = await render()
	page.vm.form = { name: ' Records system ', url: 'https://records.example/hook', events: [EVENTS.signed, EVENTS.registered], secret: 's3cret' }

	await page.vm.save()

	expect(axios.post).toHaveBeenCalledWith(BASE, {
		name: 'Records system',
		url: 'https://records.example/hook',
		method: 'POST',
		events: [EVENTS.signed, EVENTS.registered],
		secret: 's3cret',
		enabled: true,
	})
	expect(page.vm.receivers.map((row) => row.id)).toEqual([9])
	expect(page.vm.form).toEqual({ name: '', url: '', events: [], secret: '' })
	expect(showSuccess).toHaveBeenCalledWith('Receiver added')
	page.unmount()
})

test('a test that arrives is reported and the row shows the last delivery', async () => {
	mockLoad([RECEIVER], [])
	axios.post.mockResolvedValue({ data: { success: true } })
	const page = await render()

	await page.vm.sendTest(RECEIVER)

	expect(axios.post).toHaveBeenCalledWith(BASE + '/3/test')
	expect(showSuccess).toHaveBeenCalledWith('The test arrived')
	expect(page.vm.lastDelivery(page.vm.receivers[0])).toBe('Last delivery arrived')
	page.unmount()
})

test('a failed test is reported', async () => {
	mockLoad([RECEIVER], [])
	axios.post.mockRejectedValue({ response: { status: 500 } })
	const page = await render()

	await page.vm.sendTest(RECEIVER)

	expect(showError).toHaveBeenCalledWith('The test did not arrive')
	expect(page.vm.lastDelivery({ lastSuccessAt: '2026-11-12T10:00:00+00:00', lastFailureAt: '2026-11-13T10:00:00+00:00' })).toBe('Last delivery failed')
	page.unmount()
})

test('deleting removes the receiver', async () => {
	mockLoad([RECEIVER], [])
	axios.delete.mockResolvedValue({ data: {} })
	const page = await render()

	await page.vm.remove(RECEIVER)

	expect(axios.delete).toHaveBeenCalledWith(BASE + '/3')
	expect(page.vm.receivers).toEqual([])
	expect(showSuccess).toHaveBeenCalledWith('Receiver deleted')
	page.unmount()
})

test('a refused load is reported and the loading state ends', async () => {
	axios.get.mockRejectedValue(new Error('403'))

	const page = await render()

	expect(page.vm.loading).toBe(false)
	expect(showError).toHaveBeenCalledWith('Could not load the document webhooks')
	page.unmount()
})
