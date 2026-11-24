/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Open a document named by its Nextcloud file id, as a deep link does
 * (`/apps/filinq/my-documents?file=<id>`). The id is resolved through a
 * WebDAV SEARCH in the caller's own files, so a file the caller cannot read
 * resolves to nothing and filinq learns nothing about it.
 */
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { generateRemoteUrl } from '@nextcloud/router'

const DAV_NS = 'DAV:'
const OC_NS = 'http://owncloud.org/ns'

/**
 * The file id a route query names, or null when it names none.
 *
 * Only a positive whole number counts; anything else is ignored rather than
 * sent to the server.
 *
 * @param {object|null|undefined} query The route query.
 * @return {number|null} The file id.
 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md#requirement-unified-search-finds-document-content-through-openregister-req-wcs-004
 */
export function fileIdFromQuery(query) {
	const raw = Array.isArray(query?.file) ? query.file[0] : query?.file
	const text = String(raw ?? '').trim()
	if (/^[1-9]\d*$/.test(text) === false) {
		return null
	}
	const id = Number(text)
	return Number.isSafeInteger(id) ? id : null
}

/**
 * The WebDAV SEARCH body that finds one file by id in a user's files.
 *
 * @param {string} uid    The user id.
 * @param {number} fileId The file id.
 * @return {string} The XML body.
 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md#requirement-unified-search-finds-document-content-through-openregister-req-wcs-004
 */
export function fileSearchBody(uid, fileId) {
	return `<?xml version="1.0" encoding="UTF-8"?>
<d:searchrequest xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
	<d:basicsearch>
		<d:select>
			<d:prop>
				<d:displayname />
				<d:getcontenttype />
				<d:resourcetype />
				<oc:fileid />
			</d:prop>
		</d:select>
		<d:from>
			<d:scope>
				<d:href>/files/${encodeURIComponent(uid)}</d:href>
				<d:depth>infinity</d:depth>
			</d:scope>
		</d:from>
		<d:where>
			<d:eq>
				<d:prop>
					<oc:fileid />
				</d:prop>
				<d:literal>${Number(fileId)}</d:literal>
			</d:eq>
		</d:where>
		<d:limit>
			<d:nresults>1</d:nresults>
		</d:limit>
	</d:basicsearch>
</d:searchrequest>`
}

/**
 * The text of the first element with this namespace and name, or ''.
 *
 * @param {Element} element The parent element.
 * @param {string}  ns      The namespace.
 * @param {string}  name    The local name.
 * @return {string} The text.
 */
function textOf(element, ns, name) {
	return element.getElementsByTagNameNS(ns, name)[0]?.textContent?.trim() ?? ''
}

/**
 * The user-relative path of a SEARCH answer's href, e.g. `/DocuDesk/a.pdf`.
 *
 * @param {string} href The href, as the server answers it.
 * @param {string} uid  The user id.
 * @return {string|null} The path, or null when the href is outside the user's files.
 */
function userPath(href, uid) {
	const decoded = href.split('/').map((segment) => {
		try {
			return decodeURIComponent(segment)
		} catch {
			return segment
		}
	}).join('/')
	const marker = `/dav/files/${uid}/`
	const at = decoded.indexOf(marker)
	if (at === -1) {
		return null
	}
	const path = decoded.slice(at + marker.length - 1).replace(/\/+$/, '')
	return path.length > 1 ? path : null
}

/**
 * The viewer descriptor of the file a SEARCH answer names.
 *
 * @param {string} xml    The multistatus answer.
 * @param {string} uid    The user id.
 * @param {number} fileId The file id that was asked for.
 * @return {{fileId: number, fileName: string, mimeType: string, path: string}|null}
 *         The file, or null for no match, a folder or another id.
 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md#requirement-unified-search-finds-document-content-through-openregister-req-wcs-004
 */
export function descriptorFromSearch(xml, uid, fileId) {
	if (typeof xml !== 'string' || xml === '') {
		return null
	}
	const doc = new DOMParser().parseFromString(xml, 'application/xml')
	for (const response of doc.getElementsByTagNameNS(DAV_NS, 'response')) {
		if (Number(textOf(response, OC_NS, 'fileid')) !== Number(fileId)) {
			continue
		}
		if (response.getElementsByTagNameNS(DAV_NS, 'collection').length > 0) {
			return null
		}
		const path = userPath(textOf(response, DAV_NS, 'href'), uid)
		if (path === null) {
			return null
		}
		return {
			fileId: Number(fileId),
			fileName: textOf(response, DAV_NS, 'displayname') || path.split('/').pop(),
			mimeType: textOf(response, DAV_NS, 'getcontenttype'),
			path,
		}
	}
	return null
}

/**
 * Resolve a file id to the viewer descriptor, in the caller's own files.
 *
 * @param {number} fileId The file id.
 * @return {Promise<{fileId: number, fileName: string, mimeType: string, path: string}|null>}
 *         The file, or null when the caller has no such file or the search fails.
 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md#requirement-unified-search-finds-document-content-through-openregister-req-wcs-004
 */
export async function resolveFileById(fileId) {
	const user = getCurrentUser()
	if (!user || !fileId) {
		return null
	}
	try {
		const response = await axios({
			method: 'SEARCH',
			url: generateRemoteUrl('dav'),
			headers: { 'Content-Type': 'text/xml' },
			data: fileSearchBody(user.uid, fileId),
		})
		return descriptorFromSearch(response?.data, user.uid, fileId)
	} catch {
		return null
	}
}

/**
 * Open the viewer on the document a route query names.
 *
 * @param {object|null|undefined} query  The route query.
 * @param {{currentFile: object|null, open: function(object): void}} viewer The file viewer store.
 * @return {Promise<string>} `opened`; `missing` when the caller has no such file;
 *         `none` when the query names no file or it is already open.
 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md#requirement-unified-search-finds-document-content-through-openregister-req-wcs-004
 */
export async function openLinkedFile(query, viewer) {
	const fileId = fileIdFromQuery(query)
	if (fileId === null || Number(viewer.currentFile?.fileId) === fileId) {
		return 'none'
	}
	const file = await resolveFileById(fileId)
	if (file === null) {
		return 'missing'
	}
	viewer.open(file)
	return 'opened'
}
