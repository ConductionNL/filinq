/* eslint-disable no-console */
import axios from '@nextcloud/axios'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

/**
 * Build the WebDAV URL for a file inside the current user's storage.
 *
 * Accepts both user-relative paths (e.g. /DocuDesk/foo.pdf) and Nextcloud's
 * internal `Node::getPath()` format (e.g. /admin/files/DocuDesk/foo.pdf).
 * The internal prefix `/<uid>/files/` is stripped so backend endpoints that
 * forward the raw node path (upload, anonymise) don't need to know about
 * the DAV layout.
 *
 * @param {string} path Absolute path — user-relative or Nextcloud-internal.
 * @return {string} Full WebDAV URL.
 */
export function buildWebdavUrl(path) {
	const user = getCurrentUser()
	if (!user) {
		throw new Error('User not authenticated')
	}
	const normalised = toUserRelativePath(path, user.uid)
	const segments = normalised.split('/').map((s) => encodeURIComponent(s)).join('/')
	return generateRemoteUrl(`dav/files/${user.uid}${segments}`)
}

/**
 * Normalise a Nextcloud internal node path to the user-relative form used by
 * the WebDAV layout. `/admin/files/DocuDesk/foo.pdf` becomes `/DocuDesk/foo.pdf`;
 * already user-relative paths pass through unchanged.
 *
 * @param {string} path Raw path.
 * @param {string} uid  Current user id, used to match the internal prefix.
 * @return {string} User-relative path starting with `/`.
 */
function toUserRelativePath(path, uid) {
	if (typeof path !== 'string' || path.length === 0) {
		return '/'
	}
	const prefix = `/${uid}/files`
	if (path === prefix || path.startsWith(`${prefix}/`)) {
		return path.slice(prefix.length) || '/'
	}
	return path.startsWith('/') ? path : `/${path}`
}

/**
 * Fetch a file as an ArrayBuffer (for pdfjs / mammoth).
 *
 * @param {string} path Absolute path inside the user's storage.
 * @return {Promise<ArrayBuffer>}
 */
export async function fetchFileAsArrayBuffer(path) {
	const url = buildWebdavUrl(path)
	const response = await axios.get(url, { responseType: 'arraybuffer' })
	return response.data
}

/**
 * Fetch a file as plain text.
 *
 * @param {string} path Absolute path inside the user's storage.
 * @return {Promise<string>}
 */
export async function fetchFileAsText(path) {
	const url = buildWebdavUrl(path)
	const response = await axios.get(url, { responseType: 'text' })
	return response.data
}
