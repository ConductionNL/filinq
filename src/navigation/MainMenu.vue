<script setup>
import { translate as t } from '@nextcloud/l10n'
import { navigationStore } from '../store/store.js'
</script>

<template>
	<NcAppNavigation>
		<NcAppNavigationList>
			<NcAppNavigationItem :active="navigationStore.selected === 'anonymization'" :name="t('docudesk', 'Anonymization')" @click="navigationStore.setSelected('anonymization')">
				<template #icon>
					<LockOutline :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'myDocuments'" :name="t('docudesk', 'My Documents')" @click="navigationStore.setSelected('myDocuments')">
				<template #icon>
					<TextBoxOutline :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'settings'" :name="t('docudesk', 'Settings')" @click="navigationStore.setSelected('settings')">
				<template #icon>
					<TuneVertical :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'dashboard'" :name="t('docudesk', 'Dashboard')" @click="navigationStore.setSelected('dashboard')">
				<template #icon>
					<MonitorDashboard :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'folderAnonymization'" :name="t('docudesk', 'Folder Analysis')" @click="navigationStore.setSelected('folderAnonymization')">
				<template #icon>
					<FolderSearchOutline :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'consent' || navigationStore.selected === 'consentDetail'" :name="t('docudesk', 'Consent Management')" @click="navigationStore.setSelected('consent')">
				<template #icon>
					<AccountCheckOutline :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'templates' || navigationStore.selected === 'templateDetail'" :name="t('docudesk', 'Templates')" @click="navigationStore.setSelected('templates')">
				<template #icon>
					<FileDocumentMultipleOutline :size="24" />
				</template>
			</NcAppNavigationItem>
		</NcAppNavigationList>
	</NcAppNavigation>
</template>
<script>

import {
	NcAppNavigation,
	NcAppNavigationList,
	NcAppNavigationItem,
} from '@nextcloud/vue'

// Icons
import MonitorDashboard from 'vue-material-design-icons/MonitorDashboard.vue'
import AccountCheckOutline from 'vue-material-design-icons/AccountCheckOutline.vue'
import LockOutline from 'vue-material-design-icons/LockOutline.vue'
import FileDocumentMultipleOutline from 'vue-material-design-icons/FileDocumentMultipleOutline.vue'
import TextBoxOutline from 'vue-material-design-icons/TextBoxOutline.vue'
import FolderSearchOutline from 'vue-material-design-icons/FolderSearchOutline.vue'
import TuneVertical from 'vue-material-design-icons/TuneVertical.vue'

export default {
	name: 'MainMenu',
	components: {
		NcAppNavigation,
		NcAppNavigationList,
		NcAppNavigationItem,
		MonitorDashboard,
		AccountCheckOutline,
		LockOutline,
		FileDocumentMultipleOutline,
		TextBoxOutline,
		FolderSearchOutline,
		TuneVertical,
	},
}
</script>

<style scoped>
/* NcAppNavigation overrides — prefer NC CSS variables, fall back to direct
   property overrides only where the lib does not expose a variable. */
.app-navigation {
	--app-navigation-padding: 16px;
	--color-main-background-blur: var(--color-white-54, rgba(255, 255, 255, 0.54));
	border-radius: 20px;
	box-shadow: 0 4px 22px -3px rgba(0, 0, 0, 0.08);
	margin-right: 8px;
}

:deep(.app-navigation-entry) {
	--default-clickable-area: 48px;
	--border-radius-element: 11px;
	--color-background-hover: #efefef;
}

/* Active item: `--color-primary-element-text` drives both link colour and
   icon colour, so no per-element colour override is needed. */
:deep(.app-navigation-entry.active) {
	--color-primary-element: #fff;
	--color-primary-element-hover: #fff;
	--color-primary-element-text: var(--color-main-text);
	box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.12), 0 0 2px 0 rgba(0, 0, 0, 0.24);
}
</style>
