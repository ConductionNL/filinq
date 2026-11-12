/**
 * SPDX-FileCopyrightText: 2025 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import { quietRequest, storeRequest } from '../storeRequest.js'

/**
 * The edit lock URL of a template.
 *
 * @param {string} id the template
 * @return {string} the URL
 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
 */
function lockUrl(id) {
	return generateUrl(`/apps/filinq/api/templates/${id}/lock`)
}

/**
 * Keep the template a response carries as the store's current template.
 *
 * @param {object} store the template store
 * @param {object} response the response
 * @return {object} the template
 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
 */
function keepItem(store, response) {
	store.templateItem = response.data
	return response.data
}

export const useTemplateStore = defineStore('template', {
	state: () => ({
		templates: [],
		templateItem: null,
		versions: [],
		total: 0,
		loading: false,
		error: null,
		/** Currently selected content language for template metadata (REQ-I18N-021) */
		selectedLanguage: null,
		/** Whether the last fetch returned content in a fallback language (REQ-I18N-012) */
		isFallbackLanguage: false,
		/** The language actually served by the last fetch */
		servedLanguage: null,
	}),
	actions: {
		/**
		 * Set the preferred content language for template metadata (REQ-I18N-021).
		 *
		 * @param {string|null} language BCP 47 language code or null to use browser default
		 *
		 * @spec openspec/changes/register-i18n/tasks.md#task-1
		 */
		setLanguage(language) {
			this.selectedLanguage = language
		},
		/**
		 * Build the Accept-Language header value for the current preference.
		 *
		 * @return {object} Headers object, potentially with Accept-Language
		 *
		 * @spec openspec/changes/register-i18n/tasks.md#task-1
		 */
		buildLanguageHeaders() {
			if (!this.selectedLanguage) {
				return {}
			}
			// Build quality-weighted header: "nl, en;q=0.9" (REQ-I18N-032)
			const supported = ['nl', 'en']
			const others = supported.filter((l) => l !== this.selectedLanguage)
			const parts = [
				this.selectedLanguage,
				...others.map((l, i) => `${l};q=${(0.9 - i * 0.1).toFixed(1)}`),
			]
			return { 'Accept-Language': parts.join(', ') }
		},
		/**
		 * Record language negotiation headers from an OR response.
		 *
		 * @param {object} response The axios response object
		 *
		 * @spec openspec/changes/register-i18n/tasks.md#task-1
		 */
		recordResponseLanguage(response) {
			this.servedLanguage = response.headers?.['content-language'] || null
			this.isFallbackLanguage =
				response.headers?.['x-content-language-fallback'] === 'true'
		},
		/**
		 * Fetch templates with optional category/tag/search filters.
		 *
		 * @param {object} filters the filters, by field
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-6
		 */
		fetchTemplates(filters = {}) {
			return storeRequest(this, {
				failure: 'Failed to fetch templates:',
				run: async () => {
					const params = new URLSearchParams()
					Object.entries(filters).forEach(([key, value]) => {
						if (value !== undefined && value !== null && value !== '') {
							params.append(key, value)
						}
					})
					const response = await axios.get(
						generateUrl('/apps/filinq/api/templates')
							+ '?'
							+ params.toString(),
						{ headers: this.buildLanguageHeaders() },
					)
					this.templates = response.data.results || []
					this.total = response.data.total || 0
					this.recordResponseLanguage(response)
				},
			})
		},
		/**
		 * Fetch a single template by ID.
		 *
		 * @param {string} id the template
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-6
		 */
		fetchTemplate(id) {
			return storeRequest(this, {
				failure: 'Failed to fetch template:',
				fallback: null,
				run: async () => {
					const response = await axios.get(
						generateUrl(`/apps/filinq/api/templates/${id}`),
						{ headers: this.buildLanguageHeaders() },
					)
					this.templateItem = response.data
					this.recordResponseLanguage(response)
					return response.data
				},
			})
		},
		/**
		 * Create a new template.
		 *
		 * @param {object} data the template fields
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		createTemplate(data) {
			return storeRequest(this, {
				failure: 'Failed to create template:',
				fallback: null,
				run: async () =>
					keepItem(
						this,
						await axios.post(
							generateUrl('/apps/filinq/api/templates'),
							data,
						),
					),
			})
		},
		/**
		 * Update an existing template.
		 *
		 * @param {string} id the template
		 * @param {object} data the template fields
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		updateTemplate(id, data) {
			return storeRequest(this, {
				failure: 'Failed to update template:',
				fallback: null,
				run: async () =>
					keepItem(
						this,
						await axios.put(
							generateUrl(`/apps/filinq/api/templates/${id}`),
							data,
						),
					),
			})
		},
		/**
		 * Delete a template by ID.
		 *
		 * @param {string} id the template
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		deleteTemplate(id) {
			return storeRequest(this, {
				failure: 'Failed to delete template:',
				fallback: false,
				run: async () => {
					await axios.delete(
						generateUrl(`/apps/filinq/api/templates/${id}`),
					)
					this.templates = this.templates.filter((t) => t.id !== id)
					return true
				},
			})
		},
		/**
		 * Duplicate an existing template into a new draft.
		 *
		 * @param {string} id the template
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		duplicateTemplate(id) {
			return storeRequest(this, {
				failure: 'Failed to duplicate template:',
				fallback: null,
				run: async () => {
					const response = await axios.post(
						generateUrl(`/apps/filinq/api/templates/${id}/duplicate`),
					)
					return response.data
				},
			})
		},
		/**
		 * Fetch the version history for a template.
		 *
		 * @param {string} id the template
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-4
		 */
		fetchVersions(id) {
			return storeRequest(this, {
				failure: 'Failed to fetch versions:',
				fallback: null,
				run: async () => {
					const response = await axios.get(
						generateUrl(`/apps/filinq/api/templates/${id}/versions`),
					)
					this.versions = response.data.results || []
					return response.data
				},
			})
		},
		/**
		 * Restore a template to a prior version.
		 *
		 * @param {string} templateId the template
		 * @param {string} versionId the version
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-4
		 */
		restoreVersion(templateId, versionId) {
			return storeRequest(this, {
				failure: 'Failed to restore version:',
				fallback: null,
				run: async () =>
					keepItem(
						this,
						await axios.post(
							generateUrl(
								`/apps/filinq/api/templates/${templateId}/versions/${versionId}/restore`,
							),
						),
					),
			})
		},
		/**
		 * Render a live preview of template content with sample data, with
		 * its accessibility lint.
		 *
		 * @param {string} content the template content
		 * @param {object} data the sample data
		 * @return {Promise<{html: string, lint: Array<object>}|null>} The preview, null on failure.
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-5
		 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.3
		 */
		previewTemplate(content, data = {}) {
			return quietRequest(this, {
				failure: 'Failed to preview template:',
				fallback: null,
				run: async () => {
					const response = await axios.post(
						generateUrl('/apps/filinq/api/templates/preview'),
						{ content, data },
					)
					return {
						html: response.data.html,
						lint: response.data.lint || [],
					}
				},
			})
		},
		/**
		 * Acquire an edit lock on a template.
		 *
		 * @param {string} id the template
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		acquireLock(id) {
			return quietRequest(this, {
				failure: 'Failed to acquire lock:',
				fallback: null,
				serverError: true,
				run: async () => (await axios.post(lockUrl(id))).data,
			})
		},
		/**
		 * Release the edit lock on a template.
		 *
		 * @param {string} id the template
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		releaseLock(id) {
			return quietRequest(this, {
				failure: 'Failed to release lock:',
				fallback: null,
				run: async () => (await axios.delete(lockUrl(id))).data,
			})
		},
	},
})
