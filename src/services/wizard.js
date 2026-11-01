/**
 * Guided document wizards: the API calls and the runner's skip logic.
 *
 * `visibleKeys` and `translateAnswers` follow the server's WizardConditions
 * and WizardAnswers exactly; the server checks the answers again before it
 * generates, so these are for the runner's flow, not for trust.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

export const OPERATORS = ['equals', 'notEquals', 'answered']

export const QUESTION_TYPES = ['text', 'choice', 'date', 'registerObject']

/**
 * Whether an answer counts as given.
 *
 * @param {*} answer The answer.
 * @return {boolean} False for null, undefined, blank text and an empty list.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-2
 */
export function isAnswered(answer) {
	if (answer === null || answer === undefined) {
		return false
	}
	if (Array.isArray(answer)) {
		return answer.length > 0
	}
	return typeof answer !== 'string' || answer.trim() !== ''
}

/**
 * The keys of the questions that are asked, in order.
 *
 * A condition that cannot be evaluated shows the question; a question whose
 * trigger is not asked is not asked either.
 *
 * @param {Array<object>} questions The wizard's questions.
 * @param {object} answers The answers by key.
 * @return {Array<string>} The visible keys.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-2
 */
export function visibleKeys(questions, answers) {
	const visible = new Set()
	const earlier = new Set()
	const scope = { answers: answers || {}, earlier, visible }
	for (const question of questions || []) {
		if (isAsked(question, scope)) {
			visible.add(question.key)
		}
		earlier.add(question.key)
	}
	return [...visible]
}

/**
 * Whether one question is asked.
 *
 * @param {object} question The question.
 * @param {{answers: object, earlier: Set<string>, visible: Set<string>}} scope
 *        The answers, the keys before it and the keys asked so far.
 * @return {boolean} True when asked.
 */
function isAsked(question, scope) {
	const condition = question.condition
	if (!condition || typeof condition !== 'object') {
		return true
	}
	const on = condition.questionKey || ''
	if (!on || !scope.earlier.has(on) || !OPERATORS.includes(condition.operator)) {
		return true
	}
	return scope.visible.has(on) && conditionHolds(condition, scope.answers[on])
}

/**
 * Whether a condition holds for the answer to the question it is on.
 *
 * @param {{operator: string, value: (string|number|undefined)}} condition The condition.
 * @param {(string|number|Array<string>|null|undefined)} answer The answer it looks at.
 * @return {boolean}
 */
function conditionHolds(condition, answer) {
	if (condition.operator === 'answered') {
		return isAnswered(answer)
	}
	const same =
		answer !== undefined
		&& answer !== null
		&& String(answer) === String(condition.value ?? '')
	if (condition.operator === 'equals') {
		return isAnswered(answer) && same
	}
	return !same
}

/**
 * Write a value at a dotted path.
 *
 * @param {object} data The data, changed in place.
 * @param {string} path The dotted path.
 * @param {*} value The value.
 */
function setPath(data, path, value) {
	const segments = path.split('.')
	let node = data
	segments.slice(0, -1).forEach((segment) => {
		if (typeof node[segment] !== 'object' || node[segment] === null) {
			node[segment] = {}
		}
		node = node[segment]
	})
	node[segments[segments.length - 1]] = value
}

/**
 * Turn the answers into the generate request's dataRefs, adHocData and wizardContext.
 *
 * @param {object} wizard The wizard, with uuid and version.
 * @param {object} answers The answers by key.
 * @return {{dataRefs: Array<object>, adHocData: object, wizardContext: object}} The payload parts.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-3
 */
export function translateAnswers(wizard, answers) {
	const questions = wizard.questions || []
	const visible = new Set(visibleKeys(questions, answers))
	const dataRefs = []
	const adHocData = {}
	const kept = {}
	for (const question of questions) {
		const answer = answers[question.key]
		if (!visible.has(question.key) || !isAnswered(answer)) {
			continue
		}
		kept[question.key] = answer
		if (question.type === 'registerObject') {
			dataRefs.push({
				register: question.register,
				schema: question.schema,
				id: String(answer),
			})
		} else if (question.mapsTo) {
			setPath(adHocData, question.mapsTo, answer)
		}
	}
	return {
		dataRefs,
		adHocData,
		wizardContext: {
			wizardId: wizard.uuid || '',
			wizardVersion: String(wizard.version || ''),
			answers: kept,
		},
	}
}

/**
 * The scalar answers that replace data of a picked object (the review marks them).
 *
 * @param {object} wizard The wizard.
 * @param {object} answers The answers.
 * @return {Array<string>} The keys of the overriding answers.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
 */
