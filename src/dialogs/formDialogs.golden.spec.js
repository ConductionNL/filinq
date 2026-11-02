/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the record form dialogs (publish never, publish always,
 * custom dictionary): the rendered template with child components stubbed,
 * and what each button emits, for a create, an edit and a save in flight.
 * RECORD_GOLDEN=1 rewrites the golden file.
 */

import fs from 'fs'
import path from 'path'
import CustomDictionaryFormDialog from './CustomDictionaryFormDialog.vue'
import ProhibitionFormModal from './ProhibitionFormModal.vue'
import StandingConsentFormModal from './StandingConsentFormModal.vue'
import { renderStubbed } from '../../tests/jest/renderGolden.js'

jest.mock(
	'@nextcloud/l10n',
	() => ({
		translate: (app, text, vars) =>
			vars ? text + ' ' + JSON.stringify(vars) : text,
	}),
	{ virtual: true },
)
jest.mock(
	'@nextcloud/vue',
	() => ({
		NcButton: {},
		NcCheckboxRadioSwitch: {},
		NcDialog: {},
		NcLoadingIcon: {},
		NcSelect: {},
		NcTextField: {},
	}),
	{ virtual: true },
)
jest.mock('vue-material-design-icons/Delete.vue', () => ({}))

const GOLDEN = path.join(__dirname, '../../tests/jest/golden/form-dialogs.json')
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

const RECORD_ROW = {
	primaryName: 'Jan Jansen',
	entityText: 'Jan Jansen',
	label: 'Woordenlijst',
	reason: 'Court order',
	consentMethod: 'paper',
	matchRules: [{ type: 'bsn', value: '123456782' }],
}

const DIALOGS = {
	prohibition: ProhibitionFormModal,
	standingConsent: StandingConsentFormModal,
	customDictionary: CustomDictionaryFormDialog,
}

const STATES = {
	create: { open: true, editingRecord: null, saving: false },
	edit: { open: true, editingRecord: RECORD_ROW, saving: false },
	saving: {
		open: true,
		editingRecord: RECORD_ROW,
		saving: true,
		formError: 'Save failed',
	},
	closed: { open: false, editingRecord: null, saving: false },
}

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('record form dialogs golden', () => {
	for (const [dialog, component] of Object.entries(DIALOGS)) {
		for (const [state, props] of Object.entries(STATES)) {
			it(`${dialog} ${state}`, async () => {
				const emitted = []
				const listeners = {}
				for (const event of ['onSubmit', 'onCancel', 'onUpdate:open']) {
					listeners[event] = (...args) => emitted.push([event, ...args])
				}
				const page = await renderStubbed(component, {
					props: { ...props, ...listeners },
					real: ['FormDialogActions'],
				})
				if (props.editingRecord && page.vm.resetForm) {
					page.vm.resetForm()
				}
				await page.vm.$nextTick()
				const actual = {
					html: page.html().split('><').join('>\n<').split('\n'),
				}
				actual.buttons = page.stubs
					.filter(
						(s) =>
							s.name === 'NcButton' && s.attrs.variant !== 'secondary',
					)
					.map((s) => {
						emitted.length = 0
						if (s.attrs.onClick) {
							s.attrs.onClick({ type: 'click' })
						}
						return {
							variant: s.attrs.variant ?? null,
							emitted: JSON.parse(JSON.stringify(emitted)),
						}
					})
				page.unmount()
				if (RECORD) {
					golden[dialog + '.' + state] = actual
				} else {
					expect(actual.html).toEqual(golden[dialog + '.' + state].html)
					expect(actual.buttons).toEqual(
						golden[dialog + '.' + state].buttons,
					)
				}
			})
		}
	}
})
