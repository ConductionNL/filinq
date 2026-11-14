// @vitest-environment jsdom
/**
 * Golden: which wizard questions are asked, the question forms and their
 * problems, recorded on the implementation before the condition and form
 * rules were split out.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-1
 */

import { describe, it } from 'vitest'
import {
	missingKeys,
	overridingKeys,
	questionProblem,
	questionToForm,
	translateAnswers,
	visibleKeys,
} from '../../src/services/wizard.js'
import { golden } from './golden/golden.js'

const QUESTIONS = [
	{ key: 'kind', type: 'choice', required: true },
	{
		key: 'name',
		type: 'text',
		mapsTo: 'applicant.name',
		condition: { questionKey: 'kind', operator: 'equals', value: 'person' },
	},
	{
		key: 'org',
		type: 'text',
		condition: { questionKey: 'kind', operator: 'notEquals', value: 'person' },
	},
	{
		key: 'case',
		type: 'registerObject',
		register: 'r',
		schema: 'applicant',
		condition: { questionKey: 'org', operator: 'answered' },
	},
	{
		key: 'later',
		type: 'text',
		condition: { questionKey: 'after', operator: 'equals', value: 'x' },
	},
	{ key: 'after', type: 'text', required: true },
	{
		key: 'odd',
		type: 'text',
		condition: { questionKey: 'kind', operator: 'bogus', value: 'x' },
	},
	{ key: 'none', type: 'text', condition: 'not an object' },
	{
		key: 'blank',
		type: 'text',
		condition: { questionKey: '', operator: 'equals' },
	},
	{
		key: 'hidden',
		type: 'text',
		condition: { questionKey: 'name', operator: 'answered' },
	},
	{
		key: 'numeric',
		type: 'text',
		mapsTo: 'n',
		condition: { questionKey: 'count', operator: 'equals', value: 3 },
	},
	{ key: 'count', type: 'text' },
	{
		key: 'nullish',
		type: 'text',
		condition: { questionKey: 'kind', operator: 'equals' },
	},
	{
		key: 'notnull',
		type: 'text',
		condition: { questionKey: 'kind', operator: 'notEquals' },
	},
	{
		key: 'num2',
		type: 'text',
		condition: { questionKey: 'count', operator: 'equals', value: 3 },
	},
	{
		key: 'num3',
		type: 'text',
		condition: { questionKey: 'count', operator: 'notEquals', value: 3 },
	},
]

const ANSWER_SETS = [
	{},
	{ kind: 'person' },
	{ kind: 'person', name: 'Jan' },
	{ kind: 'company', org: 'ACME', case: 'c-1' },
	{ kind: ['a'], org: '   ', after: 'x' },
	{ kind: [], org: 0, after: '' },
	{ kind: null, count: '3', numeric: 'yes' },
	{ kind: '', count: 3, numeric: 'n', applicant: 1 },
	{ kind: 'person', name: 'Piet', case: 'c', org: 'O' },
]

const FORMS = [
	{},
	{ key: '1bad', label: 'Q' },
	{ key: 'k', label: ' ' },
	{ key: 'used', label: 'Q' },
	{ key: ' ok ', label: 'Q', type: 'choice', choicesText: ' ' },
	{ key: 'ok', label: 'Q', type: 'choice', choicesText: 'a=A' },
	{ key: 'ok', label: 'Q', type: 'registerObject', register: 'r', schema: ' ' },
	{
		key: 'ok',
		label: 'Q',
		type: 'registerObject',
		register: '',
		schema: 's',
		mapsTo: 'bad path',
	},
	{ key: 'ok', label: 'Q', type: 'registerObject', register: 'r', schema: 's' },
	{ key: 'ok', label: 'Q', type: 'text', mapsTo: 'a.b_c.d1' },
	{ key: 'ok', label: 'Q', type: 'text', mapsTo: 'a..b' },
	{ key: 'ok', label: 'Q', type: 'date', mapsTo: ' 1a ' },
	{ key: 'ok', label: 'Q', type: 'text', mapsTo: '  ' },
	{ key: 'a'.repeat(64), label: 'Q' },
	{ key: 'a'.repeat(65), label: 'Q' },
	{ key: 'x-y_z', label: 'Q', type: 'choice', choicesText: '' },
	{ key: ' used ', label: 'Q' },
	{
		key: 'ok',
		label: 'Q',
		type: 'registerObject',
		register: 'r',
		schema: 's',
		mapsTo: 'bad path',
	},
]

const BLANK_FORM = {
	key: '',
	label: '',
	type: 'text',
	choicesText: '',
	register: '',
	schema: '',
	mapsTo: '',
}

describe('wizard golden', () => {
	it('asks and translates the recorded way', () => {
		const wizard = { uuid: 'w', version: 2, questions: QUESTIONS }
		golden(
			'wizardAnswers',
			ANSWER_SETS.map((answers) => ({
				visible: visibleKeys(QUESTIONS, answers),
				missing: missingKeys(wizard, answers),
				overriding: overridingKeys(wizard, answers),
				payload: translateAnswers(wizard, answers),
			})).concat([
				{ visible: visibleKeys(undefined, undefined) },
				{ visible: visibleKeys(QUESTIONS, null) },
			]),
		)
	})

	it('builds forms and finds their problems the recorded way', () => {
		golden('wizardForms', {
			forms: [
				null,
				undefined,
				...QUESTIONS,
				{
					key: 'c',
					choices: [
						{ value: 'a', label: 'A' },
						{ value: 'b', label: 'B' },
					],
					condition: { questionKey: 'k', value: 0 },
					required: 'true',
				},
			].map(questionToForm),
			problems: FORMS.map((form) =>
				questionProblem({ ...BLANK_FORM, ...form }, ['used']),
			),
		})
	})
})
