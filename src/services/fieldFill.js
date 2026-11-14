/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The calls behind model filling: whether it is available, a suggestion for
 * a record from one of its files, the clerk's decisions, and the admin's
 * switch and profiles (extraction-model-filled-fields).
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Whether model filling is switched on and a text model is there.
 *
 * @return {Promise<{enabled: boolean, provider: (string|null), available: boolean}>}
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
export async function fetchFieldFillStatus() {
	const { data } = await axios.get(generateUrl('/apps/filinq/api/extraction/fields/status'))
	return data
}

/**
 * Switch model filling on or off (admin).
 *
 * @param {boolean} enabled On or off.
 * @return {Promise<object>} The new status.
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
export async function setFieldFillEnabled(enabled) {
	const { data } = await axios.put(generateUrl('/apps/filinq/api/extraction/fields/status'), { enabled })
	return data
}

/**
 * Ask the model for the declared fields of a record from one of its files.
 *
 * @param {{fileId: number, register: string, schema: string, objectId: string}} target The file and the record.
 * @return {Promise<object>} The stored suggestion, with the record's current values.
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
export async function suggestFields(target) {
	const { data } = await axios.post(generateUrl('/apps/filinq/api/extraction/fields'), {
		fileId: target.fileId,
		register: target.register,
		schema: target.schema,
		objectId: target.objectId,
	})
	return data
}

/**
 * Send the clerk's decision per field.
 *
 * @param {string} id The suggestion.
 * @param {object} decisions Per field `{decision: 'accept'|'change'|'reject', value?}`.
 * @return {Promise<object>} The decided suggestion.
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
export async function decideFields(id, decisions) {
	const { data } = await axios.post(generateUrl('/apps/filinq/api/extraction/fields/{id}/decide', { id }), { decisions })
	return data
}

/**
 * The declared field fill profiles (admin).
 *
 * @return {Promise<Array<object>>}
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
export async function listFieldProfiles() {
	const { data } = await axios.get(generateUrl('/apps/filinq/api/extraction/field-profiles'))
	return Array.isArray(data?.results) ? data.results : []
}

/**
 * Create or update a field fill profile (admin).
 *
 * @param {object} profile `{declaringApp, register, schema, fields, active}`.
 * @param {string} [id] The profile to update; a new one without.
 * @return {Promise<object>} The saved profile.
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
export async function saveFieldProfile(profile, id = '') {
	if (id) {
		const { data } = await axios.put(generateUrl('/apps/filinq/api/extraction/field-profiles/{id}', { id }), profile)
		return data
	}
	const { data } = await axios.post(generateUrl('/apps/filinq/api/extraction/field-profiles'), profile)
	return data
}

/**
 * The id a stored object is known by.
 *
 * @param {object} row The object.
 * @return {string}
 */
export function idOf(row) {
	return String(row?.uuid || row?.id || row?.['@self']?.id || '')
}
