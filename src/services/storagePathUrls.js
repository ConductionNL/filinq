/**
 * Links built from a Nextcloud-internal storage path such as
 * `/alice/files/Dossier/report.pdf`: the part after the first `files`
 * segment is the path the user sees.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { generateRemoteUrl, generateUrl } from '@nextcloud/router'

/**
 * The path segments after the first `files` segment.
 *
 * @param {string} storagePath Storage path, e.g. `/alice/files/a/b.pdf`.
 * @return {string[]|null} The user-relative segments, or null without a `files` segment.
 */
function userSegments(storagePath) {
	const parts = storagePath.split('/')
	const filesIndex = parts.indexOf('files')
	return filesIndex >= 0 ? parts.slice(filesIndex + 1) : null
}

/**
 * Files app link that opens the file's folder and scrolls to the file.
 *
 * @param {string} storagePath Storage path of the file.
 * @return {string} The link; the Files app root without a `files` segment.
 * @spec openspec/specs/dashboard/spec.md#requirement-nextcloud-dashboard-widgets-req-dash-02
 */
export function filesAppLink(storagePath) {
	const segments = userSegments(storagePath)
	if (segments === null) {
		return generateUrl('/apps/files')
	}
	const relativePath = '/' + segments.join('/')
	const dir = relativePath.substring(0, relativePath.lastIndexOf('/')) || '/'
	const file = relativePath.substring(relativePath.lastIndexOf('/') + 1)
	return generateUrl('/apps/files/?dir={dir}&scrollto={file}', { dir, file })
}

/**
 * WebDAV download URL with the user-relative path appended as it is.
 *
 * @param {string} storagePath Storage path of the file.
 * @return {string} The URL; the WebDAV root without a `files` segment.
 * @spec openspec/specs/dashboard/spec.md#requirement-nextcloud-dashboard-widgets-req-dash-02
 */
export function webdavDownloadUrl(storagePath) {
	const segments = userSegments(storagePath)
	if (segments === null) {
		return generateRemoteUrl('webdav')
	}
	return generateRemoteUrl('webdav') + '/' + segments.join('/')
}

/**
 * WebDAV download URL with every path segment URI-encoded, so a name holding
 * `?`, `#` or `&` stays part of the path.
 *
 * @param {string} storagePath Storage path of the file.
 * @return {string} The URL; '' without a path, the WebDAV root without a `files` segment.
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */
export function encodedWebdavDownloadUrl(storagePath) {
	if (!storagePath) {
		return ''
	}
	const segments = userSegments(storagePath)
	if (segments === null) {
		return generateRemoteUrl('webdav')
	}
	return (
		generateRemoteUrl('webdav')
		+ '/'
		+ segments.map(encodeURIComponent).join('/')
	)
}
