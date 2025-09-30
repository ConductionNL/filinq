/**
 * ReportSideBar - Vue component for displaying individual report details in a sidebar
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
		v-if="report"
		ref="sidebar"
		v-model="activeTab"
		:name="report.fileName || 'Unnamed Report'"
		:subtitle="formatFileSize(report.fileSize)"
		subname="Report Details"
		:open="navigationStore.sidebarState.report"
		@update:open="(e) => {
			navigationStore.setSidebar('report', e)
		}">
		<template #secondary-actions>
			<NcButton @click="navigationStore.setModal('editReport')">
				<template #icon>
					<Pencil :size="20" />
				</template>
				{{ t('docudesk', 'Edit Report') }}
			</NcButton>
			<NcButton @click="downloadReport">
				<template #icon>
					<Download :size="20" />
				</template>
				{{ t('docudesk', 'Download Report') }}
			</NcButton>
			<NcButton type="error" @click="navigationStore.setDialog('deleteObject', { objectType: 'report', dialogTitle: 'Report' })">
				<template #icon>
					<Delete :size="20" />
				</template>
				{{ t('docudesk', 'Delete Report') }}
			</NcButton>
		</template>

		<NcAppSidebarTab id="overview-tab" name="Overview" :order="1">
			<template #icon>
				<InformationOutline :size="20" />
			</template>

			<div class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'Status & Risk') }}
				</div>
				<div class="statusContainer">
					<div class="statusBadges">
						<NcCounterBubble
							:type="getStatusBadgeType(report.status)"
							:class="{ 'status-badge': true }">
							{{ report.status }}
						</NcCounterBubble>
						<NcCounterBubble
							v-if="report.riskLevel"
							:type="getRiskLevelBadgeType(report.riskLevel)"
							:class="{ 'risk-badge': true }">
							Risk: {{ report.riskLevel }}
						</NcCounterBubble>
					</div>

					<div v-if="report.riskScore" class="riskScoreContainer">
						<div class="riskScoreCircle" :class="getRiskScoreClass(report.riskScore)">
							<span class="riskScoreValue">{{ formatRiskScore(report.riskScore) }}</span>
						</div>
						<div class="riskScoreDetails">
							<p class="riskExplanation">
								{{ getRiskExplanation(report.riskLevel) }}
							</p>
						</div>
					</div>
				</div>
			</div>

			<div class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'Anonymization') }}
				</div>
				<div class="anonymizationContainer">
					<div v-if="report.anonymization && report.anonymization.status === 'completed'" class="anonymizedInfo">
						<NcCounterBubble type="success">
							{{ t('docudesk', 'Document is anonymized') }}
						</NcCounterBubble>
						<div class="anonymizedDetails">
							<p>{{ t('docudesk', 'This document has been processed to remove or mask sensitive information.') }}</p>
							<div v-if="report.anonymization && report.anonymization.endTime" class="anonymizedMeta">
								<strong>{{ t('docudesk', 'Anonymized on:') }}</strong> {{ formatDate(new Date(report.anonymization.endTime * 1000)) }}
							</div>
						</div>
					</div>
					<div v-else class="notAnonymized">
						<NcCounterBubble type="warning">
							{{ t('docudesk', 'Document contains sensitive data') }}
						</NcCounterBubble>
						<div class="anonymizeAction">
							<p>{{ t('docudesk', 'This document contains {count} sensitive entities that could be anonymized.', { count: report.entities?.length || 0 }) }}</p>
							<NcButton type="primary" @click="anonymizeDocument">
								<template #icon>
									<Incognito :size="20" />
								</template>
								{{ t('docudesk', 'Anonymize Document') }}
							</NcButton>
						</div>
					</div>
				</div>
			</div>

			<div class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'File Information') }}
				</div>
				<div class="statsContainer">
					<table class="statisticsTable">
						<tbody>
							<tr>
								<td>{{ t('docudesk', 'File Path') }}</td>
								<td colspan="2">{{ report.filePath || 'N/A' }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'File Type') }}</td>
								<td colspan="2">{{ report.fileType || 'N/A' }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'File Extension') }}</td>
								<td colspan="2">{{ report.fileExtension || 'N/A' }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'File Size') }}</td>
								<td colspan="2">{{ formatFileSize(report.fileSize) }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'File Hash') }}</td>
								<td colspan="2" class="hashValue">{{ report.fileHash || 'N/A' }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Node ID') }}</td>
								<td colspan="2">{{ report.nodeId || 'N/A' }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<div v-if="report.errorMessage" class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'Error Information') }}
				</div>
				<NcNoteCard type="error">
					{{ report.errorMessage }}
				</NcNoteCard>
			</div>
		</NcAppSidebarTab>

		<NcAppSidebarTab id="entities-tab" name="Entities" :order="2">
			<template #icon>
				<TagOutline :size="20" />
			</template>

			<div class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'Detected Entities') }}
				</div>

				<div v-if="report.entities && report.entities.length > 0">
					<div class="entitySummary">
						<p>{{ t('docudesk', 'Found {count} entities across {types} different types', {
							count: report.entities.length,
							types: getEntityTypes(report.entities).length
						}) }}</p>
					</div>

					<div class="entityList">
						<div v-for="(entity, index) in report.entities" :key="index" class="entityItem">
							<div class="entityHeader">
								<span class="entityType">{{ formatEntityType(entity.entityType) }}</span>
								<div class="entityActions">
									<NcCheckboxRadioSwitch
										:checked="entity.anonymize !== undefined ? entity.anonymize : true"
										type="switch"
										@update:checked="toggleEntityAnonymization(index, $event)">
										{{ t('docudesk', 'Anonymize') }}
									</NcCheckboxRadioSwitch>
									<span class="entityScore">{{ (entity.score * 100).toFixed(1) }}%</span>
								</div>
							</div>
							<div class="entityText">{{ entity.text }}</div>
						</div>
					</div>
				</div>
				<div v-else class="emptyContainer">
					<NcEmptyContent
						:title="t('docudesk', 'No entities found')"
						:description="t('docudesk', 'No sensitive entities were detected in this document')"
						icon="icon-checkmark">
					</NcEmptyContent>
				</div>
			</div>
		</NcAppSidebarTab>

		<NcAppSidebarTab id="compliance-tab" name="Compliance" :order="3">
			<template #icon>
				<ShieldCheckOutline :size="20" />
			</template>

			<div class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'WCAG Compliance') }}
				</div>
				<div v-if="report.wcagComplianceResults && Object.keys(report.wcagComplianceResults).length > 0" class="complianceContainer">
					<table class="statisticsTable">
						<tbody>
							<tr v-for="(value, key) in report.wcagComplianceResults" :key="key">
								<td>{{ formatKey(key) }}</td>
								<td colspan="2">{{ formatValue(value) }}</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div v-else class="emptyContainer">
					<NcEmptyContent
						:title="t('docudesk', 'No compliance data')"
						:description="t('docudesk', 'WCAG compliance analysis has not been performed for this document')"
						icon="icon-info">
					</NcEmptyContent>
				</div>
			</div>

			<div class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'Language Level') }}
				</div>
				<div v-if="report.languageLevelResults && Object.keys(report.languageLevelResults).length > 0" class="languageContainer">
					<table class="statisticsTable">
						<tbody>
							<tr v-for="(value, key) in report.languageLevelResults" :key="key">
								<td>{{ formatKey(key) }}</td>
								<td colspan="2">{{ formatValue(value) }}</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div v-else class="emptyContainer">
					<NcEmptyContent
						:title="t('docudesk', 'No language data')"
						:description="t('docudesk', 'Language level analysis has not been performed for this document')"
						icon="icon-info">
					</NcEmptyContent>
				</div>
			</div>

			<div class="section">
				<div class="sectionTitle">
					{{ t('docudesk', 'Retention Policy') }}
				</div>
				<div class="retentionContainer">
					<table class="statisticsTable">
						<tbody>
							<tr>
								<td>{{ t('docudesk', 'Retention Period') }}</td>
								<td colspan="2">{{ report.retentionPeriod ? `${report.retentionPeriod} days` : 'Indefinite' }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Retention Expiry') }}</td>
								<td colspan="2">{{ report.retentionExpiry || 'Not set' }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Legal Basis') }}</td>
								<td colspan="2">{{ report.legalBasis || 'Not specified' }}</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Purpose') }}</td>
								<td colspan="2">{{ report.purpose || 'Not specified' }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</NcAppSidebarTab>
	</NcAppSidebar>
</template>

<script>
import { NcAppSidebar, NcAppSidebarTab, NcButton, NcEmptyContent, NcNoteCard, NcCounterBubble, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { objectStore, navigationStore } from '../../store/store.js'
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue'
import TagOutline from 'vue-material-design-icons/TagOutline.vue'
import ShieldCheckOutline from 'vue-material-design-icons/ShieldCheckOutline.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import Download from 'vue-material-design-icons/Download.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Incognito from 'vue-material-design-icons/Incognito.vue'

export default {
	name: 'ReportSideBar',
	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		NcButton,
		NcEmptyContent,
		NcNoteCard,
		NcCounterBubble,
		NcCheckboxRadioSwitch,
		InformationOutline,
		TagOutline,
		ShieldCheckOutline,
		Pencil,
		Download,
		Delete,
		Incognito,
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
		 * Get the active report from the store
		 * @return {object|null} The active report object
		 */
		report() {
			return objectStore.getActiveObject('report')
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
		 * Get CSS class for risk score
		 * @param {number} riskScore - Risk score
		 * @return {string} CSS class
		 */
		getRiskScoreClass(riskScore) {
			// Handle percentage values (>=1)
			if (riskScore >= 1) {
				if (riskScore >= 90) return 'risk-critical'
				if (riskScore >= 70) return 'risk-high'
				if (riskScore >= 40) return 'risk-medium'
				return 'risk-low'
			}

			// Handle decimal values (0-1)
			if (riskScore >= 0.9) return 'risk-critical'
			if (riskScore >= 0.7) return 'risk-high'
			if (riskScore >= 0.4) return 'risk-medium'
			return 'risk-low'
		},

		/**
		 * Format risk score for display
		 * @param {number} riskScore - Risk score
		 * @return {string} Formatted risk score with percentage
		 */
		formatRiskScore(riskScore) {
			if (!riskScore && riskScore !== 0) return '0%'

			// If the score is already a percentage (>= 1), use it directly
			if (riskScore >= 1) {
				return Math.round(riskScore) + '%'
			}

			// If it's a decimal (between 0 and 1), convert to percentage
			return Math.round(riskScore * 100) + '%'
		},

		/**
		 * Get risk explanation based on level
		 * @param {string} riskLevel - Risk level
		 * @return {string} Risk explanation
		 */
		getRiskExplanation(riskLevel) {
			switch (riskLevel?.toLowerCase()) {
			case 'critical':
				return 'This document contains extremely sensitive information that poses severe risk and requires urgent action.'
			case 'high':
				return 'This document contains highly sensitive information that requires immediate attention.'
			case 'medium':
				return 'This document contains moderately sensitive information that should be handled carefully.'
			case 'low':
				return 'This document contains minimal sensitive information.'
			default:
				return 'Risk level has not been determined for this document.'
			}
		},

		/**
		 * Get unique entity types from entities array
		 * @param {Array} entities - Array of entity objects
		 * @return {Array} Array of unique entity types with counts
		 */
		getEntityTypes(entities) {
			if (!entities) return []

			const typeCounts = {}
			entities.forEach(entity => {
				const type = entity.entityType
				typeCounts[type] = (typeCounts[type] || 0) + 1
			})

			return Object.entries(typeCounts).map(([type, count]) => ({ type, count }))
		},

		/**
		 * Format entity type for display
		 * @param {string} entityType - Entity type
		 * @return {string} Formatted entity type
		 */
		formatEntityType(entityType) {
			return entityType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
		},

		/**
		 * Format key for display (camelCase to readable)
		 * @param {string} key - Key to format
		 * @return {string} Formatted key
		 */
		formatKey(key) {
			return key.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())
		},

		/**
		 * Format value for display
		 * @param {*} value - Value to format
		 * @return {string} Formatted value
		 */
		formatValue(value) {
			if (typeof value === 'boolean') {
				return value ? 'Yes' : 'No'
			}
			if (typeof value === 'number') {
				return value.toString()
			}
			return value || 'N/A'
		},

		/**
		 * Download the report file
		 */
		async downloadReport() {
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
		 * Anonymize the document
		 */
		async anonymizeDocument() {
			// Implementation depends on your API
			// This is a placeholder - implement according to your backend
			showError(this.t('docudesk', 'Anonymization functionality not implemented yet'))
		},

		/**
		 * Format date for display
		 * @param {string|number} date - Date to format
		 * @return {string} Formatted date
		 */
		formatDate(date) {
			return new Date(date).toLocaleDateString()
		},

		/**
		 * Toggle entity anonymization
		 * @param {number} index - Index of the entity in the entities array
		 * @param {boolean} shouldAnonymize - New anonymization state
		 */
		toggleEntityAnonymization(index, shouldAnonymize) {
			// Implementation depends on your API
			// This is a placeholder - implement according to your backend
			// Update the entity's anonymization state
			if (this.report.entities && this.report.entities[index]) {
				this.report.entities[index].anonymize = shouldAnonymize
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

/* Entities tab specific styling */
#entities-tab .section {
	height: calc(100vh - 200px);
	display: flex;
	flex-direction: column;
	margin-bottom: 0;
}

#entities-tab .sectionTitle {
	flex-shrink: 0;
}

#entities-tab .entitySummary {
	flex-shrink: 0;
}

