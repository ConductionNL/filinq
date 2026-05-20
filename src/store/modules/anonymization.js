/* eslint-disable no-console */
/**
 * Anonymisation store — queue-based pipeline with a manual review step
 * and optional dossier-folder placement.
 *
 * Per-file lifecycle:
 *   queued -> uploading -> (moving) -> extracting -> extracted
 *     [user reviews per-entity bases + skipAnonymization]
 *     -> anonymising -> completed
 *
 * Two entry points:
 *   - `addFiles(fileList)` — uploads to /DocuDesk/ root, then queues
 *     for extract + review.
 *   - `addFilesAsDossier(fileList, folderName)` — creates
 *     /DocuDesk/<folderName>/ via WebDAV MKCOL (idempotent on 405),
 *     uploads into root, then MOVEs each file into the dossier folder
 *     before extraction. The Nextcloud file id is preserved by MOVE so
 *     the extract+anonymise pipeline still references the same node.
 *
 * Review-then-anonymise is the canonical UX (Wave 1.3
 * entity-relation-grondslagen requires per-entity bases assignment for
 * compliance). The widget collects decisions and calls
 * `anonymiseEntry(entry)`, which PATCHes each modified relation through
 * OpenRegister's `/api/entity-relations/{id}` endpoint and then triggers
 * the anonymise step on the OR side.
 *
 * `addFiles` / `addFilesAsDossier` no longer auto-anonymise. The widget
 * must call `anonymiseEntry` (or `anonymiseAllExtracted`) once the user
 * is done reviewing.
 */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl, generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

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

/**
 * Build a fresh queue entry. Shared between addFiles + addFilesAsDossier
 * so the entry shape stays a single source of truth.
 *
 * @param {File} file Source File object.
 * @param {string|null} dossier Dossier folder name (under /DocuDesk/) or null.
 * @return {object}
 */
