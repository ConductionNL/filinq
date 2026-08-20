/* eslint-disable no-console */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'

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
		 * @param filters
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-6
		 */
		async fetchTemplates(filters = {}) {
			this.loading = true
			this.error = null
			try {
				const params = new URLSearchParams()
				Object.entries(filters).forEach(([key, value]) => {
					if (value !== undefined && value !== null && value !== '') {
						params.append(key, value)
					}
				})
				const response = await axios.get(
					generateUrl('/apps/docudesk/api/templates')
						+ '?'
						+ params.toString(),
					{ headers: this.buildLanguageHeaders() },
				)
				this.templates = response.data.results || []
				this.total = response.data.total || 0
				this.recordResponseLanguage(response)
			} catch (err) {
				console.error('Failed to fetch templates:', err)
				this.error = err.message
			} finally {
				this.loading = false
			}
		},
		/**
		 * Fetch a single template by ID.
		 *
		 * @param id
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-6
		 */
		async fetchTemplate(id) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(
					generateUrl(`/apps/docudesk/api/templates/${id}`),
					{ headers: this.buildLanguageHeaders() },
				)
				this.templateItem = response.data
				this.recordResponseLanguage(response)
				return response.data
			} catch (err) {
				console.error('Failed to fetch template:', err)
				this.error = err.message
				return null
			} finally {
				this.loading = false
			}
		},
		/**
		 * Create a new template.
		 *
		 * @param data
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async createTemplate(data) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.post(
					generateUrl('/apps/docudesk/api/templates'),
					data,
				)
				this.templateItem = response.data
				return response.data
			} catch (err) {
				console.error('Failed to create template:', err)
				this.error = err.message
				return null
			} finally {
				this.loading = false
			}
		},
		/**
		 * Update an existing template.
		 *
		 * @param id
		 * @param data
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async updateTemplate(id, data) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.put(
					generateUrl(`/apps/docudesk/api/templates/${id}`),
					data,
				)
				this.templateItem = response.data
				return response.data
			} catch (err) {
				console.error('Failed to update template:', err)
				this.error = err.message
				return null
			} finally {
				this.loading = false
			}
		},
		/**
		 * Delete a template by ID.
		 *
		 * @param id
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async deleteTemplate(id) {
			this.loading = true
			this.error = null
			try {
				await axios.delete(generateUrl(`/apps/docudesk/api/templates/${id}`))
				this.templates = this.templates.filter((t) => t.id !== id)
				return true
			} catch (err) {
				console.error('Failed to delete template:', err)
				this.error = err.message
				return false
			} finally {
				this.loading = false
			}
		},
		/**
		 * Duplicate an existing template into a new draft.
		 *
		 * @param id
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async duplicateTemplate(id) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.post(
					generateUrl(`/apps/docudesk/api/templates/${id}/duplicate`),
				)
				return response.data
			} catch (err) {
				console.error('Failed to duplicate template:', err)
				this.error = err.message
				return null
			} finally {
				this.loading = false
			}
		},
		/**
		 * Fetch the version history for a template.
		 *
		 * @param id
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-4
		 */
		async fetchVersions(id) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(
					generateUrl(`/apps/docudesk/api/templates/${id}/versions`),
				)
				this.versions = response.data.results || []
				return response.data
			} catch (err) {
				console.error('Failed to fetch versions:', err)
				this.error = err.message
				return null
			} finally {
				this.loading = false
			}
		},
		/**
		 * Restore a template to a prior version.
		 *
		 * @param templateId
		 * @param versionId
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-4
		 */
		async restoreVersion(templateId, versionId) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.post(
					generateUrl(
						`/apps/docudesk/api/templates/${templateId}/versions/${versionId}/restore`,
					),
				)
				this.templateItem = response.data
				return response.data
			} catch (err) {
				console.error('Failed to restore version:', err)
				this.error = err.message
				return null
			} finally {
				this.loading = false
			}
		},
		/**
		 * Render a live preview of template content with sample data.
		 *
		 * @param content
		 * @param data
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-5
		 */
		async previewTemplate(content, data = {}) {
			this.error = null
			try {
				const response = await axios.post(
					generateUrl('/apps/docudesk/api/templates/preview'),
					{ content, data },
				)
				return response.data.html
			} catch (err) {
				console.error('Failed to preview template:', err)
				this.error = err.message
				return null
			}
		},
		/**
		 * Acquire an edit lock on a template.
		 *
		 * @param id
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async acquireLock(id) {
			this.error = null
			try {
				const response = await axios.post(
					generateUrl(`/apps/docudesk/api/templates/${id}/lock`),
				)
				return response.data
			} catch (err) {
				console.error('Failed to acquire lock:', err)
				this.error = err.response?.data?.error || err.message
				return null
			}
		},
		/**
		 * Release the edit lock on a template.
		 *
		 * @param id
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async releaseLock(id) {
			this.error = null
			try {
				const response = await axios.delete(
					generateUrl(`/apps/docudesk/api/templates/${id}/lock`),
				)
				return response.data
			} catch (err) {
				console.error('Failed to release lock:', err)
				this.error = err.message
				return null
			}
		},
	},
})
