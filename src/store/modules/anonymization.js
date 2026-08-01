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
 * Matches any bracketed token `[ … ]` so the inner text can be inspected
 * for the anonymisation placeholder `[<TYPE>: <entity_id>]` produced by
 * OpenRegister's DocumentProcessingHandler.
 *
 * The two-step (match brackets, then validate inner) approach is
 * deliberate: pdfjs rebuilds a PDF's text by joining positioned glyph
 * runs with spaces, so a single `[LOCATION: 205]` can surface as
 * `[ LOCATION : 2 0 5 ]` — spaces injected around the type, the colon AND
 * between the digits. A single strict regex can't anticipate every split,
 * so we grab the bracket then strip whitespace from the candidate type
 * and id before validating. The type stays all-caps + the id all-digits,
 * which rejects ordinary bracketed prose (`[zie bijlage 2]`).
 *
 * @type {RegExp}
 */
const PLACEHOLDER_RE = /\[([^[\]]{1,60})\]/g

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
		// Split on the first colon; strip whitespace pdfjs may have injected
		// inside the type token and between the id's digits.
		const colon = match[1].indexOf(':')
		if (colon === -1) {
			continue
		}
		const type = match[1].slice(0, colon).replace(/\s+/g, '')
		const idText = match[1].slice(colon + 1).replace(/\s+/g, '')
		if (/^[A-Z][A-Z0-9_]*$/.test(type) === false || /^\d+$/.test(idText) === false) {
			continue
		}
		const entityId = Number(idText)
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
 * Collapse duplicate entity rows that represent the same removed value.
 *
 * The OpenRegister extract pipeline appends entity_relation rows on each
 * run instead of replacing them, so a source file accumulates several rows
 * for the same `(type, value)` (see the entity-double-extract TODO — DB
 * shows e.g. 188 rows where 47 distinct values exist). This is a display
 * band-aid: it does not clean the DB, it just shows each distinct value
 * once with its occurrence counts summed. Keyed on `(type, value)` —
 * resolving the same PII text once regardless of how many duplicate
 * `openregister_entities.id`s the appends minted.
 *
 * @param {Array<object>} entities Resolved anonymised-card entities.
 * @return {Array<object>} Deduplicated list, first-appearance order preserved.
 */