#entities-tab .entityList,
#entities-tab .emptyContainer {
	flex: 1;
	min-height: 0;
}

.sectionTitle {
	font-size: 16px;
	font-weight: 600;
	margin-bottom: 12px;
	color: var(--color-main-text);
}

.statusContainer {
	margin-bottom: 16px;
}

.statusBadges {
	display: flex;
	gap: 8px;
	margin-bottom: 16px;
}

.status-badge,
.risk-badge {
	font-size: 12px;
	font-weight: 500;
}

.riskScoreContainer {
	display: flex;
	align-items: center;
	gap: 16px;
}

.riskScoreCircle {
	width: 60px;
	height: 60px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-weight: bold;
	color: white;
	flex-shrink: 0;
}

.risk-critical {
	background-color: #8B0000;
}

.risk-high {
	background-color: var(--color-error);
}

.risk-medium {
	background-color: var(--color-warning);
}

.risk-low {
	background-color: var(--color-success);
}

.riskScoreValue {
	font-size: 18px;
}

.riskScoreDetails {
	flex: 1;
}

.riskExplanation {
	margin: 0;
	font-size: 14px;
	color: var(--color-text-maxcontrast);
	line-height: 1.4;
}

.statsContainer,
.complianceContainer,
.languageContainer,
.retentionContainer {
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

.entitySummary {
	margin-bottom: 16px;
	padding: 12px;
	background-color: var(--color-background-hover);
	border-radius: 8px;
}

.entitySummary p {
	margin: 0;
	color: var(--color-text-maxcontrast);
}

.entityList {
	overflow-y: auto;
	flex: 1;
	min-height: 200px;
}

.entityItem {
	padding: 12px;
	border: 1px solid var(--color-border);
	border-radius: 8px;
	margin-bottom: 8px;
	background-color: var(--color-background-hover);
}

.entityHeader {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 8px;
}

.entityType {
	font-weight: 500;
	color: var(--color-main-text);
	font-size: 14px;
}

.entityActions {
	display: flex;
	align-items: center;
	gap: 12px;
}

.entityScore {
	background-color: var(--color-primary);
	color: white;
	padding: 2px 8px;
	border-radius: 12px;
	font-size: 12px;
	font-weight: 500;
	flex-shrink: 0;
}

.entityText {
	font-family: monospace;
	background-color: var(--color-background-dark);
	padding: 8px;
	border-radius: 4px;
	font-size: 13px;
	color: var(--color-main-text);
	word-break: break-word;
}

.emptyContainer {
	padding: 20px;
	text-align: center;
}

.anonymizationContainer {
	margin-bottom: 16px;
}

.anonymizedInfo {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.anonymizedDetails {
	padding: 12px;
	background-color: var(--color-background-hover);
	border-radius: 8px;
}

.anonymizedDetails p {
	margin: 0 0 8px 0;
	color: var(--color-text-maxcontrast);
}

.anonymizedMeta {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.notAnonymized {
	margin-bottom: 16px;
}

.anonymizeAction {
	margin-top: 12px;
	padding: 12px;
	background-color: var(--color-background-hover);
	border-radius: 8px;
}

.anonymizeAction p {
	margin: 0 0 12px 0;
	color: var(--color-text-maxcontrast);
}
</style>
