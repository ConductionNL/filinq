/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Publishing a custom record page's object into CnAppRoot's shared
 * `objectSidebarState`, so RecordLeafSidebar hosts the registry's leaves for
 * it. A page publishes when its object has loaded and withdraws when it
 * leaves, so the sidebar never shows the leaves of a record that is no longer
 * on screen.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */

/**
 * Publish one record into the sidebar state.
 *
 * @param {object|null} state The injected `objectSidebarState`, or null outside CnAppRoot.
 * @param {object} record The record.
 * @param {string} record.schema The schema slug; the object type is `filinq-<schema>`, as CnDetailPage names it.
 * @param {string} record.id The object's uuid.
 * @param {string} [record.title] The title shown in the sidebar header.
 * @param {object} [record.object] The loaded object, for the data tab.
 * @return {boolean} True when published; false without a state or an id.
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */
export function publishRecord(state, { schema, id, title = '', object = null }) {
	if (!state || !schema || !id) {
		return false
	}

	Object.assign(state, {
		active: true,
		objectType: `filinq-${schema}`,
		objectId: String(id),
		register: 'filinq',
		schema,
		title,
		subtitle: '',
		object,
		requestedTab: null,
	})
	return true
}

/**
 * Withdraw the record a page published, and close the sidebar, unless another
 * record has been published since.
 *
 * @param {object|null} state The injected `objectSidebarState`.
 * @param {string} id The object id the page published.
 * @return {void}
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */
export function withdrawRecord(state, id) {
	if (!state || state.objectId !== String(id)) {
		return
	}

	Object.assign(state, { active: false, open: false, objectType: '', objectId: '', object: null, title: '' })
}

/**
 * Open or close the sidebar of the published record.
 *
 * @param {object|null} state The injected `objectSidebarState`.
 * @return {void}
 */
export function toggleRecordSidebar(state) {
	if (state && state.active === true) {
		state.open = state.open !== true
	}
}

/**
 * A mixin for a hand-built record page: it publishes the page's record into
 * the shared sidebar state whenever the record changes, withdraws it when the
 * page leaves, and offers `toggleLeafSidebar()` plus `leafSidebarAvailable`
 * for a "Linked apps" button.
 *
 * @param {object} options The options.
 * @param {string} options.schema The record's schema slug.
 * @param {Function} options.record Reads the loaded record from the component.
 * @param {Function} [options.title] Reads the sidebar title from the record.
 * @return {object} The mixin.
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */
export function recordLeafSidebarMixin({ schema, record, title = () => '' }) {
	return {
		inject: {
			objectSidebarState: { default: null },
		},

		data() {
			return { publishedRecordId: '' }
		},

		computed: {
			leafSidebarAvailable() {
				return !!this.objectSidebarState && this.publishedRecordId !== ''
			},
		},

		created() {
			this.$watch(
				() => record(this),
				(loaded) => {
					const id = loaded ? String(loaded.id || loaded.uuid || '') : ''
					if (id !== '' && publishRecord(this.objectSidebarState, { schema, id, title: title(loaded) || '', object: loaded })) {
						this.publishedRecordId = id
					}
				},
				{ immediate: true },
			)
		},

		beforeUnmount() {
			withdrawRecord(this.objectSidebarState, this.publishedRecordId)
		},

		methods: {
			toggleLeafSidebar() {
				toggleRecordSidebar(this.objectSidebarState)
			},
		},
	}
}
