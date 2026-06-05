<template>
	<NcAppNavigation>
		<NcAppNavigationList>
			<NcAppNavigationItem
				:active="isActive('Anonymization')"
				:name="t('docudesk', 'Anonymization')"
				:to="{ name: 'Anonymization' }">
				<template #icon>
					<LockOutline :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('MyDocuments')"
				:name="t('docudesk', 'My Documents')"
				:to="{ name: 'MyDocuments' }">
				<template #icon>
					<TextBoxOutline :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('Settings')"
				:name="t('docudesk', 'Settings')"
				:to="{ name: 'Settings' }">
				<template #icon>
					<TuneVertical :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('AnonymizationPoc')"
				:name="t('docudesk', 'Anonymisation PoC')"
				:to="{ name: 'AnonymizationPoc' }">
				<template #icon>
					<TestTube :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('Dashboard')"
				:name="t('docudesk', 'Dashboard')"
				:to="{ name: 'Dashboard' }">
				<template #icon>
					<MonitorDashboard :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('FolderAnonymization')"
				:name="t('docudesk', 'Folder Analysis')"
				:to="{ name: 'FolderAnonymization' }">
				<template #icon>
					<FolderSearchOutline :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('Consent')"
				:name="t('docudesk', 'Consent Management')"
				:to="{ name: 'Consent' }">
				<template #icon>
					<AccountCheckOutline :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('Templates')"
				:name="t('docudesk', 'Templates')"
				:to="{ name: 'Templates' }">
				<template #icon>
					<FileDocumentMultipleOutline :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('StandingConsents')"
				:name="t('docudesk', 'Standing Consents')"
				:to="{ name: 'StandingConsents' }">
				<template #icon>
					<AccountStar :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('Prohibitions')"
				:name="t('docudesk', 'Prohibitions')"
				:to="{ name: 'Prohibitions' }">
				<template #icon>
					<AlertOctagon :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('Signing')"
				:name="t('docudesk', 'Digital Signing')"
				:to="{ name: 'SigningRequestList' }">
				<template #icon>
					<PenLock :size="20" />
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

import MonitorDashboard from 'vue-material-design-icons/MonitorDashboard.vue'
import AccountCheckOutline from 'vue-material-design-icons/AccountCheckOutline.vue'
import LockOutline from 'vue-material-design-icons/LockOutline.vue'
import FileDocumentMultipleOutline from 'vue-material-design-icons/FileDocumentMultipleOutline.vue'
import TextBoxOutline from 'vue-material-design-icons/TextBoxOutline.vue'
import FolderSearchOutline from 'vue-material-design-icons/FolderSearchOutline.vue'
import TuneVertical from 'vue-material-design-icons/TuneVertical.vue'
// Icons
import AccountStar from 'vue-material-design-icons/AccountStar.vue'
import AlertOctagon from 'vue-material-design-icons/AlertOctagon.vue'
import TestTube from 'vue-material-design-icons/TestTube.vue'
import PenLock from 'vue-material-design-icons/PenLock.vue'

const ACTIVE_GROUPS = {
	Consent: ['Consent', 'ConsentDetail'],
	Templates: ['Templates', 'TemplateDetail', 'TemplateNew'],
	Anonymization: ['Anonymization', 'BatchAnonymization'],
	AnonymizationPoc: ['AnonymizationPoc'],
	Signing: ['SigningRequestList', 'SigningRequestDetail', 'SigningRequestForm', 'BulkSigningPanel', 'SignatureVerification'],
}

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
		AccountStar,
		AlertOctagon,
		TestTube,
		PenLock,
	},
	methods: {
		/**
		 * True when the current route matches the menu entry (or any of its grouped routes).
		 *
		 * @param {string} name Route name as registered in the router.
		 * @return {boolean}
		 */
		isActive(name) {
			const group = ACTIVE_GROUPS[name] || [name]
			return group.includes(this.$route.name)
		},
	},
}
</script>

<style scoped>
/* NcAppNavigation overrides — prefer NC CSS variables, fall back to direct
   property overrides only where the lib does not expose a variable. */
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

/* Active item: `--color-primary-element-text` drives both link colour and
   icon colour, so no per-element colour override is needed. */
:deep(.app-navigation-entry.active) {
	--color-primary-element: #fff;
	--color-primary-element-hover: #fff;
	--color-primary-element-text: var(--color-main-text);
	box-shadow: var(--dd-shadow-popout);
}
</style>
