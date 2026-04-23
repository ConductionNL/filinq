/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl, generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

/*
 * Each file entry in the queue:
 * {
 *   id: string,                   - Unique client-side id.
 *   name: string,                 - Original file name.
 *   status: string,               - 'queued' | 'uploading' | 'extracting' | 'anonymizing' | 'completed' | 'error'.
 *   error: string | null,         - Error message if status is 'error'.
 *   fileId: number | null,        - Nextcloud file id after upload.
 *   filePath: string | null,      - Full path in Nextcloud files.
 *   entityCount: number,          - Number of entities detected.
 *   replacementCount: number,     - Number of entities replaced.
 *   anonymizedFileId: number | null,
 *   anonymizedFileName: string | null,
 *   anonymizedFilePath: string | null,
 *   dossier: string | null,       - Folder name (under /DocuDesk/) when part of a dossier, null otherwise.
 * }
 */

let fileCounter = 0

/**
 * Build the WebDAV base URL for the currently logged-in user.
 *
 * @return {string} Absolute URL like https://host/remote.php/dav/files/<uid>
 * @throws {Error} If no user is logged in.
 */
function davBaseUrl() {
	const uid = getCurrentUser()?.uid
	if (!uid) {
		throw new Error('Not logged in')
	}
	return generateRemoteUrl('dav/files/' + uid)
}

/**
 * Encode each path segment for safe inclusion in a URL.
 * Preserves `/` separators while escaping spaces, accents, etc.
 *
 * @param {string} path Raw path (segments separated by /).
 * @return {string} Path with each segment URL-encoded.
 */
function encodePath(path) {
	return path.split('/').map(encodeURIComponent).join('/')
}

