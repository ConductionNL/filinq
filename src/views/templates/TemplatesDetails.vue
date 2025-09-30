<script setup>
import { objectStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<div class="detailContainer">
		<div class="head">
			<h1 class="h1">
				{{ template.name }}
			</h1>

			<NcActions :disabled="objectStore.isLoading('template')"
				:primary="true"
				:inline="1"
				:menu-name="objectStore.isLoading('template') ? 'Loading...' : 'Actions'">
				<template #icon>
					<span>
						<NcLoadingIcon v-if="objectStore.isLoading('template')"
							:size="20"
							appearance="dark" />
						<DotsHorizontal v-if="!objectStore.isLoading('template')" :size="20" />
					</span>
				</template>
				<NcActionButton @click="navigationStore.setModal('editTemplate')">
					<template #icon>
						<Pencil :size="20" />
					</template>
					Edit Template
				</NcActionButton>
				<NcActionButton @click="navigationStore.setModal('addSkillToTemplate')">
					<template #icon>
						<FileOutline :size="20" />
					</template>
					Edit Skills
				</NcActionButton>
				<NcActionButton @click="navigationStore.setModal('addItemToTemplate')">
					<template #icon>
						<AccountPlus :size="20" />
					</template>
					Edit Items
				</NcActionButton>
				<NcActionButton @click="navigationStore.setModal('addConditionToTemplate')">
					<template #icon>
						<EmoticonSickOutline :size="20" />
					</template>
					Edit Conditions
				</NcActionButton>
				<NcActionButton @click="navigationStore.setModal('addEventToTemplate')">
					<template #icon>
						<CalendarPlus :size="20" />
					</template>
					Edit Events
				</NcActionButton>
				<NcActionButton @click="downloadTemplatePdf()">
					<template #icon>
						<Download :size="20" />
					</template>
					Download as PDF
				</NcActionButton>
				<NcActionButton @click="navigationStore.setDialog('deleteObject', { objectType: 'template', dialogTitle: 'Template' })">
					<template #icon>
						<TrashCanOutline :size="20" />
					</template>
					Delete
				</NcActionButton>
			</NcActions>
		</div>

		<div class="container">
			<NcNoteCard v-if="template.notice" type="info">
				{{ template.notice }}
			</NcNoteCard>

			<div class="detailGrid">
				<div>
					<b>Summary:</b>
					<span>{{ template.summary }}</span>
				</div>
				<div>
					<b>Description:</b>
					<span>{{ template.description || '-' }}</span>
				</div>
			</div>

			<div class="tabContainer">
				<BTabs content-class="mt-3" justified>
					<BTab title="Skills" active>
						<div v-if="template.skills && template.skills.length > 0">
							<NcListItem v-for="(skill, i) in template.skills"
								:key="`${skill}${i}`"
								:name="skill.name || 'Unnamed Skill'"
								:force-display-actions="true">
								<template #icon>
									<Sword :size="44" />
								</template>
								<template #subname>
									{{ skill.description || 'No description available' }}
								</template>
								<template #actions>
									<NcActionButton @click="navigationStore.setModal('editSkill', skill)">
										<template #icon>
											<Pencil :size="20" />
										</template>
										Edit Skill
									</NcActionButton>
									<NcActionButton @click="navigationStore.setDialog('deleteSkill', skill)">
										<template #icon>
											<TrashCanOutline :size="20" />
										</template>
										Delete Skill
									</NcActionButton>
								</template>
							</NcListItem>
						</div>
						<div v-else class="empty-state">
							<p>No skills defined for this template.</p>
							<NcButton type="primary" @click="navigationStore.setModal('addSkillToTemplate')">
								Add Skill
							</NcButton>
						</div>
					</BTab>

					<BTab title="Items">
						<div v-if="template.items && template.items.length > 0">
							<NcListItem v-for="(item, i) in template.items"
								:key="`${item}${i}`"
								:name="item.name || 'Unnamed Item'"
								:force-display-actions="true">
								<template #icon>
									<ShieldSwordOutline :size="44" />
								</template>
								<template #subname>
									{{ item.description || 'No description available' }}
								</template>
								<template #actions>
									<NcActionButton @click="navigationStore.setModal('editItem', item)">
										<template #icon>
											<Pencil :size="20" />
										</template>
										Edit Item
									</NcActionButton>
									<NcActionButton @click="navigationStore.setDialog('deleteItem', item)">
										<template #icon>
											<TrashCanOutline :size="20" />
										</template>
										Delete Item
									</NcActionButton>
								</template>
							</NcListItem>
						</div>
						<div v-else class="empty-state">
							<p>No items defined for this template.</p>
							<NcButton type="primary" @click="navigationStore.setModal('addItemToTemplate')">
								Add Item
							</NcButton>
						</div>
					</BTab>

					<BTab title="Conditions">
						<div v-if="template.conditions && template.conditions.length > 0">
							<NcListItem v-for="(condition, i) in template.conditions"
								:key="`${condition}${i}`"
								:name="condition.name || 'Unnamed Condition'"
								:force-display-actions="true">
								<template #icon>
									<EmoticonSickOutline :size="44" />
								</template>
								<template #subname>
									{{ condition.description || 'No description available' }}
								</template>
								<template #actions>
									<NcActionButton @click="navigationStore.setModal('editCondition', condition)">
										<template #icon>
											<Pencil :size="20" />
										</template>
										Edit Condition
									</NcActionButton>
									<NcActionButton @click="navigationStore.setDialog('deleteCondition', condition)">
										<template #icon>
											<TrashCanOutline :size="20" />
										</template>
										Delete Condition
									</NcActionButton>
								</template>
							</NcListItem>
						</div>
						<div v-else class="empty-state">
							<p>No conditions defined for this template.</p>
							<NcButton type="primary" @click="navigationStore.setModal('addConditionToTemplate')">
								Add Condition
							</NcButton>
						</div>
					</BTab>

					<BTab title="Events">
						<div v-if="template.events && template.events.length > 0">
							<NcListItem v-for="(event, i) in template.events"
								:key="`${event}${i}`"
								:name="event.name || 'Unnamed Event'"
								:force-display-actions="true">
								<template #icon>
									<CalendarMonthOutline :size="44" />
								</template>
								<template #subname>
									{{ event.description || 'No description available' }}
								</template>
								<template #actions>
									<NcActionButton @click="navigationStore.setModal('editEvent', event)">
										<template #icon>
											<Pencil :size="20" />
										</template>
										Edit Event
									</NcActionButton>
									<NcActionButton @click="navigationStore.setDialog('deleteEvent', event)">
										<template #icon>
											<TrashCanOutline :size="20" />
										</template>
										Delete Event
									</NcActionButton>
								</template>
							</NcListItem>
						</div>
						<div v-else class="empty-state">
							<p>No events defined for this template.</p>
							<NcButton type="primary" @click="navigationStore.setModal('addEventToTemplate')">
								Add Event
							</NcButton>
						</div>
					</BTab>
				</BTabs>
			</div>
		</div>
	</div>
</template>

<script>
/**
 * Component for displaying and managing template details
 * Includes functionality for editing templates, managing template properties,
 * skills, items, conditions, events and downloading templates
 *
 * @package
 * @author Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (c) 2024 Conduction B.V.
 * @license EUPL-1.2
 * @version 1.0.0
 */
import { BTabs, BTab } from 'bootstrap-vue'
import { NcActions, NcActionButton, NcListItem, NcNoteCard, NcButton, NcLoadingIcon } from '@nextcloud/vue'

// Icons
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import AccountPlus from 'vue-material-design-icons/AccountPlus.vue'
import CalendarPlus from 'vue-material-design-icons/CalendarPlus.vue'
import FileOutline from 'vue-material-design-icons/FileOutline.vue'
import TrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import Sword from 'vue-material-design-icons/Sword.vue'
import EmoticonSickOutline from 'vue-material-design-icons/EmoticonSickOutline.vue'
import CalendarMonthOutline from 'vue-material-design-icons/CalendarMonthOutline.vue'
import ShieldSwordOutline from 'vue-material-design-icons/ShieldSwordOutline.vue'
import Download from 'vue-material-design-icons/Download.vue'

export default {
	name: 'TemplateDetails',
	components: {
		// Components
		NcActions,
		NcActionButton,
		NcListItem,
		NcNoteCard,
		NcButton,
		NcLoadingIcon,
		BTabs,
		BTab,
		// Icons
		DotsHorizontal,
		Pencil,
		AccountPlus,
		CalendarPlus,
		FileOutline,
		TrashCanOutline,
		Sword,
		EmoticonSickOutline,
		CalendarMonthOutline,
		ShieldSwordOutline,
		Download,
	},
	computed: {
		template() {
			return objectStore.getActiveObject('template')
		},
	},
	methods: {
		/**
		 * Download the template as PDF
		 */
		downloadTemplatePdf() {
			const templateId = this.template.id
			fetch(`templates/${templateId}/download`)
				.then(response => {
					if (!response.ok) {
						throw new Error('Network response was not ok')
					}
					return response.blob()
				})
				.then(blob => {
					const link = document.createElement('a')
					link.href = window.URL.createObjectURL(blob)
					link.download = `${this.template.name}_template_sheet.pdf`
					link.click()
					window.URL.revokeObjectURL(link.href)
				})
				.catch(error => {
					console.error('Error downloading PDF:', error)
				})
		},
	},
}
</script>

<style>
h4 {
  font-weight: bold;
}

.head {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16px;
}

.h1 {
  display: block !important;
  font-size: 2em !important;
  margin-block-start: 0.67em !important;
  margin-block-end: 0.67em !important;
  margin-inline-start: 0px !important;
  margin-inline-end: 0px !important;
  font-weight: bold !important;
  unicode-bidi: isolate !important;
}

.container {
	padding: 20px;
}

.detailGrid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 16px;
	margin: 20px 0;
}

.detailGrid > div {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.detailGrid b {
	color: var(--color-text-maxcontrast);
}

.tabContainer {
	margin-top: 20px;
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
