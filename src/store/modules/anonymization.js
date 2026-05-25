/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

// Each file entry in the queue:
// {
// id: string,          - unique id.
// name: string,        - original file name.
// status: string,      - 'queued' | 'uploading' | 'extracting' | 'anonymizing' | 'completed' | 'error'.
// error: string|null,
// fileId: number|null,         - Nextcloud file ID after upload.
// filePath: string|null,       - path in Nextcloud files.
// entities: array,             - per-entity rows returned by /extract (type, value, confidence, ...).
// entityCount: number,         - entities detected.
// replacementCount: number,    - entities replaced.
// anonymizedFileId: number|null,
// anonymizedFileName: string|null,
// anonymizedFilePath: string|null.
// End of entry definition.
let fileCounter = 0

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
			/*
                 * Add files to the queue and start processing.
                 *
                 * @param {File[]} fileList Array of File objects.
                 *
                 * @spec openspec/specs/anonymization/spec.md#requirement-frontend-file-processing-queue-req-anon-10
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
						entities: [],
						entityCount: 0,
						replacementCount: 0,
						anonymizedFileId: null,
						anonymizedFileName: null,
						anonymizedFilePath: null,
						_file: file,
						// Keep reference for upload.
					}),
				)

				this.files.push(...newEntries)
				await this.processQueue()
			},

			/*
                 * Process all queued files sequentially.
                 *
                 * @spec openspec/specs/anonymization/spec.md#requirement-frontend-file-processing-queue-req-anon-10
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

			/*
                 * Run the full pipeline for a single file entry.
                 *
                 * @param {object} entry File entry from the queue.
                 *
                 * @spec openspec/specs/anonymization/spec.md#requirement-frontend-file-processing-queue-req-anon-10
                 */
			async processFile(entry) {
				try {
					// Step 1: Upload.
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
					// Step 2: Extract entities.
					entry.status = 'extracting'
					const extractResponse = await axios.post(
						generateUrl(`/apps/docudesk/api/anonymization/extract/${entry.fileId}`),
					)

					const entities = extractResponse.data.entities || []
					entry.entities = entities
					entry.entityCount = entities.length

					// No entities? Mark complete.
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
				}// end try

			},

			/*
                 * Clear all completed/errored files from the list.
                 *
                 * @spec openspec/specs/anonymization/spec.md#requirement-frontend-file-processing-queue-req-anon-10
                 */
			clearCompleted() {
				this.files = this.files.filter((f) => f.status !== 'completed' && f.status !== 'error')
			},

			/*
                 * Reset everything.
                 *
                 * @spec openspec/specs/anonymization/spec.md#requirement-frontend-file-processing-queue-req-anon-10
                 */
			reset() {
				this.files = []
				this.processing = false
			},
		},
	},
)
