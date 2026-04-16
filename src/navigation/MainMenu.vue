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
		<template #footer>
			<NcAppNavigationSettings>
				<NcAppNavigationItem :name="t('docudesk', 'Settings')" @click="$emit('open-settings')">
					<template #icon>
						<Cog :size="20" />
					</template>
				</NcAppNavigationItem>
			</NcAppNavigationSettings>
		</template>
	</NcAppNavigation>
</template>
<script>

import {
	NcAppNavigation,
	NcAppNavigationList,
	NcAppNavigationItem,
	NcAppNavigationSettings,
} from '@nextcloud/vue'

// Icons
import Finance from 'vue-material-design-icons/Finance.vue'
import AccountCheck from 'vue-material-design-icons/AccountCheck.vue'
import ShieldLock from 'vue-material-design-icons/ShieldLock.vue'
import FileDocumentMultiple from 'vue-material-design-icons/FileDocumentMultiple.vue'
import Cog from 'vue-material-design-icons/Cog.vue'

export default {
	name: 'MainMenu',
	components: {
		NcAppNavigation,
		NcAppNavigationList,
		NcAppNavigationItem,
		NcAppNavigationSettings,
		Finance,
		AccountCheck,
		ShieldLock,
		FileDocumentMultiple,
		Cog,
	},
}
</script>
