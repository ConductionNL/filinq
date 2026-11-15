/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the template editor dialogs (merge field, conditional
 * section, wizard question) and the separator sheets dialog. Per phase
 * (empty, filled in, after the confirming click, done, refused, cancel) it
 * records the title, the text, the fields and each button's label, variant,
 * disabled flag and spinner, plus the calls and events. RECORD_GOLDEN=1
 * rewrites the golden file.
 */

const fs = require('fs')
const path = require('path')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) =>
		vars ? text + ' ' + JSON.stringify(vars) : text,
	translatePlural: (app, one, many, count) =>
		(count === 1 ? one : many) + ' #' + count,
}))
jest.mock('@nextcloud/vue', () =>
	require('../../tests/jest/dialogGolden.js').ncVueModule(),
)
jest.mock('@conduction/nextcloud-vue', () =>
	require('../../tests/jest/dialogGolden.js').cnVueModule(),
)

const {
	mockRelativeImports,
	recordPhases,
} = require('../../tests/jest/dialogGolden.js')

const KEEP = ['mergeFieldSuggestions', 'wizard']
const PARTIAL = {
	separatorSheets: ['listScanProfiles', 'renderSeparatorSheets'],
}
const PROFILE = { id: 'p-1', label: 'Post' }

const SITES = {
	mergeField: {
		file: './MergeFieldDialog.vue',
		props: {},
		events: ['close', 'insert'],
		fill: { fieldName: 'zaak.nummer' },
	},
	conditionalSection: {
		file: './ConditionalSectionDialog.vue',
		props: {},
		events: ['close', 'insert'],
		fill: {
			condField: 'zaak.type',
			condOp: { label: 'equals', value: 'equals' },
			condValue: 'bezwaar',
		},
	},
	wizardQuestionNew: {
		file: './WizardQuestionDialog.vue',
		props: { usedKeys: ['naam'] },
		events: ['close', 'save'],
		fill: (vm) => {
			vm.form.key = 'datum'
			vm.form.label = 'Datum'
		},
	},
	wizardQuestionTakenKey: {
		file: './WizardQuestionDialog.vue',
		props: { usedKeys: ['naam'] },
		events: ['close', 'save'],
		fill: (vm) => {
			vm.form.key = 'naam'
			vm.form.label = 'Naam'
		},
	},
	separatorSheets: {
		file: './SeparatorSheetsDialog.vue',
		props: {},
		events: ['close'],
		fill: { profile: PROFILE, caseNumbersText: 'Z-1\nZ-2' },
		ok: {
			listScanProfiles: [PROFILE, { id: 'p-2', label: 'Balie' }],
			renderSeparatorSheets: new Blob(['%PDF']),
		},
		refused: {
			listScanProfiles: [PROFILE],
			renderSeparatorSheets: {
				then: (resolve, reject) => reject(new Error('refused')),
			},
		},
	},
}

const GOLDEN = path.join(__dirname, '../../tests/jest/golden/template-dialogs.json')
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

for (const site of Object.values(SITES)) {
	mockRelativeImports(path.resolve(__dirname, site.file), KEEP, PARTIAL)
}

beforeAll(() => {
	URL.createObjectURL = () => 'blob:sheets'
	URL.revokeObjectURL = () => {}
	HTMLAnchorElement.prototype.click = () => {}
})

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('template dialogs golden', () => {
	for (const [name, site] of Object.entries(SITES)) {
		it(name, async () => {
			const component = require(site.file).default
			const actual = await recordPhases(component, {
				ok: {},
				refused: {},
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
