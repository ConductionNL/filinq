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
			<NcAppNavigationItem :active="navigationStore.selected === 'anonymization'" :name="t('docudesk', 'Anonymization')" @click="navigationStore.setSelected('anonymization')">
				<template #icon>
					<ShieldLock :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'anonymizationPoc'" :name="t('docudesk', 'Anonymisation PoC')" @click="navigationStore.setSelected('anonymizationPoc')">
				<template #icon>
					<TestTube :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'folderAnonymization'" :name="t('docudesk', 'Folder Analysis')" @click="navigationStore.setSelected('folderAnonymization')">
				<template #icon>
					<FolderSearch :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'consent' || navigationStore.selected === 'consentDetail'" :name="t('docudesk', 'Consent Workflow')" @click="navigationStore.setSelected('consent')">
				<template #icon>
					<AccountCheck :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'standingConsents'" :name="t('docudesk', 'Standing Consents')" @click="navigationStore.setSelected('standingConsents')">
				<template #icon>
					<AccountStar :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'prohibitions'" :name="t('docudesk', 'Prohibitions')" @click="navigationStore.setSelected('prohibitions')">
				<template #icon>
					<AlertOctagon :size="20" />
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
import AccountStar from 'vue-material-design-icons/AccountStar.vue'
import AlertOctagon from 'vue-material-design-icons/AlertOctagon.vue'
import ShieldLock from 'vue-material-design-icons/ShieldLock.vue'
import FileDocumentMultiple from 'vue-material-design-icons/FileDocumentMultiple.vue'
import FolderSearch from 'vue-material-design-icons/FolderSearch.vue'
import TestTube from 'vue-material-design-icons/TestTube.vue'

export default {
	name: 'MainMenu',
	components: {
		NcAppNavigation,
		NcAppNavigationList,
		NcAppNavigationItem,
		Finance,
		AccountCheck,
		AccountStar,
		AlertOctagon,
		ShieldLock,
		FileDocumentMultiple,
		FolderSearch,
		TestTube,
	},
}
</script>
