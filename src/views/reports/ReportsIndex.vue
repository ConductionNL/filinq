/**
 * ReportsIndex component for displaying reports in table or card view with sidebar
 *
 * @category Vue Components
 * @package DocuDesk
 * @author Conduction B.V. info@conduction.nl
 * @copyright Copyright (c) 2024 Conduction B.V.
 * @license EUPL-1.2
 * @version 1.0.0
 * @link https://github.com/ConductionNL/docudesk
 */

<template>
	<div class="app-container">
		<NcAppContent>
			<!-- Fixed Header -->
			<div class="pageHeaderContainer">
				<h2 class="pageHeader">
					Reports
				</h2>
				<div class="headerActionsContainer">
					<NcButton @click="objectStore.fetchCollection('report')">
						<template #icon>
							<Refresh :size="20" />
						</template>
						Refresh
					</NcButton>
				</div>
			</div>

			<!-- Scrollable Content Area -->
			<div class="reportsContent">
				<div v-if="objectStore.isLoading('report')" class="loading">
					<NcLoadingIcon :size="32" />
					<span>Loading reports...</span>
				</div>
				<div v-else-if="error" class="error">
					<NcEmptyContent :title="error" icon="icon-error" />
				</div>
				<div v-else-if="!filteredReports.length" class="empty">
					<NcEmptyContent title="No reports found" icon="icon-folder" />
				</div>
				<div v-else class="tableContainer">
					<table class="statisticsTable reportStats reportsTable">
						<thead>
							<tr>
								<th>Name</th>
								<th>Risk Level</th>
								<th>File Type</th>
								<th>File Size</th>
								<th>Entities</th>
								<th>WCAG</th>
								<th>Language Level</th>
								<th>Archive</th>
								<th>Anonymized</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="report in paginatedReports"
								:key="report.id"
								:class="{ 'selected-row': selectedReport?.id === report.id }"
								@click="selectReport(report)">
								<td>
									<span :title="report.fileName || 'Unnamed Report'">{{ report.fileName || 'Unnamed Report' }}</span>
								</td>
								<td>
									<NcCounterBubble
										v-if="report.riskLevel"
										:type="getRiskLevelBadgeType(report.riskLevel)"
										:class="getRiskLevelClass(report.riskLevel)">
										{{ report.riskLevel }}
									</NcCounterBubble>
									<span v-else>-</span>
								</td>
								<td>{{ formatFileType(report.fileType) }}</td>
								<td>{{ formatFileSize(report.fileSize) }}</td>
								<td>{{ report.entities?.length || 0 }}</td>
								<td>{{ report.wcagLevel || 'N/A' }}</td>
								<td>{{ report.languageLevel || 'N/A' }}</td>
								<td>
									<NcCounterBubble v-if="report.isArchived" type="success">
										Yes
									</NcCounterBubble>
									<span v-else>N/A</span>
								</td>
								<td>
									<NcCounterBubble v-if="report.isAnonymized" type="success">
										Yes
									</NcCounterBubble>
									<span v-else>N/A</span>
								</td>
								<td>
									<NcButton
										type="tertiary"
										@click.stop="selectReport(report)">
										<template #icon>
											<InformationOutline :size="20" />
										</template>
										Details
									</NcButton>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Fixed Footer -->
			<div v-if="totalPages > 1 || pagination.total > 0" class="paginationFooter">
				<div class="paginationContainer">
					<div class="paginationLeft">
						<label for="limit-select" class="limitLabel">Show:</label>
						<select id="limit-select"
							v-model="limit"
							class="limitSelect"
							@change="changeLimit">
							<option v-for="option in limitOptions"
								:key="option"
								:value="option">
								{{ option }}
							</option>
						</select>
						<span class="limitLabel">per page</span>
					</div>

					<div class="paginationCenter">
						<span class="pageInfo">
							Page {{ currentPage }} of {{ totalPages }} ({{ pagination.total }} total)
						</span>
					</div>

					<div class="paginationRight">
						<NcButton
							:disabled="!hasPreviousPages"
							@click="changePage(currentPage - 1)">
							<template #icon>
								<ChevronLeft :size="20" />
							</template>
							Previous
						</NcButton>

						<NcButton
							:disabled="!hasMorePages"
							@click="changePage(currentPage + 1)">
							Next
							<template #icon>
								<ChevronRight :size="20" />
							</template>
						</NcButton>
					</div>
				</div>
			</div>
		</NcAppContent>

		<!-- Sidebars -->
		<ReportsSideBar v-if="navigationStore.sidebarState.reports" />
		<ReportSideBar v-if="navigationStore.sidebarState.report" />
	</div>
