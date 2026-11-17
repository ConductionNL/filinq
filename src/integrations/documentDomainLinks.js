// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.
//
// Link a document to another record, or remove its link to one, over
// `POST` / `DELETE api/case-documents/{uuid}/domains`, and copy a document to
// another record over `POST api/case-documents/copy`. The server checks that
// the caller may write the record before it stores anything and answers 403
// when they may not.

import { generateUrl } from '@nextcloud/router'

/**
 * The record a link form names, or null while one part is missing.
 *
 * @param {{register?: string, schema?: string, id?: string}} form The form fields.
 * @return {{register: string, schema: string, id: string}|null} The record, trimmed.
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.5
 */
export function linkTarget(form) {
	const target = {
		register: String(form?.register ?? '').trim(),
		schema: String(form?.schema ?? '').trim(),
		id: String(form?.id ?? '').trim(),
	}
	if (target.register === '' || target.schema === '' || target.id === '') {
		return null
	}
	return target
}

/**
 * The route that links or unlinks one record of one document.
 *
 * @param {string} uuid The document record.
 * @param {{register: string, schema: string, id: string}} target The record.
 * @return {string} The URL with the record as query parameters.
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.5
 */
export function domainLinkUrl(uuid, target) {
	const query = new URLSearchParams({ register: target.register, schema: target.schema, id: target.id })
	return generateUrl('/apps/filinq/api/case-documents/{uuid}/domains', { uuid }) + '?' + query.toString()
}

/**
 * Link a document to a record, or remove the link.
 *
 * @param {'POST'|'DELETE'} method POST links, DELETE unlinks.
 * @param {string} uuid The document record.
 * @param {{register: string, schema: string, id: string}} target The record.
 * @param {string} requestToken The page's request token.
 * @return {Promise<{ok: boolean, status: number, error: string}>} What the server answered.
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.5
 */
export async function changeDomainLink(method, uuid, target, requestToken) {
	return send(domainLinkUrl(uuid, target), method, requestToken)
}

/**
 * Copy one file of a record to another record. The server checks read on the
 * source and write on the target before it writes anything, and answers 403
 * when either fails.
 *
 * @param {number} fileId The file on the source record.
 * @param {{register: string, schema: string, id: string}} source The record it is on.
 * @param {{register: string, schema: string, id: string}} target The record to copy it to.
 * @param {string} requestToken The page's request token.
 * @return {Promise<{ok: boolean, status: number, error: string}>} What the server answered.
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
 */
export async function copyToRecord(fileId, source, target, requestToken) {
	const query = new URLSearchParams({
		fileId: String(fileId),
		sourceRegister: source.register,
		sourceSchema: source.schema,
		sourceId: source.id,
		register: target.register,
		schema: target.schema,
		id: target.id,
	})
	return send(generateUrl('/apps/filinq/api/case-documents/copy') + '?' + query.toString(), 'POST', requestToken)
}

/**
 * Send one request and say how it went, without throwing.
 *
 * @param {string} url The URL.
 * @param {string} method The HTTP method.
 * @param {string} requestToken The page's request token.
 * @return {Promise<{ok: boolean, status: number, error: string}>} What the server answered.
 */
async function send(url, method, requestToken) {
	try {
		const response = await fetch(url, {
			method,
			headers: { requesttoken: requestToken, Accept: 'application/json' },
		})
		if (response.ok) {
			return { ok: true, status: response.status, error: '' }
		}
		let error = ''
		try {
			error = String((await response.json())?.error ?? '')
		} catch {
			error = ''
		}
		return { ok: false, status: response.status, error }
	} catch {
		return { ok: false, status: 0, error: '' }
	}
}
