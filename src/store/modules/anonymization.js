/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl, generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { extractDocumentText } from '../../services/fileViewerService.js'

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
				const newEntries = Array.from(fileList).map((file) => makeEntry(file, null))
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

					const raw = extractResponse.data.entities || []
					entry.entities = decorateEntities(raw)
					entry.entityCount = entry.entities.length

					// No entities? Mark complete.
					if (raw.length === 0) {
						entry.status = 'completed'
						return
					}

					// Step 3: Anonymize.
					entry.status = 'anonymizing'
					const anonymizeResponse = await axios.post(
						generateUrl(`/apps/docudesk/api/anonymization/anonymize/${entry.fileId}`),
						{ entities: raw },
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
			 * Add a synthetic queue entry for a file that was opened from the
			 * navigation (not uploaded this session). Starts in `extracting`
			 * state so the sidebar can render a skeleton until the backend
			 * returns its cached entities.
			 *
			 * @param {object} fileMeta File descriptor from the viewer store.
			 * @param {number} fileMeta.fileId Nextcloud file id.
			 * @param {string} fileMeta.fileName File name with extension.
			 * @param {string} fileMeta.path Absolute path inside the user's storage.
			 * @return {object} The created queue entry.
			 */
			ensureExtracted(fileMeta) {
				const entry = makeSyntheticEntry(fileMeta)
				this.files.push(entry)
				return entry
			},

			/**
			 * Remove all completed or errored files from the queue.
			 * Used by the "Clear completed" button in the widget.
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