export const useAnonymizationStore = defineStore(
	'anonymization',
	{
		state: () => ({
			files: [],
			processing: false,
		}),
		getters: {
			hasFiles: (state) => state.files.length > 0,
			hasCompleted: (state) => state.files.some((f) => f.status === 'completed'),
			allDone: (state) => state.files.length > 0 && state.files.every((f) => f.status === 'completed' || f.status === 'error'),
			isProcessing: (state) => state.processing,
		},
		actions: {
			/**
			 * Add files to the queue (no dossier) and start processing.
			 * Each file is uploaded to /DocuDesk/ and pipelined through
			 * extract + anonymize sequentially.
			 *
			 * @param {File[] | FileList} fileList Files selected by the user.
			 * @return {Promise<void>}
			 */
			async addFiles(fileList) {
				const newEntries = Array.from(fileList).map(
					(file) => ({
						id: `file - ${++fileCounter}`,
						name: file.name,
						status: 'queued',
						error: null,
						fileId: null,
						filePath: null,
						entityCount: 0,
						replacementCount: 0,
						anonymizedFileId: null,
						anonymizedFileName: null,
						anonymizedFilePath: null,
						dossier: null,
						_file: file,
						// Keep reference for upload.
					}),
				)

				this.files.push(...newEntries)
				await this.processQueue()
			},

			/**
			 * Add files to the queue grouped into a dossier folder.
			 * Creates /DocuDesk/<folderName>/ via WebDAV MKCOL (if it does
			 * not exist yet), then pipelines each file through upload →
			 * MOVE-to-dossier → extract → anonymize.
			 *
			 * Anonymized copies automatically end up in the dossier folder
			 * because OpenRegister writes them next to the original file.
			 *
			 * @param {File[] | FileList} fileList Files selected by the user.
			 * @param {string} folderName Dossier/folder name under /DocuDesk/.
			 * @return {Promise<void>}
			 * @throws {Error} If the folder name is empty or folder creation fails.
			 */
			async addFilesAsDossier(fileList, folderName) {
				const cleanName = (folderName || '').trim()
				if (!cleanName) {
					throw new Error('Folder name is required')
				}

				try {
					await this.createDossierFolder(cleanName)
				} catch (err) {
					console.error('Failed to create dossier folder:', err)
					throw err
				}

				const newEntries = Array.from(fileList).map(
					(file) => ({
						id: `file - ${++fileCounter}`,
						name: file.name,
						status: 'queued',
						error: null,
						fileId: null,
						filePath: null,
						entityCount: 0,
						replacementCount: 0,
						anonymizedFileId: null,
						anonymizedFileName: null,
						anonymizedFilePath: null,
						dossier: cleanName,
						_file: file,
					}),
				)

				this.files.push(...newEntries)
				await this.processQueue()
			},

			/**
			 * Create the dossier folder under /DocuDesk/ via WebDAV MKCOL.
			 * A 405 response means the folder already exists — we treat
			 * that as success so uploads can still land inside.
			 *
			 * @param {string} folderName Folder name under /DocuDesk/.
			 * @return {Promise<void>}
			 */
			async createDossierFolder(folderName) {
				const url = `${davBaseUrl()}/DocuDesk/${encodePath(folderName)}/`
				try {
					await axios({ method: 'MKCOL', url })
				} catch (err) {
					if (err.response && err.response.status === 405) {
						// Folder already exists — reuse it.
						return
					}
					throw err
				}
			},

			/**
			 * Move an uploaded file from /DocuDesk/<name> into
			 * /DocuDesk/<folderName>/<name> via WebDAV MOVE.
			 * The Nextcloud file id stays the same, so the extraction
			 * pipeline keeps working with the same fileId.
			 *
			 * @param {string} fileName Original file name.
			 * @param {string} folderName Target dossier folder.
			 * @return {Promise<void>}
			 */
			async moveToDossier(fileName, folderName) {
				const base = davBaseUrl()
				const source = `${base}/DocuDesk/${encodePath(fileName)}`
				const destination = `${base}/DocuDesk/${encodePath(folderName)}/${encodePath(fileName)}`
				await axios({
					method: 'MOVE',
					url: source,
					headers: { Destination: destination },
				})
			},

			/**
			 * Process all queued files sequentially.
			 * Guards against concurrent invocations via the `processing` flag.
			 *
			 * @return {Promise<void>}
			 */
			async processQueue() {
				if (this.processing) {
					return
				}

				this.processing = true

				for (const entry of this.files) {
					if (entry.status !== 'queued') {
						continue
					}

					await this.processFile(entry)
				}

				this.processing = false
			},

			/**
			 * Run the full pipeline for a single file entry:
			 *   1. Upload to /DocuDesk/
			 *   2. MOVE into dossier folder (if entry.dossier is set)
			 *   3. Extract entities
			 *   4. Anonymize (skipped when no entities were detected)
			 *
			 * Mutates the entry in place with intermediate status and results.
			 * Errors bubble up into `entry.error` and `entry.status = 'error'`.
			 *
			 * @param {object} entry File entry from `this.files`.
			 * @return {Promise<void>}
			 */
			async processFile(entry) {
				try {
					// Step 1: Upload. Always lands in /DocuDesk/ root first.
					entry.status = 'uploading'
					const formData = new FormData()
					formData.append('file', entry._file)

					const uploadResponse = await axios.post(
						generateUrl('/apps/docudesk/api/anonymization/upload'),
						formData,
						{ headers: { 'Content-Type': 'multipart/form-data' } },
					)

					entry.fileId = uploadResponse.data.fileId
					entry.filePath = uploadResponse.data.filePath
					delete entry._file
					// Free memory.

					// Step 1b: Move into the dossier folder when applicable.
					// The fileId is preserved by MOVE, so later pipeline
					// steps keep referencing the same Nextcloud node.
					if (entry.dossier) {
						await this.moveToDossier(entry.name, entry.dossier)
						entry.filePath = `/DocuDesk/${entry.dossier}/${entry.name}`
					}

					// Step 2: Extract entities.
					entry.status = 'extracting'
					const extractResponse = await axios.post(
						generateUrl(`/apps/docudesk/api/anonymization/extract/${entry.fileId}`),
					)

					const entities = extractResponse.data.entities || []
					entry.entityCount = entities.length

					// No entities? Nothing to anonymize — mark complete.
					if (entities.length === 0) {
						entry.status = 'completed'
						return
					}

					// Step 3: Anonymize.
					entry.status = 'anonymizing'
					const anonymizeResponse = await axios.post(
						generateUrl(`/apps/docudesk/api/anonymization/anonymize/${entry.fileId}`),
						{ entities },
					)

					entry.anonymizedFileId = anonymizeResponse.data.anonymizedFileId
					entry.anonymizedFileName = anonymizeResponse.data.anonymizedFileName
					entry.anonymizedFilePath = anonymizeResponse.data.anonymizedFilePath
					entry.replacementCount = anonymizeResponse.data.replacementCount || 0
					entry.status = 'completed'
				} catch (err) {
					console.error(`Failed to process ${entry.name}:`, err)
					if (err.response && err.response.data && err.response.data.error) {
						entry.error = err.response.data.error
					} else {
						entry.error = err.message
					}

					entry.status = 'error'
				}
			},

			/**
			 * Remove all completed or errored files from the queue.
			 * Used by the "Clear completed" button in the widget.
			 */
			clearCompleted() {
				this.files = this.files.filter((f) => f.status !== 'completed' && f.status !== 'error')
			},

			/**
			 * Reset the queue and processing flag completely.
			 */
			reset() {
				this.files = []
				this.processing = false
			},
		},
	},
)