function dedupeAnonEntities(entities) {
	const byKey = new Map()
	for (const e of entities || []) {
		// Fall back to entityId/placeholder when the value is hidden/unresolved,
		// so unresolved rows still collapse on their stable id.
		const key = `${e.type}\u0000${e.value ?? e.entityId ?? e.placeholder ?? ''}`
		const existing = byKey.get(key)
		if (existing) {
			existing.count += e.count || 1
			// Union of grondslagen across the merged rows.
			existing.bases = [...new Set([...(existing.bases || []), ...(e.bases || [])])]
		} else {
			byKey.set(key, { ...e, count: e.count || 1 })
		}
	}
	return Array.from(byKey.values())
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
		// Read-only files_confidential sensitivity signal (files-confidential-labels).
		// Both stay null until an extract response resolves a label.
		confidentialityLabel: null,
		confidentialityLevel: null,
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
		// Read-only files_confidential sensitivity signal (files-confidential-labels).
		confidentialityLabel: null,
		confidentialityLevel: null,
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
 * Rows are grouped by `(type, value)`: OpenRegister's extract pipeline
 * appends entity_relation rows on each run (force=true) instead of
 * replacing them, so a single occurrence of e.g. "Claudia Fischer" comes
 * back as several rows — each with its own `relationId`. Showing one card
 * per raw row makes a value that appears once look like it appears twice
 * (see the entity-double-extract TODO). Grouping collapses them to one
 * review row while keeping **every** `relationId` in `relationIds`, so the
 * decision PATCH + anonymise still cover all duplicate relations.
 *
 * @param {Array<object>} entities Raw entities array.
 * @return {Array<object>}
 */
function decorateEntities(entities) {
	const byKey = new Map()
	for (const e of entities || []) {
		const type = e.type ?? 'UNKNOWN'
		const value = e.value ?? ''
		const key = `${type}\u0000${value}`
		const relationId = e.relationId ?? null
		const existing = byKey.get(key)
		if (existing) {
			// Duplicate row from the append bug: merge into the first card.
			if (relationId != null && existing.relationIds.includes(relationId) === false) {
				existing.relationIds.push(relationId)
			}
			existing.count += 1
			existing.highestConfidence = Math.max(existing.highestConfidence, e.confidence ?? 0)
			existing.confidence = existing.highestConfidence
			// Adopt grondslagen / skip metadata from whichever row carries it.
			if ((existing.bases == null || existing.bases.length === 0)
				&& Array.isArray(e.bases) && e.bases.length > 0) {
				existing.bases = [...e.bases]
				existing._decisionBases = [...e.bases]
			}
			if (e.skipAnonymization) {
				existing.skipAnonymization = true
				existing._decisionSkip = true
				existing.included = false
			}
			continue
		}
		byKey.set(key, {
			...e,
			type,
			value,
			included: !e.skipAnonymization,
			confidence: e.confidence ?? 0,
			highestConfidence: e.confidence ?? 0,
			fileCount: 1,
			count: 1,
			relationId,
			relationIds: relationId != null ? [relationId] : [],
			bases: Array.isArray(e.bases) ? [...e.bases] : (e.bases ?? null),
			// Read-only prohibition hint from the extract response: null, or
			// { ruleId, ruleName, highConfidence }. Drives the skip-toggle lock.
			prohibitionMatch: e.prohibitionMatch ?? null,
			_decisionBases: Array.isArray(e.bases) ? [...e.bases] : [],
			_decisionSkip: !!e.skipAnonymization,
			_patchError: null,
		})
	}
	return Array.from(byKey.values())
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
			/**
			 * Progress of a batch "anonymise all" run (see `anonymiseAllExtracted`).
			 * `running` gates the dossier nav button; `done`/`failed` drive the
			 * "Anonymizing X/N…" label and the completion summary.
			 *
			 * @type {{running: boolean, total: number, done: number, failed: number}}
			 */
			batch: { running: false, total: 0, done: 0, failed: 0 },
		}),
		getters: {
			hasFiles: (state) => state.files.length > 0,
			hasCompleted: (state) => state.files.some((f) => f.status === 'completed'),
			hasExtracted: (state) => state.files.some((f) => f.status === 'extracted'),
			/**
			 * Queue entries in the `extracted` state whose fileId is in the
			 * given set — i.e. the files a dossier batch run would anonymise.
			 * Used by `FolderFilesNavigation` to label and gate the
			 * "Anonymize all files (N)" button against the dossier's listing.
			 *
			 * @param {object} state Store state.
			 * @return {(fileIds: Array<number>) => Array<object>}
			 */
			extractedInFiles: (state) => (fileIds) => {
				const set = new Set((fileIds || []).map(Number))
				return state.files.filter(
					(f) => f.status === 'extracted' && set.has(Number(f.fileId)),
				)
			},
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

					// Seed per-row review state on every detected entity.
					// `decorateEntities` adds the extra fields (`included`,
					// `highestConfidence`, `fileCount`, `relationIds`) that
					// `EntityReviewTable` expects — see helpers at the top of
					// this file. Same shape is used by `ensureExtracted` so the
					// sidebar can read entities regardless of how the file
					// entered the queue. It also de-duplicates the append-bug
					// rows, so count the grouped result, not the raw rows.
					entry.entities = decorateEntities(entities)
					entry.entityCount = entry.entities.length
					// Read-only files_confidential signal — present only when the
					// backend resolved a matching label (files-confidential-labels).
					entry.confidentialityLabel = extractResponse.data.confidentialityLabel ?? null
					entry.confidentialityLevel = extractResponse.data.confidentialityLevel ?? null

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
			 * @param {object} [options] Anonymisation options.
			 * @param {boolean} [options.appendBasisSummary] Append the
			 *   grondslagen summary to the output document. Only takes effect
			 *   when `outputFormat` is also supplied — the backend requires
			 *   both flags before it generates and appends the summary.
			 * @param {string} [options.outputFormat] Output document format
			 *   (e.g. `pdf`). Required alongside `appendBasisSummary`.
			 * @return {Promise<void>}
			 */
			async anonymiseEntry(entry, options = {}) {
				if (entry.status !== 'extracted') {
					return
				}

				entry.status = 'anonymising'
				try {
					// Step 1 — PATCH decisions for entities the user modified.
					for (const entity of entry.entities) {
						// A grouped row may own several relation rows (append bug);
						// fall back to the single id for older entries.
						const relationIds = Array.isArray(entity.relationIds) && entity.relationIds.length > 0
							? entity.relationIds
							: (entity.relationId != null ? [entity.relationId] : [])
						if (relationIds.length === 0) {
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
							// Apply the decision to every duplicate relation so a
							// re-extracted copy can't slip through unredacted.
							await Promise.all(relationIds.map((rid) => axios.patch(
								generateUrl(`/apps/docudesk/api/anonymization/relations/${rid}`),
								{ bases: newBases, skipAnonymization: !!entity._decisionSkip },
							)))
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
					//
					// Longest value first: OpenRegister replaces matches in
					// payload order via str_ireplace. If a shorter span runs
					// first it eats its own text out of an overlapping longer
					// span — e.g. redacting "Claudia Fischer" before "Mevrouw
					// Claudia Fischer" leaves a dangling "Mevrouw". Sorting by
					// descending length makes the longest overlap redact first.
					const anonymizePayload = {
						entities: entry.entities
							.filter((e) => e.included !== false)
							.map((e) => ({
								type: e.type,
								value: e.value,
								confidence: e.confidence,
							}))
							.sort((a, b) => (b.value || '').length - (a.value || '').length),
						// Placeholder-numbering scope: a single-document anonymise
						// numbers entities locally to this file. Folder/dossier
						// consistency is handled by the batch path (scope=dossier).
						scope: 'document',
					}

					// Grondslagen summary. The backend only generates and appends
					// it when BOTH flags are present, so send them as a pair or
					// not at all — a lone flag is a silent no-op.
					if (options.appendBasisSummary && options.outputFormat) {
						anonymizePayload.appendBasisSummary = true
						anonymizePayload.outputFormat = options.outputFormat
					}
					const anonymizeResponse = await axios.post(
						generateUrl(`/apps/docudesk/api/anonymization/anonymize/${entry.fileId}`),
						anonymizePayload,
					)

					entry.anonymizedFileId = anonymizeResponse.data.anonymizedFileId
					entry.anonymizedFileName = anonymizeResponse.data.anonymizedFileName
					entry.anonymizedFilePath = anonymizeResponse.data.anonymizedFilePath
					entry.replacementCount = anonymizeResponse.data.replacementCount || 0
					// Best-effort: the file is produced even if some entities could not
					// be fully removed. `complete === false` drives a warning so the
					// operator can refine entities (manual / skip unselected) and re-run.
					entry.complete = anonymizeResponse.data.complete !== false
					entry.residualCount = anonymizeResponse.data.residualCount || 0
					entry.residualEntities = anonymizeResponse.data.residualEntities || []
					// The re-anonymise sub-flow (if any) is done — clear the marker
					// so the dossier footer returns to its batch state.
					entry.reanonymize = false
					entry.status = 'completed'

					// Faithful markers: resolve the produced file so the finished
					// result cards show the SAME placeholder the document carries
					// (`[<localized-TYPE>: <scope-local-number>]`) rather than the
					// reconstructed `[<TYPE>]`. Reuse the anonymised-document
					// resolver on the output file, but with push:false so it does
					// not append a second card for this entry. Best-effort — on a
					// read/parse failure the cards keep the reconstructed fallback.
					try {
						const resolved = await this.loadAnonymizedEntities(
							{
								fileId: entry.anonymizedFileId,
								fileName: entry.anonymizedFileName,
								path: entry.anonymizedFilePath,
							},
							{ push: false },
						)
						if (resolved && Array.isArray(resolved.entities) && resolved.entities.length > 0) {
							entry.resolvedEntities = resolved.entities
						}
					} catch (err) {
						console.error(`Failed to resolve anonymised placeholders for ${entry.name}:`, err)
					}
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
			 * @param {object} [options] Anonymisation options, forwarded to
			 *   `anonymiseEntry` (see its signature for `appendBasisSummary`
			 *   and `outputFormat`).
			 * @param {Array<number>} [options.fileIds] Scope the run to these
			 *   file ids (a dossier's files); omit to run every extracted entry.
			 * @param {Array<object>} [options.files] File descriptors to extract
			 *   before anonymising — covers dossier files the user never opened,
			 *   which are otherwise missing from the queue.
			 * @return {Promise<void>}
			 */
			async anonymiseAllExtracted(options = {}) {
				const { fileIds, files, ...entryOptions } = options

				// Dossier batch: the listing may contain files the user never
				// opened in the viewer, so they were never lazily extracted and
				// aren't in the queue yet. Extract them first — otherwise the
				// run silently skips every un-opened file. ensureExtracted is a
				// no-op for files already in the queue, so this is idempotent.
				if (Array.isArray(files) && files.length > 0) {
					this.batch = { running: true, total: 0, done: 0, failed: 0 }
					for (const meta of files) {
						await this.ensureExtracted(meta)
					}
				}

				const scope = fileIds ? new Set(fileIds.map(Number)) : null

				// Snapshot the targets up-front: anonymiseEntry mutates each
				// entry's status to 'completed'/'error', so iterating live
				// would be order-sensitive.
				const targets = this.files.filter(
					(entry) => entry.status === 'extracted'
						&& (!scope || scope.has(Number(entry.fileId))),
				)

				this.batch = { running: true, total: targets.length, done: 0, failed: 0 }
				try {
					for (const entry of targets) {
						try {
							await this.anonymiseEntry(entry, entryOptions)
						} catch (err) {
							// anonymiseEntry already sets entry.status = 'error';
							// swallow so one bad file doesn't abort the batch.
							console.error(`Batch anonymise failed for ${entry.name}:`, err)
						}
						// Classify by the entry's own outcome — anonymiseEntry
						// can mark 'error' without throwing on partial failures.
						if (entry.status === 'error') {
							this.batch.failed++
						} else {
							this.batch.done++
						}
					}
				} finally {
					this.batch.running = false
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
					entry.entities = decorateEntities(entities)
					entry.entityCount = entry.entities.length
					entry.confidentialityLabel = extractResponse.data.confidentialityLabel ?? null
					entry.confidentialityLevel = extractResponse.data.confidentialityLevel ?? null
					entry.status = entities.length === 0 ? 'completed' : 'extracted'
				} catch (err) {
					console.error(`Failed to load entities for ${entry.name}:`, err)
					entry.error = err.response?.data?.error || err.message
					entry.status = 'error'
				}

				return entry
			},

			/**
			 * Force a fresh re-analysis of an already-known file, discarding the
			 * resume/cached path. Use when the operator explicitly asks to
			 * re-analyse (e.g. after changing the enabled entity types). Normal
			 * opens go through `ensureExtracted`, which resumes from the existing
			 * EntityRelations.
			 *
			 * @param {number} fileId Nextcloud file id of a file already in the queue.
			 * @return {Promise<object|null>} The refreshed entry, or null if unknown.
			 */
			async reanalyseEntry(fileId) {
				const entry = this.findByFileId(fileId)
				if (!entry) {
					return null
				}
				entry.status = 'extracting'
				try {
					const res = await axios.post(
						generateUrl(`/apps/docudesk/api/anonymization/extract/${fileId}`),
						{ force: true },
					)
					const entities = res.data.entities || []
					entry.entities = decorateEntities(entities)
					entry.entityCount = entry.entities.length
					entry.confidentialityLabel = res.data.confidentialityLabel ?? null
					entry.confidentialityLevel = res.data.confidentialityLevel ?? null
					entry.status = entities.length === 0 ? 'completed' : 'extracted'
				} catch (err) {
					entry.error = err.response?.data?.error || err.message
					entry.status = 'error'
				}
				return entry
			},

			/**
			 * Re-open an already-anonymised file for another anonymisation run.
			 *
			 * Re-extraction is the source of truth: an anonymised entry's stored
			 * entities are the *removed* set (read-only, resolved from
			 * placeholders / the source link), not a fresh editable review set
			 * with the relation ids the decision PATCH needs. Re-POSTing
			 * `extract` on the source rebuilds the editable cards. The backend
			 * short-circuits on `isSourceUpToDate`, so this is effectively a
			 * cached lookup.
			 *
			 * Prior per-entity decisions (`_decisionBases` / `_decisionSkip`) are
			 * carried over by `(type, value)` so a re-run does not silently
			 * discard earlier review work; newly detected entities take the
			 * extract defaults.
			 *
			 * The existing `anonymizedFile*` fields are kept so the viewer's
			 * "Show anonymised" toggle still flips to the current result while the
			 * user re-reviews. The next `anonymiseEntry` overwrites that output
			 * (same `_anonymized` name, new file id) and refreshes the fields.
			 *
			 * @param {object} entry Queue entry of an already-anonymised file.
			 * @return {Promise<object>} The same entry, transitioned to `extracted`.
			 */
			async prepareReanonymize(entry) {
				if (!entry || !entry.fileId) {
					return entry
				}

				// Snapshot prior decisions before re-extraction overwrites the
				// entity list, so grounds / skip choices the user already made
				// survive the re-run.
				const priorDecisions = new Map()
				for (const e of entry.entities || []) {
					priorDecisions.set(`${e.type}\u0000${e.value}`, {
						bases: Array.isArray(e._decisionBases) ? [...e._decisionBases] : [],
						skip: !!e._decisionSkip,
					})
				}

				entry.status = 'extracting'
				entry.error = null
				try {
					const extractResponse = await axios.post(
						generateUrl(`/apps/docudesk/api/anonymization/extract/${entry.fileId}`),
					)
					const entities = decorateEntities(extractResponse.data.entities || [])
					for (const e of entities) {
						const prior = priorDecisions.get(`${e.type}\u0000${e.value}`)
						if (prior) {
							e._decisionBases = prior.bases
							e._decisionSkip = prior.skip
						}
					}
					entry.entities = entities
					entry.entityCount = entities.length
					entry.confidentialityLabel = extractResponse.data.confidentialityLabel ?? null
					entry.confidentialityLevel = extractResponse.data.confidentialityLevel ?? null
					// Drop the read-only anonymised view so the editable review
					// list + "Anonymize" button take over. Keep anonymizedFile*
					// so the viewer toggle can still show the current result until
					// the re-run lands.
					entry.viewMode = undefined
					entry.detailUnavailable = false
					// Mark the re-anonymise sub-flow so the dossier sidebar can
					// surface the per-file review + "Anonymize" button alongside the
					// batch footer (which otherwise shadows the single-file button).
					// Cleared once the re-run completes in `anonymiseEntry`.
					entry.reanonymize = true
					entry.status = entities.length === 0 ? 'completed' : 'extracted'
				} catch (err) {
					console.error(`Failed to re-extract ${entry.name}:`, err)
					entry.error = err.response?.data?.error || err.message
					entry.status = 'error'
				}

				return entry
			},

			/**
			 * Resolve the source↔anonymised mapping recorded for a file.
			 *
			 * Every successful anonymisation persists an `anonymizationLink`
			 * object in the OpenRegister `document` register (feat #107). Both
			 * `sourceFileId` and `anonymizedFileId` are facetable, so the link
			 * resolves in either direction. The sidebar uses the *reverse*
			 * direction — given the file the user opened, is it the anonymised
			 * output of some source file? — to recognise an already-anonymised
			 * document even when its extracted text carries no parseable
			 * `[<TYPE>: <id>]` placeholders (e.g. a redacted PDF whose text
			 * layout breaks the pattern). Without this, such a file falls
			 * through to `ensureExtracted` and the un-anonymised review flow
			 * starts again on an already-anonymised file.
			 *
			 * @param {number} anonymizedFileId Nextcloud file id of the opened file.
			 * @return {Promise<object|null>} The link object, or null when none / on error.
			 */
			async findAnonymizationLink(anonymizedFileId) {
				if (anonymizedFileId === null || anonymizedFileId === undefined) {
					return null
				}
				try {
					const r = await axios.get(
						generateUrl('/apps/openregister/api/objects/document/anonymizationLink'),
						{ params: { anonymizedFileId } },
					)
					const results = r.data?.results || []
					return results[0] || null
				} catch (err) {
					// Best-effort: a missing register / search failure must not
					// block the placeholder fallback below.
					console.error(`Failed to resolve anonymization link for file ${anonymizedFileId}:`, err)
					return null
				}
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
			 * Detection is anchored on the durable `anonymizationLink` DB
			 * mapping (reverse lookup by file id); placeholder parsing is the
			 * entity source when present. Returns `null` only when the file is
			 * neither linked nor carries placeholders — the caller should then
			 * fall back to `ensureExtracted`. When a link exists but no
			 * placeholders parse (e.g. a redacted PDF), the entities are
			 * resolved from the linked source file instead.
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
			 * @param {object} [opts] Options.
			 * @param {boolean} [opts.push] Append the resolved entry to the queue
			 *   (default true). Pass false to reuse the resolution for an entry
			 *   already in the queue (e.g. a just-finished run) without creating a
			 *   duplicate card.
			 * @return {Promise<object|null>} The review entry, or null when not anonymised.
			 */
			async loadAnonymizedEntities(fileMeta, { push = true } = {}) {
				// Authoritative signal: the source↔anonymised DB mapping. A hit
				// means this file IS an anonymised output regardless of whether
				// its text yields parseable placeholders.
				const link = await this.findAnonymizationLink(fileMeta.fileId)

				let text = ''
				try {
					text = await extractDocumentText({
						path: fileMeta.path,
						mimeType: fileMeta.mimeType,
						fileName: fileMeta.fileName,
					})
				} catch (err) {
					console.error(`Failed to read text for ${fileMeta.fileName}:`, err)
					// A linked file is still anonymised even if its text is
					// unreadable — fall through to the source-entity resolution.
					if (!link) {
						return null
					}
				}

				const placeholders = parsePlaceholders(text)
				if (placeholders.length === 0 && !link) {
					return null
				}

				// No parseable placeholders but a DB link exists (e.g. redacted
				// PDF): resolve the removed entities from the linked source file
				// and map them onto the anonymised-card shape.
				if (placeholders.length === 0) {
					return this.buildLinkedAnonymizedEntry(fileMeta, link, { push })
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

				// Collapse rows duplicated by the extract-append bug so the same
				// removed value is shown once (see entity-double-extract TODO).
				const deduped = dedupeAnonEntities(resolved)

				const entry = {
					id: `file-${++fileCounter}`,
					name: fileMeta.fileName,
					status: 'completed',
					viewMode: 'anonymized',
					error: null,
					fileId: fileMeta.fileId,
					filePath: fileMeta.path,
					entities: deduped,
					entityCount: deduped.length,
					replacementCount: link?.replacementCount
						?? deduped.reduce((sum, e) => sum + e.count, 0),
					anonymizedFileId: null,
					anonymizedFileName: null,
					anonymizedFilePath: null,
					// Source mapping (feat #107) when a link was recorded.
					sourceFileId: link?.sourceFileId ?? null,
					sourceFileName: link?.sourceFileName ?? null,
					sourceFilePath: link?.sourceFilePath ?? null,
					anonymizationLinkId: link?.id ?? null,
					runCount: link?.runCount ?? null,
					dossier: inferDossier(fileMeta.path),
				}
				if (push) {
					this.files.push(entry)
				}
				return entry
			},

			/**
			 * Build a read-only summary entry from a DB link alone, for files
			 * whose text yields no parseable `[<TYPE>: <id>]` placeholders
			 * (e.g. a flattened PDF whose redaction markers don't survive text
			 * extraction).
			 *
			 * Deliberately does NOT call the `extract` endpoint: that endpoint
			 * re-runs OpenRegister's `extractFile($id, force: true)`, which
			 * APPENDS entity_relation rows every call (see the
			 * entity-double-extract TODO). Triggering it just to *view* a file
			 * grows the DB on every open/refresh — exactly the bug this avoids.
			 * Opening a file must be read-only. We therefore surface only what
			 * the link object already records (replacement count, source name);
			 * the per-entity list is available on the DOCX twin via the
			 * placeholder path, or once the file's placeholders are readable.
			 *
			 * @param {object} fileMeta File descriptor of the opened (anonymised) file.
			 * @param {object} link The resolved `anonymizationLink` object.
			 * @param {object} [opts] Options.
			 * @param {boolean} [opts.push] Append the entry to the queue (default true).
			 *   Pass false to reuse the resolution for an existing entry without
			 *   creating a duplicate card.
			 * @return {object} The read-only summary entry.
			 */
			buildLinkedAnonymizedEntry(fileMeta, link, { push = true } = {}) {
				const entry = {
					id: `file-${++fileCounter}`,
					name: fileMeta.fileName,
					status: 'completed',
					viewMode: 'anonymized',
					error: null,
					fileId: fileMeta.fileId,
					filePath: fileMeta.path,
					entities: [],
					entityCount: 0,
					replacementCount: link?.replacementCount ?? 0,
					// No readable placeholders → the detailed list can't be shown
					// without a (mutating) re-extract. The sidebar renders a note.
					detailUnavailable: true,
					anonymizedFileId: null,
					anonymizedFileName: null,
					anonymizedFilePath: null,
					sourceFileId: link?.sourceFileId ?? null,
					sourceFileName: link?.sourceFileName ?? null,
					sourceFilePath: link?.sourceFilePath ?? null,
					anonymizationLinkId: link?.id ?? null,
					runCount: link?.runCount ?? null,
					dossier: inferDossier(fileMeta.path),
				}
				if (push) {
					this.files.push(entry)
				}
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
			 * @param {boolean} force Release a sub-threshold prohibition match.
			 * @return {void}
			 */
			async toggleEntity(entry, idx, force = false) {
				const entity = entry?.entities?.[idx]
				if (entity === undefined) {
					return { ok: false, status: 0, body: {} }
				}
				// The include checkbox is the skip decision: currently included =>
				// exclude (skip=true). Fires a guarded PATCH immediately.
				return this._persistEntityDecision(entity, { skip: entity.included, force })
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
			async setEntityBases(entry, idx, bases) {
				const entity = entry?.entities?.[idx]
				if (entity === undefined) {
					return { ok: false, status: 0, body: {} }
				}
				const clean = Array.isArray(bases) ? bases : []
				entity._decisionBases = clean
				return this._persistEntityDecision(entity, { skip: !!entity._decisionSkip, bases: clean })
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
			 * @param {boolean} force Release a sub-threshold prohibition match.
			 * @return {void}
			 */
			async setEntitySkip(entry, idx, skip, force = false) {
				const entity = entry?.entities?.[idx]
				if (entity === undefined) {
					return { ok: false, status: 0, body: {} }
				}
				return this._persistEntityDecision(entity, { skip: !!skip, force })
			},

			/**
			 * Persist an entity's skip/bases decision across all its relations via
			 * the guarded endpoint, immediately. Local state is updated only on
			 * success, so a blocked skip reverts and surfaces `_patchError`.
			 * Returns {ok, status, body}; a 422 body carries {threshold,
			 * prohibitionMatch} for a dialog.
			 *
			 * @param {object} entity The entity row (mutated on success).
			 * @param {object} opts Decision: { skip, bases?, force? }.
			 * @param {boolean} opts.skip Whether the entity is skipped (excluded
			 * from the document mutation).
			 * @param {Array<string>} [opts.bases] Legal grounds to persist alongside
			 * the decision; omitted leaves the stored grounds untouched.
			 * @param {boolean} [opts.force] Release a sub-threshold prohibition match.
			 * @return {Promise<{ok: boolean, status: number, body: object}>}
			 */
			async _persistEntityDecision(entity, { skip, bases = undefined, force = false }) {
				const relationIds = Array.isArray(entity.relationIds) && entity.relationIds.length > 0
					? entity.relationIds
					: (entity.relationId != null ? [entity.relationId] : [])
				if (relationIds.length === 0) {
					return { ok: false, status: 0, body: {} }
				}
				const results = await Promise.all(relationIds.map((rid) => this.setRelationSkip(rid, skip, force, bases)))
				const bad = results.find((r) => !r.ok)
				if (bad) {
					entity._patchError = bad.body?.error || 'Kon de wijziging niet opslaan'
					return bad
				}
				entity._decisionSkip = skip
				entity.skipAnonymization = skip
				entity.included = !skip
				if (Array.isArray(bases)) {
					entity._decisionBases = bases
					entity.bases = [...bases]
				}
				entity._patchError = null
				return { ok: true, status: 200, body: {} }
			},

			/**
			 * Persist one relation's skip/include decision through DocuDesk's
			 * guarded endpoint. Returns {ok, status, body}; a 422 body carries
			 * {threshold, prohibitionMatch} so the caller can offer a force retry.
			 *
			 * @param {number} relationId The EntityRelation id.
			 * @param {boolean} skip Whether to skip (true) or include (false).
			 * @param {boolean} force Release a sub-threshold prohibition match.
			 * @param {Array<string>} [bases] Legal grounds to send with the PATCH;
			 * omitted leaves the stored grounds untouched.
			 * @return {Promise<{ok: boolean, status: number, body: object}>}
			 */
			async setRelationSkip(relationId, skip, force = false, bases = undefined) {
				try {
					const body = { skipAnonymization: !!skip, force: !!force }
					if (Array.isArray(bases)) {
						body.bases = bases
					}
					const res = await axios.patch(
						generateUrl(`/apps/docudesk/api/anonymization/relations/${relationId}`),
						body,
					)
					return { ok: true, status: res.status, body: res.data }
				} catch (err) {
					return { ok: false, status: err.response?.status ?? 0, body: err.response?.data ?? {} }
				}
			},

			/**
			 * Add a manually-selected piece of text as a new entity on the file.
			 *
			 * POSTs to the existing OpenRegister manual-entities endpoint, which
			 * persists the entity plus one relation per match in the document,
			 * then prepends the resulting review row so the newest addition sits
			 * at the top of the list. When grondslagen are supplied they are
			 * PATCHed onto the new relations straight away so they survive a
			 * reopen (rather than only at anonymise-time).
			 *
			 * Overwrite priority: the actual document replacement order is decided
			 * in `anonymiseEntry`, which sorts the payload by descending value
			 * length (longest first) so an overlapping longer span always redacts
			 * before a shorter one. Prepending here is purely the list/display
			 * priority; the length sort guarantees correctness.
			 *
			 * @param {object} entry Queue entry (must have fileId).
			 * @param {object} payload Manual-entity input.
			 * @param {string} payload.value Selected text to anonymise.
			 * @param {string} payload.type Entity type tag.
			 * @param {string[]} [payload.bases] Grondslagen to apply to the new relations.
			 * @param {boolean} [payload.wholeWord] Whole-word match flag (default true).
			 * @param {boolean} [payload.caseSensitive] Case-sensitive match flag (default true).
			 * @return {Promise<object>} The backend response payload (entity, relations, matchCount).
			 * @throws {Error} With `.message` operator-facing; on missing fileId/value/type or HTTP error.
			 */
			async addManualEntity(entry, payload) {
				if (!entry?.fileId) {
					const err = new Error('Entry has no fileId yet.')
					err.status = 0
					throw err
				}
				const value = (payload?.value || '').trim()
				const type = payload?.type || ''
				if (!value || !type) {
					const err = new Error('A value and a type are required.')
					err.status = 0
					throw err
				}

				const body = {
					value,
					type,
					wholeWord: payload?.wholeWord ?? true,
					caseSensitive: payload?.caseSensitive ?? true,
				}

				const response = await axios.post(
					generateUrl(`/apps/openregister/api/files/${entry.fileId}/manual-entities`),
					body,
				)
				const data = response.data || {}

				const relations = Array.isArray(data.relations) ? data.relations : []
				const relationIds = relations.map((r) => r.id).filter((id) => id != null)
				const bases = Array.isArray(payload?.bases) ? payload.bases : []

				// Persist the chosen grondslagen immediately (mirrors the PATCH in
				// anonymiseEntry). On failure leave `bases` empty so anonymiseEntry
				// retries the PATCH when the user anonymises.
				let persistedBases = []
				if (bases.length > 0 && relationIds.length > 0) {
					try {
						await Promise.all(relationIds.map((rid) => axios.patch(
							generateUrl(`/apps/openregister/api/entity-relations/${rid}`),
							{ bases, skipAnonymization: false },
						)))
						persistedBases = bases
					} catch (err) {
						console.error('[anonymization] failed to set grondslagen on manual entity:', err)
					}
				}

				// Build a review row in the same shape decorateEntities produces,
				// grouping every matched relation under one card.
				const newRow = {
					type: data?.entity?.type ?? type,
					value: data?.entity?.value ?? value,
					included: true,
					confidence: 1.0,
					highestConfidence: 1.0,
					fileCount: 1,
					count: relationIds.length || 1,
					relationId: relationIds[0] ?? null,
					relationIds,
					bases: [...persistedBases],
					_decisionBases: [...bases],
					_decisionSkip: false,
					skipAnonymization: false,
					_patchError: null,
				}

				// Prepend so the newest addition sits at the top of the list.
				entry.entities = [newRow, ...entry.entities]
				entry.entityCount = entry.entities.length

				return data
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
				this.batch = { running: false, total: 0, done: 0, failed: 0 }
			},
		},
	},
)
