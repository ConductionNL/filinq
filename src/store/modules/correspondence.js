// SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
// SPDX-License-Identifier: EUPL-1.2

/**
 * Pinia store for correspondence generation state.
 *
 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-5
 */

import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const useCorrespondenceStore = defineStore('correspondence', {
	state: () => ({
		/** @type {string} */
		templateId: '',
		/** @type {Array<{register: string, schema: string, id: string}>} */
		dataRefs: [],
		/** @type {string} */
		format: 'pdf',
		/** @type {string|null} */
		huisstijlId: null,
		/** @type {string} */
		caseReference: '',
		/** @type {string} */
		recipientIdsText: '',
		/** @type {boolean} */
		loading: false,
		/** @type {string|null} */
		error: null,
		/** @type {string|null} */
		jobId: null,
		/** @type {object|null} */
		jobStatus: null,
		/** @type {Array<string>} */
		warnings: [],
	}),

	getters: {
		/**
		 * Parse recipient IDs from textarea (one UUID per line).
		 *
		 * @param {object} state The Vuex state.
		 * @return {string[]}
		 */
		recipientIds(state) {
			return state.recipientIdsText
				.split('\n')
				.map((s) => s.trim())
				.filter(Boolean)
		},

		/**
		 * True when a batch job is running.
		 *
		 * @param {object} state The Vuex state.
		 * @return {boolean}
		 */
		isBatchMode(state) {
			return (
				state.dataRefs.length === 0
				&& state.recipientIdsText.trim().length > 0
			)
		},
	},

	actions: {
		/**
		 * Generate a single correspondence document and trigger download.
		 *
		 * @param {string} filename Desired download filename.
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-5
		 */
		async generate(filename = 'brief.pdf') {
			this.loading = true
			this.error = null
			this.warnings = []

			try {
				const url = generateUrl('/apps/docudesk/api/correspondence/generate')
				const response = await axios.post(
					url,
					{
						templateId: this.templateId,
						dataRefs: this.dataRefs,
						options: {
							format: this.format,
							huisstijlId: this.huisstijlId || undefined,
							caseReference: this.caseReference || undefined,
						},
						filename,
					},
					{
						responseType:
							this.format === 'pdf' || this.format === 'docx'
								? 'blob'
								: 'json',
					},
				)

				if (this.format === 'pdf' || this.format === 'docx') {
					this._triggerDownload(response.data, filename, this.format)
				} else {
					this.warnings = response.data.warnings || []
				}
			} catch (err) {
				this.error = err.response?.data?.error || err.message
			} finally {
				this.loading = false
			}
		},

		/**
		 * Generate correspondence for a batch of recipients.
		 *
		 * @param {string} register Register slug for recipients.
		 * @param {string} schema   Schema slug for recipients.
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-5
		 */
		async generateBatch(register, schema) {
			this.loading = true
			this.error = null
			this.jobId = null
			this.jobStatus = null

			try {
				const url = generateUrl(
					'/apps/docudesk/api/correspondence/generate/batch',
				)
				const response = await axios.post(url, {
					templateId: this.templateId,
					recipientIds: this.recipientIds,
					options: {
						register,
						schema,
						format: this.format,
						huisstijlId: this.huisstijlId || undefined,
						caseReference: this.caseReference || undefined,
					},
				})

				if (response.data.jobId) {
					this.jobId = response.data.jobId
					this.jobStatus = {
						status: 'queued',
						total: response.data.totalRecipients,
						completed: 0,
						errors: 0,
					}
				} else {
					this.jobStatus = response.data
				}
			} catch (err) {
				this.error = err.response?.data?.error || err.message
			} finally {
				this.loading = false
			}
		},

		/**
		 * Poll the async batch job status.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-5
		 */
		async pollJobStatus() {
			if (!this.jobId) {
				return
			}

			try {
				const url = generateUrl(
					'/apps/docudesk/api/correspondence/jobs/'
						+ encodeURIComponent(this.jobId),
				)
				const response = await axios.get(url)
				this.jobStatus = response.data
			} catch (err) {
				this.error = err.response?.data?.error || err.message
			}
		},

		/**
		 * Reset form state.
		 *
		 * @return {void}
		 */
		reset() {
			this.templateId = ''
			this.dataRefs = []
			this.format = 'pdf'
			this.huisstijlId = null
			this.caseReference = ''
			this.recipientIdsText = ''
			this.error = null
			this.jobId = null
			this.jobStatus = null
			this.warnings = []
		},

		/**
		 * Trigger a browser file download from a Blob.
		 *
		 * @param {Blob}   blob      Response blob.
		 * @param {string} filename  Suggested filename.
		 * @param {string} format    Output format (pdf or docx).
		 * @return {void}
		 */
		_triggerDownload(blob, filename, format) {
			const ext = format === 'docx' ? '.docx' : '.pdf'
			const name = filename.endsWith(ext)
				? filename
				: filename.replace(/\.[^.]+$/, '') + ext
			const objectUrl = URL.createObjectURL(blob)
			const a = document.createElement('a')
			a.href = objectUrl
			a.download = name
			document.body.appendChild(a)
			a.click()
			document.body.removeChild(a)
			URL.revokeObjectURL(objectUrl)
		},
	},
})
