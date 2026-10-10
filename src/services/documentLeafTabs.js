/**
 * Which integration leaves belong on the document detail surface, and when.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * 🔴 THE DECISION LIVES HERE, NOT IN THE TEMPLATE. `v-if`s spread across a
 * 1,800-line sidebar cannot be asserted without mounting it, and this app has
 * no component-mount harness. Keeping the choice in a function means the rule
 * "shown when the leaf is enabled, hidden when its app is absent" is a test
 * that can fail, rather than a claim about markup nobody exercises.
 *
 * 🔴 FILINQ AUTHORS NO TAB SYSTEM HERE (ADR-019 / ADR-022). This module SELECTS
 * from the shared registry's snapshot; the rendering is the library's own tab
 * host. Everything it returns is a registry descriptor, so a leaf that changes
 * its label or icon changes them here with no edit in this repo.
 *
 * 🔑 AN UNKNOWN LEAF IS NOT SHOWN, AND THAT IS DELIBERATE. The registry carries
 * every integration OpenRegister installed — twenty-six of them. Rendering all
 * of them on a document would bury the three this surface is about, so the
 * allowlist below is the surface's contract rather than a filter somebody can
 * widen by accident.
 *
 * @spec openspec/changes/document-detail-leaf-widgets/specs/document-register/spec.md
 */

/**
 * The leaves the document detail surface consumes, in the order they render.
 *
 * Contacts first because "who this document concerns" is the question asked of
 * a document most often; shares last because it answers a question about
 * access rather than about the document.
 *
 * @type {string[]}
 */
export const DOCUMENT_LEAF_IDS = ['contacts', 'activity', 'shares']

/**
 * Each schema's `configuration.linkedTypes`, as `lib/Settings/filinq_register.json` declares them.
 *
 * 🔴 THE REGISTER IS THE SOURCE; THIS IS ITS COPY FOR THE BROWSER, PINNED BY A
 * TEST. Importing the register JSON would put the whole register (megabytes of
 * schemas) into the main bundle to read six short lists, and fetching each
 * schema from OpenRegister on every detail page is a round trip for a value
 * that only changes with a filinq release. `tests/vitest/documentLeafTabs.spec.js`
 * reads the register file and fails the moment the two differ, so a schema that
 * gains or drops a linked type cannot drift from what its surface renders.
 *
 * @type {Object<string, string[]>}
 *
 * @spec openspec/changes/leaf-integrations/tasks.md#2-1
 */
export const SCHEMA_LINKED_TYPES = Object.freeze({
	signingRequest: ['mail', 'calendar'],
	signerRecord: ['contacts'],
	publicationConsent: ['mail', 'calendar', 'deck'],
	correspondence: ['mail'],
	generatedDocument: ['files'],
	dossier: ['files', 'deck'],
})

/**
 * Linked-type ids OpenRegister still accepts that are not the registry's own id.
 *
 * 🔴 `mail` IS A LEGACY linkedTypes ID; THE REGISTRY CALLS THE LEAF `email`.
 * OpenRegister's Schema keeps the old ids valid (its legacy allow-list), but
 * the integration registry the tabs read from registers the mail leaf as
 * `email`. Looking `mail` up as-is finds nothing, and the mail tab would be
 * missing on every surface with no error anywhere.
 *
 * @type {Object<string, string>}
 *
 * @spec openspec/changes/leaf-integrations/tasks.md#2-1
 */
export const LINKED_TYPE_TO_LEAF_ID = Object.freeze({ mail: 'email' })

/**
 * The leaf ids a record surface consumes, from its schema's linked types.
 *
 * A schema with no declared linked types answers an empty list: such a record
 * shows no leaf section rather than a guessed one.
 *
 * @param {string} schema The OpenRegister schema slug of the record.
 *
 * @return {string[]} Registry ids, in the order the schema declares them.
 *
 * @spec openspec/changes/leaf-integrations/tasks.md#2-1
 */
export function leafIdsForSchema(schema) {
	const linkedTypes = SCHEMA_LINKED_TYPES[schema] ?? []
	return linkedTypes.map((type) => LINKED_TYPE_TO_LEAF_ID[type] ?? type)
}

/**
 * How one leaf renders: its own mount hand-off, its own tab, or the library's host.
 *
 * A `renderMode: 'mount'` leaf brings its own framework instance and must get a
 * bare element (CnLeafMountHost); handing it to `<component :is>` renders
 * nothing. A component leaf renders its registered tab; a descriptor with no
 * tab of its own falls back to the library's generic CnIntegrationTab.
 *
 * @param {object}  descriptor The registry descriptor.
 * @param {?object} tab        What `resolveTab(id)` returned for it.
 *
 * @return {'mount'|'tab'|'generic'} The render path.
 *
 * @spec openspec/changes/leaf-integrations/tasks.md#2-1
 */
