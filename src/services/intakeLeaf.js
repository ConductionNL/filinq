/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * What the document intake leaf does on another app's record: list the
 * documents waiting in filinq's inbox, put those whose source reference names
 * this record first, and assign one to this record through filinq's own route.
 * The leaf never calls the host app (ADR-066 decision 2).
 *
 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Order the waiting documents for one host record: documents whose source
 * reference names the record (its id or its reference, such as a case number
 * printed on a separator sheet) first, each marked, the rest in their order.
 *
 * @param {Array<object>} documents The waiting intake documents.
 * @param {{objectId?: string, reference?: string}} host The host record.
 * @return {Array<object>} The documents, each with `matchesHost`.
 * @spec openspec/changes/scan-intake-with-separator-sheets/tasks.md#task-4.1
 */
export function orderForHost(documents, host) {
	const names = [host?.objectId, host?.reference]
		.map((value) => String(value ?? '').trim())
		.filter((value) => value !== '')
	const marked = (Array.isArray(documents) ? documents : []).map((document) => {
		const reference = String(document?.sourceRef ?? '').trim()
		return {
			...document,
			matchesHost: reference !== '' && names.includes(reference),
		}
	})
	return [
		...marked.filter((row) => row.matchesHost),
		...marked.filter((row) => !row.matchesHost),
	]
}

/**
 * Assign one waiting document to the host record.
 *
 * @param {string} uuid The intake document.
 * @param {{register: string, schema: string, objectId: string}} host The host record.
 * @return {Promise<object>} The assigned document.
 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
 */
export async function assignToHost(uuid, host) {
	const url = generateUrl('/apps/filinq/api/intake/documents/{uuid}/assign', {
		uuid,
	})
	const { data } = await axios.post(url, {
		register: host?.register || '',
		schema: host?.schema || '',
		id: host?.objectId || '',
	})
	return data
}
