/**
 * ReportsSideBar component for displaying reports overview statistics and filters
 *
 * @category Vue Components
 * @package DocuDesk
 * @author Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (c) 2024 Conduction B.V.
 * @license EUPL-1.2
 * @version 1.0.0
 * @link https://github.com/ConductionNL/docudesk
 */

<template>
	<NcAppSidebar
		ref="sidebar"
		v-model="activeTab"
		name="Reports"
		subtitle="Report Overview"
		subname="Statistics and Metrics"
		:open="navigationStore.sidebarState.reports"
		@update:open="(e) => {
			navigationStore.setSidebar('reports', e)
		}">
		<NcAppSidebarTab id="overview-tab" name="Overview" :order="1">
			<template #icon>
				<ChartBar :size="20" />
			</template>

			<!-- Filter Section -->
			<div class="filterSection">
				<h3>{{ t('docudesk', 'Filter Reports') }}</h3>
				<div class="filterGroup">
					<label for="statusSelect">{{ t('docudesk', 'Status') }}</label>
					<NcSelect
						v-model="selectedStatus"
						:options="statusOptions"
						placeholder="All statuses"
						:clearable="true"
						@update:model-value="handleStatusChange" />
				</div>
				<div class="filterGroup">
					<label for="riskSelect">{{ t('docudesk', 'Risk Level') }}</label>
					<NcSelect
						v-model="selectedRiskLevel"
						:options="riskLevelOptions"
						placeholder="All risk levels"
						:clearable="true"
						@update:model-value="handleRiskLevelChange" />
				</div>
			</div>

			<!-- System Totals Section -->
			<div class="section">
				<h3 class="sectionTitle">
					{{ t('docudesk', 'Report Totals') }}
				</h3>
				<div v-if="objectStore.isLoading('report')" class="loadingContainer">
					<NcLoadingIcon :size="20" />
					<span>{{ t('docudesk', 'Loading statistics...') }}</span>
				</div>
				<div v-else-if="systemTotals" class="statsContainer">
					<table class="statisticsTable">
						<tbody>
							<tr>
								<td>{{ t('docudesk', 'Total Reports') }}</td>
								<td>{{ filteredReports.length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Total File Size') }}</td>
								<td>{{ totalFileCount }}</td>
								<td>{{ formatBytes(totalFileSize) }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'High Risk') }}</td>
								<td>{{ getReportsByRisk('High').length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Medium Risk') }}</td>
								<td>{{ getReportsByRisk('Medium').length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Low Risk') }}</td>
								<td>{{ getReportsByRisk('Low').length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Completed') }}</td>
								<td>{{ getReportsByStatus('completed').length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Processing') }}</td>
								<td>{{ getReportsByStatus('processing').length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Failed') }}</td>
								<td>{{ getReportsByStatus('failed').length }}</td>
								<td>-</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Recent Activity Section -->
			<div class="section">
				<h3 class="sectionTitle">
					{{ t('docudesk', 'Recent Activity') }}
				</h3>
				<div v-if="objectStore.isLoading('report')" class="loadingContainer">
					<NcLoadingIcon :size="20" />
					<span>{{ t('docudesk', 'Loading activity...') }}</span>
				</div>
				<div v-else-if="recentReports.length > 0" class="recentReports">
					<div v-for="report in recentReports" :key="report.id" class="recentReportItem">
						<div class="reportHeader">
							<FileDocumentOutline :size="16" />
							<span class="reportName">{{ report.fileName || 'Unnamed Report' }}</span>
						</div>
						<div class="reportMeta">
							<span class="reportStatus" :class="getStatusClass(report.status)">
								{{ report.status }}
							</span>
							<span class="reportDate">
								{{ formatDate(report.created) }}
							</span>
						</div>
					</div>
				</div>
				<div v-else class="emptyContainer">
					<NcEmptyContent
						:title="t('docudesk', 'No recent activity')"
						:description="t('docudesk', 'Reports will appear here as they are created or updated')"
						icon="icon-history">
					</NcEmptyContent>
				</div>
			</div>
		</NcAppSidebarTab>

		<NcAppSidebarTab id="settings-tab" name="Settings" :order="2">
			<template #icon>
				<Cog :size="20" />
			</template>

			<!-- Settings Section -->
			<div class="section">
				<h3 class="sectionTitle">
					Report Settings
				</h3>
				<NcNoteCard type="info">
					Settings will be added in a future update
				</NcNoteCard>
			</div>
		</NcAppSidebarTab>
	</NcAppSidebar>
</template>

<script>
import { NcAppSidebar, NcAppSidebarTab, NcLoadingIcon, NcNoteCard, NcSelect, NcEmptyContent } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { objectStore, navigationStore } from '../../store/store.js'
import ChartBar from 'vue-material-design-icons/ChartBar.vue'
import Cog from 'vue-material-design-icons/Cog.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'

export default {
	name: 'ReportsSideBar',
	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		NcLoadingIcon,
		NcNoteCard,
		NcSelect,
		NcEmptyContent,
		ChartBar,
		Cog,
		FileDocumentOutline,
	},
	data() {
		return {
			/**
			 * Active tab in the sidebar
			 * @type {string}
			 */
			activeTab: 'overview-tab',
			/**
			 * Selected status filter
			 * @type {string|null}
			 */
			selectedStatus: null,
			/**
			 * Selected risk level filter
			 * @type {string|null}
			 */
			selectedRiskLevel: null,
			// Store references
			objectStore,
			navigationStore,
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
		 * Get filtered reports based on selected filters
		 * @return {Array} Filtered array of report objects
		 */
		filteredReports() {
			let filtered = this.reports

			if (this.selectedStatus) {
				filtered = filtered.filter(report => report.status === this.selectedStatus)
			}

			if (this.selectedRiskLevel) {
				filtered = filtered.filter(report => report.riskLevel === this.selectedRiskLevel)
			}

			return filtered
		},

		/**
		 * Get recent reports (last 10)
		 * @return {Array} Array of recent report objects
		 */
		recentReports() {
			return [...this.reports]
				.sort((a, b) => new Date(b.created || 0) - new Date(a.created || 0))
				.slice(0, 10)
		},

		/**
		 * Get system totals calculated from all reports
		 * @return {object} System totals object
		 */
		systemTotals() {
			return {
				totalReports: this.reports.length,
				totalFileSize: this.totalFileSize,
				totalFileCount: this.totalFileCount,
			}
		},

		/**
		 * Calculate total file size from all reports
		 * @return {number} Total file size in bytes
		 */
		totalFileSize() {
			return this.reports.reduce((total, report) => {
				return total + (report.fileSize || 0)
			}, 0)
		},

		/**
		 * Calculate total file count
		 * @return {number} Total number of files
		 */
		totalFileCount() {
			return this.reports.length
		},

		/**
		 * Status filter options
		 * @return {Array} Array of status options
		 */
		statusOptions() {
			const statuses = [...new Set(this.reports.map(report => report.status).filter(Boolean))]
			return statuses.map(status => ({
				id: status,
				label: status.charAt(0).toUpperCase() + status.slice(1),
			}))
		},

		/**
		 * Risk level filter options
		 * @return {Array} Array of risk level options
		 */
		riskLevelOptions() {
			const riskLevels = [...new Set(this.reports.map(report => report.riskLevel).filter(Boolean))]
			return riskLevels.map(level => ({
				id: level,
				label: level,
			}))
		},
	},
	mounted() {
		// Ensure reports are loaded
		objectStore.fetchCollection('report')
	},
	methods: {
		/**
		 * Translation function
		 * @param {string} app - App name
		 * @param {string} text - Text to translate
		 * @return {string} Translated text
		 */
		t,

		/**
		 * Handle status filter change
		 * @param {string|null} status - Selected status
		 */
		handleStatusChange(status) {
			this.selectedStatus = status?.id || null
		},

		/**
		 * Handle risk level filter change
		 * @param {string|null} riskLevel - Selected risk level
		 */
		handleRiskLevelChange(riskLevel) {
			this.selectedRiskLevel = riskLevel?.id || null
		},

		/**
		 * Get reports by risk level
		 * @param {string} riskLevel - Risk level to filter by
		 * @return {Array} Filtered reports
		 */
		getReportsByRisk(riskLevel) {
			return this.reports.filter(report => report.riskLevel === riskLevel)
		},

		/**
		 * Get reports by status
		 * @param {string} status - Status to filter by
		 * @return {Array} Filtered reports
		 */
		getReportsByStatus(status) {
			return this.reports.filter(report => report.status === status)
		},

		/**
		 * Format bytes to human readable format
		 * @param {number} bytes - Number of bytes
		 * @return {string} Formatted byte string
		 */
		formatBytes(bytes) {
			if (bytes === 0) return '0 Bytes'
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
			if (!date) return 'Unknown'
			return new Date(date).toLocaleDateString()
		},

		/**
		 * Get CSS class for status
		 * @param {string} status - Report status
		 * @return {string} CSS class
		 */
		getStatusClass(status) {
			switch (status) {
			case 'completed':
				return 'status-success'
			case 'processing':
				return 'status-warning'
			case 'failed':
				return 'status-error'
			default:
				return 'status-default'
			}
		},
	},
}
</script>

<style scoped>
.filterSection {
	padding: 16px;
	border-bottom: 1px solid var(--color-border);
	margin-bottom: 16px;
}

.filterGroup {
	margin-bottom: 12px;
}

.filterGroup label {
	display: block;
	margin-bottom: 4px;
	font-weight: 500;
	color: var(--color-text-maxcontrast);
}

.section {
	padding: 16px;
	margin-bottom: 16px;
}

.sectionTitle {
	font-size: 16px;
	font-weight: 600;
	margin-bottom: 12px;
	color: var(--color-main-text);
}

.loadingContainer {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 16px;
	color: var(--color-text-maxcontrast);
}

.statsContainer {
	margin-top: 8px;
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
}

.statisticsTable td:nth-child(2),
.statisticsTable td:nth-child(3) {
	text-align: right;
	font-weight: 500;
}

.recentReports {
	max-height: 300px;
	overflow-y: auto;
}

.recentReportItem {
	padding: 8px;
	border-bottom: 1px solid var(--color-border-dark);
	margin-bottom: 8px;
}

.reportHeader {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 4px;
}

.reportName {
	font-weight: 500;
	color: var(--color-main-text);
	flex: 1;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.reportMeta {
	display: flex;
	justify-content: space-between;
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.reportStatus {
	padding: 2px 6px;
	border-radius: 12px;
	font-size: 11px;
	font-weight: 500;
	text-transform: uppercase;
}

.status-success {
	background-color: var(--color-success);
	color: white;
}

.status-warning {
	background-color: var(--color-warning);
	color: white;
}

.status-error {
	background-color: var(--color-error);
	color: white;
}

.status-default {
	background-color: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
}

.emptyContainer {
	padding: 20px;
	text-align: center;
}
</style> 