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
			<NcAppNavigationSpacer />
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
			<!-- Temporary feature: kept at the bottom, set apart by a spacer. -->
			<NcAppNavigationSpacer />
			<NcAppNavigationItem
				:active="isActive('Gallery')"
				:name="t('docudesk', 'Component Gallery')"
				:to="{ name: 'Gallery' }">
				<template #icon>
					<Palette :size="20" />
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
	NcAppNavigationSpacer,
} from '@nextcloud/vue'

import MonitorDashboard from 'vue-material-design-icons/MonitorDashboard.vue'
import AccountCheckOutline from 'vue-material-design-icons/AccountCheckOutline.vue'
import LockOutline from 'vue-material-design-icons/LockOutline.vue'
import FileDocumentMultipleOutline from 'vue-material-design-icons/FileDocumentMultipleOutline.vue'
import TextBoxOutline from 'vue-material-design-icons/TextBoxOutline.vue'
import FolderSearchOutline from 'vue-material-design-icons/FolderSearchOutline.vue'
// Icons
import AccountStar from 'vue-material-design-icons/AccountStar.vue'
import AlertOctagon from 'vue-material-design-icons/AlertOctagon.vue'
import Palette from 'vue-material-design-icons/Palette.vue'

const ACTIVE_GROUPS = {
	Consent: ['Consent', 'ConsentDetail'],
	Templates: ['Templates', 'TemplateDetail', 'TemplateNew'],
	Anonymization: ['Anonymization', 'BatchAnonymization'],
}

export default {
	name: 'MainMenu',
	components: {
		NcAppNavigation,
		NcAppNavigationList,
		NcAppNavigationItem,
		NcAppNavigationSpacer,
		MonitorDashboard,
		AccountCheckOutline,
		LockOutline,
		FileDocumentMultipleOutline,
		TextBoxOutline,
		FolderSearchOutline,
		AccountStar,
		AlertOctagon,
		Palette,
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

/* Collapse toggle (open nav): move it from its default top-right overhang to
   the bottom-left corner inside the navigation. The wrapper is a child of
   `.app-navigation` (which carries this component's scope id), so a plain
   `:deep()` matches; the doubled class lifts specificity above the library
   rule that pins it top-right via `[data-v-…]`. */
:deep(.app-navigation-toggle-wrapper.app-navigation-toggle-wrapper) {
	top: auto;
	right: auto;
	bottom: var(--app-navigation-padding);
	left: var(--app-navigation-padding);
	margin-right: 0;
}

/* Collapse toggle (closed nav): when collapsed the whole navigation slides
   left off-screen, so a left-anchored toggle would go with it and become
   unreachable. Restore NC's right-edge overhang (keeping the bottom anchor)
   so the button still peeks on-screen and can reopen the menu. The scope id
   sits on `.app-navigation` itself, so prefix with the (non-deep) close class
   and `:deep()` the descendant wrapper. */
.app-navigation--close :deep(.app-navigation-toggle-wrapper.app-navigation-toggle-wrapper) {
	left: auto;
	right: calc(0px - var(--app-navigation-padding));
	margin-right: calc(-1 * var(--default-clickable-area));
}
</style>
