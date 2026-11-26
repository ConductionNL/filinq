/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The review of one destruction list: its proposed records from OpenRegister,
 * keeping records with a reason, the partial approval that carries them, and
 * the rejection.
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { createApp, nextTick } from 'vue'
import DestructionListReview, {
	destructionStatusLabel,
	localDate,
} from './DestructionListReview.vue'

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
}))
jest.mock('@nextcloud/vue', () => ({
	NcButton: { name: 'NcButton', render: () => null },
	NcLoadingIcon: { name: 'NcLoadingIcon', render: () => null },
	NcNoteCard: { name: 'NcNoteCard', render: () => null },
}))

const LIST_URL = '/index.php/apps/openregister/api/archival/destruction-lists/l-1'
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
 * Mount the section.
 *
 * @param {object} props The section's props.
 * @param {object} route The route double.
 * @return {Promise<object>} `{ vm, unmount }`.
 */
async function mountReview(props = { objectId: 'l-1' }, route = { params: {} }) {
	const el = document.createElement('div')
	document.body.appendChild(el)
	const app = createApp(DestructionListReview, props)
	app.config.globalProperties.$route = route
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

describe('DestructionListReview', () => {
	it('labels every status OpenRegister gives a list', () => {
		expect(destructionStatusLabel('in_review')).toBe('In review')
		expect(destructionStatusLabel('awaiting_second_approval')).toBe(
			'Awaiting second approval',
		)
		expect(destructionStatusLabel('approved')).toBe('Approved')
		expect(destructionStatusLabel('rejected')).toBe('Rejected')
		expect(destructionStatusLabel('completed')).toBe('Completed')
		expect(destructionStatusLabel('executing')).toBe('Executing')
		expect(destructionStatusLabel('executed')).toBe('Executed')
		expect(destructionStatusLabel('something')).toBe('something')
		expect(destructionStatusLabel('')).toBe('Unknown')
	})

	it("writes a date the way the user's locale does and leaves a missing one empty", () => {
		expect(localDate('')).toBe('')
		expect(localDate('not a date')).toBe('')
		expect(localDate('2026-01-01')).toBe(
			new Date('2026-01-01').toLocaleDateString(),
		)
	})

	it('reads the list the detail page names and lets the archivist decide', async () => {
		axios.get.mockResolvedValue({ data: LIST })
		await mountReview()

		expect(axios.get).toHaveBeenCalledWith(LIST_URL)
		expect(rendered.vm.entries.map((row) => row.uuid)).toEqual(['o-1', 'o-2'])
		expect(rendered.vm.entries[0].actionDate).toBe(
			new Date('2026-01-01').toLocaleDateString(),
		)
		expect(rendered.vm.decidable).toBe(true)
		expect(rendered.vm.approveLabel).toBe('Approve list')
	})

	it('names each record\'s schema and register by title, and keeps the id when no title can be read', async () => {
		axios.get.mockImplementation((url) => {
			if (url === LIST_URL) {
				return Promise.resolve({ data: LIST })
			}
			if (url.endsWith('/api/schemas/12')) {
				return Promise.resolve({ data: { title: 'Generated Document' } })
			}
			return Promise.reject(new Error('forbidden'))
		})
		await mountReview()

		expect(rendered.vm.entries[0].schema).toBe('Generated Document')
		expect(rendered.vm.entries[0].register).toBe('7')
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/openregister/api/schemas/12')
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/openregister/api/registers/7')
	})

	it("falls back to the route's id", async () => {
		axios.get.mockResolvedValue({ data: LIST })
		await mountReview({}, { params: { id: 'l-1' } })

		expect(axios.get).toHaveBeenCalledWith(LIST_URL)
	})

	it("shows OpenRegister's refusal of a non-archivist as it comes", async () => {
		axios.get.mockRejectedValue({
			response: {
				status: 403,
				data: { error: 'Onvoldoende rechten: archivaris rol is vereist' },
			},
		})
		await mountReview()

		expect(rendered.vm.list).toBe(null)
		expect(rendered.vm.error).toBe(
			'Onvoldoende rechten: archivaris rol is vereist',
		)
	})

	it('approves the rest with the kept record and its reason, and shows the new status', async () => {
		axios.get.mockResolvedValue({ data: LIST })
		axios.post.mockResolvedValue({ data: { status: 'approved' } })
		await mountReview()

		rendered.vm.excluding = LIST.objects[1]
		expect(await rendered.vm.exclude('Beroep loopt')).toEqual({ ok: true })
		expect(rendered.vm.approveLabel).toBe('Approve the rest')

		await rendered.vm.approve()

		expect(axios.post).toHaveBeenCalledWith(LIST_URL + '/approve', {
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
		await mountReview()

		rendered.vm.excluding = LIST.objects[0]
		await rendered.vm.exclude('Beroep loopt')
		rendered.vm.include('o-1')
		await rendered.vm.approve()

		expect(axios.post).toHaveBeenCalledWith(LIST_URL + '/approve', {
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
		await mountReview()

		await rendered.vm.approve()

		expect(showError).toHaveBeenCalledWith(
			'De tweede goedkeuring moet door een andere archivaris worden gegeven',
		)
		expect(rendered.vm.status).toBe('in_review')
	})

	it('rejects the list with the reason and shows it rejected', async () => {
		axios.get.mockResolvedValue({ data: LIST })
		axios.post.mockResolvedValue({ data: { status: 'rejected' } })
		await mountReview()

		const result = await rendered.vm.reject('Lijst onvolledig')

		expect(axios.post).toHaveBeenCalledWith(LIST_URL + '/reject', {
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
		await mountReview()

		const result = await rendered.vm.reject('x')

		expect(result).toEqual({
			ok: false,
			status: 400,
			error: 'Een reden voor afwijzing is verplicht',
		})
		expect(rendered.vm.status).toBe('in_review')
	})

	// @spec openspec/changes/archiefwet-retention-engine/tasks.md#task-5.1
	it('shows which proposed records a legal hold keeps, naming the case when the server does', async () => {
		axios.get.mockImplementation((url) => {
			if (url === LIST_URL) {
				return Promise.resolve({ data: LIST })
			}
			return Promise.resolve({
				data: url.endsWith('/status/o-1')
					? { held: true, cases: [{ name: 'Beroep Jansen' }] }
					: { held: false, cases: [] },
			})
		})
		await mountReview()
		await flush()

		expect(axios.get).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/legal-holds/status/o-1',
		)
		expect(axios.get).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/legal-holds/status/o-2',
		)
		expect(rendered.vm.entryColumns.map((column) => column.key)).toContain(
			'hold',
		)
		expect(rendered.vm.holdText('o-1')).toBe('Under a legal hold: Beroep Jansen')
		expect(rendered.vm.holdText('o-2')).toBe('')
	})

	// @spec openspec/changes/archiefwet-retention-engine/tasks.md#task-5.1
	it('says "Under a legal hold" without a name to somebody without hold authority', async () => {
		axios.get.mockImplementation((url) =>
			Promise.resolve({
				data: url === LIST_URL ? LIST : { held: true, cases: [] },
			}),
		)
		await mountReview()
		await flush()

		expect(rendered.vm.holdText('o-2')).toBe('Under a legal hold')
	})

	// @spec openspec/changes/archiefwet-retention-engine/tasks.md#task-5.1
	it('keeps the holds of the list on screen when an older read answers late', async () => {
		axios.get.mockResolvedValue({ data: LIST })
		await mountReview()
		await flush()

		const answers = []
		axios.get.mockImplementation(
			() =>
				new Promise((resolve) => {
					answers.push(resolve)
				}),
		)
		const late = rendered.vm.loadHolds()
		rendered.vm.list = { ...LIST }
		rendered.vm.holds = {}
		answers.forEach((answer) => answer({ data: { held: true } }))
		await late

		expect(rendered.vm.holds).toEqual({})
	})
})
