/**
 * Document drafts (generate-draft-and-attended-edit): a generation stopped
 * before it is stored. Opening one for editing and finishing it go to the
 * app's two routes; reading, saving and discarding are plain object calls to
 * OpenRegister, whose `private` scope keeps a draft to its maker (design D5).
 * Every save moves `expiresAt` 30 days on (D2).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { answer, refusal } from './apiAnswer.js'

export const REGISTER = 'filinq'

export const SCHEMA = 'documentDraft'

/** Days a draft is kept after its last save. */
export const DRAFT_TTL_DAYS = 30

/** The stages a draft can still be worked on in. */
export const OPEN_STAGES = ['answering', 'editing']

/** OpenRegister's metadata and the fields a page never writes back. */
const READ_ONLY_FIELDS = ['@self', 'id', 'uuid', 'finishedDocumentId']

/**
 * When a draft saved now expires.
 *
 * @param {Date} now The moment of the save.
 * @return {string} ISO 8601, 30 days on.
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
 */
export function expiresAt(now = new Date()) {
	return new Date(now.getTime() + DRAFT_TTL_DAYS * 24 * 60 * 60 * 1000).toISOString()
}

/**
 * The object API URL of the drafts, or of one draft.
 *
 * @param {string} [id] The draft.
 * @return {string} The URL.
 */
function objectUrl(id) {
	const base = generateUrl(`/apps/openregister/api/objects/${REGISTER}/${SCHEMA}`)
	return id ? `${base}/${encodeURIComponent(id)}` : base
}

/**
 * The draft's own fields, without OpenRegister's metadata.
 *
 * @param {object} draft The draft as read.
 * @return {object} The writable fields.
 */
function writable(draft) {
	return Object.fromEntries(
		Object.entries(draft || {}).filter(([key]) => !READ_ONLY_FIELDS.includes(key)),
	)
}

/**
 * The draft's id, wherever the object API put it.
 *
 * @param {object} draft The draft.
 * @return {string} The id, or ''.
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
 */
export function draftId(draft) {
	return String(draft?.id || draft?.uuid || draft?.['@self']?.id || '')
}

/**
 * Whether a draft can still be edited or answered.
 *
 * @param {object} draft The draft.
 * @return {boolean} False for a finished draft.
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
 */
export function isOpen(draft) {
	return OPEN_STAGES.includes(draft?.stage)
}

/**
 * Open a generation for editing: the server renders it and saves the draft.
 *
 * @param {object} request The generation.
 * @param {string} request.templateId The template.
 * @param {Array<object>} request.dataRefs The records to merge.
 * @param {object} request.options Format, output and, from a wizard, `wizardContext`.
 * @param {string} request.origin `correspondence`, `document` or `wizard`.
 * @return {Promise<object>} `{ ok, data: { id, stage, html, warnings, expiresAt } }`, or the refusal (409 for a plain-language template).
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
 */
export function createDraft({ templateId, dataRefs = [], options = {}, origin = 'document' }) {
	return answer(
		axios.post(generateUrl('/apps/filinq/api/documents/drafts'), {
			templateId,
			dataRefs,
			options,
			origin,
		}),
	)
}

/**
 * Read one draft. Another clerk's draft answers as not found (404).
 *
 * @param {string} id The draft.
 * @return {Promise<object>} `{ ok, data }` or the refusal.
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
 */
export function loadDraft(id) {
	return answer(axios.get(objectUrl(id)))
}

/**
 * Save changes to a draft and move its expiry 30 days on.
 *
 * @param {object} draft The draft as read.
 * @param {object} changes The fields that changed, such as `html`.
 * @param {Date} [now] The moment of the save.
 * @return {Promise<object>} `{ ok, data }` or the refusal.
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
 */
export function saveDraft(draft, changes, now = new Date()) {
	return answer(
		axios.put(objectUrl(draftId(draft)), {
			...writable(draft),
			...changes,
			expiresAt: expiresAt(now),
		}),
	)
}

/**
 * Discard a draft. Nothing was filed from it.
 *
 * @param {string} id The draft.
 * @return {Promise<object>} `{ ok }` or the refusal.
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
 */
export function discardDraft(id) {
	return answer(axios.delete(objectUrl(id)))
}

/**
 * Save a wizard run to finish later: the answers so far and the step it
 * stopped at, on a draft in stage `answering` (REQ-GDA-003).
 *
 * @param {object} run The run.
 * @param {object|null} run.draft The run's draft when it was saved before.
 * @param {string} run.templateId The template.
 * @param {string} run.templateName The template's name, for the drafts list.
 * @param {object} run.wizard The wizard.
 * @param {object} run.answers The answers so far.
 * @param {number} run.step The step to continue at.
 * @param {string} run.format The chosen format.
 * @param {Date} [now] The moment of the save.
 * @return {Promise<object>} `{ ok, data }` with the saved draft, or the refusal.
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.4
 */
export function saveWizardDraft({ draft = null, templateId, templateName = '', wizard, answers, step, format = 'pdf' }, now = new Date()) {
	const fields = {
		wizardId: String(wizard?.uuid || ''),
		wizardAnswers: { ...(answers || {}) },
		wizardStep: Math.max(0, Number(step) || 0),
		format,
	}
	if (draft && draftId(draft)) {
		return saveDraft(draft, fields, now)
	}
	return answer(
		axios.post(objectUrl(), {
			origin: 'wizard',
			stage: 'answering',
			templateId,
			templateName,
			...fields,
			expiresAt: expiresAt(now),
		}),
	)
}

/**
 * Where a draft continues: the wizard at its saved step for a run still
 * being answered, else the draft editor.
 *
 * @param {object} draft The draft.
 * @return {object} A router location.
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.4
 */
export function resumeRoute(draft) {
	if (draft?.stage === 'answering') {
		return {
			name: 'WizardRunner',
			params: { id: String(draft.templateId || '') },
			query: { draft: draftId(draft) },
		}
	}
	return { name: 'DraftEditor', params: { id: draftId(draft) } }
}

/**
 * The JSON an error or HTML answer carries, read from a blob response.
 *
 * @param {Blob|object|undefined} data The response body.
 * @return {Promise<object>} The parsed body, or {}.
 */
async function jsonBody(data) {
	try {
		const text = typeof data?.text === 'function' ? await data.text() : JSON.stringify(data ?? {})
		return JSON.parse(text || '{}')
	} catch {
		return {}
	}
}

/**
 * Finish a draft: the server files and logs the edited text and closes it.
 *
 * @param {string} id The draft.
 * @return {Promise<object>} `{ ok, data: Blob|null, json, filename }`, or
 *         `{ ok: false, status, error, errors }` (422 lists the wizard answers that did not pass).
 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
 */
export async function finishDraft(id) {
	try {
		const response = await axios.post(
			generateUrl(`/apps/filinq/api/documents/drafts/${encodeURIComponent(id)}/finish`),
			{},
			{ responseType: 'blob' },
		)
		const type = String(response.headers?.['content-type'] || '')
		if (type.includes('application/json')) {
			return { ok: true, data: null, json: await jsonBody(response.data), filename: '' }
		}
		const disposition = String(response.headers?.['content-disposition'] || '')
		const match = disposition.match(/filename="?([^";]+)"?/)
		return { ok: true, data: response.data, json: null, filename: match ? match[1] : 'document' }
	} catch (error) {
		const body = await jsonBody(error?.response?.data)
		return {
			...refusal(error),
			error: body.error || '',
			errors: body.errors || {},
		}
	}
}
