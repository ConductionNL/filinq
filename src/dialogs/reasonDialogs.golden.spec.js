/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the dialogs that ask for a template, a record or a reason
 * before they act: generate a contract document, end a contract, assign an
 * intake document and give a final-document reason. Per phase (empty, filled
 * in, busy after the click, done, refused, cancel) it records the title, the
 * text, the fields and each button's label, variant, disabled flag and
 * spinner, plus the calls and events. RECORD_GOLDEN=1 rewrites the golden file.
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
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }), {
	virtual: true,
})

const {
	mockRelativeImports,
	recordPhases,
} = require('../../tests/jest/dialogGolden.js')

const SITES = {
	generateContractDocument: {
		file: './GenerateContractDocumentDialog.vue',
		props: { contractId: 'c-1', contractTitle: 'Huurcontract' },
		events: ['close', 'generated'],
		fill: { template: { id: 't-1', label: 'Standaard' } },
		ok: {
			listTemplates: { ok: true, data: [{ id: 't-1', name: 'Standaard' }] },
			generateDocument: { ok: true, data: { fileId: 'f-9' } },
		},
		refused: {
			listTemplates: { ok: true, data: [] },
			generateDocument: { ok: false, error: 'No rights' },
		},
	},
	terminateContract: {
		file: './TerminateContractDialog.vue',
		props: { contractId: 'c-1', contractTitle: 'Huurcontract' },
		events: ['close', 'terminated'],
		fill: { reason: '  Moved out  ' },
		ok: { terminateContract: { ok: true, data: { id: 'c-1' } } },
		refused: { terminateContract: { ok: false, error: 'No rights' } },
	},
	intakeAssign: {
		file: './IntakeAssignDialog.vue',
		props: { document: { subject: 'Brief' } },
		events: ['confirm', 'cancel'],
		fill: {
			register: 'r',
			schema: 's',
			selected: { id: 'rec-1', label: 'Zaak 1' },
		},
		ok: {},
		refused: {},
		busyProps: { saving: true, error: 'Refused' },
	},
	finalDocumentReason: {
		file: './FinalDocumentReasonDialog.vue',
		props: {
			name: 'Make final',
			explanation: 'Why?',
			placeholder: 'Reason',
			confirmLabel: 'Freeze',
		},
		events: ['confirm', 'cancel'],
		fill: { reason: '  Signed  ' },
		ok: {},
		refused: {},
	},
}

const GOLDEN = path.join(__dirname, '../../tests/jest/golden/reason-dialogs.json')
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

for (const site of Object.values(SITES)) {
	mockRelativeImports(path.resolve(__dirname, site.file))
}

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('reason dialogs golden', () => {
	for (const [name, site] of Object.entries(SITES)) {
		it(name, async () => {
			const component = require(site.file).default
			const actual = await recordPhases(component, site)
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