</template>

<script>

import {
	NcAppContent,
	NcButton,
	NcLoadingIcon,
	NcEmptyContent,
	NcCounterBubble,
} from '@nextcloud/vue'

import { showError } from '@nextcloud/dialogs'
import { objectStore, navigationStore } from '../../store/store.js'
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue'
import Refresh from 'vue-material-design-icons/Refresh.vue'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue'
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue'

// Sidebar components
import ReportsSideBar from '../../sidebars/reports/ReportsSideBar.vue'
import ReportSideBar from '../../sidebars/reports/ReportSideBar.vue'

export default {
	name: 'ReportsIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcLoadingIcon,
		NcButton,
		NcCounterBubble,
		InformationOutline,
		Refresh,
		ChevronLeft,
		ChevronRight,
		// Sidebar components
		ReportsSideBar,
		ReportSideBar,
	},
	data() {
		return {
			error: null,
			// Selected report
			selectedReport: null,
			// Store references
			objectStore,
			navigationStore,
			// Pagination limit
			limit: 20,
			limitOptions: [10, 20, 50, 100],
		}
	},
	computed: {
		/**
		 * Get all reports from the store
		 * @return {Array} Array of report objects
		 */
		reports() {
			return objectStore.getCollection('report').results || []
		},

		/**
		 * Get filtered reports (for future search/filter functionality)
		 * @return {Array} Filtered array of report objects
		 */
		filteredReports() {
			return this.reports
		},

		/**
		 * Get current page reports (now using all fetched reports since pagination is server-side)
		 * @return {Array} Current page report objects
		 */
		paginatedReports() {
			return this.filteredReports
		},

		/**
		 * Get pagination info from store
		 * @return {object} Pagination information
		 */
		pagination() {
			return objectStore.getPagination('report')
		},

		/**
		 * Get current page from store
		 * @return {number} Current page
		 */
		currentPage() {
			return this.pagination.page || 1
		},

		/**
		 * Calculate total number of pages from store
		 * @return {number} Total pages
		 */
		totalPages() {
			return this.pagination.pages || 1
		},

		/**
		 * Check if there are more pages
		 * @return {boolean} Has more pages
		 */
		hasMorePages() {
			return objectStore.hasMorePages('report')
		},

		/**
		 * Check if there are previous pages
		 * @return {boolean} Has previous pages
		 */
		hasPreviousPages() {
			return objectStore.hasPreviousPages('report')
		},
	},
	mounted() {
		// Load reports when component mounts
		this.loadReports()
	},
	methods: {
		/**
		 * Load reports from the API
		 */
		async loadReports() {
			try {
				await objectStore.fetchCollection('report', { _page: 1, _limit: this.limit })
			} catch (error) {
				console.error('Error loading reports:', error)
				this.error = 'Failed to load reports'
			}
		},

		/**
		 * Select a report and show details
		 * @param {object} report - The report to select
		 */
		selectReport(report) {
			this.selectedReport = report
			objectStore.setActiveObject('report', report)
			navigationStore.setSidebar('report', true)
			navigationStore.setSelected('reports') // Keep the navigation state
		},

		/**
		 * Toggle sidebar state
		 * @param {string} sidebarName - Name of the sidebar to toggle
		 */
		toggleSidebar(sidebarName) {
			const currentState = navigationStore.sidebarState[sidebarName]
			navigationStore.setSidebar(sidebarName, !currentState)
		},

		/**
		 * Change current page
		 * @param {number} page - Page number to navigate to
		 */
		async changePage(page) {
			if (page >= 1 && page <= this.totalPages) {
				try {
					await objectStore.fetchCollection('report', { _page: page, _limit: this.limit })
				} catch (error) {
					console.error('Error loading page:', error)
					this.error = 'Failed to load page'
				}
			}
		},

		/**
		 * Get status badge type for report status
		 * @param {string} status - Report status
		 * @return {string} Badge type
		 */
		getStatusBadgeType(status) {
			switch (status) {
			case 'completed':
				return 'success'
			case 'processing':
				return 'primary'
			case 'pending':
				return 'warning'
			case 'failed':
				return 'error'
			default:
				return 'primary'
			}
		},

		/**
		 * Get risk level badge type
		 * @param {string} riskLevel - Risk level
		 * @return {string} Badge type
		 */
		getRiskLevelBadgeType(riskLevel) {
			switch (riskLevel?.toLowerCase()) {
			case 'critical':
				return 'error'
			case 'high':
				return 'warning'
			case 'medium':
				return 'warning'
			case 'low':
				return 'success'
			default:
				return 'primary'
			}
		},

		/**
		 * Format file size to human readable format
		 * @param {number} bytes - Number of bytes
		 * @return {string} Formatted file size
		 */
		formatFileSize(bytes) {
			if (!bytes) return '0 Bytes'
			const k = 1024
			const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
			const i = Math.floor(Math.log(bytes) / Math.log(k))
			return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
		},

		/**
		 * Format date to readable format
		 * @param {string} date - Date string
		 * @return {string} Formatted date
		 */
		formatDate(date) {
			if (!date) return '-'
			return new Date(date).toLocaleDateString() + ', ' + new Date(date).toLocaleTimeString()
		},

		/**
		 * Download a report
		 * @param {object} report - Report to download
		 */
		async downloadReport(report) {
			try {
				// Implementation depends on your API
				// This is a placeholder - implement according to your backend
				showError(this.t('docudesk', 'Download functionality not implemented yet'))
			} catch (error) {
				console.error('Error downloading report:', error)
				showError(this.t('docudesk', 'Failed to download report'))
			}
		},

		/**
		 * Format file type to readable format
		 * @param {string} fileType - File type
		 * @return {string} Formatted file type
		 */
		formatFileType(fileType) {
			if (!fileType) return 'Unknown'

			// Handle specific MIME types
			if (fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
				return 'Word Document'
			}
			if (fileType === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
				return 'Excel Spreadsheet'
			}
			if (fileType === 'application/vnd.openxmlformats-officedocument.presentationml.presentation') {
				return 'PowerPoint Presentation'
			}

			// Handle common file extensions and types
			switch (fileType.toLowerCase()) {
			case 'pdf':
			case 'application/pdf':
				return 'PDF'
			case 'doc':
			case 'application/msword':
				return 'Word Document'
			case 'docx':
				return 'Word Document'
			case 'xls':
			case 'application/vnd.ms-excel':
				return 'Excel Spreadsheet'
			case 'xlsx':
				return 'Excel Spreadsheet'
			case 'ppt':
			case 'application/vnd.ms-powerpoint':
				return 'PowerPoint Presentation'
			case 'pptx':
				return 'PowerPoint Presentation'
			case 'txt':
			case 'text/plain':
				return 'Text File'
			case 'csv':
			case 'text/csv':
				return 'CSV File'
			case 'jpg':
			case 'jpeg':
			case 'image/jpeg':
				return 'JPEG Image'
			case 'png':
			case 'image/png':
				return 'PNG Image'
			case 'gif':
			case 'image/gif':
				return 'GIF Image'
			case 'bmp':
			case 'image/bmp':
				return 'BMP Image'
			case 'webp':
			case 'image/webp':
				return 'WebP Image'
			case 'svg':
			case 'image/svg+xml':
				return 'SVG Image'
			case 'mp4':
			case 'video/mp4':
				return 'MP4 Video'
			case 'mp3':
			case 'audio/mp3':
			case 'audio/mpeg':
				return 'MP3 Audio'
			case 'wav':
			case 'audio/wav':
				return 'WAV Audio'
			case 'flac':
			case 'audio/flac':
				return 'FLAC Audio'
			default:
				// If it's a MIME type, try to extract a readable part
				if (fileType.includes('/')) {
					const parts = fileType.split('/')
					return parts[1].charAt(0).toUpperCase() + parts[1].slice(1)
				}
				return fileType.charAt(0).toUpperCase() + fileType.slice(1)
			}
		},

		/**
		 * Get risk level class
		 * @param {string} riskLevel - Risk level
		 * @return {string} Class name
		 */
		getRiskLevelClass(riskLevel) {
			switch (riskLevel?.toLowerCase()) {
			case 'critical':
				return 'risk-critical'
			case 'high':
				return 'risk-high'
			case 'medium':
				return 'risk-medium'
			case 'low':
				return 'risk-low'
			default:
				return ''
			}
		},

		/**
		 * Change pagination limit
		 */
		async changeLimit() {
			try {
				await objectStore.fetchCollection('report', { _page: 1, _limit: this.limit })
			} catch (error) {
				console.error('Error changing limit:', error)
				this.error = 'Failed to change page limit'
			}
		},
	},
}
</script>

