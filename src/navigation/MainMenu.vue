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
				:active="isActive('Correspondence')"
				:name="t('docudesk', 'Brieven & correspondentie')"
				:to="{ name: 'Correspondence' }">
				<template #icon>
					<EmailOutline :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('StandingConsents')"
				:name="t('docudesk', 'Publish always')"
				:to="{ name: 'StandingConsents' }">
				<template #icon>
					<AccountStar :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				:active="isActive('Prohibitions')"
				:name="t('docudesk', 'Publish never')"
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

import LockOutline from 'vue-material-design-icons/LockOutline.vue'
import TextBoxOutline from 'vue-material-design-icons/TextBoxOutline.vue'
// Icons
import AccountStar from 'vue-material-design-icons/AccountStar.vue'
import AlertOctagon from 'vue-material-design-icons/AlertOctagon.vue'
import PenLock from 'vue-material-design-icons/PenLock.vue'
import EmailOutline from 'vue-material-design-icons/EmailOutline.vue'
import Palette from 'vue-material-design-icons/Palette.vue'

const ACTIVE_GROUPS = {
	Anonymization: ['Anonymization'],
	Signing: ['SigningRequestList', 'SigningRequestDetail', 'SigningRequestForm', 'BulkSigningPanel', 'SignatureVerification'],
	Correspondence: ['Correspondence'],
}

export default {
	name: 'MainMenu',
	components: {
		NcAppNavigation,
		NcAppNavigationList,
		NcAppNavigationItem,
		NcAppNavigationSpacer,
		LockOutline,
		TextBoxOutline,
		AccountStar,
		AlertOctagon,
		PenLock,
		EmailOutline,
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
.app-navigation {
	--app-navigation-padding: 16px;
	--color-main-background-blur: var(--dd-glass-bg, rgba(255, 255, 255, 0.54));
	border-radius: var(--dd-radius-panel);
	box-shadow: var(--dd-shadow-panel);
	margin-right: 8px;
}

:deep(.app-navigation-entry) {
	--default-clickable-area: 48px;
	--border-radius-element: 11px;
	--color-background-hover: var(--dd-surface-hover, #efefef);
}

/* Active item: `--color-primary-element-text` drives both link colour and
   icon colour, so no per-element colour override is needed. */
:deep(.app-navigation-entry.active) {
	--color-primary-element: var(--dd-active-pill-bg, #fff);
	--color-primary-element-hover: var(--dd-active-pill-bg, #fff);
	--color-primary-element-text: var(--dd-ink);
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
