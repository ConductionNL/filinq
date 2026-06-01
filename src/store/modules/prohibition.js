/* eslint-disable no-console */
/**
 * Prohibition store — backs the Publication Prohibitions admin page.
 *
 * Talks to the policy controller's `api/policy/prohibitions` endpoints. Stays
 * intentionally close in shape to the consent store so the UI components can
 * mirror each other.
 */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const baseUrl = '/apps/docudesk/api/policy/prohibitions'

export const useProhibitionStore = defineStore(
	'prohibition',
	{
		state: () => ({
			prohibitions: [],
			prohibitionItem: null,
			loading: false,
			error: null,
		}),
		getters: {
			activeCount: (state) => state.prohibitions.filter(p => p.active !== false).length,
			prohibitionStats: (state) => ({
				total: state.prohibitions.length,
				active: state.prohibitions.filter(p => p.active !== false).length,
				inactive: state.prohibitions.filter(p => p.active === false).length,
			}),
		},
		actions: {
			async fetchProhibitions() {
				this.loading = true
				this.error = null
				try {
					const response = await axios.get(generateUrl(baseUrl))
					this.prohibitions = response.data
				} catch (err) {
					console.error('Failed to fetch prohibitions:', err)
					this.error = err.response?.data?.error || err.message
				} finally {
					this.loading = false
				}
			},
			async fetchProhibition(id) {
				this.loading = true
				this.error = null
				try {
					const response = await axios.get(generateUrl(`${baseUrl}/${id}`))
					this.prohibitionItem = response.data
					return response.data
				} catch (err) {
					console.error('Failed to fetch prohibition:', err)
					this.error = err.response?.data?.error || err.message
					return null
				} finally {
					this.loading = false
				}
			},
			async createProhibition(data) {
				this.loading = true
				this.error = null
				try {
					const response = await axios.post(generateUrl(baseUrl), data)
					this.prohibitions.push(response.data)
					return response.data
				} catch (err) {
					console.error('Failed to create prohibition:', err)
					this.error = err.response?.data?.error || err.message
					throw err
				} finally {
					this.loading = false
				}
			},
			async updateProhibition(id, data) {
				this.loading = true
				this.error = null
				try {
					const response = await axios.put(generateUrl(`${baseUrl}/${id}`), data)
					const idx = this.prohibitions.findIndex(p => (p['@self']?.id || p.id || p.uuid) === id)
					if (idx !== -1) {
						this.prohibitions[idx] = response.data
					}

					this.prohibitionItem = response.data
					return response.data
				} catch (err) {
					console.error('Failed to update prohibition:', err)
					this.error = err.response?.data?.error || err.message
					throw err
				} finally {
					this.loading = false
				}
			},
			async deleteProhibition(id) {
				this.loading = true
				this.error = null
				try {
					await axios.delete(generateUrl(`${baseUrl}/${id}`))
					this.prohibitions = this.prohibitions.filter(p => (p['@self']?.id || p.id || p.uuid) !== id)
				} catch (err) {
					console.error('Failed to delete prohibition:', err)
					this.error = err.response?.data?.error || err.message
					throw err
				} finally {
					this.loading = false
				}
			},
			setProhibitionItem(item) {
				this.prohibitionItem = item
			},
			clearProhibitionItem() {
				this.prohibitionItem = null
			},
		},
	},
)