export function leafRenderPath(descriptor, tab) {
	if (
		descriptor
		&& descriptor.renderMode === 'mount'
		&& typeof descriptor.mount === 'function'
		&& typeof descriptor.unmount === 'function'
	) {
		return 'mount'
	}

	return tab ? 'tab' : 'generic'
}

/**
 * The leaf tabs to render for one document record.
 *
 * 🔴 NO RECORD MEANS NO TABS, NOT EMPTY TABS. A leaf tab reads
 * `/objects/{register}/{schema}/{id}/integrations/{leaf}`, so without an object
 * id there is nothing to ask about. Rendering the tabs anyway would show three
 * permanently empty panels and read as "this document has no contacts" — a
 * statement nobody made.
 *
 * 🔴 `available === false` HIDES; `null` DOES NOT. The registry uses null for
 * "not known yet" and resolves availability later in the tab host itself.
 * Treating unknown as absent would hide a leaf whose app IS installed, every
 * time the capability read is slower than the first render.
 *
 * @param {Array<object>} integrations The registry snapshot.
 * @param {object}        binding      The record the tabs would be about.
 * @param {string}        [binding.objectId] The OpenRegister object id, '' when the document has no record.
 * @param {string[]}      [leafIds]    The leaves this surface consumes, in render order.
 *
 * @return {Array<object>} The descriptors to render, in leafIds order.
 *
 * @spec openspec/changes/document-detail-leaf-widgets/specs/document-register/spec.md
 * @spec openspec/changes/leaf-integrations/tasks.md#2-2
 */
export function visibleLeafTabs(
	integrations,
	binding = {},
	leafIds = DOCUMENT_LEAF_IDS,
) {
	const objectId = String(binding.objectId ?? '').trim()
	if (objectId === '') {
		return []
	}

	const registered = new Map()
	for (const entry of Array.isArray(integrations) ? integrations : []) {
		if (entry && typeof entry.id === 'string') {
			registered.set(entry.id, entry)
		}
	}

	const wanted = Array.isArray(leafIds) ? leafIds : DOCUMENT_LEAF_IDS
	return wanted
		.map((id) => registered.get(id))
		.filter((entry) => entry !== undefined && entry.available !== false)
}

/**
 * The OpenRegister record id for the document currently open, or ''.
 *
 * 🔑 THE DOCUMENT DETAIL SURFACE IS FILE-BACKED, AND ITS RECORD IS THE
 * ANONYMISATION LINK. Filinq's viewer opens a Nextcloud file id; the object
 * Filinq keeps per document is the `filinq/anonymizationLink` record that maps
 * that source file to what came out of it. That record is what a leaf links
 * against, so it is what the tabs bind to. A document with no link record has
 * no object yet, which is the '' this returns and the hidden section it causes.
 *
 * 🔴 THE MATCH IS ON `sourceFileId`, NOT ON POSITION OR NAME. The same document
 * can be anonymised more than once and file names repeat across folders; a name
 * match would bind the tabs of one document to another document's record, and
 * nothing downstream could tell.
 *
 * @param {Array<object>} links  The `anonymizationLink` records the store holds.
 * @param {number|string} fileId The source file the viewer has open.
 *
 * @return {string} The record id, or '' when this document has none.
 *
 * @spec openspec/changes/document-detail-leaf-widgets/specs/document-register/spec.md
 */
export function documentRecordIdFor(links, fileId) {
	const source = Number(fileId)
	if (!Number.isFinite(source)) {
		return ''
	}

	for (const link of Array.isArray(links) ? links : []) {
		if (link && Number(link.sourceFileId) === source) {
			const id = link.id ?? link.uuid ?? link['@self']?.id ?? ''
			return String(id ?? '')
		}
	}

	return ''
}

/**
 * Whether the document detail surface shows a leaf section at all.
 *
 * Separate from the list so the sidebar can leave out the heading as well as
 * the tabs: a heading above nothing is how an empty section reads as a broken
 * one.
 *
 * @param {Array<object>} integrations The registry snapshot.
 * @param {object}        binding      The record the tabs would be about.
 *
 * @return {boolean} True when at least one leaf renders.
 *
 * @spec openspec/changes/document-detail-leaf-widgets/specs/document-register/spec.md
 */
export function hasLeafTabs(integrations, binding = {}) {
	return visibleLeafTabs(integrations, binding).length > 0
}
