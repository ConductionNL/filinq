/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the policy rule form on both surfaces, publish never and
 * publish always: the index page renders with its own form dialog, the test
 * fills the form in through the dialog, clicks its submit button, and records
 * which store action the page called with what. Per surface it covers a
 * create, an update of an existing rule, and a submit the store refuses.
 * RECORD_GOLDEN=1 rewrites the golden file.
 */

const fs = require('fs')
const path = require('path')
const { nextTick } = require('vue')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) =>
		vars ? text + ' ' + JSON.stringify(vars) : text,
	translatePlural: (app, one, many, count) =>
		(count === 1 ? one : many) + ' #' + count,
}))
jest.mock('@nextcloud/vue', () => {
	const module = require('../../../tests/jest/dialogGolden.js').ncVueModule()
	const { stubComponent } = require('../../../tests/jest/renderGolden.js')
	for (const name of ['NcActions', 'NcActionButton']) {
		module[name] = stubComponent(name, (global.mockStubs ??= []))
	}
	return module
})
jest.mock('@conduction/nextcloud-vue', () => {
	const module = require('../../../tests/jest/dialogGolden.js').cnVueModule()
	// The table's row slots are out of scope here; the list renders as a marker.
	const CnIndexPage = { name: 'CnIndexPage', render: () => null }
	return new Proxy(module, {
		get: (target, key) => (key === 'CnIndexPage' ? CnIndexPage : target[key]),
	})
})
for (const icon of ['Delete', 'DotsHorizontal', 'Pencil']) {
	jest.mock('vue-material-design-icons/' + icon + '.vue', () => ({
		name: 'Icon',
		render: () => null,
	}))
}

const mockCalls = []
const mockRefuse = { value: false }

/**
 * A policy store: its list state as given, every other key an action that
 * records its call and answers, or throws once when a refusal is armed.
 *
 * @param {string} name The store name, for the record.
 * @param {object} state The list state.
 * @return {object} The store.
 */
function mockStore(name, state) {
	return new Proxy(state, {
		get(target, key) {
			if (key in target || typeof key === 'symbol' || key === 'then') {
				return target[key]
			}
			return async (...args) => {
				mockCalls.push([name, key, ...JSON.parse(JSON.stringify(args))])
				if (mockRefuse.value) {
					mockRefuse.value = false
					throw new Error('Forbidden by policy permission')
				}
				return { id: 'saved-1' }
			}
		},
	})
}

jest.mock('../../store/store.js', () => ({
	prohibitionStore: mockStore('prohibitionStore', {
		prohibitions: [],
		prohibitionStats: {},
		loading: false,
	}),
	standingConsentStore: mockStore('standingConsentStore', {
		standingConsents: [],
		standingConsentStats: {},
		loading: false,
	}),
}))

const { renderStubbed } = require('../../../tests/jest/renderGolden.js')
const PolicyRuleIndex = require('./PolicyRuleIndex.vue').default

const RULE = { type: 'bsn', value: '123456782' }

const SURFACES = {
	prohibition: {
		fill: (form) => {
			form.primaryName = ' Jan Jansen '
			form.reason = 'Court order'
			form.severity = 'high'
			form.matchRules.push({ ...RULE })
		},
		row: {
			id: 'p-7',
			primaryName: 'Piet Pietersen',
			entityType: 'PERSON',
			reason: 'Threat',
			legalAuthority: 'Rb Den Haag',
			caseReference: 'C-1',
			severity: 'low',
			jurisdiction: 'NL',
			validUntil: '2030-01-01',
			active: true,
			matchRules: [{ type: 'exact', value: 'Piet', extra: 1 }],
		},
		edit: (form) => {
			form.reason = 'Threat, renewed'
		},
	},
	standingConsent: {
		fill: (form) => {
			form.entityText = ' Gemeente Zuiddrecht '
			form.entityType = 'ORGANIZATION'
			form.consentMethod = 'digital_signature'
			form.matchRules.push({ type: 'kvk', value: '12345678' })
		},
		row: {
			uuid: 'c-9',
			entityText: 'Bakkerij de Korst',
			entityType: 'ORGANIZATION',
			consentMethod: 'paper',
			consentDocument: '42',
			consentScope: '2024',
			legalBasis: 'Art. 6',
			validFrom: '2024-01-01',
			validUntil: '',
			active: false,
			matchRules: [{ type: 'kvk', value: '87654321' }],
			consentStatus: 'consent_given',
			publicationDecision: 'publish_with_consent',
			notificationStatus: 'sent',
		},
		edit: (form) => {
			form.validUntil = '2026-12-31'
		},
	},
}

const GOLDEN = path.join(
	__dirname,
	'../../../tests/jest/golden/policy-rule-form.json',
)
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

/**
 * Let the page and its dialog re-render.
 */
async function flush() {
	for (let i = 0; i < 6; i++) {
		await nextTick()
	}
}

/**
 * The form dialog component instance under the page.
 *
 * @param {object} vnode The page's rendered tree.
 * @return {object|null} The dialog's instance proxy.
 */
function findForm(vnode) {
	if (!vnode) {
		return null
	}
	const component = vnode.component
	if (component) {
		if (/FormModal$/.test(component.type.name || '')) {
			return component.proxy
		}
		return findForm(component.subTree)
	}
	for (const child of Array.isArray(vnode.children) ? vnode.children : []) {
		const found = findForm(child)
		if (found) {
			return found
		}
	}
	return null
}

/**
 * The open dialog as the user sees it: title, fields, notes and buttons.
 *
 * @return {object|null} The view.
 */
