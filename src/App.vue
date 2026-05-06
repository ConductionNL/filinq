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
			<MainMenu />
			<Views />
			<SideBars />
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
import Modals from './modals/Modals.vue'
import Dialogs from './dialogs/Dialogs.vue'
import Views from './views/Views.vue'
import SideBars from './sidebars/SideBars.vue'
import { initializeStores, useSettingsStore } from './store/store.js'

export default {
	name: 'App',
	components: {
		NcContent,
		NcAppContent,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		MainMenu,
		Modals,
		Dialogs,
		Views,
		SideBars,
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
	},

	async created() {
		await initializeStores()
		this.storesReady = true
	},
}
</script>

<style scoped>
.content {
	padding: 8px;
	background-color: var(--background-color, #EAE9E6);
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
	border-radius: 20px;
	box-shadow: 0 4px 22px -3px rgba(0, 0, 0, 0.08);
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
