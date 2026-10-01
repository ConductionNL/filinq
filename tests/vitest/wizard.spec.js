/**
 * The wizard runner's skip logic, answer translation, question forms and calls.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/guided-document-wizard/tasks.md#4-2
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
	formToQuestion,
	generateWithWizard,
	missingKeys,
	overridingKeys,
	prefillWizard,
	questionProblem,
	questionToForm,
	saveWizard,
	translateAnswers,
	visibleKeys,
} from '../../src/services/wizard.js'

const calls = []
let answer = () => Promise.resolve({ data: {} })
function record(method) {
	return (url, body, config) => {
		calls.push([method, url, body, config])
		return answer()
	}
}
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url, body) => record('get')(url, body),
		post: (url, body, config) => record('post')(url, body, config),
		put: (url, body) => record('put')(url, body),
		delete: (url) => record('delete')(url),
	},
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (path) => path }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))

// design.md's seed wizard, the same fixture as tests/unit/Service/Wizard/WizardDoubles.php.
const SEED = {
	uuid: 'wizard-1',
	version: '1.0.2',
	questions: [
		{
			key: 'dossier',
			label: 'Which dossier?',
			type: 'registerObject',
			required: true,
			register: 'filinq',
			schema: 'dossier',
		},
		{
			key: 'besluit',
			label: 'Decision?',
			type: 'choice',
			required: true,
			choices: [
				{ value: 'toegewezen', label: 'Granted' },
				{ value: 'afgewezen', label: 'Rejected' },
			],
			mapsTo: 'besluit.uitkomst',
		},
		{
			key: 'afwijzingsreden',
			label: 'Reason',
			type: 'text',
			required: true,
			mapsTo: 'besluit.afwijzingsreden',
			condition: {
				questionKey: 'besluit',
				operator: 'equals',
				value: 'afgewezen',
			},
		},
		{
			key: 'ingangsdatum',
			label: 'Effective date',
			type: 'date',
			required: true,
			mapsTo: 'besluit.ingangsdatum',
			condition: {
				questionKey: 'besluit',
				operator: 'equals',
				value: 'toegewezen',
			},
		},
	],
}

describe('wizard skip logic', () => {
	it('asks the rejection reason only after afgewezen, as the server does', () => {
		expect(visibleKeys(SEED.questions, { besluit: 'toegewezen' })).toEqual([
			'dossier',
			'besluit',
			'ingangsdatum',
		])
		expect(visibleKeys(SEED.questions, { besluit: 'afgewezen' })).toEqual([
			'dossier',
			'besluit',
			'afwijzingsreden',
		])
		expect(visibleKeys(SEED.questions, {})).toEqual(['dossier', 'besluit'])
		expect(missingKeys(SEED, { dossier: 'd', besluit: 'afgewezen' })).toEqual([
			'afwijzingsreden',
		])
	})

	it('shows a question whose condition cannot be evaluated', () => {
		const questions = SEED.questions.map((q) =>
			q.key === 'afwijzingsreden'
				? { ...q, condition: { ...q.condition, operator: 'greaterThan' } }
				: q,
		)
		expect(visibleKeys(questions, { besluit: 'toegewezen' })).toContain(
			'afwijzingsreden',
		)
	})
})

describe('wizard answer translation', () => {
	it('turns a picked object into a dataRef and scalars into adHocData, dropping hidden answers', () => {
		const payload = translateAnswers(SEED, {
			dossier: 'd-17',
			besluit: 'toegewezen',
			ingangsdatum: '2026-11-01',
			afwijzingsreden: 'stale',
		})
		expect(payload.dataRefs).toEqual([
			{ register: 'filinq', schema: 'dossier', id: 'd-17' },
		])
		expect(payload.adHocData).toEqual({
			besluit: { uitkomst: 'toegewezen', ingangsdatum: '2026-11-01' },
		})
		expect(payload.wizardContext).toEqual({
			wizardId: 'wizard-1',
			wizardVersion: '1.0.2',
			answers: {
				dossier: 'd-17',
				besluit: 'toegewezen',
				ingangsdatum: '2026-11-01',
			},
		})
	})

	it('marks a scalar answer written under the picked object as overriding', () => {
		const wizard = {
			...SEED,
			questions: [
				...SEED.questions,
				{
					key: 'titel',
					label: 'Title',
					type: 'text',
					mapsTo: 'dossier.title',
				},
			],
		}
		expect(
			overridingKeys(wizard, {
				dossier: 'd-17',
				besluit: 'toegewezen',
				titel: 'New',
			}),
		).toEqual(['titel'])
		expect(
			overridingKeys(wizard, { besluit: 'toegewezen', titel: 'New' }),
		).toEqual([])
	})
})

describe('wizard question form', () => {
	it('round-trips a choice question with a condition', () => {
		const question = {
			key: 'besluit',
			label: 'Decision?',
			type: 'choice',
			required: true,
			choices: [{ value: 'a', label: 'A' }],
			mapsTo: 'besluit.uitkomst',
			condition: { questionKey: 'dossier', operator: 'answered' },
		}
		expect(formToQuestion(questionToForm(question))).toEqual(question)
	})

	it('refuses a reused key, a bad path and a register object without schema', () => {
		const form = questionToForm({ key: 'dossier', label: 'Q', type: 'text' })
		expect(questionProblem(form, ['dossier'])).toBe(
			'Another question already uses this key.',
		)
		expect(questionProblem({ ...form, mapsTo: 'a..b' }, [])).toBe(
			'The data path needs names joined by dots, such as applicant.name.',
		)
		expect(
			questionProblem(
				{ ...form, type: 'registerObject', register: 'filinq' },
				[],
			),
		).toBe('Name the register and the schema to pick from.')
		expect(questionProblem(form, [])).toBe('')
	})
})

describe('wizard calls', () => {
	beforeEach(() => {
		calls.length = 0
		answer = () => Promise.resolve({ data: {} })
	})

	it('creates, updates and prefills on the wizard routes', async () => {
		await saveWizard({ name: 'W', questions: [] })
		await saveWizard({ uuid: 'w/1', version: '3', name: 'W', questions: [] })
		await prefillWizard('w1', {
			register: 'filinq',
			schema: 'dossier',
			objectId: 'd-17',
		})
		expect(calls.map((c) => [c[0], c[1]])).toEqual([
			['post', '/apps/filinq/api/wizards'],
			['put', '/apps/filinq/api/wizards/w%2F1'],
			['post', '/apps/filinq/api/wizards/w1/prefill'],
		])
		expect(calls[1][2]).toEqual({ name: 'W', questions: [] })
	})

	it('generates through the one generate endpoint with the translated payload', async () => {
		answer = () =>
			Promise.resolve({
				data: new Blob(['%PDF']),
				headers: {
					'content-disposition': 'attachment; filename="besluit.pdf"',
				},
			})
		const result = await generateWithWizard('tmpl-1', SEED, {
			dossier: 'd-17',
			besluit: 'toegewezen',
			ingangsdatum: '2026-11-01',
		})
		expect(result.ok).toBe(true)
		expect(result.filename).toBe('besluit.pdf')
		expect(calls).toHaveLength(1)
		const [method, url, body, config] = calls[0]
		expect([method, url]).toEqual([
			'post',
			'/apps/filinq/api/documents/generate',
		])
		expect(body.dataRefs).toEqual([
			{ register: 'filinq', schema: 'dossier', id: 'd-17' },
		])
		expect(body.options.wizardContext.answers.besluit).toBe('toegewezen')
		expect(config).toEqual({ responseType: 'blob' })
	})

	it('reads the per-question errors of a 422 that came back as a Blob', async () => {
		answer = () =>
			Promise.reject({
				response: {
					status: 422,
					data: new Blob([
						JSON.stringify({
							error: 'x',
							errors: {
								afwijzingsreden: 'This question needs an answer.',
							},
						}),
					]),
				},
			})
		const result = await generateWithWizard('tmpl-1', SEED, {
			dossier: 'd-17',
			besluit: 'afgewezen',
		})
		expect(result).toEqual({
			ok: false,
			status: 422,
			error: 'x',
			errors: { afwijzingsreden: 'This question needs an answer.' },
		})
	})
})
