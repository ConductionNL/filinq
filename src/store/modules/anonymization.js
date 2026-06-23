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
import { extractDocumentText } from '../../services/fileViewerService.js'

let fileCounter = 0

/**
 * Matches the in-file anonymisation placeholder `[<TYPE>: <entity_id>]`
 * produced by OpenRegister's DocumentProcessingHandler. The type is an
 * uppercase token (LOCATION, ORGANIZATION, PERSON, …) and the id is the
 * stable `openregister_entities.id` primary key, resolvable via
 * `GET /apps/openregister/api/entities/{id}`.
 *
 * @type {RegExp}
 */
const PLACEHOLDER_RE = /\[([A-Z][A-Z0-9_]*):\s*(\d+)\]/g

/**
 * Scan anonymised document text for `[<TYPE>: <entity_id>]` placeholders.
 * Returns one record per distinct entity id, preserving first-appearance
 * order and counting how often each id occurs in the document.
 *
 * @param {string} text Plain text of the anonymised document.
 * @return {Array<{entityId: number, type: string, count: number}>}
 */
function parsePlaceholders(text) {
	if (typeof text !== 'string' || text.length === 0) {
		return []
	}
	const byId = new Map()
	for (const match of text.matchAll(PLACEHOLDER_RE)) {
		const type = match[1]
		const entityId = Number(match[2])
		if (!Number.isFinite(entityId)) {
			continue
		}
		const existing = byId.get(entityId)
		if (existing) {
			existing.count += 1
		} else {
			byId.set(entityId, { entityId, type, count: 1 })
		}
	}
	return Array.from(byId.values())
}

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

/**
 * Build a synthetic queue entry for a file that wasn't uploaded this
 * session (e.g. opened from FolderFilesNavigation). The entry mirrors
 * makeEntry's shape minus the source `_file` blob, and starts in the
 * `extracting` state so the sidebar can render a skeleton until the
 * backend returns its cached entities.
 *
 * @param {object} fileMeta File descriptor from the viewer store.
 * @param {number} fileMeta.fileId Nextcloud file id.
 * @param {string} fileMeta.fileName File name with extension.
 * @param {string} fileMeta.path Absolute path inside the user's storage.
 * @return {object}
 */
function makeSyntheticEntry(fileMeta) {
	const dossier = inferDossier(fileMeta.path)
	return {
		id: `file-${++fileCounter}`,
		name: fileMeta.fileName,
		status: 'extracting',
		error: null,
		fileId: fileMeta.fileId,
		filePath: fileMeta.path,
		entities: [],
		entityCount: 0,
		replacementCount: 0,
		anonymizedFileId: null,
		anonymizedFileName: null,
		anonymizedFilePath: null,
		dossier,
	}
}

/**
 * Infer the dossier folder name from a file path. Returns the segment
 * after `/DocuDesk/` when the file sits inside a sub-folder, else null.
 *
 * @param {string} path Absolute path (e.g. /DocuDesk/Foo/bar.pdf).
 * @return {string|null}
 */
function inferDossier(path) {
	if (typeof path !== 'string') {
		return null
	}
	const m = path.match(/\/DocuDesk\/([^/]+)\/[^/]+$/)
	return m ? m[1] : null
}

/**
 * Map a raw entity payload (as returned by the extract endpoint) to the
 * shape `EntityReviewTable` expects. Centralised so both `addFiles` and
 * `ensureExtracted` produce identical entry.entities.
 *
 * @param {Array<object>} entities Raw entities array.
 * @return {Array<object>}
 */
function decorateEntities(entities) {
	return (entities || []).map((e) => ({
		...e,
		included: true,
		highestConfidence: e.confidence ?? 0,
		fileCount: 1,
		relationIds: e.relationId != null ? [e.relationId] : [],
		_decisionBases: Array.isArray(e.bases) ? [...e.bases] : [],
		_decisionSkip: !!e.skipAnonymization,
		_patchError: null,
	}))
}

