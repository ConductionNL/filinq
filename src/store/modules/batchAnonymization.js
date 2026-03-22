import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
export const useBatchAnonymizationStore = defineStore('batchAnonymization', {
	state: () => ({ batchId: null, batchStatus: null, files: [], entities: [], progress: 0, totalFiles: 0, totalEntities: 0, error: null, processing: false, minConfidence: 0.0 }),
	getters: {
		isActive: (state) => state.batchId !== null,
		selectedEntityCount: (state) => state.entities.filter((e) => e.included).length,
		filesWithEntities: (state) => state.files.filter((f) => (f.entityCount || 0) > 0).length,
		extractionComplete: (state) => ['review', 'anonymizing', 'completed'].includes(state.batchStatus),
		stepNumber(state) {
			const m = { uploading: 0, extracting: 1, review: 2, anonymizing: 3, completed: 4, error: 0 }
			return m[state.batchStatus] || 0
		},
	},
	actions: {
		async uploadBatch(fileList) {
			this.processing = true; this.error = null
			try {
				const formData = new FormData()
				Array.from(fileList).forEach((file, i) => { formData.append('files' + i, file) })
				const r = await axios.post(generateUrl('/apps/docudesk/api/anonymization/batch/upload'), formData, { headers: { 'Content-Type': 'multipart/form-data' } })
				this.batchId = r.data.batchId; this.files = r.data.files || []; this.totalFiles = r.data.fileCount || 0; this.batchStatus = 'extracting'
				await this.extractAll()
			} catch (err) { this.error = err.response?.data?.error || err.message; this.batchStatus = 'error' } finally { this.processing = false }
		},
		async extractAll() {
			this.batchStatus = 'extracting'
			while (this.batchStatus === 'extracting') {
				try {
					const r = await axios.post(generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/extract'))
					this.progress = Math.round((r.data.filesExtracted / r.data.totalFiles) * 100)
					if (r.data.fileId) {
						const idx = this.files.findIndex((f) => f.fileId === r.data.fileId)
						if (idx >= 0) { this.files[idx].entityCount = r.data.entityCount || 0; this.files[idx].status = r.data.error ? 'error' : 'extracted' }
					}
					if (r.data.batchStatus === 'review') { this.batchStatus = 'review'; await this.fetchEntities() }
				} catch (err) { this.error = err.response?.data?.error || err.message; this.batchStatus = 'error'; break }
			}
		},
		async fetchEntities() {
			try {
				let url = '/apps/docudesk/api/anonymization/batch/' + this.batchId + '/entities'
				if (this.minConfidence > 0) { url += '?minConfidence=' + this.minConfidence }
				const r = await axios.get(generateUrl(url))
				this.entities = r.data.entities || []; this.totalEntities = r.data.entityCount || 0
			} catch (err) { this.error = err.response?.data?.error || err.message }
		},
		toggleEntity(index) { if (this.entities[index]) { this.entities[index].included = !this.entities[index].included } },
		setVisibleEntities(indices, included) { indices.forEach((i) => { if (this.entities[i]) { this.entities[i].included = included } }) },
		async anonymizeBatch() {
			this.processing = true; this.error = null; this.batchStatus = 'anonymizing'
			try {
				const sel = this.entities.filter((e) => e.included).map((e) => ({ type: e.type, value: e.value, confidence: e.highestConfidence }))
				await axios.post(generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/anonymize'), { entities: sel })
				this.batchStatus = 'completed'; await this.refreshStatus()
			} catch (err) { this.error = err.response?.data?.error || err.message; this.batchStatus = 'error' } finally { this.processing = false }
		},
		async refreshStatus() {
			if (!this.batchId) { return }
			try {
				const r = await axios.get(generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/status'))
				this.batchStatus = r.data.batchStatus; this.files = r.data.files || this.files; this.progress = r.data.progress || this.progress
			} catch (err) { /* silent */ }
		},
		getReportUrl() { return generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/report') },
		reset() { this.batchId = null; this.batchStatus = null; this.files = []; this.entities = []; this.progress = 0; this.totalFiles = 0; this.totalEntities = 0; this.error = null; this.processing = false; this.minConfidence = 0.0 },
	},
})
