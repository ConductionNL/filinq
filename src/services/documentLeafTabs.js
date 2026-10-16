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
 *
 * @return {Array<object>} The descriptors to render, in DOCUMENT_LEAF_IDS order.
 *
 * @spec openspec/changes/document-detail-leaf-widgets/specs/document-register/spec.md
 */
export function visibleLeafTabs(integrations, binding = {}) {
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

	return DOCUMENT_LEAF_IDS.map((id) => registered.get(id)).filter(
		(entry) => entry !== undefined && entry.available !== false,
	)
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
