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
