<template>
	<NcContent app-name="docudesk">
		<!-- OpenRegister not installed: show empty state -->
		<NcAppContent v-if="storesReady && !hasOpenRegisters" class="open-register-missing">
			<NcEmptyContent
				:name="t('docudesk', 'OpenRegister is required')"
				:description="t('docudesk', 'DocuDesk needs the OpenRegister app to store and manage data. Please install OpenRegister from the app store to get started.')">
				<template #icon>
					<img :src="appIcon" class="open-register-icon">
				</template>
				<template #action>
					<NcButton
						v-if="isAdmin"
						type="primary"
						:href="appStoreUrl">
						{{ t('docudesk', 'Install OpenRegister') }}
					</NcButton>
					<p v-else class="open-register-admin-hint">
						{{ t('docudesk', 'Ask your administrator to install the OpenRegister app.') }}
					</p>
				</template>
			</NcEmptyContent>
		</NcAppContent>

		<!-- App loaded normally -->
		<template v-else-if="storesReady && hasOpenRegisters">
			<FolderFilesNavigation v-if="inDossier" />
			<FileNavigation v-else-if="singleFileOpen" />
			<MainMenu v-else />
			<Views />
			<!-- Sidebar lives at App level (NcContent demands a direct child)
			     but is mounted strictly with the FileViewerPage host route, so
			     viewer + sidebar always appear/disappear as one unit. -->
			<FileViewerSidebar v-if="showFileViewerSidebar" />
			<Modals />
			<Dialogs />
		</template>

		<!-- Loading -->
		<NcAppContent v-else>
			<div style="display: flex; justify-content: center; align-items: center; height: 100%;">
				<NcLoadingIcon :size="64" />
			</div>
		</NcAppContent>
	</NcContent>
</template>

<script>
import { NcContent, NcAppContent, NcButton, NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import { generateUrl, imagePath } from '@nextcloud/router'
import MainMenu from './navigation/MainMenu.vue'
import FolderFilesNavigation from './navigation/FolderFilesNavigation.vue'
import FileNavigation from './navigation/FileNavigation.vue'
import Modals from './modals/Modals.vue'
import Dialogs from './dialogs/Dialogs.vue'
import Views from './views/Views.vue'
import FileViewerSidebar from './sidebars/FileViewerSidebar.vue'
import { initializeStores, useSettingsStore, myDocumentsStore, fileViewerStore } from './store/store.js'

export default {
	name: 'App',
	components: {
		NcContent,
		NcAppContent,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		MainMenu,
		FolderFilesNavigation,
		FileNavigation,
		Modals,
		Dialogs,
		Views,
		FileViewerSidebar,
	},

	data() {
		return {
			storesReady: false,
		}
	},

	computed: {
		hasOpenRegisters() {
			const settingsStore = useSettingsStore()
			return settingsStore.hasOpenRegisters
		},
		/**
		 * True when the user is browsing inside a dossier (a subfolder of /DocuDesk).
		 * Triggers the dossier-files navigation in place of the main menu.
		 *
		 * @return {boolean}
		 */
		inDossier() {
			return myDocumentsStore.currentPath !== '/DocuDesk'
		},
		/**
		 * True when a single file is open at the root of My Documents (not
		 * inside a dossier). Replaces the main menu with the minimal
		 * file navigation showing the back button and the file name.
		 *
		 * @return {boolean}
		 */
		singleFileOpen() {
			return !this.inDossier && !!fileViewerStore.currentFile
		},
		isAdmin() {
			const settingsStore = useSettingsStore()
			return settingsStore.getIsAdmin
		},
		appIcon() {
			return imagePath('docudesk', 'app-dark.svg')
		},
		appStoreUrl() {
			return generateUrl('/settings/apps/integration/openregister')
		},
		/**
		 * Sidebar lives at App level because NcContent demands a direct
		 * child, but it must only render while the file-viewer host route
		 * is active AND a file is open. This keeps the sidebar and the
		 * viewer mounted/unmounted as a single unit.
		 *
		 * @return {boolean}
		 */
		showFileViewerSidebar() {
			return this.$route?.name === 'MyDocuments' && !!fileViewerStore.currentFile
		},
	},

	watch: {
		/**
		 * Close the file viewer whenever the user navigates away from the
		 * MyDocuments host route. Without this guard the store's
		 * `currentFile` would survive a route change and the sidebar
		 * computed flag would silently flicker on the next return.
		 *
		 * @param {object} to   The route being navigated to.
		 * @param {object} from The route being left.
		 */
		$route(to, from) {
			if (from?.name === 'MyDocuments' && to?.name !== 'MyDocuments' && fileViewerStore.currentFile) {
				fileViewerStore.close()
			}
		},
	},

	async created() {
		await initializeStores()
		this.storesReady = true
	},
}
</script>

<style scoped>
/* NcContent renders the root `.content` div. A recent @nextcloud/vue update
   added `.content:not(.content--legacy)` (specificity 0,3,0) which paints a
   semi-transparent blur background, overriding our flat page background.
   The doubled `.content.content` lifts us above that (0,3,0) so our rule wins
   regardless of stylesheet order. */
.content.content:not(.content--legacy) {
	padding: 8px;
}

.open-register-icon {
	width: 64px;
	height: 64px;
	filter: var(--background-invert-if-dark);
}

.open-register-admin-hint {
	color: var(--color-text-maxcontrast);
	text-align: center;
}

/* NcAppContent main panel chrome — `--color-main-background` is honoured by
   the lib; radius and shadow have no NC variable, so override directly. */
:deep(.app-content) {
	--color-main-background: var(--color-white-54, rgba(255, 255, 255, 0.54));
	border-radius: var(--dd-radius-panel);
	box-shadow: var(--dd-shadow-panel);
}

/* NcAppNavigation defaults to a transparent background since a recent
   @nextcloud/vue update (it hardcodes `background-color: transparent`, not a
   variable). Mirror the app-content panel so the navigation keeps the same
   white-54 surface. Doubled class beats the lib's (0,2,0) selector. */
:deep(.app-navigation.app-navigation) {
	background-color: var(--color-white-54, rgba(255, 255, 255, 0.54));
}

/* Centre the NcEmptyContent when OpenRegister is not installed.
   `!important` is required because the lib sets conflicting layout rules. */
:deep(.open-register-missing) {
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	min-height: 100% !important;
}
</style>
