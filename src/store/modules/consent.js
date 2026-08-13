/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const useConsentStore = defineStore('consent', {
	state: () => ({
		consents: [],
		consentItem: null,
		loading: false,
		error: null,
	}),
	getters: {
		pendingConsents: (state) =>
			state.consents.filter((c) => c.consentStatus === 'pending'),
		approvedConsents: (state) =>
			state.consents.filter((c) => c.consentStatus === 'consent_given'),
		objectedConsents: (state) =>
			state.consents.filter((c) => c.consentStatus === 'objection_received'),
		consentStats: (state) => {
			const total = state.consents.length
			const pending = state.consents.filter(
				(c) => c.consentStatus === 'pending',
			).length
			const approved = state.consents.filter(
				(c) => c.consentStatus === 'consent_given',
			).length
			const objected = state.consents.filter(
				(c) => c.consentStatus === 'objection_received',
			).length
			const noResponse = state.consents.filter(
				(c) => c.consentStatus === 'no_response',
			).length
			const anonymized = state.consents.filter(
				(c) => c.consentStatus === 'anonymized',
			).length
			return { total, pending, approved, objected, noResponse, anonymized }
		},
	},
	actions: {
		/**
		 * Fetch all consent records.
		 *
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-listing-and-querying-req-cons-03
		 */
		async fetchConsents() {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(
					generateUrl('/apps/docudesk/api/consents'),
				)
				this.consents = response.data
			} catch (err) {
				console.error('Failed to fetch consents:', err)
				this.error = err.message
			} finally {
				this.loading = false
			}
		},
		/**
		 * Fetch a single consent record by ID.
		 *
		 * @param id
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-listing-and-querying-req-cons-03
		 */
		async fetchConsent(id) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(
					generateUrl(`/apps/docudesk/api/consents/${id}`),
				)
				this.consentItem = response.data
				return response.data
			} catch (err) {
				console.error('Failed to fetch consent:', err)
				this.error = err.message
				return null
			} finally {
				this.loading = false
			}
		},
		/**
		 * Update a consent record and sync it in the local list.
		 *
		 * @param id
		 * @param data
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-status-lifecycle-req-cons-02
		 */
		async updateConsent(id, data) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.put(
					generateUrl(`/apps/docudesk/api/consents/${id}`),
					data,
				)
				// Update in local list.
				const index = this.consents.findIndex((c) => (c.id || c.uuid) === id)
				if (index !== -1) {
					this.consents[index] = response.data
				}

				this.consentItem = response.data
				return response.data
			} catch (err) {
				console.error('Failed to update consent:', err)
				this.error = err.message
				return null
			} finally {
				this.loading = false
			}
		},
		/**
		 * Fetch all consent records linked to a specific document.
		 *
		 * @param documentId
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-listing-and-querying-req-cons-03
		 */
		async fetchConsentsByDocument(documentId) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(
					generateUrl(
						`/apps/docudesk/api/consents/document/${documentId}`,
					),
				)
				return response.data
			} catch (err) {
				console.error('Failed to fetch consents for document:', err)
				this.error = err.message
				return []
			} finally {
				this.loading = false
			}
		},
		/**
		 * Create a new consent record.
		 *
		 * @param data
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		async createConsent(data) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.post(
					generateUrl('/apps/docudesk/api/consents'),
					data,
				)
				this.consents.push(response.data)
				return response.data
			} catch (err) {
				console.error('Failed to create consent:', err)
				this.error = err.message
				return null
			} finally {
				this.loading = false
			}
		},
		setConsentItem(consent) {
			this.consentItem = consent
		},
		/**
		 * Clear the currently selected consent record.
		 *
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-ui-req-cons-10
		 */
		clearConsentItem() {
			this.consentItem = null
		},
	},
})
