/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The destruction lists page: the overview from OpenRegister, one list with
 * its proposed records, keeping records with a reason, the partial approval
 * that carries them, and the rejection.
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { createApp, nextTick } from 'vue'
import DestructionLists, { destructionStatusLabel } from './DestructionLists.vue'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), post: jest.fn() },
}))
jest.mock('@nextcloud/dialogs', () => ({
	showError: jest.fn(),
	showSuccess: jest.fn(),
}))
jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) =>
		text.replace(/{(\w+)}/g, (match, key) =>
			vars && key in vars ? vars[key] : match,
		),
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
jest.mock('@conduction/nextcloud-vue', () => ({
	CnDataTable: { name: 'CnDataTable', render: () => null },
	CnFormDialog: { name: 'CnFormDialog', render: () => null },
	CnIndexPage: { name: 'CnIndexPage', render: () => null },
}))
jest.mock('@nextcloud/vue', () => ({
	NcButton: { name: 'NcButton', render: () => null },
	NcLoadingIcon: { name: 'NcLoadingIcon', render: () => null },
	NcNoteCard: { name: 'NcNoteCard', render: () => null },
	NcSelect: { name: 'NcSelect', render: () => null },
}))

const LISTS = '/index.php/apps/openregister/api/archival/destruction-lists'
const flush = () => new Promise((resolve) => setTimeout(resolve, 0))
const LIST = {
	id: 'l-1',
	status: 'in_review',
	objects: [
		{
			uuid: 'o-1',
			title: 'Brief aan inwoner',
			schema: 12,
			register: 7,
			archiefactiedatum: '2026-01-01',
			classification: '1.1',
		},
		{
			uuid: 'o-2',
			title: 'Besluit',
			schema: 12,
			register: 7,
			archiefactiedatum: '2026-02-01',
			classification: '1.1',
		},
	],
}
let rendered

afterEach(() => {
	rendered?.unmount()
	rendered = null
	jest.resetAllMocks()
})

/**
 * Mount the page on a route.
 *
 * @param {string} id The list uuid in the route, empty for the overview.
 * @param {object} router The router double.
 * @return {Promise<object>} The rendered page.
 */
async function mountPage(id = '', router = { push: jest.fn() }) {
	const el = document.createElement('div')
	document.body.appendChild(el)
	const app = createApp(DestructionLists)
	Object.assign(app.config.globalProperties, {
		$route: { params: { id } },
		$router: router,
	})
	const vm = app.mount(el)
	await nextTick()
	await flush()
	rendered = {
		vm,
		unmount: () => {
			app.unmount()
			el.remove()
		},
	}
	return rendered
}

