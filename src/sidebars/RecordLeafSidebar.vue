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
  installed app registered (Mail, Calendar, Contacts, Deck, Files…). A leaf
  whose app is absent is simply not registered, so it is not shown — no
  per-leaf check here, and no tab system of Filinq's own.

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
		:hiddenTabs="state.hiddenTabs || []"
		:requestedTab="state.requestedTab"
		@update:open="state.open = $event" />
</template>

<script>
import { CnObjectSidebar } from '@conduction/nextcloud-vue'

export default {
	name: 'RecordLeafSidebar',

	components: { CnObjectSidebar },

	inject: {
		objectSidebarState: { default: null },
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
