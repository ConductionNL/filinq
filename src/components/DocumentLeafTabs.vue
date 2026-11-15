<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V.
  - SPDX-License-Identifier: EUPL-1.2
  -->

<script>
import { CnIntegrationTab, useIntegrationRegistry } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'
import { visibleLeafTabs } from '../services/documentLeafTabs.js'

/**
 * The contacts, activity and shares leaves, on one document record.
 *
 * 🔴 THE TABS ARE THE REGISTRY'S, NOT THIS APP'S (ADR-019 / ADR-022). Each leaf
 * renders through `resolveTab(id)` — the component the leaf itself registered —
 * and falls back to the library's own `CnIntegrationTab` when a descriptor
 * ships no tab of its own. Filinq authors no per-document tab system: writing
 * one would mean this app re-implements contacts, activity and shares, three
 * capabilities it does not own, and they would drift the moment a leaf changed.
 *
 * 🔴 `resolveTab` RESOLVES IN THE RENDERING BUNDLE, WHICH IS WHY IT IS CALLED
 * HERE AND NOT STORED. A descriptor's component object comes from whichever
 * bundle registered it; the library's composable re-resolves library-owned ids
 * against its own component table so the tab renders under one Vue runtime.
 * Caching the component in this app's data would reintroduce exactly the
 * dual-runtime trap that resolution exists to avoid.
 *
 * 🔑 THE REGISTRY IS READ, NEVER POPULATED HERE. OpenRegister's bootstrap
 * installs the shared registry and every leaf registers into it; this component
 * only asks what is enabled. So a leaf absent from this instance is absent from
 * the list, with no availability check of Filinq's own to keep in step.
 *
 * @spec openspec/changes/document-detail-leaf-widgets/specs/document-register/spec.md
 */
export default {
	name: 'DocumentLeafTabs',

	components: { CnIntegrationTab },

	props: {
		/** OpenRegister register slug the document record lives in. */
		register: { type: String, required: true },
		/** OpenRegister schema slug the document record lives in. */
		schema: { type: String, required: true },
		/**
		 * The document's OpenRegister object id.
		 *
		 * Empty when this document has no record yet, which hides the section:
		 * see documentLeafTabs.js for why empty tabs are worse than none.
		 */
		objectId: { type: String, default: '' },
	},

	/**
	 * The leaf tabs to render, recomputed as the registry changes.
	 *
	 * @param {object} props This component's props.
	 *
	 * @return {object} The bindings the template reads.
	 *
	 * @spec openspec/changes/document-detail-leaf-widgets/specs/document-register/spec.md
	 */
	setup(props) {
		const { integrations, resolveTab } = useIntegrationRegistry()

		const tabs = computed(() =>
			visibleLeafTabs(integrations.value, { objectId: props.objectId }),
		)

		return { tabs, resolveTab, t }
	},

	methods: {
		/**
		 * The component a leaf renders its tab with.
		 *
		 * @param {string} id The integration id.
		 *
		 * @return {?object} The leaf's own tab component, or null for the generic host.
		 *
		 * @spec openspec/changes/document-detail-leaf-widgets/specs/document-register/spec.md
		 */
		tabComponent(id) {
			return this.resolveTab(id) || null
		},
	},
}
</script>

<template>
	<section v-if="tabs.length > 0" class="document-leaf-tabs">
		<h3 class="document-leaf-tabs__heading">
			{{ t('filinq', 'Linked elsewhere') }}
		</h3>
		<div
			v-for="tab in tabs"
			:key="tab.id"
			class="document-leaf-tabs__tab"
			:data-leaf="tab.id">
			<h4 class="document-leaf-tabs__label">
				{{ tab.label }}
			</h4>
			<!-- The leaf's own tab when it registered one; the library's
			     generic host otherwise. Both are the registry's components. -->
			<component
				:is="tabComponent(tab.id)"
				v-if="tabComponent(tab.id)"
				:register="register"
				:schema="schema"
				:objectId="objectId" />
			<CnIntegrationTab
				v-else
				:integrationId="tab.id"
				:register="register"
				:schema="schema"
				:objectId="objectId"
				:emptyLabel="t('filinq', 'Nothing linked to this document yet')"
				:unavailableLabel="
					t('filinq', 'This integration is not available on this server.')
				" />
		</div>
	</section>
</template>

<style scoped>
.document-leaf-tabs {
	border-top: 1px solid var(--color-border);
	margin-top: 1rem;
	padding-top: 1rem;
}

.document-leaf-tabs__heading {
	color: var(--color-text-maxcontrast);
	font-size: 0.9rem;
	margin: 0 0 0.5rem;
}

.document-leaf-tabs__label {
	font-size: 0.85rem;
	margin: 0.75rem 0 0.25rem;
}
</style>
