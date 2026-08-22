/* eslint-disable no-console */
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'

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
	return String(path || '')
		.split('/')
		.map(encodeURIComponent)
		.join('/')
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

export const useMyDocumentsStore = defineStore('myDocuments', {
	state: () => ({
		documents: [],
		loading: false,
		error: null,
		total: 0,
		currentPath: '/Filinq',
		breadcrumbs: [{ name: 'Filinq', path: '/Filinq' }],
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
			const sourceToAnons = new Map()
			const anonToSource = new Map()
			const anonymizedIds = new Set()
			for (const link of state.anonymizationLinks) {
				const src = Number(link.sourceFileId)
				const anon = Number(link.anonymizedFileId)
				// Guard against degenerate links (missing/self-referential ids)
				// that could otherwise mask a source as its own output.
				if (
					!Number.isFinite(src)
					|| !Number.isFinite(anon)
					|| src === anon
				) {
					continue
				}
				sourceToAnon.set(src, anon)
				anonToSource.set(anon, src)
				anonymizedIds.add(anon)
				// A source can accumulate several outputs across re-anonymise
				// runs. Keep the full set so the overview can collapse stale
				// duplicates down to the newest output (feat #107, dedupe).
				let anons = sourceToAnons.get(src)
				if (!anons) {
					anons = new Set()
					sourceToAnons.set(src, anons)
				}
				anons.add(anon)
			}
			return { sourceToAnon, sourceToAnons, anonToSource, anonymizedIds }
		},
		/**
		 * The newest still-present anonymized output for each source, keyed by
		 * source file id. A source re-anonymised several times leaves multiple
		 * `_anonymized` outputs behind; the overview shows only the newest and
		 * hides the older duplicates. "Newest" is decided by the file's
		 * modification time (falling back to 0), scoped to outputs that are
		 * actually present in the current listing.
		 *
		 * @return {Map<number, object>} sourceFileId → newest output document.
		 */
		newestOutputBySource() {
			const { anonToSource } = this.linkMaps
			const newest = new Map()
			for (const d of this.documents) {
				if (d.isFolder) continue
				const src = anonToSource.get(Number(d.fileId))
				if (src == null) continue
				const prev = newest.get(src)
				if (!prev || (d.modified || 0) > (prev.modified || 0)) {
					newest.set(src, d)
				}
			}
			return newest
		},
		/**
		 * File ids of anonymized outputs left stranded by a RE-UPLOAD. When a
		 * file is re-uploaded into a dossier, the upload replaces the old source
		 * (moved to trash) but leaves its `_anonymized` output behind, so the
		 * output lingers as a stale "anonymized version" with no live original —
		 * making the fresh re-upload look already anonymized.
		 *
		 * The signature is narrow on purpose, so a legitimately standalone
		 * anonymized file (whose source the user simply removed) is NOT hidden:
		 * an output qualifies only when (a) its linked source is absent from
		 * this listing AND (b) a fresh CONCEPT file carrying that same recorded
		 * source name is present AND (c) that fresh file is newer than the
		 * output — i.e. it was uploaded to replace the original, not an
		 * unrelated file that merely shares the name. When timestamps are
		 * missing/unreliable the comparison fails closed and we keep the output
		 * visible, biasing toward "show" over wrongly hiding a real result.
		 * (Cleaning up the leftover file itself is backend work; here we only
		 * stop surfacing it.)
		 *
		 * @return {Set<number>} File ids of re-upload-orphaned anonymized outputs.
		 */
		orphanedOutputIds() {
			const { anonToSource } = this.linkMaps
			const presentIds = new Set(this.documents.map((d) => Number(d.fileId)))
			// Newest upload time of each present CONCEPT (non-output) file name.
			// Anonymized outputs are excluded so an output's own name can't be
			// mistaken for a re-uploaded source.
			const conceptModifiedByName = new Map()
			for (const d of this.documents) {
				if (d.isFolder || d.isAnonymized) continue
				const mod = d.modified || 0
				const prev = conceptModifiedByName.get(d.fileName)
				if (prev == null || mod > prev) {
					conceptModifiedByName.set(d.fileName, mod)
				}
			}
			// anonymizedFileId → recorded source file name, from the links.
			const anonToSourceName = new Map()
			for (const l of this.anonymizationLinks) {
				const anon = Number(l.anonymizedFileId)
				if (Number.isFinite(anon) && l.sourceFileName) {
					anonToSourceName.set(anon, l.sourceFileName)
				}
			}
			const orphans = new Set()
			for (const d of this.documents) {
				if (d.isFolder) continue
				const fileId = Number(d.fileId)
				const src = anonToSource.get(fileId)
				// No link, or the source is still here (a live pair) → not stale.
				if (src == null || presentIds.has(src)) continue
				// Source gone. Only stale when a fresh concept file under the same
				// recorded name exists AND it is newer than this output — i.e. it
				// replaced the original rather than merely sharing its name.
				const srcName = anonToSourceName.get(fileId)
				if (!srcName) continue
				const freshModified = conceptModifiedByName.get(srcName)
				if (freshModified != null && freshModified > (d.modified || 0)) {
					orphans.add(fileId)
				}
			}
			return orphans
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
			const { sourceToAnons, anonToSource } = this.linkMaps
			const presentIds = new Set(this.documents.map((d) => Number(d.fileId)))
			const newestOutputBySource = this.newestOutputBySource
			const orphanedOutputIds = this.orphanedOutputIds
			return this.documents.filter((d) => {
				if (d.isFolder) return true
				const fileId = Number(d.fileId)
				// Hide orphaned anonymized outputs: a leftover `_anonymized` file
				// whose source was replaced by a re-upload (and moved to trash)
				// is not a live pair — showing it makes the fresh re-upload look
				// already anonymized.
				if (orphanedOutputIds.has(fileId)) {
					return false
				}
				// Hide the concept once any of its anonymized outputs is present
				// — the original stays reachable through the "Show original"
				// toggle in the file viewer.
				const anonIds = sourceToAnons.get(fileId)
				if (anonIds && [...anonIds].some((anon) => presentIds.has(anon))) {
					return false
				}
				// Hide superseded duplicate outputs: when a source has several
				// `_anonymized` outputs left over from earlier runs, keep only
				// the newest so stale copies don't linger as extra "anonymized"
				// files in the overview.
				const src = anonToSource.get(fileId)
				if (src != null) {
					const newest = newestOutputBySource.get(src)
					if (newest && Number(newest.fileId) !== fileId) {
						return false
					}
				}
				return true
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
				// Prefer the newest present output so a re-anonymised source
				// links to its latest result rather than a stale duplicate.
				const newest = this.newestOutputBySource.get(Number(doc.fileId))
				if (newest) return newest
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
			const entitiesDetected = docs.reduce(
				(sum, f) => sum + (f.entityCount || 0),
				0,
			)
			const highRisk = docs.filter(
				(f) => f.riskLevel === 'high' || f.riskLevel === 'very_high',
			).length
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
				const webdavUrl = generateRemoteUrl(
					`dav/files/${user.uid}${encodeDavPath(targetPath)}`,
				)

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
					const isFolder =
						resourceType?.querySelector('collection') !== null
					const mimeType =
						resp.querySelector('getcontenttype')?.textContent
						|| (isFolder
							? 'httpd/unix-directory'
							: 'application/octet-stream')
					const fileSize = parseInt(
						resp.querySelector('getcontentlength')?.textContent
							|| resp.querySelector('size')?.textContent
							|| '0',
						10,
					)
					const modified =
						resp.querySelector('getlastmodified')?.textContent || ''
					const fileId = parseInt(
						resp.querySelector('fileid')?.textContent || '0',
						10,
					)

					// Extract filename from href (strip trailing slash for folders first)
					const hrefWithoutTrailingSlash = href.endsWith('/')
						? href.slice(0, -1)
						: href
					const fileName = decodeURIComponent(
						hrefWithoutTrailingSlash.split('/').pop() || '',
					)

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

				// Flag each dossier (folder) as fully anonymized or not, so the
				// overview can tag it "Anonymized" once every source inside has
				// an anonymized output, and "Concept" otherwise. Needs a child
				// listing per folder (the Depth:1 listing above carries no
				// grandchildren), so it runs before assigning to keep the first
				// paint correct.
				await this.annotateDossiers(targetPath, user)

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
		 * Annotate every folder (dossier) in the current listing with
		 * `allChildrenAnonymized`: true when the dossier holds at least one
		 * source file and every source has an anonymized output present
		 * inside it. Drives the dossier's "Anonymized" vs "Concept" tag in
		 * the overview. Folders are listed (Depth:1 PROPFIND) in parallel;
		 * a failed listing degrades the folder to `false` (Concept) rather
		 * than blocking the overview.
		 *
		 * @param {string} parentPath Path whose folders are being annotated.
		 * @param {object} user       Current user (for the WebDAV URL).
		 * @return {Promise<void>}
		 */
		async annotateDossiers(parentPath, user) {
			const { sourceToAnon, anonymizedIds } = this.linkMaps
			const folders = this.documents.filter((d) => d.isFolder)
			await Promise.all(
				folders.map(async (folder) => {
					try {
						const children = await this.listFolderChildren(
							`${parentPath}/${folder.fileName}`,
							user,
						)
						const childIds = new Set(children.map((c) => c.fileId))
						// Sources are the non-folder children that are not themselves
						// an anonymized output. The dossier is "anonymized" when every
						// source has its output present alongside it.
						const sources = children.filter(
							(c) => !c.isFolder && !anonymizedIds.has(c.fileId),
						)
						folder.allChildrenAnonymized =
							sources.length > 0
							&& sources.every((c) => {
								const anon = sourceToAnon.get(c.fileId)
								return anon != null && childIds.has(anon)
							})
					} catch (err) {
						console.error(
							`Failed to read dossier ${folder.fileName}:`,
							err,
						)
						folder.allChildrenAnonymized = false
					}
				}),
			)
		},

		/**
		 * List the direct children of a folder via a Depth:1 WebDAV PROPFIND,
		 * returning the minimal shape needed to judge anonymization status.
		 *
		 * @param {string} folderPath Absolute path of the folder.
		 * @param {object} user       Current user (for the WebDAV URL).
		 * @return {Promise<Array<{fileId: number, isFolder: boolean}>>}
		 */
		async listFolderChildren(folderPath, user) {
			const webdavUrl = generateRemoteUrl(
				`dav/files/${user.uid}${encodeDavPath(folderPath)}`,
			)
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
								<oc:fileid />
							</d:prop>
						</d:propfind>`,
			})
			const parser = new DOMParser()
			const xmlDoc = parser.parseFromString(response.data, 'text/xml')
			const responses = xmlDoc.querySelectorAll('response')
			const children = []
			responses.forEach((resp, index) => {
				// Skip the folder itself (the first PROPFIND entry).
				if (index === 0) {
					return
				}
				const resourceType = resp.querySelector('resourcetype')
				const isFolder = resourceType?.querySelector('collection') !== null
				const fileId = parseInt(
					resp.querySelector('fileid')?.textContent || '0',
					10,
				)
				children.push({ fileId, isFolder })
			})
			return children
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
					generateUrl(
						'/apps/openregister/api/objects/document/anonymizationLink',
					),
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
			const webdavUrl = generateRemoteUrl(
				`dav/files/${user.uid}${encodeDavPath(targetPath)}`,
			)

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
				fileNames.map((fileName) =>
					axios({
						method: 'DELETE',
						url: generateRemoteUrl(
							`dav/files/${user.uid}${encodeDavPath(`${this.currentPath}/${fileName}`)}`,
						),
					}),
				),
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
			this.breadcrumbs = [{ name: 'Filinq', path: '/Filinq' }]

			let currentPath = '/Filinq'
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
		 * /Filinq/, sorted newest first. Does NOT mutate store state —
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
			const webdavUrl = generateRemoteUrl(`dav/files/${user.uid}/Filinq`)

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
				// /Filinq/ may not exist yet for fresh users — return empty list.
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
				// First response is /Filinq/ itself — skip.
				if (index === 0) return

				const href = resp.querySelector('href')?.textContent || ''
				const hrefWithoutTrailingSlash = href.endsWith('/')
					? href.slice(0, -1)
					: href
				const decoded = decodeURIComponent(hrefWithoutTrailingSlash)
				const path = decoded.startsWith(davPrefix)
					? decoded.slice(davPrefix.length)
					: decoded
				const fileName = path.split('/').pop() || ''

				const resourceType = resp.querySelector('resourcetype')
				const isFolder = resourceType?.querySelector('collection') !== null
				const isAnonymized = fileName.includes('_anonymized')

				// Filter to dossier folders + anonymized files only.
				// Skip the dossier folder itself (depth-1) when it has no
				// modified value yet (extremely rare) — Date parse handles it.
				if (!isFolder && !isAnonymized) return

				const mimeType =
					resp.querySelector('getcontenttype')?.textContent
					|| (isFolder
						? 'httpd/unix-directory'
						: 'application/octet-stream')
				const fileSize = parseInt(
					resp.querySelector('getcontentlength')?.textContent || '0',
					10,
				)
				const modified =
					resp.querySelector('getlastmodified')?.textContent || ''
				const fileId = parseInt(
					resp.querySelector('fileid')?.textContent || '0',
					10,
				)

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
})
