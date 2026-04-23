<script setup>
import { translate as t } from '@nextcloud/l10n'
import { navigationStore } from '../store/store.js'
</script>

<template>
	<NcAppNavigation>
		<NcAppNavigationList>
			<NcAppNavigationItem :active="navigationStore.selected === 'dashboard'" :name="t('docudesk', 'Dashboard')" @click="navigationStore.setSelected('dashboard')">
				<template #icon>
					<Finance :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'myDocuments'" :name="t('docudesk', 'My Documents')" @click="navigationStore.setSelected('myDocuments')">
				<template #icon>
					<FileDocumentCheck :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'anonymization'" :name="t('docudesk', 'Anonymization')" @click="navigationStore.setSelected('anonymization')">
				<template #icon>
					<ShieldLock :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'folderAnonymization'" :name="t('docudesk', 'Folder Analysis')" @click="navigationStore.setSelected('folderAnonymization')">
				<template #icon>
					<FolderSearch :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'consent' || navigationStore.selected === 'consentDetail'" :name="t('docudesk', 'Consent Management')" @click="navigationStore.setSelected('consent')">
				<template #icon>
					<AccountCheck :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'templates' || navigationStore.selected === 'templateDetail'" :name="t('docudesk', 'Templates')" @click="navigationStore.setSelected('templates')">
				<template #icon>
					<FileDocumentMultiple :size="20" />
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
import Finance from 'vue-material-design-icons/Finance.vue'
import AccountCheck from 'vue-material-design-icons/AccountCheck.vue'
import ShieldLock from 'vue-material-design-icons/ShieldLock.vue'
import FileDocumentMultiple from 'vue-material-design-icons/FileDocumentMultiple.vue'
import FileDocumentCheck from 'vue-material-design-icons/FileDocumentCheck.vue'
import FolderSearch from 'vue-material-design-icons/FolderSearch.vue'

export default {
	name: 'MainMenu',
	components: {
		NcAppNavigation,
		NcAppNavigationList,
		NcAppNavigationItem,
		Finance,
		AccountCheck,
		ShieldLock,
		FileDocumentMultiple,
		FileDocumentCheck,
		FolderSearch,
	},
}
</script>

<style>
.app-navigation {
	--app-navigation-padding: 16px;
	border-radius: 20px !important;
	background: var(--color-white-54, rgba(255, 255, 255, 0.54)) !important;
	box-shadow: 0 4px 22px -3px rgba(0, 0, 0, 0.08) !important;
	margin-right: 8px !important;
}

.app-navigation-entry-link {
	border-radius: 11px !important;
	padding-block: 12px !important;
	padding-inline: 12px 36px !important;
}

.app-navigation-entry {
	--color-primary-element-hover: #efefef;
}

.app-navigation-entry.active {
	--color-primary-element: #fff;
}

.app-navigation-entry.active .app-navigation-entry-link,
.app-navigation-entry.active .app-navigation-entry__name {
	color: #000 !important;
}
</style>
