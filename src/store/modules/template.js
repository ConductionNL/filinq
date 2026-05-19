/* eslint-disable no-console */
import { defineStore } from 'pinia'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const useTemplateStore = defineStore('template', {
	state: () => ({
		templates: [],
		templateItem: null,
		versions: [],
		total: 0,
		loading: false,
		error: null,
	}),
	actions: {
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
				const response = await axios.get(generateUrl('/apps/docudesk/api/templates') + '?' + params.toString())
				this.templates = response.data.results || []
				this.total = response.data.total || 0
			} catch (err) {
				console.error('Failed to fetch templates:', err)
				this.error = err.message
			} finally {
				this.loading = false
			}
		},
		async fetchTemplate(id) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(generateUrl(`/apps/docudesk/api/templates/${id}`))
				this.templateItem = response.data
				return response.data
			} catch (err) {
				console.error('Failed to fetch template:', err)
				this.error = err.message
				return null
			} finally {
				this.loading = false
			}
		},
		async createTemplate(data) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.post(generateUrl('/apps/docudesk/api/templates'), data)
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
		async updateTemplate(id, data) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.put(generateUrl(`/apps/docudesk/api/templates/${id}`), data)
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
		async deleteTemplate(id) {
			this.loading = true
			this.error = null
			try {
				await axios.delete(generateUrl(`/apps/docudesk/api/templates/${id}`))
				this.templates = this.templates.filter(t => t.id !== id)
				return true
			} catch (err) {
				console.error('Failed to delete template:', err)
				this.error = err.message
				return false
			} finally {
				this.loading = false
			}
		},
		async duplicateTemplate(id) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.post(generateUrl(`/apps/docudesk/api/templates/${id}/duplicate`))
				return response.data
			} catch (err) {
				console.error('Failed to duplicate template:', err)
				this.error = err.message
				return null
			} finally {
				this.loading = false
			}
		},
		async fetchVersions(id) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.get(generateUrl(`/apps/docudesk/api/templates/${id}/versions`))
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
		async restoreVersion(templateId, versionId) {
			this.loading = true
			this.error = null
			try {
				const response = await axios.post(generateUrl(`/apps/docudesk/api/templates/${templateId}/versions/${versionId}/restore`))
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
		async previewTemplate(content, data = {}) {
			this.error = null
			try {
				const response = await axios.post(generateUrl('/apps/docudesk/api/templates/preview'), { content, data })
				return response.data.html
			} catch (err) {
				console.error('Failed to preview template:', err)
				this.error = err.message
				return null
			}
		},
		async acquireLock(id) {
			this.error = null
			try {
				const response = await axios.post(generateUrl(`/apps/docudesk/api/templates/${id}/lock`))
				return response.data
			} catch (err) {
				console.error('Failed to acquire lock:', err)
				this.error = err.response?.data?.error || err.message
				return null
			}
		},
		async releaseLock(id) {
			this.error = null
			try {
				const response = await axios.delete(generateUrl(`/apps/docudesk/api/templates/${id}/lock`))
				return response.data
			} catch (err) {
				console.error('Failed to release lock:', err)
				this.error = err.message
				return null
			}
		},
	},
})
