/* eslint-disable no-console */
/**
 * Standing-consent store — backs the Standing Publication Consents admin page.
 *
 * Talks to `api/policy/standing-consents` which filters `publicationConsent`
 * records to `scope: "entity"` server-side; the store therefore does NOT need
 * to re-filter rows it receives.
 */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const baseUrl = '/apps/docudesk/api/policy/standing-consents'

export const useStandingConsentStore = defineStore('standingConsent', {
	state: () => ({
		standingConsents: [],
		standingConsentItem: null,
		loading: false,
		error: null,
	}),
	getters: {
		standingConsentStats: (state) => ({
			total: state.standingConsents.length,
			active: state.standingConsents.filter((s) => s.active !== false).length,
			inactive: state.standingConsents.filter((s) => s.active === false)
				.length,
		}),
	},
	actions: {
		async fetchStandingConsents() {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(generateUrl(baseUrl))
				this.standingConsents = response.data
			} catch (err) {
				console.error('Failed to fetch standing consents:', err)
				this.error = err.response?.data?.error || err.message
			} finally {
				this.loading = false
			}
		},
		async fetchStandingConsent(id) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(generateUrl(`${baseUrl}/${id}`))
				this.standingConsentItem = response.data
				return response.data
			} catch (err) {
				console.error('Failed to fetch standing consent:', err)
				this.error = err.response?.data?.error || err.message
				return null
			} finally {
				this.loading = false
			}
		},
		async createStandingConsent(data) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.post(generateUrl(baseUrl), data)
				this.standingConsents.push(response.data)
				return response.data
			} catch (err) {
				console.error('Failed to create standing consent:', err)
				this.error = err.response?.data?.error || err.message
				throw err
			} finally {
				this.loading = false
			}
		},
		async updateStandingConsent(id, data) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.put(
					generateUrl(`${baseUrl}/${id}`),
					data,
				)
				const idx = this.standingConsents.findIndex(
					(s) => (s['@self']?.id || s.id || s.uuid) === id,
				)
				if (idx !== -1) {
					this.standingConsents[idx] = response.data
				}

				this.standingConsentItem = response.data
				return response.data
			} catch (err) {
				console.error('Failed to update standing consent:', err)
				this.error = err.response?.data?.error || err.message
				throw err
			} finally {
				this.loading = false
			}
		},
		async deleteStandingConsent(id) {
			this.loading = true
			this.error = null
			try {
				await axios.delete(generateUrl(`${baseUrl}/${id}`))
				this.standingConsents = this.standingConsents.filter(
					(s) => (s['@self']?.id || s.id || s.uuid) !== id,
				)
			} catch (err) {
				console.error('Failed to delete standing consent:', err)
				this.error = err.response?.data?.error || err.message
				throw err
			} finally {
				this.loading = false
			}
		},
		setStandingConsentItem(item) {
			this.standingConsentItem = item
		},
		clearStandingConsentItem() {
			this.standingConsentItem = null
		},
	},
})
