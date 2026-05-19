/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

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

					// Use Nextcloud WebDAV API to list folder contents
					const webdavUrl = generateRemoteUrl(`dav/files/${user.uid}${targetPath}`)

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
		},
	},
)
