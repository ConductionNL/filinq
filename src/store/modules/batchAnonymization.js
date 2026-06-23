import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
export const useBatchAnonymizationStore = defineStore('batchAnonymization', {
	state: () => ({ batchId: null, batchStatus: null, files: [], entities: [], progress: 0, totalFiles: 0, totalEntities: 0, error: null, processing: false, minConfidence: 0.0 }),
	getters: {
		isActive: (state) => state.batchId !== null,
		selectedEntityCount: (state) => state.entities.filter((e) => e.included).length,
		filesWithEntities: (state) => state.files.filter((f) => (f.entityCount || 0) > 0).length,
		stepNumber(state) { return { uploading: 0, extracting: 1, review: 2, anonymizing: 3, completed: 4, error: 0 }[state.batchStatus] || 0 },
	},
	actions: {
		async uploadBatch(fileList) {
			this.processing = true; this.error = null
			try {
				const fd = new FormData(); Array.from(fileList).forEach((f, i) => fd.append('files' + i, f))
				const r = await axios.post(generateUrl('/apps/docudesk/api/anonymization/batch/upload'), fd, { headers: { 'Content-Type': 'multipart/form-data' } })
				this.batchId = r.data.batchId; this.files = r.data.files || []; this.totalFiles = r.data.fileCount || 0; this.batchStatus = 'extracting'
				await this.extractAll()
			} catch (e) { this.error = e.response?.data?.error || e.message; this.batchStatus = 'error' } finally { this.processing = false }
		},
		async extractAll() {
			while (this.batchStatus === 'extracting') {
				try {
					const r = await axios.post(generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/extract'))
					this.progress = Math.round((r.data.filesExtracted / r.data.totalFiles) * 100)
					if (r.data.fileId) { const i = this.files.findIndex((f) => f.fileId === r.data.fileId); if (i >= 0) { this.files[i].entityCount = r.data.entityCount || 0; this.files[i].status = r.data.error ? 'error' : 'extracted' } }
					if (r.data.batchStatus === 'review') { this.batchStatus = 'review'; await this.fetchEntities() }
				} catch (e) { this.error = e.response?.data?.error || e.message; this.batchStatus = 'error'; break }
			}
		},
		async fetchEntities() {
			try {
				let u = '/apps/docudesk/api/anonymization/batch/' + this.batchId + '/entities'
				if (this.minConfidence > 0) u += '?minConfidence=' + this.minConfidence
				const r = await axios.get(generateUrl(u)); this.entities = r.data.entities || []; this.totalEntities = r.data.entityCount || 0
			} catch (e) { this.error = e.response?.data?.error || e.message }
		},
		toggleEntity(i) { if (this.entities[i]) this.entities[i].included = !this.entities[i].included },
		setVisibleEntities(indices, inc) { indices.forEach((i) => { if (this.entities[i]) this.entities[i].included = inc }) },
		async anonymizeBatch() {
			this.processing = true; this.error = null; this.batchStatus = 'anonymizing'
			try {
				const sel = this.entities.filter((e) => e.included).map((e) => ({ type: e.type, value: e.value, confidence: e.highestConfidence }))
				// scope=dossier: a batch IS a folder/dossier, so a person gets the
				// same scope-local placeholder number across all the batch's files.
				await axios.post(generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/anonymize'), { entities: sel, scope: 'dossier' })
				this.batchStatus = 'completed'; await this.refreshStatus()
			} catch (e) { this.error = e.response?.data?.error || e.message; this.batchStatus = 'error' } finally { this.processing = false }
		},
		async refreshStatus() {
			if (!this.batchId) return
			try { const r = await axios.get(generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/status')); this.batchStatus = r.data.batchStatus; this.files = r.data.files || this.files } catch (e) { /* silent */ }
		},
		getReportUrl() { return generateUrl('/apps/docudesk/api/anonymization/batch/' + this.batchId + '/report') },
		reset() { Object.assign(this, { batchId: null, batchStatus: null, files: [], entities: [], progress: 0, totalFiles: 0, totalEntities: 0, error: null, processing: false, minConfidence: 0.0 }) },
	},
})
