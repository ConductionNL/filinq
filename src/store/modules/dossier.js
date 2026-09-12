/* eslint-disable no-console */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
/**
 * Dossier store — backs the Dossiers index and detail.
 *
 * Talks to DossierManagementController's `api/dossiers` endpoints, which do the
 * aggregation server-side (home folder ∪ documents[], grondslagen resolution,
 * batch runs, presence gates). The store deliberately holds no aggregation
 * logic of its own: two places deciding what a dossier contains is how the two
 * answers drift.
 *
 * Every mutating action returns the refreshed detail the server sends back, so
 * the caller never has to re-fetch to see its own write — that round trip is
 * what the "list updates immediately" requirement rules out.
 */
import { defineStore } from 'pinia'

const baseUrl = '/apps/filinq/api/dossiers'

/**
 * Read a readable message out of an axios failure.
 *
 * The controller answers `{ error }` with a meaningful status — 409 for a
 * refused lifecycle transition, 404 for absent-or-unreadable. Surfacing that
 * text is what keeps a rejection legible instead of "request failed".
 *
 * @param {object} err The axios error.
 * @return {string} The message to show.
 */
function messageFor(err) {
	return err.response?.data?.error || err.message
}

export const useDossierStore = defineStore('dossier', {
	state: () => ({
		dossiers: [],
		dossier: null,
		loading: false,
		saving: false,
		error: null,
		/**
		 * The document currently open in the inline viewer.
		 *
		 * Held in the store rather than the route so switching documents swaps
		 * the viewer without a navigation — the requirement is explicitly "no
		 * page reload".
		 */
		selectedDocumentId: null,
	}),
	getters: {
		/**
		 * Dossiers that still hold an unresolvable membership reference.
		 *
		 * @param {object} state The store state.
		 * @return {Array} The dossiers with at least one missing member.
		 */
		withMissingDocuments: (state) =>
			state.dossiers.filter((d) => (d.missingCount || 0) > 0),
		/**
		 * The document currently open in the viewer, or null.
		 *
		 * @param {object} state The store state.
		 * @return {object|null} The open document.
		 */
		selectedDocument: (state) =>
			(state.dossier?.documents || []).find(
				(d) => d.id === state.selectedDocumentId,
			) || null,
	},
	actions: {
		async fetchDossiers() {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(generateUrl(baseUrl))
				this.dossiers = response.data.results || []
			} catch (err) {
				console.error('Failed to fetch dossiers:', err)
				this.error = messageFor(err)
			} finally {
				this.loading = false
			}
		},

		async fetchDossier(id) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(generateUrl(`${baseUrl}/${id}`))
				this.dossier = response.data
				// Open the first document so the detail lands on something
				// rather than an empty viewer pane.
				const first = (this.dossier.documents || []).find((d) => !d.missing)
				this.selectedDocumentId = first ? first.id : null
				return this.dossier
			} catch (err) {
				console.error('Failed to fetch dossier:', err)
				this.error = messageFor(err)
				return null
			} finally {
				this.loading = false
			}
		},

		/**
		 * Swap the inline viewer. No route change, by design.
		 *
		 * @param {number} fileId The document's node id.
		 */
		selectDocument(fileId) {
			this.selectedDocumentId = fileId
		},

		async createDossier({ name, description = '', bases = [] }) {
			return this.write(() =>
				axios.post(generateUrl(baseUrl), { name, description, bases }),
			)
		},

		async renameDossier(id, name) {
			return this.write(() =>
				axios.put(generateUrl(`${baseUrl}/${id}/name`), { name }),
			)
		},

		async transitionDossier(id, status) {
			return this.write(() =>
				axios.put(generateUrl(`${baseUrl}/${id}/status`), { status }),
			)
		},

		async linkDocument(id, fileId) {
			return this.write(() =>
				axios.post(generateUrl(`${baseUrl}/${id}/documents`), { fileId }),
			)
		},

		async removeDocument(id, fileId) {
			return this.write(() =>
				axios.delete(generateUrl(`${baseUrl}/${id}/documents/${fileId}`)),
			)
		},

		/**
		 * Ask whether removing a document would trash it or unlink it.
		 *
		 * Read BEFORE the confirmation renders, so the dialog can name the
		 * consequence. A confirm that reads the same for both is how an
		 * operator deletes a file they meant to unlink.
		 *
		 * @param {string} id The dossier uuid.
		 * @param {number} fileId The document's node id.
		 * @return {Promise<string>} Either 'trash' or 'unlink'.
		 */
		async removalMode(id, fileId) {
			try {
				const response = await axios.get(
					generateUrl(`${baseUrl}/${id}/documents/${fileId}/removal-mode`),
				)
				return response.data.mode
			} catch (err) {
				console.error('Failed to read the removal mode:', err)
				// Fail SAFE: unable to prove the file would only be unlinked,
				// so tell the operator it will be trashed.
				return 'trash'
			}
		},

		/**
		 * Run a mutating request and adopt the detail it returns.
		 *
		 * @param {() => Promise<object>} request The axios call.
		 * @return {Promise<object|null>} The refreshed dossier, or null on failure.
		 */
		async write(request) {
			this.saving = true
			this.error = null
			try {
				return this.adopt(await request())
			} catch (err) {
				console.error('Dossier write failed:', err)
				this.error = messageFor(err)
				return null
			} finally {
				this.saving = false
			}
		},

		/**
		 * Adopt a detail payload into state and refresh the index row.
		 *
		 * @param {object} response The axios response.
		 * @return {object|null} The dossier, or null.
		 */
		adopt(response) {
			const dossier = response?.data
			if (!dossier || !dossier.id) {
				return null
			}

			this.dossier = dossier

			const index = this.dossiers.findIndex((d) => d.id === dossier.id)
			const row = {
				id: dossier.id,
				name: dossier.name,
				description: dossier.description,
				status: dossier.status,
				checkedOn: dossier.checkedOn,
				documentCount: (dossier.documents || []).length,
				missingCount: (dossier.documents || []).filter((d) => d.missing)
					.length,
				bases: dossier.bases,
			}

			if (index === -1) {
				this.dossiers.push(row)
			} else {
				this.dossiers.splice(index, 1, row)
			}

			return dossier
		},
	},
})