export function overridingKeys(wizard, answers) {
	const questions = wizard.questions || []
	const visible = new Set(visibleKeys(questions, answers))
	const picked = new Set(
		questions
			.filter(
				(q) =>
					q.type === 'registerObject'
					&& visible.has(q.key)
					&& isAnswered(answers[q.key]),
			)
			.map((q) => q.schema),
	)
	return questions
		.filter(
			(q) =>
				q.type !== 'registerObject'
				&& q.mapsTo
				&& visible.has(q.key)
				&& isAnswered(answers[q.key]),
		)
		.filter((q) => picked.has(q.mapsTo.split('.')[0]))
		.map((q) => q.key)
}

/**
 * The required visible questions without an answer.
 *
 * @param {object} wizard The wizard.
 * @param {object} answers The answers.
 * @return {Array<string>} The keys.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
 */
export function missingKeys(wizard, answers) {
	const visible = new Set(visibleKeys(wizard.questions || [], answers))
	return (wizard.questions || [])
		.filter(
			(q) => visible.has(q.key) && q.required && !isAnswered(answers[q.key]),
		)
		.map((q) => q.key)
}

/**
 * The editable form of a question.
 *
 * @param {object|null} question The question, or null for a new one.
 * @return {object} The form.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-1
 */
export function questionToForm(question) {
	const q = question || {}
	return {
		key: q.key || '',
		label: q.label || '',
		helpText: q.helpText || '',
		type: q.type || 'text',
		required: q.required === true,
		choicesText: (q.choices || [])
			.map((c) => `${c.value}=${c.label}`)
			.join('\n'),
		register: q.register || '',
		schema: q.schema || '',
		mapsTo: q.mapsTo || '',
		...conditionForm(q.condition),
	}
}

/**
 * The condition fields of a question form.
 *
 * @param {object|undefined} condition The question's condition.
 * @return {{conditionKey: (string|null), conditionOperator: string, conditionValue: (string|number)}}
 */
function conditionForm(condition) {
	return {
		conditionKey: condition?.questionKey || null,
		conditionOperator: condition?.operator || 'equals',
		conditionValue: condition?.value || '',
	}
}

/**
 * The question a form describes, with only the fields its type uses.
 *
 * @param {object} form The form.
 * @return {object} The question.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-1
 */
export function formToQuestion(form) {
	const question = {
		key: form.key.trim(),
		label: form.label.trim(),
		type: form.type,
		required: form.required === true,
	}
	if (form.helpText.trim()) {
		question.helpText = form.helpText.trim()
	}
	if (form.type === 'choice') {
		question.choices = form.choicesText
			.split('\n')
			.map((line) => line.trim())
			.filter(Boolean)
			.map((line) => {
				const at = line.indexOf('=')
				return at > 0
					? {
							value: line.slice(0, at).trim(),
							label: line.slice(at + 1).trim(),
						}
					: { value: line, label: line }
			})
	}
	if (form.type === 'registerObject') {
		question.register = form.register.trim()
		question.schema = form.schema.trim()
	} else if (form.mapsTo.trim()) {
		question.mapsTo = form.mapsTo.trim()
	}
	if (form.conditionKey) {
		question.condition = {
			questionKey: form.conditionKey,
			operator: form.conditionOperator,
		}
		if (form.conditionOperator !== 'answered') {
			question.condition.value = form.conditionValue
		}
	}
	return question
}

const KEY_PATTERN = /^[a-zA-Z][a-zA-Z0-9_-]{0,63}$/

const DATA_PATH_PATTERN = /^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)*$/

/**
 * The rules a question form must meet, in the order they are reported.
 * Each problem is a function so the text is translated when it is shown.
 *
 * @type {Array<{fails: function(object, Array<string>): boolean, problem: function(): string}>}
 */
const QUESTION_RULES = [
	{
		fails: (form) => !form.label.trim(),
		problem: () => t('filinq', 'Write the question.'),
	},
	{
		fails: (form) => !KEY_PATTERN.test(form.key.trim()),
		problem: () =>
			t(
				'filinq',
				'The key needs letters, digits, - or _, starting with a letter.',
			),
	},
	{
		fails: (form, usedKeys) => usedKeys.includes(form.key.trim()),
		problem: () => t('filinq', 'Another question already uses this key.'),
	},
	{
		fails: (form) => form.type === 'choice' && !form.choicesText.trim(),
		problem: () => t('filinq', 'Add at least one choice.'),
	},
	{
		fails: (form) =>
			form.type === 'registerObject'
			&& (!form.register.trim() || !form.schema.trim()),
		problem: () => t('filinq', 'Name the register and the schema to pick from.'),
	},
	{
		fails: (form) =>
			form.type !== 'registerObject'
			&& form.mapsTo.trim() !== ''
			&& !DATA_PATH_PATTERN.test(form.mapsTo.trim()),
		problem: () =>
			t(
				'filinq',
				'The data path needs names joined by dots, such as applicant.name.',
			),
	},
]