function makeEntry(file, dossier) {
	return {
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
		dossier,
		_file: file,
	}
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
			hasExtracted: (state) => state.files.some((f) => f.status === 'extracted'),
			allDone: (state) => state.files.length > 0
				&& state.files.every((f) => f.status === 'completed' || f.status === 'error'),
			isProcessing: (state) => state.processing,
		},
		actions: {
			/**
			 * Add files to the queue (no dossier) and start uploading + extracting.
			 *
			 * Stops at the `extracted` state — does NOT auto-anonymise.
			 * The widget is responsible for calling `anonymiseEntry`
			 * once the user has reviewed each file's entities.
			 *
			 * @param {File[] | FileList} fileList Files to enqueue.
			 * @return {Promise<void>}
			 */
			async addFiles(fileList) {
				const newEntries = Array.from(fileList).map((file) => makeEntry(file, null))
				this.files.push(...newEntries)
				await this.processQueue()
			},

			/**
			 * Add files to the queue grouped into a dossier folder.
			 *
			 * Creates /DocuDesk/<folderName>/ via WebDAV MKCOL (idempotent —
			 * a 405 means the folder already exists), then pipelines each
			 * file through upload → MOVE-to-dossier → extract. Stops at
			 * `extracted` for review just like `addFiles`. The Nextcloud
			 * file id survives the MOVE, so subsequent steps keep working
			 * with the same fileId.
			 *
			 * @param {File[] | FileList} fileList Files to enqueue.
			 * @param {string} folderName Dossier folder name under /DocuDesk/.
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

				const newEntries = Array.from(fileList).map((file) => makeEntry(file, cleanName))
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
			 * Walk the queue running upload + extract on every `queued` entry.
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

					await this.uploadAndExtract(entry)
				}

				this.processing = false
			},

			/**
			 * Upload + (optional) MOVE-to-dossier + extract for a single
			 * entry. Stops at `extracted` so the user can review bases /
			 * skipAnonymization decisions.
			 *
			 * @param {object} entry Queue entry.
			 * @return {Promise<void>}
			 */
			async uploadAndExtract(entry) {
				try {
					// Step 1: upload. Always lands in /DocuDesk/ root first.
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

					// Step 1b: MOVE into the dossier folder when applicable.
					// The fileId is preserved by MOVE, so later pipeline
					// steps keep referencing the same Nextcloud node.
					if (entry.dossier) {
						entry.status = 'moving'
						await this.moveToDossier(entry.name, entry.dossier)
						entry.filePath = `/DocuDesk/${entry.dossier}/${entry.name}`
					}

					// Step 2: extract.
					entry.status = 'extracting'
					const extractResponse = await axios.post(
						generateUrl(`/apps/docudesk/api/anonymization/extract/${entry.fileId}`),
					)
					const entities = extractResponse.data.entities || []
					entry.entityCount = entities.length

					// Seed per-row review state on every detected entity.
					// The extra fields (`included`, `highestConfidence`, `fileCount`,
					// `relationIds`) are what `EntityReviewTable` expects — the
					// folder/batch flows pre-aggregate into that shape too. Single
					// file → fileCount 1 and a 1-element relationIds array (or
					// empty when the entity has no relation, e.g. regex-only path).
					entry.entities = entities.map((e) => ({
						...e,
						included: true,
						highestConfidence: e.confidence ?? 0,
						fileCount: 1,
						relationIds: e.relationId != null ? [e.relationId] : [],
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
			 * Apply review decisions (bases / skipAnonymization) by PATCHing
			 * each relation, then trigger anonymisation. Decisions that
			 * haven't changed from the extracted state are skipped to avoid
			 * no-op writes.
			 *
			 * @param {object} entry Queue entry (must be in `extracted` status).
			 * @return {Promise<void>}
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

					// Step 2 — anonymise. Only `included` entities are sent; the
					// review checkbox in EntityReviewTable controls inclusion.
					// The OR side additionally filters skipAnonymization=true
					// relations, so an explicit skip still takes effect even
					// when the entity was left included.
					const anonymizePayload = {
						entities: entry.entities
							.filter((e) => e.included !== false)
							.map((e) => ({
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
			 *
			 * @return {Promise<void>}
			 */
			async anonymiseAllExtracted() {
				for (const entry of this.files) {
					if (entry.status === 'extracted') {
						await this.anonymiseEntry(entry)
					}
				}
			},

			/**
			 * Flip the `included` flag on a single entity within a file entry.
			 *
			 * Backs the row checkbox in `EntityReviewTable`. Unchecking means
			 * the entity won't be sent to the anonymise call (excluded from
			 * the document mutation).
			 *
			 * @param {object} entry Queue entry that owns the entity.
			 * @param {number} idx Index of the entity in `entry.entities`.
			 * @return {void}
			 */
			toggleEntity(entry, idx) {
				if (entry?.entities?.[idx] === undefined) {
					return
				}
				entry.entities[idx].included = !entry.entities[idx].included
			},

			/**
			 * Bulk-set the `included` flag for a list of entity indices in a
			 * file entry. Used by the "Select / Deselect All Visible" buttons.
			 *
			 * @param {object} entry Queue entry that owns the entities.
			 * @param {number[]} idxList Indices of entities to update.
			 * @param {boolean} included New inclusion state.
			 * @return {void}
			 */
			setVisibleEntities(entry, idxList, included) {
				if (Array.isArray(entry?.entities) === false) {
					return
				}
				for (const idx of idxList) {
					if (entry.entities[idx]) {
						entry.entities[idx].included = !!included
					}
				}
			},

			/**
			 * Set the grondslagen (Woo Art. 5 bases) decision for a single
			 * entity. Persisted on the relation via the PATCH step inside
			 * `anonymiseEntry`.
			 *
			 * @param {object} entry Queue entry that owns the entity.
			 * @param {number} idx Index of the entity in `entry.entities`.
			 * @param {string[]} bases New bases array.
			 * @return {void}
			 */
			setEntityBases(entry, idx, bases) {
				if (entry?.entities?.[idx] === undefined) {
					return
				}
				entry.entities[idx]._decisionBases = Array.isArray(bases) ? bases : []
			},

			/**
			 * Set the skipAnonymization decision for a single entity. Persisted
			 * on the relation via the PATCH step inside `anonymiseEntry`; the
			 * OR side filters skipAnonymization=true relations out of the
			 * document mutation.
			 *
			 * @param {object} entry Queue entry that owns the entity.
			 * @param {number} idx Index of the entity in `entry.entities`.
			 * @param {boolean} skip New skip state.
			 * @return {void}
			 */
			setEntitySkip(entry, idx, skip) {
				if (entry?.entities?.[idx] === undefined) {
					return
				}
				entry.entities[idx]._decisionSkip = !!skip
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
