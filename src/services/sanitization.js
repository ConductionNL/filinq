/**
 * Sanitization: the calls behind the Sanitize action and the report panel,
 * and the words for what was removed.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-sanitization/tasks.md#4-1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The URL of a file's sanitization route.
 *
 * @param {number|string} fileId The file.
 * @return {string} The URL.
 */
function url(fileId) {
	return generateUrl(`/apps/filinq/api/sanitization/${encodeURIComponent(fileId)}`)
}

/**
 * A refusal as a person reads it.
 *
 * @param {number} status The HTTP status.
 * @param {string} reason The server's reason.
 * @return {string} The sentence.
 * @spec openspec/changes/document-sanitization/tasks.md#4-1
 */
export function refusalMessage(status, reason) {
	if (reason === 'encrypted') {
		return t('filinq', 'This document is encrypted. Remove the password and try again.')
	}
	if (status === 404) {
		return t('filinq', 'This file is not there, or you cannot open it.')
	}
	if (status === 415) {
		return t('filinq', 'Only Word and OpenDocument text files can be sanitized.')
	}
	return t('filinq', 'The document could not be sanitized. Try again later.')
}

/**
 * Sanitize a file into a new file beside it.
 *
 * @param {number|string} fileId The file.
 * @return {Promise<object>} { ok, data } or { ok: false, status, error }.
 * @spec openspec/changes/document-sanitization/tasks.md#4-1
 */
export async function sanitizeFile(fileId) {
	try {
		const { data } = await axios.post(url(fileId))
		return { ok: true, data }
	} catch (error) {
		const status = error.response?.status || 0
		return {
			ok: false,
			status,
			error: refusalMessage(status, error.response?.data?.error),
		}
	}
}

/**
 * What earlier runs on a file removed, and whether it is a sanitized file.
 *
 * @param {number|string} fileId The file.
 * @return {Promise<object>} { ok, data } or { ok: false, status, error }.
 * @spec openspec/changes/document-sanitization/tasks.md#4-1
 */
export async function sanitizationStatus(fileId) {
	try {
		const { data } = await axios.get(url(fileId))
		return { ok: true, data }
	} catch (error) {
		const status = error.response?.status || 0
		return {
			ok: false,
			status,
			error: refusalMessage(status, error.response?.data?.error),
		}
	}
}

/**
 * The report as rows of label and count, leaving out categories with nothing removed.
 *
 * @param {object} report The report.
 * @return {Array<{key: string, label: string, count: number}>} The rows.
 * @spec openspec/changes/document-sanitization/tasks.md#4-1
 */
export function reportRows(report) {
	const labels = {
		commentsRemoved: t('filinq', 'Comments removed'),
		trackedChangesAccepted: t('filinq', 'Tracked changes accepted'),
		trackedChangesDropped: t('filinq', 'Tracked changes dropped'),
		revisionAttributesStripped: t('filinq', 'Revision marks removed'),
		hyperlinksFlattened: t('filinq', 'Links turned into text'),
		metadataFieldsScrubbed: t('filinq', 'Metadata fields cleared'),
		customXmlPartsDropped: t('filinq', 'Hidden data parts removed'),
		fieldCodesStripped: t('filinq', 'Field codes removed'),
		xmpNamespacesStripped: t('filinq', 'XMP metadata blocks removed'),
		annotationsRemoved: t('filinq', 'Annotations removed'),
		embeddedFilesRemoved: t('filinq', 'Embedded files removed'),
		scriptsRemoved: t('filinq', 'Scripts removed'),
	}
	return Object.keys(labels)
		.filter((key) => Number(report?.[key] || 0) > 0)
		.map((key) => ({ key, label: labels[key], count: Number(report[key]) }))
}
