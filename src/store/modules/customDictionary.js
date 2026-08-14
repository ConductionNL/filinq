/* eslint-disable no-console */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
/**
 * Custom dictionary store — backs the "Custom dictionaries" admin page
 * (custom-dictionary-recognition).
 *
 * Talks to CustomDictionaryController's `api/custom-dictionaries` endpoints.
 * Stays close in shape to the prohibition store so the UI components can
 * mirror each other (feedback_store-pattern.md).
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */
import { defineStore } from 'pinia'

const baseUrl = '/apps/docudesk/api/custom-dictionaries'

/**
 * Resolve a record's identifier — OpenRegister rows carry the UUID as
 * `@self.id` (rendered rows) or top-level `id`, never both consistently.
 *
 * @param {object} record The record.
 * @return {string|undefined} The record's UUID.
 */
function recordId(record) {
	return record?.['@self']?.id || record?.id || record?.uuid
}

export const useCustomDictionaryStore = defineStore('customDictionary', {
	state: () => ({
		dictionaries: [],
		dictionaryItem: null,
		terms: [],
		loading: false,
		termsLoading: false,
		error: null,
		importResult: null,
	}),
	actions: {
		async fetchDictionaries() {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(generateUrl(baseUrl))
				this.dictionaries = response.data
			} catch (err) {
				console.error('Failed to fetch custom dictionaries:', err)
				this.error = err.response?.data?.error || err.message
			} finally {
				this.loading = false
			}
		},
		async fetchDictionary(id) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(generateUrl(`${baseUrl}/${id}`))
				this.dictionaryItem = response.data
				return response.data
			} catch (err) {
				console.error('Failed to fetch custom dictionary:', err)
				this.error = err.response?.data?.error || err.message
				return null
			} finally {
				this.loading = false
			}
		},
		async createDictionary(data) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.post(generateUrl(baseUrl), data)
				this.dictionaries.push(response.data)
				return response.data
			} catch (err) {
				console.error('Failed to create custom dictionary:', err)
				this.error = err.response?.data?.error || err.message
				throw err
			} finally {
				this.loading = false
			}
		},
		async updateDictionary(id, data) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.put(
					generateUrl(`${baseUrl}/${id}`),
					data,
				)
				const idx = this.dictionaries.findIndex((d) => recordId(d) === id)
				if (idx !== -1) {
					this.dictionaries[idx] = response.data
				}

				this.dictionaryItem = response.data
				return response.data
			} catch (err) {
				console.error('Failed to update custom dictionary:', err)
				this.error = err.response?.data?.error || err.message
				throw err
			} finally {
				this.loading = false
			}
		},
		async deleteDictionary(id) {
			this.loading = true
			this.error = null
			try {
				await axios.delete(generateUrl(`${baseUrl}/${id}`))
				this.dictionaries = this.dictionaries.filter(
					(d) => recordId(d) !== id,
				)
			} catch (err) {
				console.error('Failed to delete custom dictionary:', err)
				this.error = err.response?.data?.error || err.message
				throw err
			} finally {
				this.loading = false
			}
		},
		async fetchTerms(dictionaryId) {
			this.termsLoading = true
			this.error = null
			try {
				const response = await axios.get(
					generateUrl(`${baseUrl}/${dictionaryId}/terms`),
				)
				this.terms = response.data
				return response.data
			} catch (err) {
				console.error('Failed to fetch terms:', err)
				this.error = err.response?.data?.error || err.message
				return []
			} finally {
				this.termsLoading = false
			}
		},
		async createTerm(dictionaryId, data) {
			try {
				const response = await axios.post(
					generateUrl(`${baseUrl}/${dictionaryId}/terms`),
					data,
				)
				this.terms.push(response.data)
				return response.data
			} catch (err) {
				console.error('Failed to create term:', err)
				this.error = err.response?.data?.error || err.message
				throw err
			}
		},
		async deleteTerm(dictionaryId, termId) {
			try {
				await axios.delete(
					generateUrl(`${baseUrl}/${dictionaryId}/terms/${termId}`),
				)
				this.terms = this.terms.filter((term) => recordId(term) !== termId)
			} catch (err) {
				console.error('Failed to delete term:', err)
				this.error = err.response?.data?.error || err.message
				throw err
			}
		},
		/**
		 * Import terms from a pasted newline list or an uploaded CSV file.
		 *
		 * @param {string} dictionaryId The owning dictionary's UUID.
		 * @param {object} payload      Either `{ content, format }` (pasted text;
		 *                              format is 'newline' or 'csv') or
		 *                              `{ file }` (a File object, parsed
		 *                              server-side per REQ-DDCDR-005 — never
		 *                              client-side).
		 * @return {Promise<{added: number, skipped: number, total: number}|null>}
		 */
		async importTerms(dictionaryId, payload) {
			this.error = null
			try {
				let response
				if (payload.file) {
					const formData = new FormData()
					formData.append('file', payload.file)
					response = await axios.post(
						generateUrl(`${baseUrl}/${dictionaryId}/import`),
						formData,
					)
				} else {
					response = await axios.post(
						generateUrl(`${baseUrl}/${dictionaryId}/import`),
						{
							content: payload.content,
							format: payload.format || 'newline',
						},
					)
				}

				this.importResult = response.data
				await this.fetchTerms(dictionaryId)
				return response.data
			} catch (err) {
				console.error('Failed to import terms:', err)
				this.error = err.response?.data?.error || err.message
				throw err
			}
		},
		clearDictionaryItem() {
			this.dictionaryItem = null
			this.terms = []
			this.importResult = null
		},
	},
})
