import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

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
			bases: ['persoonsgegevens'],
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
		selectedEntityCount: (state) => state.entities.filter((e) => e.included).length,
		filesWithEntities: (state) => state.files.filter((f) => (f.entityCount || 0) > 0).length,
		extractedCount: (state) => state.files.filter((f) => f.status === 'extracted' || f.status === 'error').length,
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
					this.dossier.name = this.folderPath.split('/').filter(Boolean).pop() || this.folderPath
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
		 * defaulted to ['persoonsgegevens'] but the operator can multi-select
		 * from the six canonical Woo Art. 5 grondslagen.
		 */
		async createDossier() {
			if (!this.folderId) {
				this.dossier.error = 'No folder bound yet — start the folder batch first.'
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
				this.dossier.uuid = data?.['@self']?.id || data?.id || data?.uuid || null
				if (!this.dossier.uuid) {
					this.dossier.error = 'Dossier created but UUID not found in response.'
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
					generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/status'),
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
				let url = '/apps/docudesk/api/anonymization/batch/' + this.batchId + '/entities'
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
				this.entities[index]._decisionBases = Array.isArray(bases) ? [...bases] : []
				this.entities[index]._patchError = null
			}
		},

		setEntitySkip(index, skip) {
			if (this.entities[index]) {
				this.entities[index]._decisionSkip = !!skip
				this.entities[index]._patchError = null
			}
		},

		async anonymizeBatch() {
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

					const hasBases = Array.isArray(entity._decisionBases) && entity._decisionBases.length > 0
					const hasSkip = !!entity._decisionSkip
					if (!hasBases && !hasSkip) {
						continue
					}

					const relationIds = Array.isArray(entity.relationIds) ? entity.relationIds : []
					if (relationIds.length === 0) {
						entity._patchError = 'No relation ids — decisions cannot persist.'
						continue
					}

					const payload = {
						bases: hasBases ? [...entity._decisionBases] : [],
						skipAnonymization: hasSkip,
					}
					for (const relationId of relationIds) {
						try {
							await axios.patch(
								generateUrl(`/apps/openregister/api/entity-relations/${relationId}`),
								payload,
							)
						} catch (err) {
							entity._patchError = err.response?.data?.error || err.message
							// Continue with other relations + entities — partial
							// application beats all-or-nothing in a review flow.
						}
					}
				}

				// Step 2 — trigger the batch anonymise. OR honours the
				// skipAnonymization flag we just PATCHed, so skipped entities
				// stay unredacted in the output.
				const selected = this.entities
					.filter((e) => e.included)
					.map((e) => ({ type: e.type, value: e.value, confidence: e.highestConfidence }))
				await axios.post(
					generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/anonymize'),
					{
						entities: selected,
						// Wave 4a flag — when true, each per-file anonymise call
						// gets a grondslagen-summary page appended to its output.
						// The summary is only produced when appendBasisSummary
						// and outputFormat travel together, so send the format
						// alongside the flag (omit both when summarising is off).
						...(this.appendBasisSummary
							? { appendBasisSummary: true, outputFormat: 'pdf' }
							: {}),
					},
				)
				this.batchStatus = 'completed'
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
					generateUrl('/apps/docudesk/api/anonymization/dossier/' + this.dossier.uuid + '/grondslagen-pdf'),
				)
				this.report.result = r.data
			} catch (e) {
				this.report.error = e.response?.data?.error || e.message
			} finally {
				this.report.generating = false
			}
		},

		getReportUrl() {
			return generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/report')
		},

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
					bases: ['persoonsgegevens'],
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
