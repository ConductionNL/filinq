/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

/* eslint-disable no-console */
import { defineStore } from 'pinia'
import { Report } from '../../entities/index.js'

/**
 * Store for managing document reports
 *
 * @see website/docs/api/document-reports.md for documentation
 *
 * @package
 * @author Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (c) 2024 Conduction B.V.
 * @license EUPL-1.2
 * @version 1.0.0
 */
export const useReportStore = defineStore('report', {
	state: () => ({
		reportItem: false,
		reportList: [],
	}),
	actions: {
		/**
		 * Sets the active report item
		 *
		 * @param {object | null} reportItem - Report item data
		 */
		setReportItem(reportItem) {
			this.reportItem = reportItem && new Report(reportItem)
			console.log('Active report item set to ' + (reportItem ? reportItem.id : 'null'))
		},

		/**
		 * Sets the list of report items
		 *
		 * @param {Array} reportList - List of report items
		 */
		setReportList(reportList) {
			this.reportList = reportList.map(
				(reportItem) => new Report(reportItem),
			)
			console.log('Report list set to ' + reportList.length + ' items')
		},

		/**
		 * Refreshes the list of report items
		 *
		 * @param {string|null} search - Search query
		 * @param {object} filters - Filter parameters
		 * @return {Promise<object>} Response and data
		 *
		 * @phpstan-ignore-next-line
		 * @psalm-suppress UndefinedMethod
		 */
		/* istanbul ignore next */ // ignore this for Jest until moved into a service
		async refreshReportList(search = null, filters = {}) {
			let endpoint = '/index.php/apps/docudesk/api/objects/report'

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

			this.setReportList(data)

			return { response, data }
		},

		/**
		 * Gets a specific report item by ID
		 *
		 * @param {string} id - Report item ID
		 * @return {Promise<object>} Report item data
		 *
		 * @phpstan-ignore-next-line
		 * @psalm-suppress UndefinedMethod
		 */
		async getReport(id) {
			const endpoint = `/index.php/apps/docudesk/api/objects/report/${id}`
			try {
				const response = await fetch(endpoint, {
					method: 'GET',
				})
				const data = await response.json()
				this.setReportItem(data)
				return data
			} catch (err) {
				console.error(err)
				throw err
			}
		},

		/**
		 * Gets the latest report for a node
		 *
		 * @param {string} nodeId - Nextcloud node ID
		 * @return {Promise<object>} Report item data
		 *
		 * @phpstan-ignore-next-line
		 * @psalm-suppress UndefinedMethod
		 */
		async getLatestReportForNode(nodeId) {
			const endpoint = `/index.php/apps/docudesk/api/reports/node/${nodeId}`
			try {
				const response = await fetch(endpoint, {
					method: 'GET',
				})
				const data = await response.json()
				this.setReportItem(data)
				return data
			} catch (err) {
				console.error(err)
				throw err
			}
		},

		/**
		 * Deletes a report item
		 *
		 * @param {object} reportItem - Report item to delete
		 * @return {Promise<object>} Response and data
		 *
		 * @phpstan-ignore-next-line
		 * @psalm-suppress UndefinedMethod
		 */
		async deleteReport(reportItem) {
			if (!reportItem.id) {
				throw new Error('No report item to delete')
			}

			console.log('Deleting report...')

			const endpoint = `/index.php/apps/docudesk/api/objects/report/${reportItem.id}`

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

				this.refreshReportList()
				this.setReportItem(null)

				return { response, data: responseData }
			} catch (error) {
				console.error('Error deleting report:', error)
				throw new Error(`Failed to delete report: ${error.message}`)
			}
		},

		/**
		 * Saves a report item
		 *
		 * @param {object} reportItem - Report item to save
		 * @return {Promise<object>} Response and data
		 *
		 * @phpstan-ignore-next-line
		 * @psalm-suppress UndefinedMethod
		 */
		async saveReport(reportItem) {
			if (!reportItem) {
				throw new Error('No report item to save')
			}

			console.log('Saving report...')

			const isNewReport = !reportItem.id
			const endpoint = isNewReport
				? '/index.php/apps/docudesk/api/objects/report'
				: `/index.php/apps/docudesk/api/objects/report/${reportItem.id}`
			const method = isNewReport ? 'POST' : 'PUT'

			// change updated to current date as a singular iso date string
			reportItem.updated = new Date().toISOString()

			try {
				const response = await fetch(
					endpoint,
					{
						method,
						headers: {
							'Content-Type': 'application/json',
						},
						body: JSON.stringify(reportItem),
					},
				)

				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status}`)
				}

				const responseData = await response.json()

				if (!responseData || typeof responseData !== 'object') {
					throw new Error('Invalid response data')
				}

				const data = new Report(responseData)

				this.setReportItem(data)
				this.refreshReportList()

				return { response, data }
			} catch (error) {
				console.error('Error saving report:', error)
				throw new Error(`Failed to save report: ${error.message}`)
			}
		},

		/**
		 * Creates a new document report
		 *
		 * @param {string} nodeId - Nextcloud node ID of the document
		 * @param {string} fileName - Name of the document
		 * @param {string} fileHash - Hash of the file content
		 * @param {Array<string>} analysisTypes - Types of analysis to perform
		 * @return {Promise<object>} Response and data
		 *
		 * @phpstan-ignore-next-line
		 * @psalm-suppress UndefinedMethod
		 */
		async createDocumentReport(nodeId, fileName, fileHash, analysisTypes = ['anonymization', 'wcag_compliance', 'language_level']) {
			if (!nodeId || !fileName || !fileHash) {
				throw new Error('Missing required parameters')
			}

			console.log('Creating document report...')

			const endpoint = '/index.php/apps/docudesk/api/reports'

			try {
				const response = await fetch(
					endpoint,
					{
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
						},
						body: JSON.stringify({
							nodeId,
							fileName,
							fileHash,
							analysisTypes,
						}),
					},
				)

				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status}`)
				}

				const responseData = await response.json()

				if (!responseData || typeof responseData !== 'object') {
					throw new Error('Invalid response data')
				}

				// Refresh the report list to include the new report
				this.refreshReportList()

				return { response, data: responseData }
			} catch (error) {
				console.error('Error creating document report:', error)
				throw new Error(`Failed to create document report: ${error.message}`)
			}
		},
	},
})
