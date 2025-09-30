<script setup>
import { objectStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcAppContentList>
		<ul>
			<div class="listHeader">
				<NcTextField
					:value="objectStore.getSearchTerm('template')"
					:show-trailing-button="objectStore.getSearchTerm('template') !== ''"
					label="Search"
					class="searchField"
					trailing-button-icon="close"
					@update:value="(value) => objectStore.setSearchTerm('template', value)"
					@trailing-button-click="objectStore.clearSearch('template')">
					<Magnify :size="20" />
				</NcTextField>
				<NcActions>
					<NcActionButton @click="objectStore.fetchCollection('template')">
						<template #icon>
							<Refresh :size="20" />
						</template>
						Refresh
					</NcActionButton>
					<NcActionButton @click="objectStore.clearActiveObject('template'); navigationStore.setModal('editTemplate')">
						<template #icon>
							<Plus :size="20" />
						</template>
						Add Template
					</NcActionButton>
				</NcActions>
			</div>

			<div v-if="!objectStore.isLoading('template')">
				<NcListItem v-for="(template, i) in objectStore.getCollection('template').results"
					:key="`${template}${i}`"
					:name="template?.name || 'Unnamed Template'"
					:force-display-actions="true"
					:active="objectStore.getActiveObject('template')?.id === template?.id"
					:details="template.approved === 'approved' ? 'Approved': 'Not approved'"
					:counter-number="template?.skills?.length || 0"
					@click="handleTemplateSelect(template)">
					<template #icon>
						<BriefcaseAccountOutline :class="objectStore.getActiveObject('template')?.id === template?.id && 'selectedIcon'"
							disable-menu
							:size="44" />
					</template>
					<template #subname>
						{{ template?.summary || 'No summary available' }}
					</template>
					<template #actions>
						<NcActionButton @click="objectStore.setActiveObject('template', template); navigationStore.setModal('editTemplate')">
							<template #icon>
								<Pencil :size="20" />
							</template>
							Edit
						</NcActionButton>
						<NcActionButton @click="objectStore.setActiveObject('template', template); navigationStore.setDialog('deleteObject', { objectType: 'template', dialogTitle: 'Template' })">
							<template #icon>
								<TrashCanOutline :size="20" />
							</template>
							Delete
						</NcActionButton>
					</template>
				</NcListItem>
			</div>
		</ul>

		<NcLoadingIcon v-if="objectStore.isLoading('template')"
			class="loadingIcon"
			:size="64"
			appearance="dark"
			name="Loading templates" />

		<div v-if="!objectStore.getCollection('template').results.length" class="empty-state">
			<p>No templates defined yet.</p>
			<NcButton type="primary" @click="objectStore.clearActiveObject('template'); navigationStore.setModal('editTemplate')">
				Add Template
			</NcButton>
		</div>
	</NcAppContentList>
</template>

<script>
/**
 * Component for displaying and managing the list of templates
 *
 * @package
 * @author Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (c) 2024 Conduction B.V.
 * @license EUPL-1.2
 * @version 1.0.0
 */
// Components
import { NcListItem, NcActions, NcActionButton, NcAppContentList, NcTextField, NcLoadingIcon, NcButton } from '@nextcloud/vue'

// Icons
import Magnify from 'vue-material-design-icons/Magnify.vue'
import BriefcaseAccountOutline from 'vue-material-design-icons/BriefcaseAccountOutline.vue'
import Refresh from 'vue-material-design-icons/Refresh.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import TrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'

export default {
	name: 'TemplatesList',
	components: {
		// Components
		NcListItem,
		NcActions,
		NcActionButton,
		NcAppContentList,
		NcTextField,
		NcLoadingIcon,
		NcButton,
		// Icons
		BriefcaseAccountOutline,
		Magnify,
		Refresh,
		Plus,
		Pencil,
		TrashCanOutline,
	},
	mounted() {
		objectStore.fetchCollection('template')
	},
	methods: {
		/**
		 * Handle template selection
		 * @param {object} template - The selected template object
		 */
		async handleTemplateSelect(template) {
			// Set the selected template in the store
			objectStore.setActiveObject('template', template)
		},
	},
}
</script>

<style>
.listHeader {
    position: sticky;
    top: 0;
    z-index: 1000;
    background-color: var(--color-main-background);
    border-bottom: 1px solid var(--color-border);
    display: flex;
    flex-direction: row;
    align-items: center;
}

.searchField {
    padding-inline-start: 65px;
    padding-inline-end: 20px;
    margin-block-end: 6px;
}

.selectedIcon>svg {
    fill: white;
}

.loadingIcon {
    margin-block-start: var(--OC-margin-20);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    text-align: center;
    color: var(--color-text-maxcontrast);
    gap: 16px;
}
</style>
