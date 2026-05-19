/* eslint-disable no-console */
import axios from '@nextcloud/axios'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

/**
 * Build the WebDAV URL for a file inside the current user's storage.
 *
 * @param {string} path Absolute path inside the user's storage (e.g. /DocuDesk/foo.pdf).
 * @return {string} Full WebDAV URL.
 */
export function buildWebdavUrl(path) {
	const user = getCurrentUser()
	if (!user) {
		throw new Error('User not authenticated')
	}
	const segments = path.split('/').map((s) => encodeURIComponent(s)).join('/')
	return generateRemoteUrl(`dav/files/${user.uid}${segments}`)
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
