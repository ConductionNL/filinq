/* eslint-disable no-console */
/**
 * Anonymisation PoC store — operator-driven proof-of-concept variant
 * of `anonymization.js`. Sibling, not replacement.
 *
 * Adds three behaviours over the legacy store:
 *   1. `basesOptions` is a getter that lazy-fetches the list of legal
 *      grondslagen from OpenRegister's `dossier/base` catalogue and
 *      falls back to the canonical `WOO_BASES` list on any error.
 *   2. `addManualEntity(entry, body)` posts to OpenRegister's new
 *      `POST /api/files/{fileId}/manual-entities` endpoint (OR #1593)
 *      and merges the returned relations back into the entry's review
 *      table so the operator sees what they just added.
 *   3. The merged manual-entity rows carry `_decisionBases` /
 *      `_decisionSkip` review fields just like detector-produced rows,
 *      so the existing PATCH-decisions step on `anonymiseEntry`
 *      picks them up unchanged.
 *
 * Per-file lifecycle is identical to the legacy flow:
 *   queued -> uploading -> extracting -> extracted [user reviews]
 *     -> anonymising -> completed
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import { WOO_BASES } from '../../constants/grondslagen.js'

let fileCounter = 0

export const useAnonymizationPocStore = defineStore(
	'anonymizationPoc',
	{
		state: () => ({
			files: [],
			processing: false,
			basesCache: null,
			basesError: null,
			basesLoading: false,
		}),
		getters: {
			hasFiles: (state) => state.files.length > 0,
			hasCompleted: (state) => state.files.some((f) => f.status === 'completed'),
			hasExtracted: (state) => state.files.some((f) => f.status === 'extracted'),
			allDone: (state) => state.files.length > 0
				&& state.files.every((f) => f.status === 'completed' || f.status === 'error'),
			isProcessing: (state) => state.processing,
			/**
			 * Returns the cached basesCache when set, otherwise the
			 * canonical fallback list. Triggers a background load on
			 * first access so the operator-defined values replace the
			 * fallback once the request resolves.
			 *
			 * @param {object} state Store state.
			 * @return {string[]}
			 */
			basesOptions: (state) => {
				if (Array.isArray(state.basesCache) && state.basesCache.length > 0) {
					return state.basesCache
				}
				return WOO_BASES
			},
		},
		actions: {
			/**
			 * Lazy-fetch the legal-grondslagen list from
			 * `/apps/openregister/api/objects/dossier/base`. Idempotent —
			 * concurrent callers share the in-flight request. On error,
			 * silently falls back to the hardcoded list and surfaces the
			 * error message on `basesError` for the widget to render.
			 *
			 * @return {Promise<void>}
			 */
			async ensureBases() {
				if (Array.isArray(this.basesCache) && this.basesCache.length > 0) {
					return
				}
				if (this.basesLoading) {
					return
				}

				this.basesLoading = true
				try {
					const r = await axios.get(
						generateUrl('/apps/openregister/api/objects/dossier/base'),
						{ params: { _limit: 100 } },
					)
					const items = Array.isArray(r.data) ? r.data : (r.data?.results || r.data?.items || [])
					const values = items
						.map((row) => {
							if (typeof row === 'string') return row
							return row?.slug || row?.value || row?.id || row?.['@self']?.id || null
						})
						.filter((v) => typeof v === 'string' && v.length > 0)

					if (values.length > 0) {
						this.basesCache = values
						this.basesError = null
					} else {
						this.basesError = 'Empty bases catalogue — using canonical fallback list.'
						console.warn('[anonymizationPoc] dossier/base returned no usable rows; falling back to WOO_BASES.')
					}
				} catch (err) {
					this.basesError = err.response?.data?.error || err.message
					console.warn('[anonymizationPoc] dossier/base fetch failed; falling back to WOO_BASES:', this.basesError)
				} finally {
					this.basesLoading = false
				}
			},

			/**
			 * Enqueue files and run upload + extract on each. Mirrors the
			 * legacy flow: stops at `extracted` so the user can review
			 * decisions before anonymisation.
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
						manualEntityNotices: [],
						_file: file,
					}),
				)

				this.files.push(...newEntries)
				await this.processQueue()
			},

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
			 * Upload + extract a single entry. Stops at `extracted`.
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

					entry.entities = entities.map((e) => this.seedReviewState(e))

					if (entities.length === 0) {
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
			 * Stamp a freshly-fetched entity with the per-row review-state
			 * fields the widget binds against (`_decisionBases`,
			 * `_decisionSkip`, `_patchError`). Used both for detector
			 * output and for manual-entity rows the operator just added.
			 *
			 * @param {object} entity Entity row from the extract / manual-add response.
			 * @return {object}
			 */
			seedReviewState(entity) {
				return {
					...entity,
					_decisionBases: Array.isArray(entity.bases) ? [...entity.bases] : [],
					_decisionSkip: !!entity.skipAnonymization,
					_patchError: null,
				}
			},

			/**
			 * POST `/apps/openregister/api/files/{fileId}/manual-entities`
			 * for the given entry. On 201 / 200, merges the returned
			 * relations into `entry.entities` (with the operator-supplied
			 * value / type / category copied onto each row so the
			 * existing review table can render them).
			 *
			 * Errors are mapped to display-friendly strings and returned
			 * to the caller — the modal stays open so the operator can
			 * correct and retry.
			 *
			 * @param {object} entry          Queue entry (must have fileId).
			 * @param {object} body           Manual-entity request body.
			 * @param {string} body.value     Operator-supplied text.
			 * @param {string} body.type      Entity type tag.
			 * @param {string} [body.category] Optional category.
			 * @param {boolean} [body.wholeWord]     Whole-word match flag (default true).
			 * @param {boolean} [body.caseSensitive] Case-sensitive match flag (default true).
			 * @return {Promise<object>} Resolves with the response payload on success.
			 * @throws {Error} Error with `status`, `error`, optional `field` / `reason`
			 *                 properties tacked on; `.message` is the operator-facing string.
			 */
			async addManualEntity(entry, body) {
				if (!entry?.fileId) {
					const err = new Error('Entry has no fileId yet.')
					err.status = 0
					err.error = 'invalid_request'
					throw err
				}

				try {
					const response = await axios.post(
						generateUrl(`/apps/openregister/api/files/${entry.fileId}/manual-entities`),
						body,
					)

					const data = response.data || {}
					const entityShell = {
						value: data?.entity?.value ?? body.value,
						type: data?.entity?.type ?? body.type,
						category: body.category || null,
						confidence: 1.0,
						bases: [],
						skipAnonymization: false,
					}

					const newRows = (data.relations || []).map((relation) => this.seedReviewState({
						...entityShell,
						relationId: relation.id,
						chunkId: relation.chunkId,
						positionStart: relation.positionStart,
						positionEnd: relation.positionEnd,
						context: relation.context,
					}))

					entry.entities = [...entry.entities, ...newRows]
					entry.entityCount = entry.entities.length

					const notice = {
						value: body.value,
						type: body.type,
						matchCount: data.matchCount ?? newRows.length,
						matchesSkipped: data.matchesSkipped ?? 0,
						reused: !!data?.entity?.reused,
						zeroMatch: (data.matchCount ?? newRows.length) === 0,
						message: data.message || null,
					}
					entry.manualEntityNotices = [...(entry.manualEntityNotices || []), notice]

					return data
				} catch (err) {
					const status = err.response?.status ?? 0
					const rawError = err.response?.data?.error || err.message || 'unknown_error'
					const field = err.response?.data?.field
					const reason = err.response?.data?.reason

					const mapped = this.mapManualEntityError(status, rawError, field, reason)
					const mappedErr = new Error(mapped)
					mappedErr.status = status
					mappedErr.error = rawError
					mappedErr.field = field
					mappedErr.reason = reason
					throw mappedErr
				}
			},

			/**
			 * Translate a manual-entity failure to a single operator-facing
			 * message. Centralised so the widget + modal map the same way.
			 *
			 * @param {number} status HTTP status code (0 = network error).
			 * @param {string} error  Server-supplied error code or message.
			 * @param {string} [field] Field hint (set on 400 invalid_request).
			 * @param {string} [reason] Free-text reason (e.g. 415 details).
			 * @return {string}
			 */
			mapManualEntityError(status, error, field, reason) {
				if (status === 400) {
					if (error === 'invalid_request' && field) {
						return field === 'value'
							? 'Please enter the text you want to add to the anonymisation list.'
							: 'Please pick an entity type.'
					}
					if (error === 'regex_compile_failure') {
						return "That value can't be matched — it may be too long (max 200 characters) or contain invalid characters."
					}
					return reason || 'Invalid request.'
				}
				if (status === 401) {
					return 'Your session has expired. Refresh the page and sign in again.'
				}
				if (status === 403) {
					return "You don't have write access to this file."
				}
				if (status === 415) {
					return 'The request was not sent as JSON. This is a client bug — please report it.'
				}
				if (status === 422) {
					return "This file hasn't been text-extracted yet. Run extraction first, then retry."
				}
				if (status === 500 || status === 0) {
					return 'Something went wrong on the server. Try again in a moment.'
				}
				return error
			},

			/**
			 * Apply review decisions then trigger anonymisation. Identical
			 * to the legacy flow's `anonymiseEntry`.
			 *
			 * @param {object} entry Queue entry (must be in `extracted` status).
			 */
			async anonymiseEntry(entry) {
				if (entry.status !== 'extracted') {
					return
				}

				entry.status = 'anonymising'
				try {
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
						}
					}

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
