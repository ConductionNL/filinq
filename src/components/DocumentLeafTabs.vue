<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V.
  - SPDX-License-Identifier: EUPL-1.2
  -->

<script>
import {
	CnIntegrationTab,
	CnLeafMountHost,
	useIntegrationRegistry,
} from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'
import {
	DOCUMENT_LEAF_IDS,
	leafRenderPath,
	visibleLeafTabs,
} from '../services/documentLeafTabs.js'

/**
 * The integration leaves on one record: a document, a signing request, a dossier, a consent.
 *
 * Which leaves a surface shows is the surface's `leafIds`: the document sidebar
 * keeps its contacts, activity and shares; a record surface passes
 * `leafIdsForSchema(schema)`, the linked types its schema declares in the
 * register (leaf-integrations 2.1). A leaf whose app is absent is left out and
 * the rest still render (2.2).
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
 * @spec openspec/changes/leaf-integrations/tasks.md#2-1
 */
export default {
	name: 'DocumentLeafTabs',

	components: { CnIntegrationTab, CnLeafMountHost },

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
		/** The leaves this surface consumes, in render order. */
		leafIds: { type: Array, default: () => DOCUMENT_LEAF_IDS },
		/** Shown in a leaf tab that has nothing linked yet. */
		emptyLabel: { type: String, default: '' },
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
			visibleLeafTabs(
				integrations.value,
				{ objectId: props.objectId },
				props.leafIds,
			),
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

		/**
		 * The render path of one leaf: a mount hand-off, its own tab, or the generic host.
		 *
		 * @param {object} tab The registry descriptor.
		 *
		 * @return {string} 'mount', 'tab' or 'generic'.
		 *
		 * @spec openspec/changes/leaf-integrations/tasks.md#2-1
		 */
		renderPath(tab) {
			return leafRenderPath(tab, this.tabComponent(tab.id))
		},

		/**
		 * The empty-state text, defaulting to the document wording.
		 *
		 * @return {string} The translated label.
		 *
		 * @spec openspec/changes/leaf-integrations/tasks.md#2-1
		 */
		emptyText() {
			return this.emptyLabel || t('filinq', 'Nothing linked to this document yet')
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
			<!-- A mount-mode leaf gets a bare element; otherwise the leaf's own
			     tab when it registered one, the library's generic host else.
			     All three are the registry's or the library's, never Filinq's. -->
			<CnLeafMountHost
				v-if="renderPath(tab) === 'mount'"
				:provider="tab"
				:mountProps="{ register, schema, objectId }" />
			<component
				:is="tabComponent(tab.id)"
				v-else-if="renderPath(tab) === 'tab'"
				:register="register"
				:schema="schema"
				:objectId="objectId" />
			<CnIntegrationTab
				v-else
				:integrationId="tab.id"
				:register="register"
				:schema="schema"
				:objectId="objectId"
				:emptyLabel="emptyText()"
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
