/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

/* eslint-disable no-console */
import { defineStore } from 'pinia'
import { Template } from '../../entities/index.js'

/**
 * Store for managing document templates
 *
 * @package
 * @author Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (c) 2024 Conduction B.V.
 * @license EUPL-1.2
 * @version 1.0.0
 */
export const useTemplateStore = defineStore('template', {
	state: () => ({
		templateItem: false,
		templateList: [],
	}),
	actions: {
		/**
		 * Sets the active template item
		 *
		 * @param {object | null} templateItem - Template item data
		 */
		setTemplateItem(templateItem) {
			this.templateItem = templateItem && new Template(templateItem)
			console.log('Active template item set to ' + (templateItem ? templateItem.id : 'null'))
		},

		/**
		 * Sets the list of template items
		 *
		 * @param {Array} templateList - List of template items
		 */
		setTemplateList(templateList) {
			this.templateList = templateList.map(
				(templateItem) => new Template(templateItem),
			)
			console.log('Template list set to ' + templateList.length + ' items')
		},

		/**
		 * Refreshes the list of template items
		 *
		 * @param {string|null} search - Search query
		 * @param {object} filters - Filter parameters
		 * @return {Promise<object>} Response and data
		 *
		 * @phpstan-ignore-next-line
		 * @psalm-suppress UndefinedMethod
		 */
		/* istanbul ignore next */ // ignore this for Jest until moved into a service
		async refreshTemplateList(search = null, filters = {}) {
			let endpoint = '/index.php/apps/docudesk/api/objects/template'

			// Add search parameter if provided
			const params = new URLSearchParams()
			if (search !== null && search !== '') {
				params.append('_search', search)
			}

			// Add filters
			for (const [key, value] of Object.entries(filters)) {
				if (value !== null && value !== '') {
					params.append(key, value)
				}
			}

			// Append params to endpoint if any exist
			const queryString = params.toString()
			if (queryString) {
				endpoint += '?' + queryString
			}

			const response = await fetch(endpoint, {
				method: 'GET',
			})

			const data = (await response.json()).results

			this.setTemplateList(data)

			return { response, data }
		},

		/**
		 * Gets a specific template item by ID
		 *
		 * @param {string} id - Template item ID
		 * @return {Promise<object>} Template item data
		 *
		 * @phpstan-ignore-next-line
		 * @psalm-suppress UndefinedMethod
		 */
		async getTemplate(id) {
			const endpoint = `/index.php/apps/docudesk/api/objects/template/${id}`
			try {
				const response = await fetch(endpoint, {
					method: 'GET',
				})
				const data = await response.json()
				this.setTemplateItem(data)
				return data
			} catch (err) {
				console.error(err)
				throw err
			}
		},

		/**
		 * Deletes a template item
		 *
		 * @param {object} templateItem - Template item to delete
		 * @return {Promise<object>} Response and data
		 *
		 * @phpstan-ignore-next-line
		 * @psalm-suppress UndefinedMethod
		 */
		async deleteTemplate(templateItem) {
			if (!templateItem.id) {
				throw new Error('No template item to delete')
			}

			console.log('Deleting template...')

			const endpoint = `/index.php/apps/docudesk/api/objects/template/${templateItem.id}`

			try {
				const response = await fetch(endpoint, {
					method: 'DELETE',
				})

				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status}`)
				}

				const responseData = await response.json()

				if (!responseData || typeof responseData !== 'object') {
					throw new Error('Invalid response data')
				}

				this.refreshTemplateList()
				this.setTemplateItem(null)

				return { response, data: responseData }
			} catch (error) {
				console.error('Error deleting template:', error)
				throw new Error(`Failed to delete template: ${error.message}`)
			}
		},

		/**
		 * Saves a template item
		 *
		 * @param {object} templateItem - Template item to save
		 * @return {Promise<object>} Response and data
		 *
		 * @phpstan-ignore-next-line
		 * @psalm-suppress UndefinedMethod
		 */
		async saveTemplate(templateItem) {
			if (!templateItem) {
				throw new Error('No template item to save')
			}

			console.log('Saving template...')

			// Update variables based on content
			if (templateItem.content) {
				templateItem.variables = new Template(templateItem).extractVariables()
			}

			const isNewTemplate = !templateItem.id
			const endpoint = isNewTemplate
				? '/index.php/apps/docudesk/api/objects/template'
				: `/index.php/apps/docudesk/api/objects/template/${templateItem.id}`
			const method = isNewTemplate ? 'POST' : 'PUT'

			// change updated to current date as a singular iso date string
			templateItem.updated = new Date().toISOString()

			try {
				const response = await fetch(
					endpoint,
					{
						method,
						headers: {
							'Content-Type': 'application/json',
						},
						body: JSON.stringify(templateItem),
					},
				)

				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status}`)
				}

				const responseData = await response.json()

				if (!responseData || typeof responseData !== 'object') {
					throw new Error('Invalid response data')
				}

				const data = new Template(responseData)

				this.setTemplateItem(data)
				this.refreshTemplateList()

				return { response, data }
			} catch (error) {
				console.error('Error saving template:', error)
				throw new Error(`Failed to save template: ${error.message}`)
			}
		},

		/**
		 * Renders a template with the provided data
		 *
		 * @param {string} templateId - Template ID
		 * @param {object} data - Data to render the template with
		 * @param {string} format - Output format (html, pdf, docx)
		 * @return {Promise<object>} Response and rendered template
		 *
		 * @phpstan-ignore-next-line
		 * @psalm-suppress UndefinedMethod
		 */
		async renderTemplate(templateId, data, format = 'html') {
			if (!templateId) {
				throw new Error('No template ID provided')
			}

			console.log('Rendering template...')

			const endpoint = `/index.php/apps/docudesk/api/objects/template/${templateId}/render`

			try {
				const response = await fetch(
					endpoint,
					{
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
						},
						body: JSON.stringify({
							data,
							format,
						}),
					},
				)

				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status}`)
				}

				// For PDF or DOCX, we need to handle the response as a blob
				if (format === 'pdf' || format === 'docx') {
					const blob = await response.blob()
					return { response, data: blob }
				}

				// For HTML, we can handle the response as text
				const responseData = await response.text()
				return { response, data: responseData }
			} catch (error) {
				console.error('Error rendering template:', error)
				throw new Error(`Failed to render template: ${error.message}`)
			}
		},
	},
})