<style scoped>
.app-container {
	display: flex;
	height: 100vh;
	overflow: hidden;
}

/* Sticky Header */
.pageHeaderContainer {
	position: sticky;
	top: 0;
	z-index: 100;
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 16px 20px;
	border-bottom: 1px solid var(--color-border);
	background-color: var(--color-main-background);
	min-height: 60px;
}

.pageHeader {
	margin: 0;
	font-size: 24px;
	font-weight: 600;
	color: var(--color-main-text);
}

.headerActionsContainer {
	display: flex;
	align-items: center;
	gap: 16px;
}

/* Scrollable Content Area */
.reportsContent {
	flex: 1;
	overflow: auto;
	padding: 0;
}

.loading,
.error,
.empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 400px;
	gap: 16px;
	padding: 20px;
}

.loading span,
.error span,
.empty span {
	color: var(--color-text-maxcontrast);
	font-size: 16px;
}

/* Table View Styles */
.tableContainer {
	width: 100%;
}

.reportsTable {
	width: 100%;
	border-collapse: collapse;
	background-color: var(--color-main-background);
	table-layout: auto;
}

.reportsTable thead {
	background-color: var(--color-background-hover);
	position: sticky;
	top: 0;
	z-index: 10;
}

.reportsTable th {
	padding: 12px 8px;
	text-align: left;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
	border-bottom: 2px solid var(--color-border);
	background-color: var(--color-background-hover);
}