describe('DestructionLists', () => {
	it('labels every status OpenRegister gives a list', () => {
		expect(destructionStatusLabel('in_review')).toBe('In review')
		expect(destructionStatusLabel('awaiting_second_approval')).toBe(
			'Awaiting second approval',
		)
		expect(destructionStatusLabel('executed')).toBe('Executed')
		expect(destructionStatusLabel('something')).toBe('something')
		expect(destructionStatusLabel('')).toBe('Unknown')
	})

	it("lists OpenRegister's destruction lists and opens one by its uuid", async () => {
		axios.get.mockResolvedValue({
			data: {
				results: [
					{
						uuid: 'l-1',
						status: 'in_review',
						entryCount: 2,
						createdAt: '2026-11-01',
					},
				],
				configured: true,
			},
		})
		const router = { push: jest.fn() }
		await mountPage('', router)

		expect(axios.get).toHaveBeenCalledWith(LISTS, { params: {} })
		expect(rendered.vm.rows).toEqual([
			{
				uuid: 'l-1',
				status: 'in_review',
				createdAt: '2026-11-01',
				objectCount: 2,
			},
		])
		expect(rendered.vm.configured).toBe(true)

		rendered.vm.open({ uuid: 'l-1' })
		expect(router.push).toHaveBeenCalledWith({
			name: 'DestructionLists',
			params: { id: 'l-1' },
		})
	})

	it('says when OpenRegister has no destruction list register', async () => {
		axios.get.mockResolvedValue({ data: { results: [], configured: false } })
		await mountPage()

		expect(rendered.vm.configured).toBe(false)
	})

	it("shows OpenRegister's refusal of a non-archivist as it comes", async () => {
		axios.get.mockRejectedValue({
			response: {
				status: 403,
				data: { error: 'Onvoldoende rechten: archivaris rol is vereist' },
			},
		})
		await mountPage()

		expect(rendered.vm.error).toBe(
			'Onvoldoende rechten: archivaris rol is vereist',
		)
		expect(rendered.vm.rows).toEqual([])
	})

	it('filters the overview by status', async () => {
		axios.get.mockResolvedValue({ data: { results: [], configured: true } })
		await mountPage()

		rendered.vm.statusFilter = 'rejected'
		await rendered.vm.load()

		expect(axios.get).toHaveBeenLastCalledWith(LISTS, {
			params: { status: 'rejected' },
		})
	})

	it('shows one list with its proposed records and lets the archivist decide', async () => {
		axios.get.mockResolvedValue({ data: LIST })
		await mountPage('l-1')

		expect(axios.get).toHaveBeenCalledWith(LISTS + '/l-1')
		expect(rendered.vm.entries.map((row) => row.uuid)).toEqual(['o-1', 'o-2'])
		expect(rendered.vm.decidable).toBe(true)
		expect(rendered.vm.approveLabel).toBe('Approve list')
	})

	it('approves the rest with the kept record and its reason, and shows the new status', async () => {
		axios.get.mockResolvedValue({ data: LIST })
		axios.post.mockResolvedValue({ data: { status: 'approved' } })
		await mountPage('l-1')

		rendered.vm.excluding = LIST.objects[1]
		expect(await rendered.vm.exclude('Beroep loopt')).toEqual({ ok: true })
		expect(rendered.vm.approveLabel).toBe('Approve the rest')

		await rendered.vm.approve()

		expect(axios.post).toHaveBeenCalledWith(LISTS + '/l-1/approve', {
			action: 'approve_partial',
			excluded: ['o-2'],
			exclusionReasons: { 'o-2': 'Beroep loopt' },
		})
		expect(rendered.vm.status).toBe('approved')
		expect(rendered.vm.decidable).toBe(false)
		expect(rendered.vm.exclusions).toEqual({})
		expect(showSuccess).toHaveBeenCalledWith('Destruction list: Approved')
	})

	it('puts a kept record back so the list is approved in full', async () => {
		axios.get.mockResolvedValue({ data: LIST })
		axios.post.mockResolvedValue({ data: { status: 'approved' } })
		await mountPage('l-1')

		rendered.vm.excluding = LIST.objects[0]
		await rendered.vm.exclude('Beroep loopt')
		rendered.vm.include('o-1')
		await rendered.vm.approve()

		expect(axios.post).toHaveBeenCalledWith(LISTS + '/l-1/approve', {
			action: 'approve_all',
			excluded: [],
			exclusionReasons: {},
		})
	})

	it('keeps the list as it was when OpenRegister refuses the approval', async () => {
		axios.get.mockResolvedValue({ data: LIST })
		axios.post.mockRejectedValue({
			response: {
				status: 409,
				data: {
					error: 'De tweede goedkeuring moet door een andere archivaris worden gegeven',
				},
			},
		})
		await mountPage('l-1')

		await rendered.vm.approve()

		expect(showError).toHaveBeenCalledWith(
			'De tweede goedkeuring moet door een andere archivaris worden gegeven',
		)
		expect(rendered.vm.status).toBe('in_review')
	})

	it('rejects the list with the reason and shows it rejected', async () => {
		axios.get.mockResolvedValue({ data: LIST })
		axios.post.mockResolvedValue({ data: { status: 'rejected' } })
		await mountPage('l-1')

		const result = await rendered.vm.reject('Lijst onvolledig')

		expect(axios.post).toHaveBeenCalledWith(LISTS + '/l-1/reject', {
			reason: 'Lijst onvolledig',
		})
		expect(result.ok).toBe(true)
		expect(rendered.vm.status).toBe('rejected')
	})

	it('hands a refused rejection back to the dialog and keeps the status', async () => {
		axios.get.mockResolvedValue({ data: LIST })
		axios.post.mockRejectedValue({
			response: {
				status: 400,
				data: { error: 'Een reden voor afwijzing is verplicht' },
			},
		})
		await mountPage('l-1')

		const result = await rendered.vm.reject('x')

		expect(result).toEqual({
			ok: false,
			status: 400,
			error: 'Een reden voor afwijzing is verplicht',
		})
		expect(rendered.vm.status).toBe('in_review')
	})

	it('goes back to the overview', async () => {
		axios.get.mockResolvedValue({ data: LIST })
		const router = { push: jest.fn() }
		await mountPage('l-1', router)

		rendered.vm.back()

		expect(router.push).toHaveBeenCalledWith({ name: 'DestructionLists' })
		expect(rendered.vm.list).toBe(null)
	})

	it("writes a date the way the user's locale does and leaves a missing one empty", async () => {
		axios.get.mockResolvedValue({ data: { results: [] } })
		await mountPage()

		expect(rendered.vm.formatDate('')).toBe('')
		expect(rendered.vm.formatDate('not a date')).toBe('')
		expect(rendered.vm.formatDate('2026-01-01')).toBe(
			new Date('2026-01-01').toLocaleDateString(),
		)
	})
})
