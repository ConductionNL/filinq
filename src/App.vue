<template>
	<NcContent app-name="docudesk">
		<MainMenu />
		<Views />
		<SideBars />
		<Modals />
		<Dialogs />
	</NcContent>
</template>

<script>
import { NcContent } from '@nextcloud/vue'
import MainMenu from './navigation/MainMenu.vue'
import Modals from './modals/Modals.vue'
import Dialogs from './dialogs/Dialogs.vue'
import Views from './views/Views.vue'
import SideBars from './sidebars/SideBars.vue'
import { objectStore } from './store/store.js'

export default {
	name: 'App',
	components: {
		NcContent,
		MainMenu,
		Modals,
		Dialogs,
		Views,
		SideBars,
	},
	async mounted() {
		// Register entity object type
		await objectStore.registerObjectType('entity', 'entity', 'document')
		
		// Preload all collections when the app starts
		await objectStore.preloadCollections()
	},
}
</script>
