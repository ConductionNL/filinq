/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const useSigningStore = defineStore(
	'signing',
	{
		state: () => ({
			signingRequests: [],
			signingRequest: null,
			auditTrail: [],
			verificationResult: null,
			loading: false,
			error: null,
		}),
		getters: {
			pendingRequests: (state) => state.signingRequests.filter((r) => r.status === 'PENDING'),
			completedRequests: (state) => state.signingRequests.filter((r) => r.status === 'COMPLETED'),
		},
		actions: {
			/**
			 * Fetch all signing requests from the backend.
			 *
			 * @spec openspec/changes/digital-signing-integration/tasks.md#8-1
			 */
			async fetchSigningRequests() {
				this.loading = true
				this.error = null
				try {
					const response = await axios.get(generateUrl('/apps/docudesk/api/signing/requests'))
					this.signingRequests = response.data
				} catch (err) {
					console.error('Failed to fetch signing requests:', err)
					this.error = err.message
				} finally {
					this.loading = false
				}
			},
			/**
			 * Fetch a single signing request by ID.
			 *
			 * @param id
			 * @spec openspec/changes/digital-signing-integration/tasks.md#8-1
			 */
			async fetchSigningRequest(id) {
				this.loading = true
				this.error = null
				try {
					const response = await axios.get(generateUrl(`/apps/docudesk/api/signing/requests/${id}`))
					this.signingRequest = response.data
					return response.data
				} catch (err) {
					console.error('Failed to fetch signing request:', err)
					this.error = err.message
					return null
				} finally {
					this.loading = false
				}
			},
			/**
			 * Create a new signing request.
			 *
			 * @param data
			 * @spec openspec/changes/digital-signing-integration/tasks.md#8-1
			 */
			async createSigningRequest(data) {
				this.loading = true
				this.error = null
				try {
					const response = await axios.post(generateUrl('/apps/docudesk/api/signing/requests'), data)
					this.signingRequests.push(response.data)
					return response.data
				} catch (err) {
					console.error('Failed to create signing request:', err)
					this.error = err.message
					return null
				} finally {
					this.loading = false
				}
			},
			/**
			 * Sign a document for a given signer in a signing request.
			 *
			 * @param requestId
			 * @param signerId
			 * @spec openspec/changes/digital-signing-integration/tasks.md#8-1
			 */
			async signDocument(requestId, signerId) {
				this.loading = true
				this.error = null
				try {
					const response = await axios.post(
						generateUrl(`/apps/docudesk/api/signing/requests/${requestId}/sign`),
						{ signerId },
					)
					return response.data
				} catch (err) {
					console.error('Failed to sign document:', err)
					this.error = err.message
					return null
				} finally {
					this.loading = false
				}
			},
			/**
			 * Decline a signing request for a given signer with a reason.
			 *
			 * @param requestId
			 * @param signerId
			 * @param reason
			 * @spec openspec/changes/digital-signing-integration/tasks.md#8-1
			 */
			async declineRequest(requestId, signerId, reason) {
				this.loading = true
				this.error = null
				try {
					const response = await axios.post(
						generateUrl(`/apps/docudesk/api/signing/requests/${requestId}/decline`),
						{ signerId, reason },
					)
					return response.data
				} catch (err) {
					console.error('Failed to decline signing request:', err)
					this.error = err.message
					return null
				} finally {
					this.loading = false
				}
			},
			/**
			 * Cancel a signing request.
			 *
			 * @param requestId
			 * @spec openspec/changes/digital-signing-integration/tasks.md#8-1
			 */
			async cancelRequest(requestId) {
				this.loading = true
				try {
					await axios.delete(generateUrl(`/apps/docudesk/api/signing/requests/${requestId}`))
				} catch (err) {
					console.error('Failed to cancel signing request:', err)
					this.error = err.message
				} finally {
					this.loading = false
				}
			},
			/**
			 * Bulk-sign multiple signing requests at once.
			 *
			 * @param requestIds
			 * @spec openspec/changes/digital-signing-integration/tasks.md#8-1
			 */
			async bulkSign(requestIds) {
				this.loading = true
				try {
					const response = await axios.post(
						generateUrl('/apps/docudesk/api/signing/bulk'),
						{ requestIds },
					)
					return response.data
				} catch (err) {
					console.error('Failed to bulk sign:', err)
					this.error = err.message
					return null
				} finally {
					this.loading = false
				}
			},
			/**
			 * Verify the signatures embedded in a document file.
			 *
			 * @param fileId
			 * @spec openspec/changes/digital-signing-integration/tasks.md#8-1
			 */
			async verifyDocument(fileId) {
				this.loading = true
				try {
					const response = await axios.get(generateUrl(`/apps/docudesk/api/signing/verify/${fileId}`))
					this.verificationResult = response.data
					return response.data
				} catch (err) {
					console.error('Failed to verify document:', err)
					this.error = err.message
					return null
				} finally {
					this.loading = false
				}
			},
			/**
			 * Fetch the immutable audit trail for a signing request.
			 *
			 * @param requestId
			 * @spec openspec/changes/digital-signing-integration/tasks.md#8-1
			 */
			async fetchAuditTrail(requestId) {
				this.loading = true
				try {
					const response = await axios.get(
						generateUrl(`/apps/docudesk/api/signing/requests/${requestId}/audit`),
					)
					this.auditTrail = response.data
					return response.data
				} catch (err) {
					console.error('Failed to fetch audit trail:', err)
					this.error = err.message
					return []
				} finally {
					this.loading = false
				}
			},
		},
	},
)
