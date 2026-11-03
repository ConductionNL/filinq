import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
/**
 * Prohibition store — backs the Publication Prohibitions admin page.
 *
 * Talks to the policy controller's `api/policy/prohibitions` endpoints. Stays
 * intentionally close in shape to the consent store so the UI components can
 * mirror each other.
 */
import { defineStore } from 'pinia'
import { storeRequest } from '../storeRequest.js'

const baseUrl = '/apps/filinq/api/policy/prohibitions'

export const useProhibitionStore = defineStore('prohibition', {
	state: () => ({
		prohibitions: [],
		prohibitionItem: null,
		loading: false,
		error: null,
	}),
	getters: {
		activeCount: (state) =>
			state.prohibitions.filter((p) => p.active !== false).length,
		prohibitionStats: (state) => ({
			total: state.prohibitions.length,
			active: state.prohibitions.filter((p) => p.active !== false).length,
			inactive: state.prohibitions.filter((p) => p.active === false).length,
		}),
	},
	actions: {
		fetchProhibitions() {
			return storeRequest(this, {
				failure: 'Failed to fetch prohibitions:',
				serverError: true,
				run: async () => {
					const response = await axios.get(generateUrl(baseUrl))
					this.prohibitions = response.data
				},
			})
		},
		fetchProhibition(id) {
			return storeRequest(this, {
				failure: 'Failed to fetch prohibition:',
				fallback: null,
				serverError: true,
				run: async () => {
					const response = await axios.get(generateUrl(`${baseUrl}/${id}`))
					this.prohibitionItem = response.data
					return response.data
				},
			})
		},
		createProhibition(data) {
			return storeRequest(this, {
				failure: 'Failed to create prohibition:',
				rethrow: true,
				serverError: true,
				run: async () => {
					const response = await axios.post(generateUrl(baseUrl), data)
					this.prohibitions.push(response.data)
					return response.data
				},
			})
		},
		updateProhibition(id, data) {
			return storeRequest(this, {
				failure: 'Failed to update prohibition:',
				rethrow: true,
				serverError: true,
				run: async () => {
					const response = await axios.put(
						generateUrl(`${baseUrl}/${id}`),
						data,
					)
					const idx = this.prohibitions.findIndex(
						(p) => (p['@self']?.id || p.id || p.uuid) === id,
					)
					if (idx !== -1) {
						this.prohibitions[idx] = response.data
					}

					this.prohibitionItem = response.data
					return response.data
				},
			})
		},
		deleteProhibition(id) {
			return storeRequest(this, {
				failure: 'Failed to delete prohibition:',
				rethrow: true,
				serverError: true,
				run: async () => {
					await axios.delete(generateUrl(`${baseUrl}/${id}`))
					this.prohibitions = this.prohibitions.filter(
						(p) => (p['@self']?.id || p.id || p.uuid) !== id,
					)
				},
			})
		},
		setProhibitionItem(item) {
			this.prohibitionItem = item
		},
		clearProhibitionItem() {
			this.prohibitionItem = null
		},
	},
})