/**
 * What keeps a question form from being kept, the same rules the server applies.
 *
 * @param {object} form The form.
 * @param {Array<string>} usedKeys The keys of the other questions.
 * @return {string} The problem, or ''.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-1
 */
export function questionProblem(form, usedKeys) {
	const rule = QUESTION_RULES.find((r) => r.fails(form, usedKeys))
	return rule ? rule.problem() : ''
}

/**
 * Run a call and answer { ok, data } or { ok: false, status, error, errors }.
 *
 * @param {Promise<object>} call The axios call.
 * @return {Promise<object>} The answer.
 */
async function answer(call) {
	try {
		const { data } = await call
		return { ok: true, data }
	} catch (error) {
		return {
			ok: false,
			status: error?.response?.status ?? 0,
			error: error?.response?.data?.error ?? '',
			errors: error?.response?.data?.errors ?? {},
		}
	}
}

/**
 * The active wizard of a template.
 *
 * @param {string} templateId The template.
 * @return {Promise<object>} { ok, data: { wizard } }.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#3-1
 */
export function loadTemplateWizard(templateId) {
	return answer(
		axios.get(
			generateUrl(
				`/apps/filinq/api/templates/${encodeURIComponent(templateId)}/wizard`,
			),
		),
	)
}

/**
 * Save a wizard.
 *
 * @param {object} wizard The definition; with uuid to change it.
 * @return {Promise<object>} { ok, data: { wizard, warnings } } or the refusal.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#3-1
 */
export function saveWizard(wizard) {
	const body = { ...wizard }
	delete body.uuid
	delete body.version
	if (wizard.uuid) {
		return answer(
			axios.put(
				generateUrl(
					`/apps/filinq/api/wizards/${encodeURIComponent(wizard.uuid)}`,
				),
				body,
			),
		)
	}
	return answer(axios.post(generateUrl('/apps/filinq/api/wizards'), body))
}

/**
 * Remove a wizard.
 *
 * @param {string} uuid The wizard.
 * @return {Promise<object>} { ok }.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#3-1
 */
export function deleteWizard(uuid) {
	return answer(
		axios.delete(
			generateUrl(`/apps/filinq/api/wizards/${encodeURIComponent(uuid)}`),
		),
	)
}

/**
 * The active wizards that ask for an object of this register and schema.
 *
 * @param {string} register The register.
 * @param {string} schema The schema.
 * @return {Promise<object>} { ok, data: { results } }.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-3
 */
export function listWizardsFor(register, schema) {
	return answer(
		axios.get(generateUrl('/apps/filinq/api/wizards'), {
			params: { register, schema },
		}),
	)
}

/**
 * Suggested answers for a run started from a register object.
 *
 * @param {string} uuid The wizard.
 * @param {object} entry { register, schema, objectId }.
 * @return {Promise<object>} { ok, data: { answers, unresolved } }.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-5
 */
export function prefillWizard(uuid, entry) {
	return answer(
		axios.post(
			generateUrl(
				`/apps/filinq/api/wizards/${encodeURIComponent(uuid)}/prefill`,
			),
			entry,
		),
	)
}

/**
 * Generate the document of a run through the one generate endpoint.
 *
 * @param {string} templateId The template.
 * @param {object} wizard The wizard.
 * @param {object} answers The answers.
 * @param {string} format pdf, odf, docx or html.
 * @return {Promise<object>} { ok, data } where data is the file as a Blob, or the refusal.
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
 */
export async function generateWithWizard(
	templateId,
	wizard,
	answers,
	format = 'pdf',
) {
	const payload = translateAnswers(wizard, answers)
	try {
		const response = await axios.post(
			generateUrl('/apps/filinq/api/documents/generate'),
			{
				templateId,
				dataRefs: payload.dataRefs,
				options: {
					format,
					adHocData: payload.adHocData,
					wizardContext: payload.wizardContext,
				},
			},
			{ responseType: 'blob' },
		)
		return {
			ok: true,
			data: response.data,
			filename: filenameOf(response.headers),
		}
	} catch (error) {
		const body = await readErrorBody(error?.response?.data)
		return {
			ok: false,
			status: error?.response?.status ?? 0,
			error: body.error || '',
			errors: body.errors || {},
		}
	}
}

/**
 * The file name a download response names.
 *
 * @param {object} headers The response headers.
 * @return {string} The name, or document.
 */
function filenameOf(headers) {
	const disposition = headers?.['content-disposition'] || ''
	const match = disposition.match(/filename="?([^";]+)"?/)
	return match ? match[1] : 'document'
}

/**
 * Read a JSON error body that arrived as a Blob.
 *
 * @param {*} data The response data.
 * @return {Promise<object>} The parsed body, or {}.
 */
async function readErrorBody(data) {
	try {
		const text =
			typeof data?.text === 'function'
				? await data.text()
				: JSON.stringify(data ?? {})
		return JSON.parse(text) || {}
	} catch {
		return {}
	}
}
