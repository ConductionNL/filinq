/**
 * EntitiesSideBar component for displaying entities overview statistics and filters
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
		name="Entities"
		subtitle="Entity Overview"
		subname="Statistics and Metrics"
		:open="navigationStore.sidebarState.entities"
		@update:open="(e) => {
			navigationStore.setSidebar('entities', e)
		}">
		<NcAppSidebarTab id="overview-tab" name="Overview" :order="1">
			<template #icon>
				<ChartBar :size="20" />
			</template>

			<!-- Filter Section -->
			<div class="filterSection">
				<h3>{{ t('docudesk', 'Filter Entities') }}</h3>
				<div class="filterGroup">
					<label for="entityTypeSelect">{{ t('docudesk', 'Entity Type') }}</label>
					<NcSelect
						v-model="selectedEntityType"
						:options="entityTypeOptions"
						placeholder="All entity types"
						:clearable="true"
						@update:model-value="handleEntityTypeChange" />
				</div>
			</div>

			<!-- System Totals Section -->
			<div class="section">
				<h3 class="sectionTitle">
					{{ t('docudesk', 'Entity Totals') }}
				</h3>
				<div v-if="objectStore.isLoading('entity')" class="loadingContainer">
					<NcLoadingIcon :size="20" />
					<span>{{ t('docudesk', 'Loading statistics...') }}</span>
				</div>
				<div v-else-if="systemTotals" class="statsContainer">
					<table class="statisticsTable">
						<tbody>
							<tr>
								<td>{{ t('docudesk', 'Total Entities') }}</td>
								<td>{{ filteredEntities.length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Total Occurrences') }}</td>
								<td>{{ totalOccurrences }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Person Entities') }}</td>
								<td>{{ getEntitiesByType('PERSON').length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Email Addresses') }}</td>
								<td>{{ getEntitiesByType('EMAIL_ADDRESS').length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Phone Numbers') }}</td>
								<td>{{ getEntitiesByType('PHONE_NUMBER').length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Organizations') }}</td>
								<td>{{ getEntitiesByType('ORGANIZATION').length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'Locations') }}</td>
								<td>{{ getEntitiesByType('LOCATION').length }}</td>
								<td>-</td>
							</tr>
							<tr>
								<td>{{ t('docudesk', 'High Confidence') }}</td>
								<td>{{ getEntitiesByConfidence(0.8).length }}</td>
								<td>-</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Recent Activity Section -->
			<div class="section">
				<h3 class="sectionTitle">
					{{ t('docudesk', 'Recent Detections') }}
				</h3>
				<div v-if="objectStore.isLoading('entity')" class="loadingContainer">
					<NcLoadingIcon :size="20" />
					<span>{{ t('docudesk', 'Loading activity...') }}</span>
				</div>
				<div v-else-if="recentEntities.length > 0" class="recentEntities">
					<div v-for="entity in recentEntities" :key="entity.id" class="recentEntityItem">
						<div class="entityHeader">
							<TagOutline :size="16" />
							<span class="entityText">{{ entity.text || 'Unknown Entity' }}</span>
						</div>
						<div class="entityMeta">
							<span class="entityType" :class="getEntityTypeClass(entity.entityType)">
								{{ formatEntityType(entity.entityType) }}
							</span>
							<span class="entityDate">
								{{ formatDate(entity.lastDetected) }}
							</span>
						</div>
					</div>
				</div>
				<div v-else class="emptyContainer">
					<NcEmptyContent
						:title="t('docudesk', 'No recent detections')"
						:description="t('docudesk', 'Entities will appear here as they are detected in documents')"
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
					Entity Settings
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
import TagOutline from 'vue-material-design-icons/TagOutline.vue'

export default {
	name: 'EntitiesSideBar',
	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		NcLoadingIcon,
		NcNoteCard,
		NcSelect,
		NcEmptyContent,
		ChartBar,
		Cog,
		TagOutline,
	},
	data() {
		return {
			/**
			 * Active tab in the sidebar
			 * @type {string}
			 */
			activeTab: 'overview-tab',
			/**
			 * Selected entity type filter
			 * @type {string|null}
			 */
			selectedEntityType: null,
			// Store references
			objectStore,
			navigationStore,
		}
	},
	computed: {
		/**
		 * Get all entities from the store
		 * @return {Array} Array of entity objects
		 */
		entities() {
			return objectStore.getCollection('entity').results || []
		},

		/**
		 * Get filtered entities based on selected filters
		 * @return {Array} Filtered array of entity objects
		 */
		filteredEntities() {
			let filtered = this.entities

			if (this.selectedEntityType) {
				filtered = filtered.filter(entity => entity.entityType === this.selectedEntityType)
			}

			return filtered
		},

		/**
		 * Get recent entities (last 10)
		 * @return {Array} Array of recent entity objects
		 */
		recentEntities() {
			return [...this.entities]
				.sort((a, b) => new Date(b.lastDetected || 0) - new Date(a.lastDetected || 0))
				.slice(0, 10)
		},

		/**
		 * Get system totals calculated from all entities
		 * @return {object} System totals object
		 */
		systemTotals() {
			return {
				totalEntities: this.entities.length,
				totalOccurrences: this.totalOccurrences,
			}
		},

		/**
		 * Calculate total occurrences from all entities
		 * @return {number} Total occurrence count
		 */
		totalOccurrences() {
			return this.entities.reduce((total, entity) => {
				return total + (entity.occurrenceCount || 0)
			}, 0)
		},

		/**
		 * Entity type filter options
		 * @return {Array} Array of entity type options
		 */
		entityTypeOptions() {
			const entityTypes = [...new Set(this.entities.map(entity => entity.entityType).filter(Boolean))]
			return entityTypes.map(type => ({
				id: type,
				label: this.formatEntityType(type),
			}))
		},
	},
	mounted() {
		// Ensure entities are loaded
		objectStore.fetchCollection('entity')
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
		 * Handle entity type filter change
		 * @param {string|null} entityType - Selected entity type
		 */
		handleEntityTypeChange(entityType) {
			this.selectedEntityType = entityType?.id || null
		},

		/**
		 * Get entities by type
		 * @param {string} entityType - Entity type to filter by
		 * @return {Array} Filtered entities
		 */
		getEntitiesByType(entityType) {
			return this.entities.filter(entity => entity.entityType === entityType)
		},

		/**
		 * Get entities by confidence threshold
		 * @param {number} threshold - Minimum confidence threshold
		 * @return {Array} Filtered entities
		 */
		getEntitiesByConfidence(threshold) {
			return this.entities.filter(entity => (entity.averageConfidence || 0) >= threshold)
		},

		/**
		 * Format entity type to readable format
		 * @param {string} entityType - Entity type
		 * @return {string} Formatted entity type
		 */
		formatEntityType(entityType) {
			if (!entityType) return 'Unknown'
			return entityType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
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
		 * Get CSS class for entity type
		 * @param {string} entityType - Entity type
		 * @return {string} CSS class
		 */
		getEntityTypeClass(entityType) {
			switch (entityType?.toUpperCase()) {
			case 'PERSON':
				return 'type-person'
			case 'EMAIL_ADDRESS':
				return 'type-email'
			case 'PHONE_NUMBER':
				return 'type-phone'
			case 'CREDIT_CARD':
				return 'type-credit-card'
			case 'IBAN_CODE':
				return 'type-iban'
			case 'ORGANIZATION':
				return 'type-organization'
			case 'LOCATION':
				return 'type-location'
			default:
				return 'type-default'
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

.recentEntities {
	max-height: 300px;
	overflow-y: auto;
}

.recentEntityItem {
	padding: 8px;
	border-bottom: 1px solid var(--color-border-dark);
	margin-bottom: 8px;
}

.entityHeader {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 4px;
}

.entityText {
	font-weight: 500;
	color: var(--color-main-text);
	flex: 1;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.entityMeta {
	display: flex;
	justify-content: space-between;
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.entityType {
	padding: 2px 6px;
	border-radius: 12px;
	font-size: 11px;
	font-weight: 500;
	text-transform: uppercase;
}

.type-person {
	background-color: var(--color-warning);
	color: white;
}

.type-email {
	background-color: var(--color-error);
	color: white;
}

.type-phone {
	background-color: var(--color-warning);
	color: white;
}

.type-credit-card {
	background-color: var(--color-error);
	color: white;
}

.type-iban {
	background-color: var(--color-error);
	color: white;
}

.type-organization {
	background-color: var(--color-primary);
	color: white;
}

.type-location {
	background-color: var(--color-primary);
	color: white;
}

.type-default {
	background-color: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
}

.emptyContainer {
	padding: 20px;
	text-align: center;
}
</style> 