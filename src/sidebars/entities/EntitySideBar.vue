/**
 * EntitySideBar - Vue component for displaying individual entity details in a sidebar
 * @category Vue Components
 * @package DocuDesk
 * @author Conduction B.V. info@conduction.nl
 * @copyright Copyright (c) 2024 Conduction B.V.
 * @license EUPL-1.2
 * @version 1.0.0
 * @link https://docudesk.app
 */

<template>
	<NcAppSidebar
		v-if="entity"
		ref="sidebar"
		v-model="activeTab"
		:name="entity.text || 'Unknown Entity'"
		:subtitle="formatEntityType(entity.entityType)"
		subname="Entity Details"
		:open="navigationStore.sidebarState.entity"
		@update:open="(e) => {
			navigationStore.setSidebar('entity', e)
		}">
		<template #secondary-actions>
			<NcButton @click="navigationStore.setModal('editEntity')">
				<template #icon>
					<Pencil :size="20" />
				</template>
				{{ t('docudesk', 'Edit Entity') }}
			</NcButton>
			<NcButton type="error" @click="navigationStore.setDialog('deleteObject', { objectType: 'entity', dialogTitle: 'Entity' })">
				<template #icon>
					<Delete :size="20" />
				</template>
				{{ t('docudesk', 'Delete Entity') }}
			</NcButton>
		</template>

		<NcAppSidebarTab id="overview-tab" name="Overview" :order="1">
			<template #icon>
				<InformationOutline :size="20" />
			</template>

			<div class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'Entity Information') }}
				</div>
				<div class="entityContainer">
					<div class="entityBadge">
						<NcCounterBubble
							:type="getEntityTypeBadgeType(entity.entityType)"
							:class="getEntityTypeClass(entity.entityType)">
							{{ formatEntityType(entity.entityType) }}
						</NcCounterBubble>
					</div>

					<div class="entityText">
						<span class="entityValue">{{ entity.text || 'Unknown' }}</span>
					</div>
				</div>
			</div>

			<div class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'Statistics') }}
				</div>
				<div class="statsContainer">
					<table class="statisticsTable">
						<tbody>
							<tr>
								<td>{{ t('docudesk', 'Occurrence Count') }}</td>
								<td colspan="2">{{ entity.occurrenceCount || 0 }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Average Confidence') }}</td>
								<td colspan="2">{{ formatConfidence(entity.averageConfidence) }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'First Detected') }}</td>
								<td colspan="2">{{ formatDate(entity.firstDetected) }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Last Detected') }}</td>
								<td colspan="2">{{ formatDate(entity.lastDetected) }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Entity ID') }}</td>
								<td colspan="2" class="hashValue">{{ entity.id || 'N/A' }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</NcAppSidebarTab>

		<NcAppSidebarTab id="reports-tab" name="Reports" :order="2">
			<template #icon>
				<FileDocumentOutline :size="20" />
			</template>

			<div class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'Reports Containing This Entity') }}
				</div>

				<div v-if="objectStore.isLoading('entity_' + entity.id + '_used')" class="loadingContainer">
					<NcLoadingIcon :size="20" />
					<span>{{ t('docudesk', 'Loading reports...') }}</span>
				</div>

				<div v-else-if="relatedReports && relatedReports.length > 0">
					<div class="reportSummary">
						<p>{{ t('docudesk', 'Found in {count} reports', {
							count: relatedReports.length
						}) }}</p>
					</div>

					<div class="reportList">
						<div v-for="(report, index) in relatedReports" :key="index" class="reportItem">
							<div class="reportHeader">
								<span class="reportName">{{ report.fileName || 'Unnamed Report' }}</span>
								<div class="reportActions">
									<NcButton
										type="tertiary"
										@click="openReport(report)">
										<template #icon>
											<OpenInNew :size="16" />
										</template>
										Open
									</NcButton>
								</div>
							</div>
							<div class="reportMeta">
								<span class="reportPath">{{ report.filePath || 'Unknown path' }}</span>
								<span class="reportDate">{{ formatDate(report.created) }}</span>
							</div>
							<div v-if="report.riskLevel" class="reportRisk">
								<NcCounterBubble
									:type="getRiskLevelBadgeType(report.riskLevel)"
									:class="getRiskLevelClass(report.riskLevel)">
									Risk: {{ report.riskLevel }}
								</NcCounterBubble>
							</div>
						</div>
					</div>
				</div>
				<div v-else class="emptyContainer">
					<NcEmptyContent
						:title="t('docudesk', 'No reports found')"
						:description="t('docudesk', 'This entity has not been detected in any reports yet')"
						icon="icon-folder">
					</NcEmptyContent>
				</div>
			</div>
		</NcAppSidebarTab>

		<NcAppSidebarTab id="settings-tab" name="Settings" :order="3">
			<template #icon>
				<Cog :size="20" />
			</template>

			<div class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'Entity Settings') }}
				</div>
				<NcNoteCard type="info">
					Entity-specific settings will be added in a future update
				</NcNoteCard>
			</div>
		</NcAppSidebarTab>
	</NcAppSidebar>
