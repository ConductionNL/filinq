/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
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
 *   isAnonymized: boolean,       - True when the file is the anonymized output
 *                                  of some source, per the anonymizationLink
 *                                  register (NOT a filename guess).
 * }
 *
 * The concept↔anonymized pairing is read from the OpenRegister
 * `anonymizationLink` register (feat #107), which maps sourceFileId ↔
 * anonymizedFileId authoritatively. The overview uses it to show only the
 * anonymized copy once a source has been anonymized; the original stays
 * reachable through the "Show original" toggle in the file viewer.
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
			// anonymizationLink records (sourceFileId ↔ anonymizedFileId) for the
			// current user, fetched alongside the document listing.
			anonymizationLinks: [],
		}),
		getters: {
			/**
			 * Build the source↔anonymized lookups from the `anonymizationLink`
			 * records. This is the authoritative pairing (feat #107)
			 *
			 * @param {object} state Store state.
			 * @return {{ sourceToAnon: Map<number, number>, anonToSource: Map<number, number>, anonymizedIds: Set<number> }}
			 */
			linkMaps: (state) => {
				const sourceToAnon = new Map()
				const anonToSource = new Map()
				const anonymizedIds = new Set()
				for (const link of state.anonymizationLinks) {
					const src = Number(link.sourceFileId)
					const anon = Number(link.anonymizedFileId)
					if (Number.isFinite(src) && Number.isFinite(anon)) {
						sourceToAnon.set(src, anon)
						anonToSource.set(anon, src)
						anonymizedIds.add(anon)
					}
				}
				return { sourceToAnon, anonToSource, anonymizedIds }
			},
			/**
			 * Documents to show in the overview. Once a file has been anonymized
			 * the concept (original) is hidden so only the anonymized copy is
			 * listed — the original stays reachable through the "Show original"
			 * toggle in the file viewer. A source is only hidden when its
			 * anonymized output is actually present in this listing, so a deleted
			 * output never makes the source vanish. Folders and files without a
			 * link are always shown.
			 *
			 * @return {object[]} Filtered document list.
			 */
			visibleDocuments() {
				const { sourceToAnon } = this.linkMaps
				const presentIds = new Set(this.documents.map((d) => Number(d.fileId)))
				return this.documents.filter((d) => {
					if (d.isFolder) return true
					const anonId = sourceToAnon.get(Number(d.fileId))
					return !(anonId != null && presentIds.has(anonId))
				})
			},
			/**
			 * Find the concept (original) counterpart of an anonymized document
			 * via the link map. Lets the viewer wire up the "Show original" toggle
			 * when opening an anonymized file straight from the overview.
			 *
			 * @return {(doc: object) => (object|undefined)} Lookup function.
			 */
			conceptFor() {
				return (doc) => {
					if (!doc) return undefined
					const srcId = this.linkMaps.anonToSource.get(Number(doc.fileId))
					if (srcId == null) return undefined
					return this.documents.find((d) => Number(d.fileId) === srcId)
				}
			},
			/**
			 * Find the anonymized counterpart of a concept document via the link
			 * map.
			 *
			 * @return {(doc: object) => (object|undefined)} Lookup function.
			 */
			anonymizedFor() {
				return (doc) => {
					if (!doc) return undefined
					const anonId = this.linkMaps.sourceToAnon.get(Number(doc.fileId))
					if (anonId == null) return undefined
					return this.documents.find((d) => Number(d.fileId) === anonId)
				}
			},
			/**
			 * Aggregate stats for the stats blocks on the My Documents page.
			 * Counts the overview's visible documents so hidden concept
			 * originals are not double-counted alongside their anonymized copy.
			 *
			 * @param {object} state Store state.
			 * @return {{ total: number, entitiesDetected: number, highRisk: number }}
			 */
			documentStats() {
				const docs = this.visibleDocuments
				const total = docs.length
				const entitiesDetected = docs.reduce((sum, f) => sum + (f.entityCount || 0), 0)
				const highRisk = docs.filter((f) => f.riskLevel === 'high' || f.riskLevel === 'very_high').length
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

					// Fetch the authoritative source↔anonymized pairing in parallel
					// with the listing so the loop below can flag anonymized
					// outputs without guessing from filenames.
					const linksPromise = this.fetchAnonymizationLinks()

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

					// Links are needed to flag anonymized outputs; wait for them
					// before building the document list.
					await linksPromise
					const { anonymizedIds } = this.linkMaps

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
								isAnonymized: anonymizedIds.has(fileId),
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
			 * Fetch the user's `anonymizationLink` records (sourceFileId ↔
			 * anonymizedFileId) from OpenRegister and store them. Best-effort:
			 * on failure the list is cleared so the overview degrades to showing
			 * every file rather than erroring. The register/schema match the
			 * reverse lookup used by the anonymization store.
			 *
			 * @return {Promise<void>}
			 */
			async fetchAnonymizationLinks() {
				try {
					const r = await axios.get(
						generateUrl('/apps/openregister/api/objects/document/anonymizationLink'),
						// High limit: one link per anonymised source file per user.
						{ params: { _limit: 10000 } },
					)
					this.anonymizationLinks = r.data?.results || []
				} catch (err) {
					console.error('Failed to fetch anonymization links:', err)
					this.anonymizationLinks = []
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
