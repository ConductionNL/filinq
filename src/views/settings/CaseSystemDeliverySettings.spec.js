/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The case-system section of the admin bridge panel: it loads the
 * destinations and the waiting and failed deliveries from its own endpoint,
 * saves a destination and shows the server's refusal of a second active
 * one, and retries a failed delivery in place.
 *
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import CaseSystemDeliverySettings from './CaseSystemDeliverySettings.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), post: jest.fn() },
}))
jest.mock('@nextcloud/dialogs', () => ({ showError: jest.fn(), showSuccess: jest.fn() }))
jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => text.replace(/{(\w+)}/g, (match, key) => (vars && key in vars ? vars[key] : match)),
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@nextcloud/vue', () => ({
	NcButton: {},
	NcCheckboxRadioSwitch: {},
	NcLoadingIcon: {},
	NcSelect: {},
	NcSettingsSection: {},
	NcTextField: {},
}))

const BASE = '/index.php/apps/filinq/api/bridge/'
const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

const DESTINATION = {
	id: 'ns',
	templateNamespace: 'hrmq',
	templateId: '',
	sourceId: 'zgw-delft',
	informatieobjecttype: 'https://zaken.example.nl/iot/1',
	vertrouwelijkheidaanduiding: 'vertrouwelijk',
	bronorganisatie: '002220647',
	active: true,
}

const FAILED = {
	id: 'delivery-1',
	generatedDocumentId: 'doc-1',
	deliveryStatus: 'writeback_failed',
	errorMessage: 'informatieobjecttype unknown',
	metadata: { bestandsnaam: 'Contract.pdf' },
}

/**
 * Render the section after its load settles.
 *
 * @return {Promise<object>} The render.
 */
async function render() {
	const page = await renderStubbed(CaseSystemDeliverySettings)
	await flush()
	return page
}

beforeEach(() => {
	jest.clearAllMocks()
	axios.get.mockResolvedValue({ data: { destinations: [DESTINATION], deliveries: [FAILED] } })
})

test('the destinations and the open deliveries are loaded from the panel endpoint', async () => {
	const page = await render()

	expect(axios.get).toHaveBeenCalledWith(BASE + 'case-system')
	expect(page.vm.loading).toBe(false)
	expect(page.vm.destinations).toEqual([DESTINATION])
	expect(page.vm.deliveries).toEqual([FAILED])
	expect(page.vm.scopeOf(DESTINATION)).toBe('Namespace hrmq')
	expect(page.vm.scopeOf({ templateId: 'template-1', templateNamespace: 'hrmq' })).toBe('Template template-1')
	expect(page.vm.documentName(FAILED)).toBe('Contract.pdf')
	expect(page.vm.documentName({ generatedDocumentId: 'doc-2' })).toBe('doc-2')
	expect(page.vm.statusLabel(FAILED)).toBe('Failed')
	expect(page.vm.statusLabel({ deliveryStatus: 'ready_for_writeback' })).toBe('Waiting')
})

test('a refused load is reported and the panel stops loading', async () => {
	axios.get.mockRejectedValue(new Error('403'))

	const page = await render()

	expect(page.vm.loading).toBe(false)
	expect(page.vm.destinations).toEqual([])
	expect(showError).toHaveBeenCalledWith('Could not load the case-system settings')
})

test('a destination is saved, the form emptied and the list reloaded', async () => {
	axios.post.mockResolvedValue({ data: { ...DESTINATION, id: 'new' } })
	const page = await render()
	page.vm.form.templateNamespace = 'finance'
	page.vm.form.sourceId = 'zgw-delft'

	await page.vm.save()

	expect(axios.post).toHaveBeenCalledWith(BASE + 'destinations', expect.objectContaining({
		id: '',
		templateNamespace: 'finance',
		sourceId: 'zgw-delft',
		vertrouwelijkheidaanduiding: 'vertrouwelijk',
		active: true,
	}))
	expect(showSuccess).toHaveBeenCalledWith('Destination saved')
	expect(page.vm.form.templateNamespace).toBe('')
	expect(axios.get).toHaveBeenCalledTimes(2)
	expect(page.vm.saving).toBe(false)
})

test('the refusal of a second active destination shows the server reason', async () => {
	axios.post.mockRejectedValue({ response: { data: { error: 'An active destination for this template already exists.' } } })
	const page = await render()
	page.vm.edit(DESTINATION)

	await page.vm.save()

	expect(axios.post).toHaveBeenCalledWith(BASE + 'destinations', expect.objectContaining({ id: 'ns', templateNamespace: 'hrmq' }))
	expect(showError).toHaveBeenCalledWith('An active destination for this template already exists.')
	expect(page.vm.form.id).toBe('ns')
	expect(axios.get).toHaveBeenCalledTimes(1)
})

test('editing fills the form and cancelling empties it', async () => {
	const page = await render()

	page.vm.edit({ ...DESTINATION, active: false, extra: 'ignored' })
	expect(page.vm.form).toEqual({ ...DESTINATION, active: false })

	page.vm.reset()
	expect(page.vm.form.id).toBe('')
	expect(page.vm.form.active).toBe(true)
})

test('retrying a failed delivery shows it waiting again in place', async () => {
	axios.post.mockResolvedValue({ data: { ...FAILED, deliveryStatus: 'ready_for_writeback', errorMessage: undefined } })
	const page = await render()

	await page.vm.retry(FAILED)

	expect(axios.post).toHaveBeenCalledWith(BASE + 'deliveries/delivery-1/retry')
	expect(page.vm.deliveries).toHaveLength(1)
	expect(page.vm.deliveries[0].deliveryStatus).toBe('ready_for_writeback')
	expect(showSuccess).toHaveBeenCalledWith('The delivery is waiting again')
	expect(page.vm.retrying).toBe('')
})

test('a refused retry is reported and leaves the delivery as it was', async () => {
	axios.post.mockRejectedValue(new Error('409'))
	const page = await render()

	await page.vm.retry(FAILED)

	expect(page.vm.deliveries).toEqual([FAILED])
	expect(showError).toHaveBeenCalledWith('Could not retry the delivery')
})