</template>

<script>
import { NcAppSidebar, NcAppSidebarTab, NcButton, NcEmptyContent, NcNoteCard, NcCounterBubble, NcLoadingIcon } from '@nextcloud/vue'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { objectStore, navigationStore } from '../../store/store.js'
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import Cog from 'vue-material-design-icons/Cog.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'

export default {
	name: 'EntitySideBar',
	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		NcButton,
		NcEmptyContent,
		NcNoteCard,
		NcCounterBubble,
		NcLoadingIcon,
		InformationOutline,
		FileDocumentOutline,
		Cog,
		Pencil,
		Delete,
		OpenInNew,
	},
	data() {
		return {
			/**
			 * Active tab in the sidebar
			 * @type {string}
			 */
			activeTab: 'overview-tab',
			// Store references
			objectStore,
			navigationStore,
		}
	},
	computed: {
		/**
		 * Get the active entity from the store
		 * @return {object|null} The active entity object
		 */
		entity() {
			return objectStore.getActiveObject('entity')
		},

		/**
		 * Get related reports from the store using the 'used' relationship
		 * @return {Array} Array of reports that contain this entity
		 */
		relatedReports() {
			if (!this.entity?.id) return []
			
			// Get the 'used' data which contains reports where this entity is used
			const usedData = objectStore.getRelatedData('entity', 'used')
			
			if (!usedData || !usedData.results) return []
			
			// The 'used' endpoint returns reports that reference this entity
			return usedData.results || []
		},
	},
	watch: {
		/**
		 * Watch for changes in the active entity and load related data
		 * @param {object} newEntity - The new active entity
		 * @param {object} oldEntity - The previous active entity
		 */
		entity: {
			handler(newEntity, oldEntity) {
				if (newEntity && newEntity.id !== oldEntity?.id) {
					this.loadRelatedReports()
				}
			},
			immediate: true,
		},
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
		 * Load related reports for the current entity
		 */
		async loadRelatedReports() {
			if (!this.entity?.id) return

			try {
				// Fetch the 'used' relationship data to get reports containing this entity
				await objectStore.fetchRelatedData('entity', this.entity.id, 'used')
			} catch (error) {
				console.error('Error loading related reports:', error)
				showError(this.t('docudesk', 'Failed to load related reports'))
			}
		},

		/**
		 * Open a report in the reports view
		 * @param {object} report - The report to open
		 */
		openReport(report) {
			// Set the report as active and switch to reports view
			objectStore.setActiveObject('report', report)
			navigationStore.setSelected('reports')
			navigationStore.setSidebar('report', true)
			navigationStore.setSidebar('entity', false)
		},

		/**
		 * Get entity type badge type
		 * @param {string} entityType - Entity type
		 * @return {string} Badge type
		 */
		getEntityTypeBadgeType(entityType) {
			switch (entityType?.toUpperCase()) {
			case 'PERSON':
				return 'warning'
			case 'EMAIL_ADDRESS':
				return 'error'
			case 'PHONE_NUMBER':
				return 'warning'
			case 'CREDIT_CARD':
				return 'error'
			case 'IBAN_CODE':
				return 'error'
			case 'ORGANIZATION':
				return 'primary'
			case 'LOCATION':
				return 'primary'
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
				return 'error'
			case 'medium':
				return 'warning'
			case 'low':
				return 'success'
			default:
				return 'primary'
			}
		},

		/**
		 * Format entity type for display
		 * @param {string} entityType - Entity type
		 * @return {string} Formatted entity type
		 */
		formatEntityType(entityType) {
			if (!entityType) return 'Unknown'
			return entityType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
		},

		/**
		 * Format confidence score
		 * @param {number} confidence - Confidence score
		 * @return {string} Formatted confidence
		 */
		formatConfidence(confidence) {
			if (!confidence && confidence !== 0) return '-'
			return (confidence * 100).toFixed(1) + '%'
		},

		/**
		 * Format date for display
		 * @param {string|number} date - Date to format
		 * @return {string} Formatted date
		 */
		formatDate(date) {
			if (!date) return '-'
			return new Date(date).toLocaleDateString() + ', ' + new Date(date).toLocaleTimeString()
		},

		/**
		 * Get entity type class
		 * @param {string} entityType - Entity type
		 * @return {string} Class name
		 */
		getEntityTypeClass(entityType) {
			switch (entityType?.toUpperCase()) {
			case 'PERSON':
				return 'entity-person'
			case 'EMAIL_ADDRESS':
				return 'entity-email'
			case 'PHONE_NUMBER':
				return 'entity-phone'
			case 'CREDIT_CARD':
				return 'entity-credit-card'
			case 'IBAN_CODE':
				return 'entity-iban'
			case 'ORGANIZATION':
				return 'entity-organization'
			case 'LOCATION':
				return 'entity-location'
			default:
				return 'entity-default'
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
	},
}
</script>

<style scoped>
.section {
	padding: 16px;
	margin-bottom: 16px;
	border-bottom: 1px solid var(--color-border);
}

/* Reports tab specific styling */
#reports-tab .section {
	height: calc(100vh - 200px);
	display: flex;
	flex-direction: column;
	margin-bottom: 0;
}