function dialogView() {
	const frame = document.body.querySelector('x-ncdialog')
	if (!frame) {
		return null
	}
	const fields = [
		...frame.querySelectorAll(
			'x-nctextfield, x-ncselect, x-nccheckboxradioswitch',
		),
	].map((el) => el.getAttribute('label') || el.textContent.trim())
	const buttons = [
		...frame.querySelectorAll('x-slot[name="actions"] x-ncbutton'),
	].map((el) => ({
		label: el.textContent.trim(),
		disabled: el.getAttribute('disabled') === 'true',
	}))
	const notes = [...frame.querySelectorAll('.form-warning, .form-error, h4')].map(
		(el) => el.className + ': ' + el.textContent.replace(/\s+/g, ' ').trim(),
	)
	return {
		title: frame.getAttribute('name'),
		formClass: frame.querySelector('x-slot > div')?.className ?? null,
		fields,
		notes,
		buttons,
	}
}

/**
 * Click the dialog's submit button.
 */
async function clickSubmit() {
	const buttons = document.body.querySelectorAll(
		'x-ncdialog x-slot[name="actions"] x-ncbutton',
	)
	buttons[buttons.length - 1].click()
	await flush()
}

/**
 * The page's dialog state after a submit.
 *
 * @param {object} vm The page.
 * @return {object} Open flag, error and saving flag.
 */
function pageState(vm) {
	return {
		dialogOpen: vm.dialogOpen,
		formError: vm.formError,
		saving: vm.saving,
	}
}

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('policy rule form golden', () => {
	for (const [surface, site] of Object.entries(SURFACES)) {
		it(surface, async () => {
			const actual = {}
			const page = await renderStubbed(PolicyRuleIndex, {
				props: { surface },
				keep: [
					'CnIndexPage',
					'NcButton',
					'CnDialogActions',
					'ProhibitionFormModal',
					'StandingConsentFormModal',
					'PolicyRuleFormModal',
				],
			})
			await flush()
			actual.mounted = { calls: mockCalls.splice(0), dialog: dialogView() }

			page.vm.openCreateDialog()
			await flush()
			const form = findForm(page.vm.$.subTree)
			actual.createOpened = dialogView()
			site.fill(form.form)
			await flush()
			actual.createFilled = dialogView()
			await clickSubmit()
			actual.created = { calls: mockCalls.splice(0), page: pageState(page.vm) }

			page.vm.openEditDialog(site.row)
			await flush()
			actual.editOpened = {
				view: dialogView(),
				form: JSON.parse(JSON.stringify(findForm(page.vm.$.subTree).form)),
			}
			site.edit(findForm(page.vm.$.subTree).form)
			await flush()
			actual.editChanged = dialogView()
			await clickSubmit()
			actual.updated = { calls: mockCalls.splice(0), page: pageState(page.vm) }

			page.vm.openCreateDialog()
			await flush()
			site.fill(findForm(page.vm.$.subTree).form)
			mockRefuse.value = true
			await clickSubmit()
			actual.refused = {
				calls: mockCalls.splice(0),
				page: pageState(page.vm),
				dialog: dialogView(),
			}
			page.unmount()

			if (RECORD) {
				golden[surface] = actual
				return
			}
			for (const phase of Object.keys(golden[surface])) {
				expect([phase, actual[phase]]).toEqual([
					phase,
					golden[surface][phase],
				])
			}
		})
	}
})

const KEEP_REAL = [
	'CnIndexPage',
	'NcButton',
	'CnDialogActions',
	'PolicyRuleFormModal',
]

/**
 * Mount the page for a surface and open its form, to create or to edit a row.
 *
 * @param {string} surface The policy surface.
 * @param {object|null} row The rule to edit, or null to create one.
 * @return {Promise<object>} The page and the form instance.
 */
async function openForm(surface, row) {
	const page = await renderStubbed(PolicyRuleIndex, {
		props: { surface },
		keep: KEEP_REAL,
	})
	await flush()
	if (row) {
		page.vm.openEditDialog(row)
	} else {
		page.vm.openCreateDialog()
	}
	await flush()
	return { page, form: findForm(page.vm.$.subTree) }
}

const REQUIRED = {
	prohibition: ['primaryName', 'reason'],
	standingConsent: ['entityText', 'consentMethod'],
}

describe('policy rule form checks', () => {
	it('edits a publish-never rule without a severity at the schema default', async () => {
		const { page, form } = await openForm('prohibition', {
			id: 'p-8',
			primaryName: 'Piet',
			reason: 'Threat',
			matchRules: [{ type: 'bsn', value: '1' }],
		})
		expect(form.form.severity).toBe('medium')
		expect(form.severityOptions).toContain(form.form.severity)
		mockCalls.length = 0
		await clickSubmit()
		expect(mockCalls).toEqual([
			[
				'prohibitionStore',
				'updateProhibition',
				'p-8',
				expect.objectContaining({ severity: 'medium' }),
			],
		])
		page.unmount()
	})

	for (const [surface, fields] of Object.entries(REQUIRED)) {
		for (const field of fields) {
			it(`keeps ${surface} from saving without ${field}`, async () => {
				const { page, form } = await openForm(surface, null)
				SURFACES[surface].fill(form.form)
				await flush()
				expect(dialogView().buttons[1].disabled).toBe(false)
				form.form[field] =
					surface === 'standingConsent' && field === 'consentMethod'
						? ''
						: '   '
				await flush()
				expect(dialogView().buttons[1].disabled).toBe(true)
				page.unmount()
			})
		}
	}
})
