<!--
  SPDX-License-Identifier: EUPL-1.2
  SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>

  DocuDesk app shell. Mounts CnAppRoot with the bundled manifest and
  the customComponents registry derived from registry.js.

  The existing sidebar/modal/dialog overlays are preserved as slot
  overrides so the current UX (consent sidebar, anonymization modals,
  template dialogs) continues to work through the transition.
-->
<template>
	<CnAppRoot
		:ai-companion="true"
		:manifest="manifest"
		:custom-components="customComponents"
		:page-types="pageTypes"
		:registry="registry"
		app-id="docudesk"
		:translate="translateForApp"
		:permissions="permissions">
		<!--
		  Preserve legacy sidebar/modal/dialog overlays. These are rendered
		  outside the manifest page tree and manage their own visibility via
		  the navigation store, so they continue to work unchanged.
		-->
		<template #sidebar>
			<SideBars />
		</template>
		<template #footer>
			<Modals />
			<Dialogs />
		</template>
	</CnAppRoot>
</template>

<script>
import { translate as ncT } from '@nextcloud/l10n'
import { CnAppRoot } from '@conduction/nextcloud-vue'
import Modals from './modals/Modals.vue'
import Dialogs from './dialogs/Dialogs.vue'
import SideBars from './sidebars/SideBars.vue'

export default {
	name: 'App',

	components: {
		CnAppRoot,
		Modals,
		Dialogs,
		SideBars,
	},

	props: {
		/**
		 * Manifest object — passed from main.js bootstrap. CnAppRoot reads
		 * `manifest.dependencies` for the dependency-check phase and
		 * `manifest.menu` for the default CnAppNav.
		 */
		manifest: {
			type: Object,
			required: true,
		},
		/**
		 * Registry of consumer-injected components used by type:"custom" pages
		 * (page.component) and other CnPageRenderer overrides.
		 */
		customComponents: {
			type: Object,
			default: () => ({}),
		},
		/**
		 * Page-type registry — `{ index, detail, dashboard, settings, ... }`.
		 * Wired through to descendant CnPageRenderer instances via
		 * provide/inject.
		 */
		pageTypes: {
			type: Object,
			default: null,
		},
		/**
		 * 5-kind component registry for v2 manifests (hydra ADR-036).
		 * Map of registry key → `{ kind, component, ...metadata }`.
		 * See src/registry.js for the docudesk entries.
		 */
		registry: {
			type: Object,
			default: () => ({}),
		},
	},

	computed: {
		/**
		 * Current user's Nextcloud permission set, passed to the app shell.
		 *
		 * @spec openspec/specs/dashboard/spec.md#requirement-navigation-menu-req-dash-03
		 */
		permissions() {
			return window.OC?.currentUser?.permissions ?? []
		},
	},

	methods: {
		/**
		 * Translate function passed down to CnAppRoot / CnAppNav /
		 * CnPageRenderer. Closes over the Nextcloud `translate` import
		 * so the lib never has to know our app id.
		 *
		 * @param {string} key Translation key.
		 * @return {string} Translated string (or the key on miss).
		 *
		 * @spec exclude Thin i18n wrapper around @nextcloud/l10n translate; no domain behavior.
		 */
		translateForApp(key) {
			return ncT('docudesk', key)
		},
	},
}
</script>

<style scoped>
.open-register-icon {
	width: 64px;
	height: 64px;
	filter: var(--background-invert-if-dark);
}

.open-register-admin-hint {
	color: var(--color-text-maxcontrast);
	text-align: center;
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