#reports-tab .sectionTitle {
	flex-shrink: 0;
}

#reports-tab .reportSummary {
	flex-shrink: 0;
}

#reports-tab .reportList,
#reports-tab .emptyContainer {
	flex: 1;
	min-height: 0;
}

.sectionTitle {
	font-size: 16px;
	font-weight: 600;
	margin-bottom: 12px;
	color: var(--color-main-text);
}

.entityContainer {
	margin-bottom: 16px;
}

.entityBadge {
	display: flex;
	gap: 8px;
	margin-bottom: 16px;
}

.entityText {
	padding: 12px;
	background-color: var(--color-background-hover);
	border-radius: 8px;
	margin-bottom: 16px;
}

.entityValue {
	font-family: monospace;
	font-size: 16px;
	font-weight: 500;
	color: var(--color-main-text);
	word-break: break-word;
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
	vertical-align: top;
}

.statisticsTable td:first-child {
	color: var(--color-text-maxcontrast);
	font-weight: 500;
	width: 40%;
}

.hashValue {
	font-family: monospace;
	font-size: 12px;
	word-break: break-all;
}

.loadingContainer {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 16px;
	color: var(--color-text-maxcontrast);
}

.reportSummary {
	margin-bottom: 16px;
	padding: 12px;
	background-color: var(--color-background-hover);
	border-radius: 8px;
}

.reportSummary p {
	margin: 0;
	color: var(--color-text-maxcontrast);
}

.reportList {
	overflow-y: auto;
	flex: 1;
	min-height: 200px;
}

.reportItem {
	padding: 12px;
	border: 1px solid var(--color-border);
	border-radius: 8px;
	margin-bottom: 8px;
	background-color: var(--color-background-hover);
}

.reportHeader {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 8px;
}

.reportName {
	font-weight: 500;
	color: var(--color-main-text);
	font-size: 14px;
	flex: 1;
	margin-right: 8px;
}

.reportActions {
	display: flex;
	align-items: center;
	gap: 8px;
}

.reportMeta {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 8px;
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.reportPath {
	flex: 1;
	margin-right: 8px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.reportRisk {
	display: flex;
	justify-content: flex-end;
}

.emptyContainer {
	padding: 20px;
	text-align: center;
}

/* Entity Type Colors */
.entity-person {
	background-color: var(--color-warning) !important;
	color: white !important;
}

.entity-email {
	background-color: var(--color-error) !important;
	color: white !important;
}

.entity-phone {
	background-color: var(--color-warning) !important;
	color: white !important;
}

.entity-credit-card {
	background-color: var(--color-error) !important;
	color: white !important;
}

.entity-iban {
	background-color: var(--color-error) !important;
	color: white !important;
}

.entity-organization {
	background-color: var(--color-primary) !important;
	color: white !important;
}

.entity-location {
	background-color: var(--color-primary) !important;
	color: white !important;
}

.entity-default {
	background-color: var(--color-primary) !important;
	color: white !important;
}

/* Risk Level Colors */
.risk-critical {
	background-color: var(--color-error) !important;
	color: white !important;
}

.risk-high {
	background-color: var(--color-error) !important;
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
</style> 