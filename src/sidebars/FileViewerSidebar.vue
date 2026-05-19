<script setup>
import { translate as t } from '@nextcloud/l10n'
import { fileViewerStore } from '../store/store.js'
</script>

<template>
	<NcAppSidebar
		v-if="fileViewerStore.currentFile"
		:name="t('docudesk', 'Sidebar')"
		@close="onClose" />
</template>

<script>
import { NcAppSidebar } from '@nextcloud/vue'

export default {
	name: 'FileViewerSidebar',
	components: {
		NcAppSidebar,
	},
	methods: {
		/**
		 * Close handler — closes the file viewer, which hides this sidebar
		 * because it is bound to fileViewerStore.currentFile.
		 */
		onClose() {
			fileViewerStore.close()
		},
	},
}
</script>

<style scoped>
/* Mirror the chrome of MainMenu / NcAppContent: rounded card, soft shadow,
   translucent background. Lib does not expose a variable for radius/shadow,
   so override directly. */
.app-sidebar {
	--color-main-background: var(--color-white-54, rgba(255, 255, 255, 0.54));
	border-radius: 20px;
	box-shadow: 0 4px 22px -3px rgba(0, 0, 0, 0.08);
	margin-left: 8px;
}
</style>
