/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/*
 * Each document entry (mapped from OpenRegister's /api/files response):
 * {
 *   fileId: number,              - Nextcloud file id.
 *   fileName: string,            - File name including extension.
 *   mimeType: string,            - MIME type.
 *   fileSize: number,            - Size in bytes.
 *   entityCount: number,         - Entities detected by the pipeline.
 *   riskLevel: string,           - 'none' | 'low' | 'medium' | 'high' | 'very_high'.
 *   modified: string | number,   - Timestamp of last extraction.
 *   extractionStatus: string,    - Status from OpenRegister.
 *   isAnonymized: boolean,       - True when the file name contains '_anonymized'.
 * }
 */

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
			/**
			 * Aggregate stats for the stats blocks on the My Documents page.
			 *
			 * @param {object} state Store state.
			 * @return {{ total: number, entitiesDetected: number, highRisk: number }}
			 */
			documentStats: (state) => {
				const total = state.documents.length
				const entitiesDetected = state.documents.reduce((sum, f) => sum + (f.entityCount || 0), 0)
				const highRisk = state.documents.filter((f) => f.riskLevel === 'high' || f.riskLevel === 'very_high').length
				return { total, entitiesDetected, highRisk }
			},
		},
		actions: {
			/**
			 * Fetch all files that went through the OpenRegister extraction
			 * pipeline and map them to the shape our UI expects.
			 * No server-side filter on anonymization — the My Documents page
			 * shows originals and anonymized copies side by side.
			 *
			 * @return {Promise<void>}
			 */
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
						// `_anonymized` is the convention both Docudesk and OpenRegister
						// use to mark anonymized copies — see DocumentProcessingHandler.
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