.reportsTable td {
	padding: 12px 8px;
	border-bottom: 1px solid var(--color-border-dark);
	vertical-align: middle;
}

.reportsTable tbody tr {
	cursor: pointer;
	transition: background-color 0.2s ease;
}

.reportsTable tbody tr:hover {
	background-color: var(--color-background-hover);
}

.selected-row {
	background-color: var(--color-primary-element-light) !important;
}

.statisticsTable {
	width: 100%;
	border-collapse: collapse;
}

.statisticsTable td {
	padding: 8px 4px;
	border-bottom: 1px solid var(--color-border-dark);
	font-size: 14px;
}

.statisticsTable td:first-child {
	color: var(--color-text-maxcontrast);
	font-weight: 500;
	width: 50%;
}

.status-badge,
.risk-critical,
.risk-high,
.risk-medium,
.risk-low {
	font-size: 12px;
	font-weight: 500;
	text-transform: uppercase;
}

/* Risk Level Colors */
.risk-critical {
	background-color: var(--color-error) !important;
	color: white !important;
}

.risk-high {
	background-color: var(--color-warning) !important;
	color: white !important;
}

.risk-medium {
	background-color: var(--color-warning) !important;
	color: white !important;
}

.risk-low {
	background-color: var(--color-success) !important;
	color: white !important;
}

/* Sticky Footer */
.paginationFooter {
	position: sticky;
	bottom: 0;
	z-index: 100;
	background-color: var(--color-main-background);
	border-top: 1px solid var(--color-border);
}

.paginationContainer {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 16px 20px;
}

.paginationLeft {
	display: flex;
	align-items: center;
	gap: 8px;
}

.limitLabel {
	color: var(--color-text-maxcontrast);
	font-size: 14px;
	font-weight: 500;
}

.limitSelect {
	padding: 4px 8px;
	border: 1px solid var(--color-border);
	border-radius: 4px;
	background-color: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 14px;
}

.limitSelect:focus {
	outline: 2px solid var(--color-primary-element);
	outline-offset: -2px;
}

.paginationCenter {
	display: flex;
	align-items: center;
	gap: 8px;
}

.pageInfo {
	color: var(--color-text-maxcontrast);
	font-size: 14px;
}

.paginationRight {
	display: flex;
	align-items: center;
	gap: 8px;
}

/* Responsive Design */
@media (max-width: 768px) {
	.pageHeaderContainer {
		flex-direction: column;
		gap: 16px;
		align-items: stretch;
	}

	.headerActionsContainer {
		justify-content: space-between;
	}

	.reportsTable {
		font-size: 14px;
	}

	.reportsTable th,
	.reportsTable td {
		padding: 8px 4px;
	}

	.paginationContainer {
		flex-direction: column;
		gap: 12px;
		align-items: stretch;
	}

	.paginationLeft,
	.paginationCenter,
	.paginationRight {
		justify-content: center;
	}
}
</style>
