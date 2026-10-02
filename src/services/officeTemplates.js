/**
 * Office templates: the calls behind uploading a DOCX or ODT template, a new
 * source revision, the field mapping, the bulk ZIP import and the text
 * fragments (bouwstenen).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const TEMPLATES = '/apps/filinq/api/templates'
const FRAGMENTS = '/apps/filinq/api/fragments'

/**
 * Build the multipart body of an upload.
 *
 * @param {File} file The file.
 * @param {object} fields Text fields beside it; arrays and objects are sent as JSON.
 * @return {FormData} The body.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
 */
export function uploadBody(file, fields = {}) {
	const body = new FormData()
	body.append('file', file)
	for (const [key, value] of Object.entries(fields)) {
		if (value === null || value === undefined || value === '') {
			continue
		}
		body.append(
			key,
			typeof value === 'object' ? JSON.stringify(value) : String(value),
		)
	}
	return body
}

/**
 * Create an office template from a DOCX or ODT.
 *
 * @param {File} file The document.
 * @param {object} fields name, namespace, description, category, boundRegister, boundSchema.
 * @return {Promise<object>} {template, converted, tagReport}.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
 */
export async function uploadOfficeTemplate(file, fields) {
	const response = await axios.post(
		generateUrl(`${TEMPLATES}/office`),
		uploadBody(file, fields),
	)
	return response.data
}

/**
 * Upload a new source revision of an office template.
 *
 * @param {string} id The template.
 * @param {File} file The document.
 * @return {Promise<object>} {template, converted, tagReport}.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
 */
export async function replaceOfficeSource(id, file) {
	const response = await axios.post(
		generateUrl(`${TEMPLATES}/${encodeURIComponent(id)}/office`),
		uploadBody(file),
	)
	return response.data
}

/**
 * The download address of an office template's DOCX source.
 *
 * @param {string} id The template.
 * @return {string} The URL.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
 */
export function officeSourceUrl(id) {
	return generateUrl(`${TEMPLATES}/${encodeURIComponent(id)}/office`)
}

/**
 * Store the tag to property mapping of an office template.
 *
 * @param {string} id The template.
 * @param {object} fieldMap Tag name to property path.
 * @return {Promise<object>} The template with its rechecked tag report.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
 */
export async function saveFieldMap(id, fieldMap) {
	const response = await axios.put(
		generateUrl(`${TEMPLATES}/${encodeURIComponent(id)}/field-map`),
		{ fieldMap },
	)
	return response.data
}

/**
 * Start the import of a ZIP of templates and fragments.
 *
 * @param {File} file The ZIP.
 * @param {object} fields namespace, boundRegister, boundSchema.
 * @return {Promise<object>} The queued import job.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
 */
export async function startImport(file, fields) {
	const response = await axios.post(
		generateUrl(`${TEMPLATES}/import`),
		uploadBody(file, fields),
	)
	return response.data
}

/**
 * The state and report of an import job.
 *
 * @param {string} jobId The job.
 * @return {Promise<object>} The job.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
 */
export async function importStatus(jobId) {
	const response = await axios.get(
		generateUrl(`${TEMPLATES}/import/${encodeURIComponent(jobId)}`),
	)
	return response.data
}

/**
 * Whether an import job is still busy.
 *
 * @param {object|null} job The job.
 * @return {boolean} True while queued or running.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
 */
export function importBusy(job) {
	return job !== null && ['queued', 'running'].includes(job.status)
}

/**
 * The tags of a template that still need a mapping.
 *
 * @param {object} template The template.
 * @return {Array<string>} The unknown tags.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
 */
export function unknownTags(template) {
	return template?.tagReport?.unknown ?? []
}

/**
 * List text fragments.
 *
 * @param {string} namespace Only this namespace, or '' for all.
 * @return {Promise<Array<object>>} The fragments.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
 */
export async function listFragments(namespace = '') {
	const response = await axios.get(generateUrl(FRAGMENTS), {
		params: namespace ? { namespace } : {},
	})
	return response.data.results ?? []
}

/**
 * Create or update a text fragment.
 *
 * @param {object} fragment The fragment; with a uuid it is updated.
 * @return {Promise<object>} The stored fragment.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
 */
export async function saveFragment(fragment) {
	const { uuid, ...fields } = fragment
	if (uuid) {
		return (
			await axios.put(
				generateUrl(`${FRAGMENTS}/${encodeURIComponent(uuid)}`),
				fields,
			)
		).data
	}
	return (await axios.post(generateUrl(FRAGMENTS), fields)).data
}

/**
 * Delete a text fragment.
 *
 * @param {string} uuid The fragment.
 * @return {Promise<void>}
 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
 */
export async function deleteFragment(uuid) {
	await axios.delete(generateUrl(`${FRAGMENTS}/${encodeURIComponent(uuid)}`))
}

/**
 * The error sentence of a refused call.
 *
 * @param {Error} error The axios error.
 * @param {string} fallback The sentence when the server gave none.
 * @return {string} The sentence.
 * @spec openspec/changes/office-template-authoring/tasks.md#4-1
 */
export function errorText(error, fallback) {
	return error?.response?.data?.error ?? fallback
}
