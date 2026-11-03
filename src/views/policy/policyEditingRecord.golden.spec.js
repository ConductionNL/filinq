/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the form values each policy surface's modal starts from:
 * per surface, the editing record of a full row, an empty row, a row with
 * falsy values, an inactive row and a row whose match rules carry extra keys.
 * Compared as JSON, so the key order is part of the record.
 * RECORD_GOLDEN=1 rewrites the golden file.
 */

const fs = require('fs')
const path = require('path')

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
	translatePlural: (app, one, many, count) => (count === 1 ? one : many),
}))
jest.mock('@nextcloud/vue', () => ({ NcActions: {}, NcActionButton: {} }))
jest.mock('@conduction/nextcloud-vue', () => ({
	CnIndexPage: {},
	CnStatsBlock: {},
	CnStatusBadge: {},
	CnConfirmDialog: {},
	indexPaging: {},
	recordFormDialog: {},
	recordDeletion: {},
}))
jest.mock('../../dialogs/PolicyRuleFormModal.vue', () => ({
	name: 'PolicyRuleFormModal',
}))
for (const icon of ['Delete', 'DotsHorizontal', 'Pencil']) {
	jest.mock('vue-material-design-icons/' + icon + '.vue', () => ({ name: 'Icon' }))
}
jest.mock('../../store/store.js', () => ({
	prohibitionStore: {},
	standingConsentStore: {},
}))

const PolicyRuleIndex = require('./PolicyRuleIndex.vue').default

const GOLDEN = path.join(
	__dirname,
	'../../../tests/jest/golden/policy-editing-record.json',
)
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

const ROWS = {
	full: {
		primaryName: 'Jan Jansen',
		entityText: 'Jan Jansen',
		entityType: 'ORGANIZATION',
		reason: 'Court order',
		legalAuthority: 'Rechtbank',
		caseReference: 'Z-1',
		severity: 'high',
		jurisdiction: 'NL',
		consentMethod: 'paper',
		consentDocument: 'doc-1',
		consentScope: 'all',
		legalBasis: 'art 6',
		validFrom: '2026-01-01',
		validUntil: '2027-01-01',
		active: true,
		matchRules: [{ type: 'bsn', value: '123456782' }],
		consentStatus: 'consent_withdrawn',
		publicationDecision: 'never',
		notificationStatus: 'sent',
		id: 'r-1',
	},
	empty: {},
	falsy: {
		primaryName: '',
		entityText: null,
		entityType: 0,
		severity: '',
		consentMethod: false,
		consentStatus: '',
		publicationDecision: null,
		notificationStatus: undefined,
		active: null,
		matchRules: 'not a list',
	},
	inactive: { active: false, matchRules: [] },
	extraRuleKeys: {
		active: 'yes',
		matchRules: [{ type: 'name', value: 'Jan', id: 7 }, { value: 'x' }],
	},
}

describe.each(['prohibition', 'standingConsent'])('%s editing record', (surface) => {
	const rules = PolicyRuleIndex.computed.rules.call({ surface })

	it.each(Object.keys(ROWS))('%s', (name) => {
		const row = ROWS[name]
		const record = PolicyRuleIndex.methods.toEditingRecord.call({ rules }, row)
		const key = surface + ': ' + name
		const actual = JSON.parse(JSON.stringify(record))
		if (RECORD) {
			golden[key] = { json: JSON.stringify(record), record: actual }
			return
		}
		expect(JSON.stringify(record)).toBe(golden[key].json)
		expect(record.matchRules).not.toBe(row.matchRules)
	})

	afterAll(() => {
		if (RECORD) {
			const previous = fs.existsSync(GOLDEN)
				? JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))
				: {}
			fs.writeFileSync(
				GOLDEN,
				JSON.stringify({ ...previous, ...golden }, null, '\t') + '\n',
			)
		}
	})
})
