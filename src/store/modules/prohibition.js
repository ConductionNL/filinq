/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */
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
		/**
		 * Load every prohibition rule into the store.
		 *
		 * @return {Promise} Resolves when the list is loaded.
		 *
		 * @spec openspec/specs/anonymisation-prohibition-gate/spec.md
		 */
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
		/**
		 * Load one prohibition rule and select it.
		 *
		 * @param {string} id The rule id.
		 * @return {Promise} The rule, or null when it could not be read.
		 *
		 * @spec openspec/specs/anonymisation-prohibition-gate/spec.md
		 */
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
		/**
		 * Create a prohibition rule and add it to the list.
		 *
		 * @param {object} data The new rule.
		 * @return {Promise} The created rule.
		 *
		 * @spec openspec/specs/anonymisation-prohibition-gate/spec.md
		 */
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
		/**
		 * Update a prohibition rule in the list and in the selection.
		 *
		 * @param {string} id The rule id.
		 * @param {object} data The changed fields.
		 * @return {Promise} The updated rule.
		 *
		 * @spec openspec/specs/anonymisation-prohibition-gate/spec.md
		 */
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
		/**
		 * Delete a prohibition rule and drop it from the list.
		 *
		 * @param {string} id The rule id.
		 * @return {Promise} Resolves when the rule is gone.
		 *
		 * @spec openspec/specs/anonymisation-prohibition-gate/spec.md
		 */
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
