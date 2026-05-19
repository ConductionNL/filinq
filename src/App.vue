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
		:manifest="manifest"
		:custom-components="customComponents"
		:page-types="pageTypes"
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
	},

	computed: {
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
		 */
		translateForApp(key) {
			return ncT('docudesk', key)
		},
	},
}
</script>
