/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The "Fill in and sign" act: what the dialog sends and how it reads the
 * server's answer. The server checks every value (signing-field-validation
 * D3); the browser runs no checksum of its own and only shows the message
 * the server gives per field.
 */

/**
 * Does this folder entry have fields for the signer to fill in.
 *
 * @param {object} entry A signing folder entry.
 * @return {boolean}
 * @spec openspec/changes/archive/2026-11-25-signing-field-validation/tasks.md#task-3.2
 */
export function hasFieldsToFillIn(entry) {
	return Array.isArray(entry?.fields) && entry.fields.length > 0
}

/**
 * The body of the sign act: the signer record and every field's value by
 * placement position. A field left empty is sent as '' so the server can
 * say it is required.
 *
 * @param {string} signerId The signer record.
 * @param {Array<{placement: number}>} fields The signer's fields.
 * @param {object} values What was typed, by placement position.
 * @return {{signerId: string, fields: object}}
 * @spec openspec/changes/archive/2026-11-25-signing-field-validation/tasks.md#task-3.2
 */
export function signBody(signerId, fields, values) {
	const typed = {}
	for (const field of fields) {
		const value = values?.[field.placement]
		typed[field.placement] = typeof value === 'string' ? value : ''
	}
	return { signerId, fields: typed }
}

/**
 * The per-field messages of a refused sign act, by placement position.
 *
 * The server answers 422 with `fieldErrors`, a list or an object keyed by
 * placement position; both read the same here. Any other failure has no
 * field messages.
 *
 * @param {object} error The request error.
 * @return {object} Messages by placement position.
 * @spec openspec/changes/archive/2026-11-25-signing-field-validation/tasks.md#task-3.2
 */
export function fieldErrorsOf(error) {
	const data = error?.response?.data
	if (error?.response?.status !== 422 || !data || typeof data.fieldErrors !== 'object' || data.fieldErrors === null) {
		return {}
	}
	const errors = {}
	for (const [position, message] of Object.entries(data.fieldErrors)) {
		if (typeof message === 'string' && message !== '') {
			errors[position] = message
		}
	}
	return errors
}

/**
 * The general message of a failed sign act, shown above the fields.
 *
 * @param {object} error The request error.
 * @param {string} fallback The message when the server gave none.
 * @return {string}
 * @spec openspec/changes/archive/2026-11-25-signing-field-validation/tasks.md#task-3.2
 */
export function signErrorOf(error, fallback) {
	const message = error?.response?.data?.error
	return typeof message === 'string' && message !== '' ? message : fallback
}
