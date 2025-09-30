/**
 * SideBars component that manages reports and entities sidebars
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
	<div class="sidebars-container">
		<!-- Report-related sidebars -->
		<ReportsSideBar v-if="navigationStore.sidebarState.reports" />
		<ReportSideBar v-if="navigationStore.sidebarState.report" />
		
		<!-- Entity-related sidebars -->
		<EntitiesSideBar v-if="navigationStore.sidebarState.entities" />
		<EntitySideBar v-if="navigationStore.sidebarState.entity" />
	</div>
</template>

<script>
import { navigationStore } from '../store/store.js'
import ReportsSideBar from './reports/ReportsSideBar.vue'
import ReportSideBar from './reports/ReportSideBar.vue'
import EntitiesSideBar from './entities/EntitiesSideBar.vue'
import EntitySideBar from './entities/EntitySideBar.vue'

export default {
	name: 'SideBars',
	components: {
		ReportsSideBar,
		ReportSideBar,
		EntitiesSideBar,
		EntitySideBar,
	},
	data() {
		return {
			navigationStore,
		}
	},
}
</script>

<style scoped>
.sidebars-container {
	position: relative;
	z-index: 1000;
}
</style>
