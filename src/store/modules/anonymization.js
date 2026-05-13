/* eslint-disable no-console */
/**
 * Anonymisation store — queue-based pipeline with a manual review step.
 *
 * Per-file lifecycle:
 *   queued -> uploading -> extracting -> extracted [user reviews]
 *     -> anonymising -> completed
 *
 * The review step is where Wave 1.3 (entity-relation-grondslagen) decisions
 * live: per-entity bases assignment + skipAnonymization flag. The widget
 * collects those decisions and calls `anonymiseEntry(entry, decisions)`,
 * which PATCHes each modified relation through OpenRegister's
 * `/api/entity-relations/{id}` endpoint and then triggers the anonymise
 * step on the OR side.
 *
 * `addFiles` no longer auto-anonymises. The widget must call
 * `anonymiseEntry` once the user is done reviewing.
 */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

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
			hasExtracted: (state) => state.files.some((f) => f.status === 'extracted'),
			allDone: (state) => state.files.length > 0
				&& state.files.every((f) => f.status === 'completed' || f.status === 'error'),
			isProcessing: (state) => state.processing,
		},
		actions: {
			/**
			 * Add files to the queue and start uploading + extracting.
			 *
			 * Stops at the `extracted` state — does NOT auto-anonymise.
			 * The widget is responsible for calling `anonymiseEntry`
			 * once the user has reviewed each file's entities.
			 *
			 * @param {File[]} fileList Files to enqueue.
			 */
			async addFiles(fileList) {
				const newEntries = Array.from(fileList).map(
					(file) => ({
						id: `file-${++fileCounter}`,
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
					}),
				)

				this.files.push(...newEntries)
				await this.processQueue()
			},

			/**
			 * Walk the queue running upload + extract on every `queued` entry.
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

					await this.uploadAndExtract(entry)
				}

				this.processing = false
			},

			/**
			 * Upload + extract for a single entry. Stops at `extracted` so the
			 * user can review bases / skipAnonymization decisions.
			 *
			 * @param {object} entry Queue entry.
			 */
			async uploadAndExtract(entry) {
				try {
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

					entry.status = 'extracting'
					const extractResponse = await axios.post(
						generateUrl(`/apps/docudesk/api/anonymization/extract/${entry.fileId}`),
					)
					const entities = extractResponse.data.entities || []
					entry.entityCount = entities.length

					// Seed per-row review state on every detected entity.
					entry.entities = entities.map((e) => ({
						...e,
						_decisionBases: Array.isArray(e.bases) ? [...e.bases] : [],
						_decisionSkip: !!e.skipAnonymization,
						_patchError: null,
					}))

					if (entities.length === 0) {
						// Nothing to anonymise; skip review and mark done.
						entry.status = 'completed'
						return
					}

					entry.status = 'extracted'
				} catch (err) {
					console.error(`Failed to upload/extract ${entry.name}:`, err)
					entry.error = err.response?.data?.error || err.message
					entry.status = 'error'
				}
			},

			/**
			 * Apply review decisions (bases / skipAnonymization) by PATCHing each
			 * relation, then trigger anonymisation. Decisions that haven't changed
			 * from the extracted state are skipped to avoid no-op writes.
			 *
			 * @param {object} entry Queue entry (must be in `extracted` status).
			 */
			async anonymiseEntry(entry) {
				if (entry.status !== 'extracted') {
					return
				}

				entry.status = 'anonymising'
				try {
					// Step 1 — PATCH decisions for entities the user modified.
					for (const entity of entry.entities) {
						if (entity.relationId == null) {
							continue
						}

						const originalBases = Array.isArray(entity.bases) ? entity.bases : []
						const newBases = Array.isArray(entity._decisionBases) ? entity._decisionBases : []
						const basesChanged = JSON.stringify(originalBases) !== JSON.stringify(newBases)
						const skipChanged = !!entity.skipAnonymization !== !!entity._decisionSkip
						if (!basesChanged && !skipChanged) {
							continue
						}

						try {
							await axios.patch(
								generateUrl(`/apps/openregister/api/entity-relations/${entity.relationId}`),
								{ bases: newBases, skipAnonymization: !!entity._decisionSkip },
							)
							entity.bases = newBases
							entity.skipAnonymization = !!entity._decisionSkip
							entity._patchError = null
						} catch (err) {
							entity._patchError = err.response?.data?.error || err.message
							// Continue with other entities — partial application is preferable
							// to all-or-nothing in a smoke-test surface.
						}
					}

					// Step 2 — anonymise. The OR side filters out skipAnonymization=true
					// relations from the document mutation, so the user's skips take effect.
					const anonymizePayload = {
						entities: entry.entities.map((e) => ({
							type: e.type,
							value: e.value,
							confidence: e.confidence,
						})),
					}
					const anonymizeResponse = await axios.post(
						generateUrl(`/apps/docudesk/api/anonymization/anonymize/${entry.fileId}`),
						anonymizePayload,
					)

					entry.anonymizedFileId = anonymizeResponse.data.anonymizedFileId
					entry.anonymizedFileName = anonymizeResponse.data.anonymizedFileName
					entry.anonymizedFilePath = anonymizeResponse.data.anonymizedFilePath
					entry.replacementCount = anonymizeResponse.data.replacementCount || 0
					entry.status = 'completed'
				} catch (err) {
					console.error(`Failed to anonymise ${entry.name}:`, err)
					entry.error = err.response?.data?.error || err.message
					entry.status = 'error'
				}
			},

			/**
			 * Bulk-anonymise every entry currently in the `extracted` state.
			 * Useful for "review all then run" UX.
			 */
			async anonymiseAllExtracted() {
				for (const entry of this.files) {
					if (entry.status === 'extracted') {
						await this.anonymiseEntry(entry)
					}
				}
			},

			clearCompleted() {
				this.files = this.files.filter((f) => f.status !== 'completed' && f.status !== 'error')
			},

			reset() {
				this.files = []
				this.processing = false
			},
		},
	},
)
