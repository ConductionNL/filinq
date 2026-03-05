<script setup>
import { navigationStore } from '../store/store.js'
</script>

<template>
	<!-- Main content container for all views -->
	<NcAppContent>
		<template #default>
			<Dashboard v-if="navigationStore.selected === 'dashboard'" />
			<ConsentIndex v-if="navigationStore.selected === 'consent'" />
			<ConsentDetail v-if="navigationStore.selected === 'consentDetail'" />
			<AnonymizationWidget v-if="navigationStore.selected === 'anonymization'" />
		</template>
	</NcAppContent>
</template>

<script>
import { NcAppContent } from '@nextcloud/vue'

import Dashboard from './dashboard/DashboardIndex.vue'
import ConsentIndex from './consent/ConsentIndex.vue'
import ConsentDetail from './consent/ConsentDetail.vue'
import AnonymizationWidget from './anonymization/AnonymizationWidget.vue'

export default {
	name: 'Views',
	components: {
		NcAppContent,
		Dashboard,
		ConsentIndex,
		ConsentDetail,
		AnonymizationWidget,
	},
}
</script>
