<script setup>
import { navigationStore, objectStore } from '../../store/store.js'
</script>

<template>
	<NcAppContent>
		<template #list>
			<TemplatesList />
		</template>
		<template #default>
			<NcEmptyContent v-if="!objectStore.getActiveObject('template') || navigationStore.selected !== 'templates'"
				class="detailContainer"
				name="No Template"
				description="No template selected">
				<template #icon>
					<BriefcaseAccountOutline />
				</template>
				<template #action>
					<NcButton type="primary" @click="objectStore.setActiveObject('template', null); navigationStore.setModal('editTemplate')">
						Add Template
					</NcButton>
				</template>
			</NcEmptyContent>
			<TemplateDetails v-if="objectStore.getActiveObject('template') && navigationStore.selected === 'templates'" />
		</template>
	</NcAppContent>
</template>

<script>
/**
 * Main component for the templates view that handles displaying the list of templates
 * and their details
 */
import { NcAppContent, NcEmptyContent, NcButton } from '@nextcloud/vue'
import TemplatesList from './TemplatesList.vue'
import TemplateDetails from './TemplatesDetails.vue'
import BriefcaseAccountOutline from 'vue-material-design-icons/BriefcaseAccountOutline.vue'

export default {
	name: 'TemplatesIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcButton,
		TemplatesList,
		TemplateDetails,
		BriefcaseAccountOutline,
	},
}
</script>
