/**
 * Document version service — thin client over the DocuDesk version endpoints,
 * which delegate to Nextcloud `files_versions`. No DocuDesk-owned version store.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-versions-detail-tab/specs/document-versions/spec.md
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * List the Nextcloud file versions of a document (newest first).
 *
 * @param {number} fileId The Nextcloud file id.
 * @param {object} [opts] Pagination options.
 * @param {number} [opts.limit] Maximum entries.
 * @param {number} [opts.offset] Entries to skip.
 * @return {Promise<{versions: Array}>} The version list.
 * @spec openspec/changes/document-versions-detail-tab/specs/document-versions/spec.md
 */
export async function listVersions(fileId, opts = {}) {
	const url = generateUrl('/apps/docudesk/api/documents/{fileId}/versions', { fileId })
	const { data } = await axios.get(url, { params: { limit: opts.limit || 50, offset: opts.offset || 0 } })
	return data
}

/**
 * Build the download URL for a specific version (0 = current).
 *
 * @param {number} fileId The Nextcloud file id.
 * @param {number} versionTimestamp The version timestamp.
 * @return {string} The download URL.
 * @spec openspec/changes/document-versions-detail-tab/specs/document-versions/spec.md
 */
export function versionDownloadUrl(fileId, versionTimestamp) {
	return generateUrl(
		'/apps/docudesk/api/documents/{fileId}/versions/{versionTimestamp}/download',
		{ fileId, versionTimestamp },
	)
}

/**
 * Restore a prior version (requires write access).
 *
 * @param {number} fileId The Nextcloud file id.
 * @param {number} versionTimestamp The version timestamp to restore.
 * @return {Promise<object>} The restore result.
 * @spec openspec/changes/document-versions-detail-tab/specs/document-versions/spec.md
 */
export async function restoreVersion(fileId, versionTimestamp) {
	const url = generateUrl(
		'/apps/docudesk/api/documents/{fileId}/versions/{versionTimestamp}/restore',
		{ fileId, versionTimestamp },
	)
	const { data } = await axios.post(url, {})
	return data
}