/**
 * Extract a Nextcloud file id from a WebDAV PROPFIND response.
 *
 * @param {string} xmlText Response body.
 * @return {number|null} Numeric file id or null when missing/malformed.
 */
function parseFileIdFromPropfind(xmlText) {
	if (typeof xmlText !== 'string' || xmlText.length === 0) {
		return null
	}
	try {
		const doc = new DOMParser().parseFromString(xmlText, 'text/xml')
		const node = doc.getElementsByTagNameNS('http://owncloud.org/ns', 'fileid')[0]
		if (!node) {
			return null
		}
		const id = Number(node.textContent)
		return Number.isFinite(id) ? id : null
	} catch {
		return null
	}
}

export const useAnonymizationStore = defineStore(
	'anonymization',
	{
		state: () => ({
			files: [],
			processing: false,
			/**
			 * OR dossier records created during this session. Keyed by
			 * folderName so multiple uploads-as-dossier in the same view
			 * don't clash. Each entry: { folderName, folderId, uuid, name, bases }.
			 *
			 * @type {Array<{folderName: string, folderId: number, uuid: string, name: string, bases: string[]}>}
			 */
			dossiers: [],
		}),
		getters: {
			hasFiles: (state) => state.files.length > 0,
			hasCompleted: (state) => state.files.some((f) => f.status === 'completed'),
			hasExtracted: (state) => state.files.some((f) => f.status === 'extracted'),
			allDone: (state) => state.files.length > 0
				&& state.files.every((f) => f.status === 'completed' || f.status === 'error'),
			isProcessing: (state) => state.processing,
			/**
			 * Find a queue entry by its Nextcloud file id. Used by the
			 * file-viewer sidebar to look up the entities of whichever
			 * file is currently open in the viewer.
			 *
			 * Matches either the original `fileId` or the post-anonymisation
			 * `anonymizedFileId`, so the sidebar keeps rendering the same
			 * entry (and its success card) after the viewer auto-swaps to
			 * the anonymised file.
			 *
			 * @param {object} state Store state.
			 * @return {(fileId: number) => object | undefined}
			 */
			findByFileId: (state) => (fileId) => {
				if (fileId === null || fileId === undefined) {
					return undefined
				}
				const target = Number(fileId)
				return state.files.find(
					(f) => Number(f.fileId) === target
						|| Number(f.anonymizedFileId) === target,
				)
			},
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
					// `decorateEntities` adds the extra fields (`included`,
					// `highestConfidence`, `fileCount`, `relationIds`) that
					// `EntityReviewTable` expects — see helpers at the top of
					// this file. Same shape is used by `ensureExtracted` so the
					// sidebar can read entities regardless of how the file
					// entered the queue.
					entry.entities = decorateEntities(entities)

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
						// Placeholder-numbering scope: a single-document anonymise
						// numbers entities locally to this file. Folder/dossier
						// consistency is handled by the batch path (scope=dossier).
						scope: 'document',
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
			 * Ensure a queue entry exists for the given file id with its
			 * entities populated. Backs the file-viewer sidebar — when the
			 * user opens a file that was uploaded earlier (e.g. by clicking
			 * one in `FolderFilesNavigation`), the store wouldn't otherwise
			 * know about it.
			 *
			 * Look-up first; on a miss, create a synthetic entry and POST
			 * `extract/{fileId}` to seed entities. The backend short-circuits
			 * on `isSourceUpToDate`, so this is effectively a DB-cached
			 * lookup for previously processed files.
			 *
			 * @param {object} fileMeta File descriptor.
			 * @param {number} fileMeta.fileId Nextcloud file id.
			 * @param {string} fileMeta.fileName File name with extension.
			 * @param {string} fileMeta.path Absolute path.
			 * @param {string} [fileMeta.mimeType] MIME type (optional, unused server-side).
			 * @return {Promise<object>} The queue entry.
			 */
			async ensureExtracted(fileMeta) {
				const existing = this.findByFileId(fileMeta.fileId)
				if (existing) {
					return existing
				}

				const entry = makeSyntheticEntry(fileMeta)
				this.files.push(entry)

				try {
					const extractResponse = await axios.post(
						generateUrl(`/apps/docudesk/api/anonymization/extract/${entry.fileId}`),
					)
					const entities = extractResponse.data.entities || []
					entry.entityCount = entities.length
					entry.entities = decorateEntities(entities)
					entry.status = entities.length === 0 ? 'completed' : 'extracted'
				} catch (err) {
					console.error(`Failed to load entities for ${entry.name}:`, err)
					entry.error = err.response?.data?.error || err.message
					entry.status = 'error'
				}

				return entry
			},

			/**
			 * Build a read-only review entry for an already-anonymised file by
			 * resolving the `[<TYPE>: <entity_id>]` placeholders baked into the
			 * document text back to their source entities.
			 *
			 * Unlike `ensureExtracted` (which detects PII in the *source*
			 * file), this works on the *anonymised output* itself: the
			 * placeholders carry stable `openregister_entities.id` keys, so we
			 * never need the source↔anonymised link. Each id is resolved via
			 * `GET /apps/openregister/api/entities/{id}`, which returns the
			 * original value, type and the entity's relations (bases).
			 *
			 * Returns `null` when the document contains no placeholders — the
			 * caller should then fall back to `ensureExtracted`.
			 *
			 * NOTE: `entity.value` is the *original, un-anonymised* text.
			 * Surfacing it re-exposes the data the file hid — the sidebar
			 * gates this behind an explicit reveal. See [[project-anonymized-pair-persistence]].
			 *
			 * @param {object} fileMeta File descriptor.
			 * @param {number} fileMeta.fileId   Nextcloud file id (anonymised file).
			 * @param {string} fileMeta.fileName File name with extension.
			 * @param {string} fileMeta.path     Absolute path.
			 * @param {string} [fileMeta.mimeType] MIME type (picks the text extractor).
			 * @return {Promise<object|null>} The review entry, or null when not anonymised.
			 */
			async loadAnonymizedEntities(fileMeta) {
				let text
				try {
					text = await extractDocumentText({
						path: fileMeta.path,
						mimeType: fileMeta.mimeType,
						fileName: fileMeta.fileName,
					})
				} catch (err) {
					console.error(`Failed to read text for ${fileMeta.fileName}:`, err)
					return null
				}

				const placeholders = parsePlaceholders(text)
				if (placeholders.length === 0) {
					return null
				}

				// Resolve every distinct entity id in parallel. A failed lookup
				// (deleted entity, permission) degrades to a placeholder-only
				// row rather than dropping the occurrence entirely.
				const resolved = await Promise.all(
					placeholders.map(async (ph) => {
						try {
							const r = await axios.get(
								generateUrl(`/apps/openregister/api/entities/${ph.entityId}`),
							)
							const data = r.data?.data || {}
							const relations = Array.isArray(r.data?.relations) ? r.data.relations : []
							// Collect bases across this entity's relations.
							const bases = relations
								.flatMap((rel) => (Array.isArray(rel.bases) ? rel.bases : []))
							return {
								type: data.type || ph.type,
								value: data.value ?? null,
								confidence: data.confidence ?? null,
								entityId: ph.entityId,
								count: ph.count,
								bases: [...new Set(bases)],
								placeholder: `[${ph.type}: ${ph.entityId}]`,
								_resolveError: null,
							}
						} catch (err) {
							return {
								type: ph.type,
								value: null,
								confidence: null,
								entityId: ph.entityId,
								count: ph.count,
								bases: [],
								placeholder: `[${ph.type}: ${ph.entityId}]`,
								_resolveError: err.response?.status === 404
									? 'Entity no longer exists'
									: (err.response?.data?.message || err.message),
							}
						}
					}),
				)

				const entry = {
					id: `file-${++fileCounter}`,
					name: fileMeta.fileName,
					status: 'completed',
					viewMode: 'anonymized',
					error: null,
					fileId: fileMeta.fileId,
					filePath: fileMeta.path,
					entities: resolved,
					entityCount: resolved.length,
					replacementCount: resolved.reduce((sum, e) => sum + e.count, 0),
					anonymizedFileId: null,
					anonymizedFileName: null,
					anonymizedFilePath: null,
					dossier: inferDossier(fileMeta.path),
				}
				this.files.push(entry)
				return entry
			},

			/**
			 * Create an OpenRegister dossier object for a folder under
			 * `/DocuDesk/`. Mirrors what `folderAnonymization.createDossier`
			 * does, but scoped to this widget's single-file queue.
			 *
			 * 1. PROPFIND the new folder to read its Nextcloud node id.
			 * 2. POST to `/apps/openregister/api/objects/dossier/dossier`
			 *    with `{ name, description, bases, @self: { folder } }`.
			 * 3. Record the result in `state.dossiers` so the sidebar /
			 *    summary actions can look it up by folderName.
			 *
			 * Idempotent on the folderName: if a dossier was already created
			 * in this session for the same folder, returns the cached record
			 * without re-posting.
			 *
			 * @param {string} folderName Folder name under /DocuDesk/.
			 * @param {object} [options] Dossier metadata.
			 * @param {string} [options.description] Free-text description.
			 * @param {string[]} [options.bases] Default grondslagen.
			 * @return {Promise<object>} { folderName, folderId, uuid, name, bases }.
			 * @throws {Error} If the PROPFIND or OR create call fails.
			 */
			async bindDossier(folderName, options = {}) {
				const cleanName = (folderName || '').trim()
				if (!cleanName) {
					throw new Error('Folder name is required')
				}

				const cached = this.dossiers.find((d) => d.folderName === cleanName)
				if (cached) {
					return cached
				}

				// Step 1 — PROPFIND for the folder's NC node id.
				const propfindUrl = `${davBaseUrl()}/DocuDesk/${encodePath(cleanName)}/`
				const propfindResponse = await axios({
					method: 'PROPFIND',
					url: propfindUrl,
					headers: { Depth: '0', 'Content-Type': 'application/xml' },
					data: `<?xml version="1.0"?>
						<d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
							<d:prop>
								<oc:fileid />
							</d:prop>
						</d:propfind>`,
				})
				const folderId = parseFileIdFromPropfind(propfindResponse.data)
				if (folderId === null) {
					throw new Error(`Failed to read folder id for ${cleanName}`)
				}

				// Step 2 — create the OR dossier object.
				const bases = Array.isArray(options.bases) && options.bases.length > 0
					? options.bases
					: ['persoonsgegevens']
				const payload = {
					name: cleanName,
					description: options.description ?? '',
					bases,
					'@self': { folder: String(folderId) },
				}

				const createResponse = await axios.post(
					generateUrl('/apps/openregister/api/objects/dossier/dossier'),
					payload,
				)
				const uuid = createResponse.data?.['@self']?.id
					?? createResponse.data?.id
					?? null
				if (uuid === null) {
					throw new Error('Dossier created but no UUID returned')
				}

				const record = { folderName: cleanName, folderId, uuid, name: cleanName, bases }
				this.dossiers.push(record)
				return record
			},

			/**
			 * Look up the OR dossier record (if any) created during this
			 * session for a given folder name. Returns undefined when the
			 * folder is not bound to an OR dossier yet.
			 *
			 * @param {string} folderName Folder name under /DocuDesk/.
			 * @return {object|undefined}
			 */
			findDossier(folderName) {
				return this.dossiers.find((d) => d.folderName === folderName)
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
			 * Reset the queue, processing flag and dossier records completely.
			 */
			reset() {
				this.files = []
				this.processing = false
				this.dossiers = []
			},
		},
	},
)
