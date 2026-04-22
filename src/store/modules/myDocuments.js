/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const useMyDocumentsStore = defineStore(
	'myDocuments',
	{
		state: () => ({
			documents: [],
			loading: false,
			error: null,
			total: 0,
		}),
		getters: {
			documentStats: (state) => {
				const total = state.documents.length
				const entitiesDetected = state.documents.reduce((sum, f) => sum + (f.entityCount || 0), 0)
				const highRisk = state.documents.filter((f) => f.riskLevel === 'high' || f.riskLevel === 'very_high').length
				return { total, entitiesDetected, highRisk }
			},
		},
		actions: {
			async fetchDocuments() {
				this.loading = true
				this.error = null
				try {
					const response = await axios.get(generateUrl('/apps/openregister/api/files'), {
						params: { limit: 500, offset: 0, sort: 'extractedAt', order: 'DESC' },
					})
					const payload = response.data || {}
					const rows = Array.isArray(payload.data) ? payload.data : []
					this.documents = rows.map((row) => ({
						fileId: row.id,
						fileName: row.fileName,
						mimeType: row.mimeType,
						fileSize: row.fileSize,
						entityCount: row.entityCount || 0,
						riskLevel: row.riskLevel || 'none',
						modified: row.extractedAt,
						extractionStatus: row.extractionStatus,
						isAnonymized: typeof row.fileName === 'string' && row.fileName.includes('_anonymized'),
					}))
					this.total = payload.count || this.documents.length
				} catch (err) {
					console.error('Failed to fetch documents:', err)
					this.error = err.message
				} finally {
					this.loading = false
				}
			},
		},
	},
)
