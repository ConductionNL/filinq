/**
 * The HTTP client for office templates, their bulk import and text
 * fragments (office-template-authoring). Every call answers
 * `{ ok: true, data }` or `{ ok: false, status, error }`.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { answer } from './apiAnswer.js'

/**
 * The statuses an import job ends in.
 */
export const FINISHED_IMPORT = ['completed', 'failed']

/**
 * A multipart body: the file as `file`, every other non-empty field as is.
 *
 * @param {File|Blob} file The file.
 * @param {object} fields The form fields.
 * @return {FormData}
 */
export function multipart(file, fields = {}) {
	const body = new FormData()
	if (file) {
		body.append('file', file)
	}
	for (const [key, value] of Object.entries(fields)) {
		if (value !== null && value !== undefined && String(value).trim() !== '') {
			body.append(key, String(value).trim())
		}
	}
	return body
}

/**
 * Upload a DOCX or ODT as a new office template.
 *
 * @param {File} file The file.
 * @param {object} fields name, namespace and the optional category, description, boundRegister, boundSchema.
 * @return {Promise<object>}
 */
export function uploadOfficeTemplate(file, fields) {
	return answer(axios.post(generateUrl('/apps/filinq/api/templates/office'), multipart(file, fields)))
}

/**
 * Give an office template a new source file, as a new version.
 *
 * @param {string} id The template's id.
 * @param {File} file The file.
 * @param {string} changelog What changed.
 * @return {Promise<object>}
 */
export function uploadNewSource(id, file, changelog = '') {
	return answer(axios.post(generateUrl('/apps/filinq/api/templates/{id}/office', { id }), multipart(file, { changelog })))
}

/**
 * Where the source DOCX of an office template downloads from.
 *
 * @param {string} id The template's id.
 * @return {string}
 */
export function sourceUrl(id) {
	return generateUrl('/apps/filinq/api/templates/{id}/office/source', { id })
}

/**
 * Where the PDF preview of an office template, through the conversion cascade, opens from.
 *
 * @param {string} id The template's id.
 * @return {string}
 */
export function previewUrl(id) {
	return generateUrl('/apps/filinq/api/templates/{id}/office/preview', { id })
}

/**
 * Save which data path each merge tag reads; an empty path removes a mapping.
 *
 * @param {string} id The template's id.
 * @param {object} fieldMap Tag => data path.
 * @return {Promise<object>}
 */
export function saveFieldMap(id, fieldMap) {
	return answer(axios.put(generateUrl('/apps/filinq/api/templates/{id}/field-map', { id }), { fieldMap }))
}

/**
 * Start a bulk import of a ZIP, or of a folder in the user's Files.
 *
 * @param {File|null} file The ZIP, or null to import `fields.path`.
 * @param {object} fields namespace and the optional boundRegister, boundSchema, path.
 * @return {Promise<object>}
 */
export function startImport(file, fields) {
	return answer(axios.post(generateUrl('/apps/filinq/api/templates/import'), multipart(file, fields)))
}

/**
 * An import job's status, counts and per-file report.
 *
 * @param {string} jobId The job's id.
 * @return {Promise<object>}
 */
export function fetchImport(jobId) {
	return answer(axios.get(generateUrl('/apps/filinq/api/templates/import/{jobId}', { jobId })))
}

/**
 * The tags of a tag report that need attention: unknown ones, each with
 * the path the field mapping already gives it.
 *
 * @param {object} template The template (tagReport, fieldMap).
 * @return {Array<{tag: string, path: string}>}
 */
export function unknownTags(template) {
	const map = (template && template.fieldMap) || {}
	const unknown = (template && template.tagReport && template.tagReport.unknown) || []
	return unknown.map((tag) => ({ tag, path: typeof map[tag] === 'string' ? map[tag] : '' }))
}

/**
 * A field mapping from edited rows, without empty paths.
 *
 * @param {Array<{tag: string, path: string}>} rows The rows.
 * @param {object} current The mapping so far, kept for tags not in the rows.
 * @return {object}
 */
export function fieldMapFrom(rows, current = {}) {
	const map = { ...(current || {}) }
	for (const row of rows) {
		const path = (row.path || '').trim()
		if (path === '') {
			delete map[row.tag]
		} else {
			map[row.tag] = path
		}
	}
	return map
}
