/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the form dialogs' footers: publish never, publish always,
 * custom dictionary (create and edit), new dossier and the dictionary term
 * import. Per phase (empty, filled in, after the confirming click, cancel,
 * and saving set by the parent) it records the title, the text, the fields
 * and each button's label, variant, disabled flag and spinner, plus the
 * events. RECORD_GOLDEN=1 rewrites the golden file.
 */

const fs = require('fs')
const path = require('path')

jest.mock(
	'@nextcloud/l10n',
	() => ({
		translate: (app, text, vars) =>
			vars ? text + ' ' + JSON.stringify(vars) : text,
		translatePlural: (app, one, many, count) =>
			(count === 1 ? one : many) + ' #' + count,
	}),
	{ virtual: true },
)
jest.mock(
	'@nextcloud/vue',
	() => require('../../tests/jest/dialogGolden.js').ncVueModule(),
	{ virtual: true },
)
jest.mock(
	'@conduction/nextcloud-vue',
	() => require('../../tests/jest/dialogGolden.js').cnVueModule(),
	{ virtual: true },
)
jest.mock('vue-material-design-icons/Delete.vue', () => ({}))

const {
	mockRelativeImports,
	recordPhases,
} = require('../../tests/jest/dialogGolden.js')

const KEEP = ['FormDialogActions']
const RULE = { type: 'bsn', value: '123456782' }
const POLICY_EVENTS = ['submit', 'cancel', 'update:open']

const SITES = {
	prohibitionCreate: {
		file: './PolicyRuleFormModal.vue',
		props: {
			surface: 'prohibition',
			open: true,
			editingRecord: null,
		},
		events: POLICY_EVENTS,
		fill: (vm) => {
			vm.form.primaryName = 'Jan Jansen'
			vm.form.reason = 'Court order'
			vm.form.matchRules.push({ ...RULE })
		},
		busyProps: { saving: true, formError: 'Save failed' },
	},
	prohibitionEdit: {
		file: './PolicyRuleFormModal.vue',
		props: {
			surface: 'prohibition',
			open: true,
			editingRecord: {
				primaryName: 'Jan Jansen',
				reason: 'Court order',
				matchRules: [RULE],
			},
		},
		events: POLICY_EVENTS,
		fill: (vm) => vm.resetForm(),
		busyProps: { saving: true },
	},
	standingConsentCreate: {
		file: './PolicyRuleFormModal.vue',
		props: {
			surface: 'standingConsent',
			open: true,
			editingRecord: null,
		},
		events: POLICY_EVENTS,
		fill: (vm) => {
			vm.form.entityText = 'Jan Jansen'
			vm.form.consentMethod = 'paper'
			vm.form.matchRules.push({ ...RULE })
		},
		busyProps: { saving: true },
	},
	standingConsentEdit: {
		file: './PolicyRuleFormModal.vue',
		props: {
			surface: 'standingConsent',
			open: true,
			editingRecord: {
				entityText: 'Jan Jansen',
				consentMethod: 'paper',
				matchRules: [RULE],
			},
		},
		events: POLICY_EVENTS,
		fill: (vm) => vm.resetForm(),
		busyProps: { saving: true },
	},
	customDictionaryCreate: {
		file: './CustomDictionaryFormDialog.vue',
		props: { editingRecord: null },
		events: ['submit', 'cancel'],
		fill: (vm) => {
			vm.form.label = 'Woordenlijst'
		},
		busyProps: { saving: true },
	},
	customDictionaryEdit: {
		file: './CustomDictionaryFormDialog.vue',
		props: { editingRecord: { label: 'Woordenlijst' } },
		events: ['submit', 'cancel'],
		fill: {},
		busyProps: { saving: true },
	},
	dossierCreate: {
		file: './DossierFormModal.vue',
		props: { open: true },
		events: ['submit', 'cancel', 'update:open'],
		fill: (vm) => {
			vm.form.name = 'Bouwvergunning'
		},
		busyProps: { saving: true },
	},
	dictionaryImport: {
		file: './CustomDictionaryImportDialog.vue',
		props: {},
		events: ['submit', 'cancel'],
		fill: { pastedContent: 'Zilverreiger\nKarekiet' },
		busyProps: { importing: true },
	},
}

const GOLDEN = path.join(
	__dirname,
	'../../tests/jest/golden/form-dialog-footers.json',
)
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

for (const site of Object.values(SITES)) {
	mockRelativeImports(path.resolve(__dirname, site.file), KEEP)
}

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('form dialog footers golden', () => {
	for (const [name, site] of Object.entries(SITES)) {
		it(name, async () => {
			const component = require(site.file).default
			const actual = await recordPhases(component, {
				ok: {},
				refused: {},
				keep: KEEP,
				...site,
			})
			if (RECORD) {
				golden[name] = actual
				return
			}
			for (const phase of Object.keys(golden[name])) {
				expect([phase, actual[phase]]).toEqual([phase, golden[name][phase]])
			}
		})
	}
})
