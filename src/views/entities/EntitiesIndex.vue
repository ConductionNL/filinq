/**
 * EntitiesIndex component for displaying entities in table view with sidebar
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
					Entities
				</h2>
				<div class="headerActionsContainer">
					<NcButton @click="objectStore.fetchCollection('entity')">
						<template #icon>
							<Refresh :size="20" />
						</template>
						Refresh
					</NcButton>
				</div>
			</div>

			<!-- Scrollable Content Area -->
			<div class="entitiesContent">
				<div v-if="objectStore.isLoading('entity')" class="loading">
					<NcLoadingIcon :size="32" />
					<span>Loading entities...</span>
				</div>
				<div v-else-if="error" class="error">
					<NcEmptyContent :title="error" icon="icon-error" />
				</div>
				<div v-else-if="!filteredEntities.length" class="empty">
					<NcEmptyContent title="No entities found" icon="icon-folder" />
				</div>
				<div v-else class="tableContainer">
					<table class="statisticsTable entityStats entitiesTable">
						<thead>
							<tr>
								<th>Text</th>
								<th>Entity Type</th>
								<th>Occurrence Count</th>
								<th>Average Confidence</th>
								<th>First Detected</th>
								<th>Last Detected</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="entity in paginatedEntities"
								:key="entity.id"
								:class="{ 'selected-row': selectedEntity?.id === entity.id }"
								@click="selectEntity(entity)">
								<td>
									<span :title="entity.text || 'Unknown'">{{ entity.text || 'Unknown' }}</span>
								</td>
								<td>
									<NcCounterBubble
										v-if="entity.entityType"
										:type="getEntityTypeBadgeType(entity.entityType)"
										:class="getEntityTypeClass(entity.entityType)">
										{{ formatEntityType(entity.entityType) }}
									</NcCounterBubble>
									<span v-else>-</span>
								</td>
								<td>{{ entity.occurrenceCount || 0 }}</td>
								<td>{{ formatConfidence(entity.averageConfidence) }}</td>
								<td>{{ formatDate(entity.firstDetected) }}</td>
								<td>{{ formatDate(entity.lastDetected) }}</td>
								<td>
									<NcButton
										type="tertiary"
										@click.stop="selectEntity(entity)">
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
		<EntitiesSideBar v-if="navigationStore.sidebarState.entities" />
		<EntitySideBar v-if="navigationStore.sidebarState.entity" />
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
import EntitiesSideBar from '../../sidebars/entities/EntitiesSideBar.vue'
import EntitySideBar from '../../sidebars/entities/EntitySideBar.vue'

export default {
	name: 'EntitiesIndex',
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
		EntitiesSideBar,
		EntitySideBar,
	},
	data() {
		return {
			error: null,
			// Selected entity
			selectedEntity: null,
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
		 * Get all entities from the store
		 * @return {Array} Array of entity objects
		 */
		entities() {
			return objectStore.getCollection('entity').results || []
		},

		/**
		 * Get filtered entities (for future search/filter functionality)
		 * @return {Array} Filtered array of entity objects
		 */
		filteredEntities() {
			return this.entities
		},

		/**
		 * Get current page entities (now using all fetched entities since pagination is server-side)
		 * @return {Array} Current page entity objects
		 */
		paginatedEntities() {
			return this.filteredEntities
		},

		/**
		 * Get pagination info from store
		 * @return {object} Pagination information
		 */
		pagination() {
			return objectStore.getPagination('entity')
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
			return objectStore.hasMorePages('entity')
		},

		/**
		 * Check if there are previous pages
		 * @return {boolean} Has previous pages
		 */
		hasPreviousPages() {
			return objectStore.hasPreviousPages('entity')
		},
	},
	mounted() {
		// Load entities when component mounts
		this.loadEntities()
	},
	methods: {
		/**
		 * Load entities from the API
		 */
		async loadEntities() {
			try {
				await objectStore.fetchCollection('entity', { _page: 1, _limit: this.limit })
			} catch (error) {
				console.error('Error loading entities:', error)
				this.error = 'Failed to load entities'
			}
		},

		/**
		 * Select an entity and show details
		 * @param {object} entity - The entity to select
		 */
		selectEntity(entity) {
			this.selectedEntity = entity
			objectStore.setActiveObject('entity', entity)
			navigationStore.setSidebar('entity', true)
			navigationStore.setSelected('entities') // Keep the navigation state
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
					await objectStore.fetchCollection('entity', { _page: page, _limit: this.limit })
				} catch (error) {
					console.error('Error loading page:', error)
					this.error = 'Failed to load page'
				}
			}
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
		 * Format entity type to readable format
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
		 * Format date to readable format
		 * @param {string} date - Date string
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
		 * Change pagination limit
		 */
		async changeLimit() {
			try {
				await objectStore.fetchCollection('entity', { _page: 1, _limit: this.limit })
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
.entitiesContent {
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

.entitiesTable {
	width: 100%;
	border-collapse: collapse;
	background-color: var(--color-main-background);
	table-layout: auto;
}

.entitiesTable thead {
	background-color: var(--color-background-hover);
	position: sticky;
	top: 0;
	z-index: 10;
}

.entitiesTable th {
	padding: 12px 8px;
	text-align: left;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
	border-bottom: 2px solid var(--color-border);
	background-color: var(--color-background-hover);
}

.entitiesTable td {
	padding: 12px 8px;
	border-bottom: 1px solid var(--color-border-dark);
	vertical-align: middle;
}

.entitiesTable tbody tr {
	cursor: pointer;
	transition: background-color 0.2s ease;
}

.entitiesTable tbody tr:hover {
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

	.entitiesTable {
		font-size: 14px;
	}

	.entitiesTable th,
	.entitiesTable td {
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