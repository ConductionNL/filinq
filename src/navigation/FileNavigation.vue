<template>
	<NcAppNavigation>
		<template #list>
			<NcAppNavigationItem
				:name="t('docudesk', 'Back to menu')"
				@click.prevent="onBack">
				<template #icon>
					<ArrowLeft :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationCaption :name="fileName" />
		</template>
	</NcAppNavigation>
</template>

<script>
import {
	NcAppNavigation,
	NcAppNavigationItem,
	NcAppNavigationCaption,
} from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'

import ArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'

import { fileViewerStore } from '../store/store.js'

export default {
	name: 'FileNavigation',
	components: {
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationCaption,
		ArrowLeft,
	},
	computed: {
		/**
		 * Name of the currently open file, shown as the navigation caption.
		 *
		 * @return {string}
		 */
		fileName() {
			return fileViewerStore.currentFile?.fileName || ''
		},
	},
	methods: {
		t,
		/**
		 * Leave the single-file view: closing the viewer clears `currentFile`,
		 * which restores the main menu in App.vue.
		 */
		onBack() {
			fileViewerStore.close()
		},
	},
}
</script>

<style scoped>
.app-navigation {
	--app-navigation-padding: 16px;
	--color-main-background-blur: var(--color-white-54, rgba(255, 255, 255, 0.54));
	border-radius: var(--dd-radius-panel);
	box-shadow: var(--dd-shadow-panel);
	margin-right: 8px;
}

:deep(.app-navigation-entry) {
	--default-clickable-area: 48px;
	--border-radius-element: 11px;
	--color-background-hover: #efefef;
}
</style>
