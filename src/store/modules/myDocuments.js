/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

/**
 * URL-encode each segment of a path while preserving the `/` separators.
 *
 * WebDAV URLs are built by interpolating user-supplied names (dossier folders,
 * file names). Without encoding, a name containing URL-significant characters
 * such as `?`, `#` or `&` is misread by the browser/axios — e.g. `?` starts the
 * query string, so a PROPFIND/DELETE silently targets the wrong path and 404s.
 * Encoding per segment (leaving `/` intact) makes every valid Nextcloud name
 * round-trip correctly.
 *
 * @param {string} path Raw path (segments separated by `/`).
 * @return {string} Path with each segment URL-encoded.
 */
function encodeDavPath(path) {
	return String(path || '').split('/').map(encodeURIComponent).join('/')
}

/*
 * Each document entry:
 * {
 *   fileId: number,              - Nextcloud file id.
 *   fileName: string,            - File name including extension.
 *   mimeType: string,            - MIME type.
 *   fileSize: number,            - Size in bytes.
 *   modified: string | number,   - Timestamp of last modification.
 *   isFolder: boolean,           - True for folders/dossiers.
 *   isAnonymized: boolean,       - True when the file name contains '_anonymized'.
 * }
 */

export const useMyDocumentsStore = defineStore(
	'myDocuments',
	{
		state: () => ({
			documents: [],
			loading: false,
			error: null,
			total: 0,
			currentPath: '/DocuDesk',
			breadcrumbs: [{ name: 'DocuDesk', path: '/DocuDesk' }],
		}),
		getters: {
			/**
			 * Aggregate stats for the stats blocks on the My Documents page.
			 *
			 * @param {object} state Store state.
			 * @return {{ total: number, entitiesDetected: number, highRisk: number }}
			 */
			documentStats: (state) => {
				const total = state.documents.length
				const entitiesDetected = state.documents.reduce((sum, f) => sum + (f.entityCount || 0), 0)
				const highRisk = state.documents.filter((f) => f.riskLevel === 'high' || f.riskLevel === 'very_high').length
				return { total, entitiesDetected, highRisk }
			},
		},
		actions: {
			/**
			 * Fetch files and folders from the current path using Nextcloud WebDAV API.
			 *
			 * @param {string|null} path Optional path to navigate to. Defaults to currentPath.
			 * @return {Promise<void>}
			 */
			async fetchDocuments(path = null) {
				this.loading = true
				this.error = null
				try {
					const targetPath = path || this.currentPath
					const user = getCurrentUser()
					if (!user) {
						throw new Error('User not authenticated')
					}

					// Use Nextcloud WebDAV API to list folder contents. Encode the
					// path so names with `?`/`#`/`&` don't break the request URL.
					const webdavUrl = generateRemoteUrl(`dav/files/${user.uid}${encodeDavPath(targetPath)}`)

					const response = await axios({
						method: 'PROPFIND',
						url: webdavUrl,
						headers: {
							Depth: '1',
							'Content-Type': 'application/xml',
						},
						data: `<?xml version="1.0"?>
							<d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
								<d:prop>
									<d:resourcetype />
									<d:getcontenttype />
									<d:getcontentlength />
									<d:getlastmodified />
									<oc:fileid />
									<oc:size />
								</d:prop>
							</d:propfind>`,
					})

					// Parse WebDAV XML response
					const parser = new DOMParser()
					const xmlDoc = parser.parseFromString(response.data, 'text/xml')
					const responses = xmlDoc.querySelectorAll('response')

					this.documents = []
					responses.forEach((resp, index) => {
						// Skip first response (it's the folder itself)
						if (index === 0) {
							return
						}

						const href = resp.querySelector('href')?.textContent || ''
						const resourceType = resp.querySelector('resourcetype')
						const isFolder = resourceType?.querySelector('collection') !== null
						const mimeType = resp.querySelector('getcontenttype')?.textContent || (isFolder ? 'httpd/unix-directory' : 'application/octet-stream')
						const fileSize = parseInt(resp.querySelector('getcontentlength')?.textContent || resp.querySelector('size')?.textContent || '0', 10)
						const modified = resp.querySelector('getlastmodified')?.textContent || ''
						const fileId = parseInt(resp.querySelector('fileid')?.textContent || '0', 10)

						// Extract filename from href (strip trailing slash for folders first)
						const hrefWithoutTrailingSlash = href.endsWith('/') ? href.slice(0, -1) : href
						const fileName = decodeURIComponent(hrefWithoutTrailingSlash.split('/').pop() || '')

						if (fileName) {
							this.documents.push({
								fileId,
								fileName,
								mimeType,
								fileSize,
								modified: new Date(modified).getTime() / 1000,
								isFolder,
								isAnonymized: fileName.includes('_anonymized'),
							})
						}
					})

					// Sort by modified date (newest first)
					this.documents.sort((a, b) => b.modified - a.modified)

					this.total = this.documents.length

					// Update current path and breadcrumbs if navigating
					if (path && path !== this.currentPath) {
						this.navigateTo(path)
					}
				} catch (err) {
					console.error('Failed to fetch documents:', err)
					this.error = err.message || 'Failed to load documents'
				} finally {
					this.loading = false
				}
			},

			/**
			 * Delete a file or dossier from the current folder via WebDAV.
			 *
			 * A WebDAV DELETE on a collection (folder/dossier) removes the folder
			 * and everything inside it recursively, so deleting a dossier wipes its
			 * documents too. After a successful delete the current folder is
			 * re-fetched so the list reflects the removal.
			 *
			 * @param {string} fileName Name of the file/folder inside currentPath.
			 * @return {Promise<void>}
			 */
			async deleteDocument(fileName) {
				const user = getCurrentUser()
				if (!user) {
					throw new Error('User not authenticated')
				}

				const targetPath = `${this.currentPath}/${fileName}`
				const webdavUrl = generateRemoteUrl(`dav/files/${user.uid}${encodeDavPath(targetPath)}`)

				await axios({
					method: 'DELETE',
					url: webdavUrl,
				})

				await this.fetchDocuments()
			},

			/**
			 * Delete several files/dossiers from the current folder in one go
			 * (bulk delete). Each WebDAV DELETE on a dossier removes it and its
			 * contents recursively. The folder is re-fetched once after all
			 * deletes settle, regardless of individual failures.
			 *
			 * @param {string[]} fileNames Names of files/folders inside currentPath.
			 * @return {Promise<string[]>} Names that failed to delete (empty on full success).
			 */
			async deleteDocuments(fileNames) {
				const user = getCurrentUser()
				if (!user) {
					throw new Error('User not authenticated')
				}

				const results = await Promise.allSettled(
					fileNames.map((fileName) => axios({
						method: 'DELETE',
						url: generateRemoteUrl(`dav/files/${user.uid}${encodeDavPath(`${this.currentPath}/${fileName}`)}`),
					})),
				)

				await this.fetchDocuments()

				return fileNames.filter((_, i) => results[i].status === 'rejected')
			},

			/**
			 * Navigate to a specific folder path.
			 *
			 * @param {string} path The folder path to navigate to.
			 */
			navigateTo(path) {
				this.currentPath = path

				// Build breadcrumbs from path
				const parts = path.split('/').filter(Boolean)
				this.breadcrumbs = [{ name: 'DocuDesk', path: '/DocuDesk' }]

				let currentPath = '/DocuDesk'
				for (let i = 1; i < parts.length; i++) {
					currentPath += `/${parts[i]}`
					this.breadcrumbs.push({
						name: parts[i],
						path: currentPath,
					})
				}
			},

			/**
			 * Navigate into a subfolder.
			 *
			 * @param {string} folderName The name of the folder to enter.
			 */
			async openFolder(folderName) {
				const newPath = `${this.currentPath}/${folderName}`
				await this.fetchDocuments(newPath)
			},

			/**
			 * Fetch the most-recent anonymized files and dossier folders under
			 * /DocuDesk/, sorted newest first. Does NOT mutate store state —
			 * intended for read-only widgets (e.g. the dashboard "Recent
			 * documents" cards) that must not clobber the currentPath /
			 * breadcrumbs of the My Documents page.
			 *
			 * Uses PROPFIND Depth: infinity so anonymized files inside dossier
			 * folders are included too.
			 *
			 * @param {number} [limit] Maximum number of items to return (default 4).
			 * @return {Promise<object[]>} Items shaped like the documents array.
			 */
			async fetchRecentAnonymized(limit = 4) {
				const user = getCurrentUser()
				if (!user) {
					throw new Error('User not authenticated')
				}

				const davPrefix = `/remote.php/dav/files/${user.uid}`
				const webdavUrl = generateRemoteUrl(`dav/files/${user.uid}/DocuDesk`)

				let response
				try {
					response = await axios({
						method: 'PROPFIND',
						url: webdavUrl,
						headers: {
							Depth: 'infinity',
							'Content-Type': 'application/xml',
						},
						data: `<?xml version="1.0"?>
							<d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
								<d:prop>
									<d:resourcetype />
									<d:getcontenttype />
									<d:getcontentlength />
									<d:getlastmodified />
									<oc:fileid />
								</d:prop>
							</d:propfind>`,
					})
				} catch (err) {
					// /DocuDesk/ may not exist yet for fresh users — return empty list.
					if (err.response && err.response.status === 404) {
						return []
					}
					throw err
				}

				const parser = new DOMParser()
				const xmlDoc = parser.parseFromString(response.data, 'text/xml')
				const responses = xmlDoc.querySelectorAll('response')

				const items = []
				responses.forEach((resp, index) => {
					// First response is /DocuDesk/ itself — skip.
					if (index === 0) return

					const href = resp.querySelector('href')?.textContent || ''
					const hrefWithoutTrailingSlash = href.endsWith('/') ? href.slice(0, -1) : href
					const decoded = decodeURIComponent(hrefWithoutTrailingSlash)
					const path = decoded.startsWith(davPrefix) ? decoded.slice(davPrefix.length) : decoded
					const fileName = path.split('/').pop() || ''

					const resourceType = resp.querySelector('resourcetype')
					const isFolder = resourceType?.querySelector('collection') !== null
					const isAnonymized = fileName.includes('_anonymized')

					// Filter to dossier folders + anonymized files only.
					// Skip the dossier folder itself (depth-1) when it has no
					// modified value yet (extremely rare) — Date parse handles it.
					if (!isFolder && !isAnonymized) return

					const mimeType = resp.querySelector('getcontenttype')?.textContent
						|| (isFolder ? 'httpd/unix-directory' : 'application/octet-stream')
					const fileSize = parseInt(resp.querySelector('getcontentlength')?.textContent || '0', 10)
					const modified = resp.querySelector('getlastmodified')?.textContent || ''
					const fileId = parseInt(resp.querySelector('fileid')?.textContent || '0', 10)

					items.push({
						fileId,
						fileName,
						path,
						mimeType,
						fileSize,
						modified: new Date(modified).getTime() / 1000,
						isFolder,
						isAnonymized,
					})
				})

				items.sort((a, b) => b.modified - a.modified)
				return items.slice(0, limit)
			},
		},
	},
)
