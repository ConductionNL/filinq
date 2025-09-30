<script setup>
import { navigationStore } from '../store/store.js'
</script>

<template>
	<!-- Main content container for all views -->
	<NcAppContent>
		<template #default>
			<Dashboard v-if="navigationStore.selected === 'dashboard'" />
			<ReportsIndex v-if="navigationStore.selected === 'reports'" />
			<EntitiesIndex v-if="navigationStore.selected === 'entities'" />
			<TemplatesIndex v-if="navigationStore.selected === 'templates'" />
		</template>
	</NcAppContent>
</template>

<script>
/**
 * Main view component that handles routing between different views based on navigation selection
 * Uses the navigationStore to determine which view to display
 */
import { NcAppContent } from '@nextcloud/vue'

// View components
import Dashboard from './dashboard/DashboardIndex.vue'
import ReportsIndex from './reports/ReportsIndex.vue'
import EntitiesIndex from './entities/EntitiesIndex.vue'
import TemplatesIndex from './templates/TemplatesIndex.vue'

export default {
	name: 'Views',
	components: {
		NcAppContent,
		Dashboard,
		ReportsIndex,
		EntitiesIndex,
		TemplatesIndex,
	},
}
</script>
