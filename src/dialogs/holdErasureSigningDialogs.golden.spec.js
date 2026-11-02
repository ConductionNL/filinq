/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the dialogs that place or release a legal hold, record or
 * run a subject erasure, and send a contract for signature. Per phase (empty,
 * filled in, busy after the confirming click, done, refused, cancel) it
 * records the title, the text, the fields and each button's label, variant,
 * disabled flag and spinner, plus the calls made before and after the click
 * and the events. RECORD_GOLDEN=1 rewrites the golden file.
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

const {
	mockRelativeImports,
	recordPhases,
	recorder,
} = require('../../tests/jest/dialogGolden.js')

const KEEP = ['signerRows']
const PARTIAL = {
	legalHolds: ['createCase', 'releaseCase'],
	subjectErasures: ['createRequest', 'runRequest'],
}
const REFUSED = { ok: false, error: 'No rights' }

const SITES = {
	newLegalHold: {
		file: './NewLegalHoldDialog.vue',
		props: {},
		events: ['close', 'created'],
		fill: {
			name: ' Onderzoek ',
			reason: ' Rechtszaak ',
			caseReference: 'Z-1',
			documents: 'doc-1\ndoc-2',
		},
		ok: { createCase: { ok: true, data: { id: 'h-1' } } },
		refused: { createCase: REFUSED },
	},
	releaseLegalHold: {
		file: './ReleaseLegalHoldDialog.vue',
		props: { holdId: 'h-1', holdName: 'Onderzoek' },
		events: ['close', 'released'],
		fill: { reason: ' Zaak gesloten ' },
		ok: { releaseCase: { ok: true, data: { id: 'h-1' } } },
		refused: { releaseCase: REFUSED },
	},
	newSubjectErasure: {
		file: './NewSubjectErasureDialog.vue',
		props: {},
		events: ['close', 'created'],
		fill: {
			subject: ' Jan Jansen ',
			identifiers: 'Jansen\nJ. Jansen',
			ground: ' AVG 17 ',
		},
		ok: { createRequest: { ok: true, data: { id: 'e-1' } } },
		refused: { createRequest: REFUSED },
	},
	runSubjectErasure: {
		file: './RunSubjectErasureDialog.vue',
		props: { requestId: 'e-1', erasable: 4, refused: 1 },
		events: ['close', 'ran'],
		fill: {},
		ok: { runRequest: { ok: true, data: { erased: 4 } } },
		refused: { runRequest: REFUSED },
	},
	sendContractForSignature: {
		file: './SendContractForSignatureDialog.vue',
		props: {
			contractId: 'c-1',
			contractTitle: 'Huurcontract',
			documents: ['11', '12'],
		},
		events: ['close', 'sent'],
		fill: {
			signerRows: [
				{ displayName: 'Jan', email: ' jan@example.org ', userId: '' },
			],
		},
		ok: {
			createSigningRequest: { id: 'sr-1' },
			linkSigningRequest: { ok: true, data: { id: 'c-1' } },
		},
		refused: {
			createSigningRequest: { id: 'sr-1' },
			linkSigningRequest: REFUSED,
		},
	},
}

const GOLDEN = path.join(
	__dirname,
	'../../tests/jest/golden/hold-erasure-signing-dialogs.json',
)
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

for (const site of Object.values(SITES)) {
	mockRelativeImports(path.resolve(__dirname, site.file), KEEP, PARTIAL)
}
jest.doMock(path.resolve(__dirname, '../store/modules/signing.js'), () => ({
	useSigningStore: () => ({
		createSigningRequest: recorder('signing', 'createSigningRequest'),
	}),
}))

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('hold, erasure and signing dialogs golden', () => {
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
