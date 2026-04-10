import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const useFolderAnonymizationStore = defineStore('folderAnonymization', {
	state: () => ({
		folderPath: '',
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
	}),
	getters: {
		isActive: (state) => state.batchId !== null,
		selectedEntityCount: (state) => state.entities.filter((e) => e.included).length,
		filesWithEntities: (state) => state.files.filter((f) => (f.entityCount || 0) > 0).length,
		extractedCount: (state) => state.files.filter((f) => f.status === 'extracted' || f.status === 'error').length,
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
				this.files = r.data.files || []
				this.totalFiles = r.data.fileCount || this.files.length
				this.batchStatus = 'extracting'
				this.startPolling()
			} catch (e) {
				this.error = e.response?.data?.error || e.message
				this.batchStatus = 'error'
			} finally {
				this.processing = false
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
				this.entities = (r.data.entities || []).map((e) => ({ ...e, included: true }))
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

		async anonymizeBatch() {
			this.processing = true
			this.error = null
			this.batchStatus = 'anonymizing'
			try {
				const selected = this.entities
					.filter((e) => e.included)
					.map((e) => ({ type: e.type, value: e.value, confidence: e.highestConfidence }))
				await axios.post(
					generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/anonymize'),
					{ entities: selected },
				)
				this.batchStatus = 'completed'
			} catch (e) {
				this.error = e.response?.data?.error || e.message
				this.batchStatus = 'error'
			} finally {
				this.processing = false
			}
		},

		getReportUrl() {
			return generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/report')
		},

		reset() {
			this.stopPolling()
			Object.assign(this, {
				folderPath: '',
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
			})
		},
	},
})
