<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->

<!--
  RecordLeafSidebar — the object sidebar for Filinq's custom record pages.

  CnAppRoot hoists a registry-driven CnObjectSidebar for any page that
  publishes its object into the provided `objectSidebarState`, but only when
  the app leaves its `#sidebar` slot empty. Filinq fills that slot (its legacy
  sidebars and the page sidebar component live there), so this component
  renders the same sidebar from the same state, inside the slot.

  The tabs come from the integration registry (ADR-019): one per leaf an
  installed app registered (Mail, Calendar, Contacts, Deck, Files…). The
  library registers its built-in leaves whether or not their app is enabled,
  so a leaf whose app is absent is added to the hidden tabs here
  (`hiddenLeafTabs()` in `src/services/leafAvailability.js`); the tab system
  stays the library's.

  @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
-->
<template>
	<CnObjectSidebar
		v-if="visible"
		:open="state.open === true"
		:useRegistry="true"
		:objectType="state.objectType"
		:objectId="state.objectId"
		:objectData="state.object"
		:register="state.register"
		:schema="state.schema"
		:title="state.title"
		:subtitle="state.subtitle"
		:hiddenTabs="hiddenTabs"
		:requestedTab="state.requestedTab"
		@update:open="state.open = $event" />
</template>

<script>
import { CnObjectSidebar, useIntegrationRegistry } from '@conduction/nextcloud-vue'
import { enabledApps, hiddenLeafTabs } from '../services/leafAvailability.js'

export default {
	name: 'RecordLeafSidebar',

	components: { CnObjectSidebar },

	inject: {
		objectSidebarState: { default: null },
	},

	/**
	 * The registry snapshot and the apps enabled for the user.
	 *
	 * @return {object}
	 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
	 */
	setup() {
		const { integrations } = useIntegrationRegistry()
		return { integrations, enabled: enabledApps() }
	},

	computed: {
		/**
		 * The shared sidebar state CnAppRoot provides, or an empty one.
		 *
		 * @return {object}
		 */
		state() {
			return this.objectSidebarState || {}
		},

		/**
		 * The page's hidden tabs plus every leaf whose app is absent.
		 *
		 * @return {string[]}
		 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
		 */
		hiddenTabs() {
			return hiddenLeafTabs(this.state.hiddenTabs, this.integrations || [], this.enabled)
		},

		/**
		 * Rendered only for a published record: active, with a type and an id.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
		 */
		visible() {
			return this.state.active === true && !!this.state.objectType && !!this.state.objectId
		},
	},
}
</script>
