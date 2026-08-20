import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import { WOO_BASES } from '../../constants/grondslagen.js'

export const useFolderAnonymizationStore = defineStore('folderAnonymization', {
	state: () => ({
		folderPath: '',
		folderId: null,
		batchId: null,
		batchStatus: null,
		files: [],
		entities: [],
		progress: 0,
		totalFiles: 0,
		totalEntities: 0,
		error: null,
		processing: false,
		pollTimer: null,
		minConfidence: 0.0,

		// Wave 4a (anonymisation-grondslagen-summary) state.
		// Operator can optionally create a dossier for the chosen folder so
		// the per-dossier grondslagen report has something to operate on.
		dossier: {
			uuid: null,
			name: '',
			description: '',
			bases: ['art-5-1-2-e'],
			creating: false,
			error: null,
		},
		appendBasisSummary: true,
		report: {
			generating: false,
			result: null,
			error: null,
		},
	}),
	getters: {
		isActive: (state) => state.batchId !== null,
		selectedEntityCount: (state) =>
			state.entities.filter((e) => e.included).length,
		filesWithEntities: (state) =>
			state.files.filter((f) => (f.entityCount || 0) > 0).length,
		extractedCount: (state) =>
			state.files.filter(
				(f) => f.status === 'extracted' || f.status === 'error',
			).length,
		basesOptions: () => WOO_BASES,
		hasDossier: (state) => state.dossier.uuid !== null,
	},
	actions: {
		async startFolderBatch(folderPath) {
			this.processing = true
			this.error = null
			this.folderPath = folderPath
			try {
				const r = await axios.post(
					generateUrl('/apps/docudesk/api/anonymization/batch/folder'),
					{ folderPath },
				)
				this.batchId = r.data.batchId
				this.folderId = r.data.folderId || null
				this.files = r.data.files || []
				this.totalFiles = r.data.fileCount || this.files.length
				this.batchStatus = 'extracting'

				// Default the dossier name to the folder's basename so the
				// operator just hits "Create" if they're happy with defaults.
				if (!this.dossier.name) {
					this.dossier.name =
						this.folderPath.split('/').filter(Boolean).pop()
						|| this.folderPath
				}

				this.startPolling()
			} catch (e) {
				this.error = e.response?.data?.error || e.message
				this.batchStatus = 'error'
			} finally {
				this.processing = false
			}
		},

		/**
		 * Create a dossier bound to the current folder.
		 *
		 * Issues a POST to OR's generic object-create endpoint, with
		 * `@self.folder` set to the folder's node id so subsequent
		 * `renderDossierSummary` calls find the right files. Bases are
		 * defaulted to ['art-5-1-2-e'] (J — persoonlijke levenssfeer) but the operator can multi-select
		 * from the six canonical Woo Art. 5 grondslagen.
		 */
		async createDossier() {
			if (!this.folderId) {
				this.dossier.error =
					'No folder bound yet — start the folder batch first.'
				return
			}
			this.dossier.creating = true
			this.dossier.error = null
			try {
				const payload = {
					name: this.dossier.name || this.folderPath || 'Dossier',
					description: this.dossier.description || '',
					bases: this.dossier.bases || [],
					'@self': { folder: String(this.folderId) },
				}
				const r = await axios.post(
					generateUrl('/apps/openregister/api/objects/dossier/dossier'),
					payload,
				)
				const data = r.data
				this.dossier.uuid =
					data?.['@self']?.id || data?.id || data?.uuid || null
				if (!this.dossier.uuid) {
					this.dossier.error =
						'Dossier created but UUID not found in response.'
				}
			} catch (e) {
				this.dossier.error = e.response?.data?.error || e.message
			} finally {
				this.dossier.creating = false
			}
		},

		startPolling() {
			this.stopPolling()
			this.pollTimer = setInterval(() => this.pollStatus(), 3000)
		},

		stopPolling() {
			if (this.pollTimer) {
				clearInterval(this.pollTimer)
				this.pollTimer = null
			}
		},

		async pollStatus() {
			if (!this.batchId) return
			try {
				const r = await axios.get(
					generateUrl(
						'/apps/docudesk/api/anonymization/batch/'
							+ this.batchId
							+ '/status',
					),
				)
				this.batchStatus = r.data.batchStatus
				this.files = r.data.files || this.files
				this.progress = r.data.progress || 0
				this.totalFiles = r.data.totalFiles || this.totalFiles

				if (r.data.batchStatus === 'review') {
					this.stopPolling()
					await this.fetchEntities()
				}
			} catch (e) {
				this.error = e.response?.data?.error || e.message
			}
		},

		async fetchEntities() {
			try {
				let url =
					'/apps/docudesk/api/anonymization/batch/'
					+ this.batchId
					+ '/entities'
				if (this.minConfidence > 0) {
					url += '?minConfidence=' + this.minConfidence
				}
				const r = await axios.get(generateUrl(url))
				this.entities = (r.data.entities || []).map((e) => ({
					...e,
					included: true,
					// Per-entity review state — mirrors AnonymizationWidget's
					// single-file flow but operates on the consolidated
					// (multi-file) view. _decisionBases applies to every
					// underlying relationId on submit.
					_decisionBases: [],
					_decisionSkip: false,
					_patchError: null,
				}))
				this.totalEntities = r.data.entityCount || 0
			} catch (e) {
				this.error = e.response?.data?.error || e.message
			}
		},

		toggleEntity(index) {
			if (this.entities[index]) {
				this.entities[index].included = !this.entities[index].included
			}
		},

		setVisibleEntities(indices, included) {
			indices.forEach((i) => {
				if (this.entities[i]) this.entities[i].included = included
			})
		},

		setEntityBases(index, bases) {
			if (this.entities[index]) {
				this.entities[index]._decisionBases = Array.isArray(bases)
					? [...bases]
					: []
				this.entities[index]._patchError = null
			}
		},

		setEntitySkip(index, skip) {
			if (this.entities[index]) {
				this.entities[index]._decisionSkip = !!skip
				this.entities[index]._patchError = null
			}
		},

		/**
		 * Anonymise the analysed folder by calling the **tested single-file**
		 * endpoint once per file with `scope: 'dossier'`.
		 *
		 * The folder *analysis* still runs through the batch path (it's the
		 * documented folder-analyse flow), but anonymisation deliberately does
		 * NOT use `POST /batch/{id}/anonymize` (lightly tested, off-architecture).
		 * Instead every extracted file goes through
		 * `POST /api/anonymization/anonymize/{fileId}` — the same endpoint the
		 * single-file flow exercises — with a dossier scope so placeholder
		 * numbering stays consistent across the whole folder.
		 */
		async anonymizeFolder() {
			this.processing = true
			this.error = null
			this.batchStatus = 'anonymizing'
			try {
				// Step 1 — PATCH per-entity grondslagen / skip decisions onto
				// every underlying EntityRelation row. Each consolidated entity
				// may span many files; the relationIds array carries one id
				// per occurrence. Decisions are applied to every relation so a
				// single picker selection covers all instances of the value.
				// Failures are surfaced per-entity but do not abort the batch.
				for (const entity of this.entities) {
					if (!entity.included) {
						continue
					}

					const hasBases =
						Array.isArray(entity._decisionBases)
						&& entity._decisionBases.length > 0
					const hasSkip = !!entity._decisionSkip
					if (!hasBases && !hasSkip) {
						continue
					}

					const relationIds = Array.isArray(entity.relationIds)
						? entity.relationIds
						: []
					if (relationIds.length === 0) {
						entity._patchError =
							'No relation ids — decisions cannot persist.'
						continue
					}

					const payload = {
						bases: hasBases ? [...entity._decisionBases] : [],
						skipAnonymization: hasSkip,
					}
					for (const relationId of relationIds) {
						try {
							await axios.patch(
								generateUrl(
									`/apps/openregister/api/entity-relations/${relationId}`,
								),
								payload,
							)
						} catch (err) {
							entity._patchError =
								err.response?.data?.error || err.message
							// Continue with other relations + entities — partial
							// application beats all-or-nothing in a review flow.
						}
					}
				}

				// Step 2 — anonymise each file individually through the tested
				// single-file endpoint. The same approved entity list is sent to
				// every file; OR honours the skipAnonymization flag we just
				// PATCHed, so skipped entities stay unredacted in the output.
				//
				// Longest value first: OpenRegister replaces matches in payload
				// order via str_ireplace. If a shorter span runs first it eats
				// its own text out of an overlapping longer span — e.g. redacting
				// "Claudia Fischer" before "Mevrouw Claudia Fischer" leaves a
				// dangling "Mevrouw". Sorting by descending length makes the
				// longest overlap redact first. (Mirrors the single-file flow in
				// anonymization.js.)
				const selected = this.entities
					.filter((e) => e.included)
					.map((e) => ({
						type: e.type,
						value: e.value,
						confidence: e.highestConfidence,
					}))
					.sort((a, b) => (b.value || '').length - (a.value || '').length)

				// Only files that extracted successfully carry entities to redact;
				// files that errored during extraction have nothing to anonymise.
				const targets = this.files.filter((f) => f.status === 'extracted')
				let succeeded = 0
				let lastError = null
				for (const file of targets) {
					try {
						const r = await axios.post(
							generateUrl(
								`/apps/docudesk/api/anonymization/anonymize/${file.fileId}`,
							),
							{
								entities: selected,
								// Dossier scope keeps placeholder numbering consistent
								// across every single-file call in this folder.
								scope: 'dossier',
								// Stable folder id names the dossier so numbering stays
								// consistent across these separate single-file calls.
								// Aligns with the @self.folder used in createDossier().
								// Omit when unknown → OR falls back to the parent folder.
								...(this.folderId
									? { dossierKey: String(this.folderId) }
									: {}),
								// Wave 4a flag — when true, each per-file anonymise call
								// gets a grondslagen-summary page appended to its output.
								// The summary is only produced when appendBasisSummary
								// and outputFormat travel together, so send the format
								// alongside the flag (omit both when summarising is off).
								...(this.appendBasisSummary
									? {
											appendBasisSummary: true,
											outputFormat: 'pdf-only',
										}
									: {}),
							},
						)
						file.status = 'completed'
						file.anonymizedFileId = r.data.anonymizedFileId
						file.replacementCount = r.data.replacementCount || 0
						file.complete = r.data.complete !== false
						file.residualCount = r.data.residualCount || 0
						file.anonymizeError = null
						succeeded++
					} catch (err) {
						// Continue with the remaining files — partial application
						// beats all-or-nothing in a review flow.
						file.status = 'error'
						file.anonymizeError =
							err.response?.data?.error || err.message
						lastError = file.anonymizeError
					}
					this.progress =
						targets.length > 0
							? Math.round((succeeded / targets.length) * 100)
							: 100
				}

				if (succeeded > 0) {
					this.batchStatus = 'completed'
					this.error = lastError ? `Some files failed: ${lastError}` : null
				} else {
					this.batchStatus = 'error'
					this.error = lastError || 'No files could be anonymised.'
				}
			} catch (e) {
				this.error = e.response?.data?.error || e.message
				this.batchStatus = 'error'
			} finally {
				this.processing = false
			}
		},

		/**
		 * Trigger the per-dossier grondslagen report regeneration.
		 *
		 * Only meaningful when a dossier has been created via
		 * {@link createDossier}; without a dossier UUID the button stays
		 * disabled on the view side.
		 */
		async generateDossierReport() {
			if (!this.dossier.uuid) {
				this.report.error = 'No dossier UUID — create a dossier first.'
				return
			}
			this.report.generating = true
			this.report.error = null
			this.report.result = null
			try {
				const r = await axios.post(
					generateUrl(
						'/apps/docudesk/api/anonymization/dossier/'
							+ this.dossier.uuid
							+ '/grondslagen-pdf',
					),
				)
				this.report.result = r.data
			} catch (e) {
				this.report.error = e.response?.data?.error || e.message
			} finally {
				this.report.generating = false
			}
		},

		// Temporarily removed. The batch CSV report
		// (GET /batch/{id}/report) reads per-file anonymisedFileId /
		// replacementCount from the batch state record and requires the batch
		// to be marked 'completed' — both written only by the batch-anonymise
		// step, which the folder flow no longer runs (we anonymise file-by-file
		// via the single-file endpoint instead). This comes back once per-file
		// results are written back to the batch record (or a client-side
		// summary is built).
		// getReportUrl() {
		//     return generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/report')
		// },

		reset() {
			this.stopPolling()
			Object.assign(this, {
				folderPath: '',
				folderId: null,
				batchId: null,
				batchStatus: null,
				files: [],
				entities: [],
				progress: 0,
				totalFiles: 0,
				totalEntities: 0,
				error: null,
				processing: false,
				minConfidence: 0.0,
				dossier: {
					uuid: null,
					name: '',
					description: '',
					bases: ['art-5-1-2-e'],
					creating: false,
					error: null,
				},
				appendBasisSummary: true,
				report: {
					generating: false,
					result: null,
					error: null,
				},
			})
		},
	},
})
