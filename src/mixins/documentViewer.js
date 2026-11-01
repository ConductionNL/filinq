/**
 * Mixins for the in-app document viewers (odt, docx, text, pdf).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 *  - `documentViewer`: the `path` and `url` props, reloading through the
 *    viewer's `load()` when either changes, the add-mode cursor flag and
 *    pushing the text selection into the viewer store.
 *  - `entityHighlighting`: the entities and pending selection to mark, and
 *    calling the viewer's `scheduleHighlights()` when either changes.
 *  - `htmlDocumentViewer`: a viewer that converts the file bytes to HTML and
 *    marks entities in its `content` ref. The viewer supplies
 *    `loadConverter()` and `toHtml(converter, arrayBuffer)`, and the console
 *    prefix for a failed load in the component option `loadFailedLog`.
 */

import { translate as t } from '@nextcloud/l10n'
import {
	fetchFileAsArrayBuffer,
	fetchUrlAsArrayBuffer,
} from '../services/fileViewerService.js'
import { applyDomHighlights, clearDomHighlights } from '../services/highlightDom.js'
import { fileViewerStore } from '../store/store.js'

export const documentViewer = {
	props: {
		path: {
			type: String,
			required: true,
		},

		// Optional: read the bytes from this same-origin URL instead of the
		// WebDAV path, for content that is not a plain file in the user's
		// storage (the compare view mounted by another app, the EML preview).
		url: {
			type: String,
			default: '',
		},
	},

	computed: {
		/**
		 * Whether the viewer is in add mode — drives the marking (highlighter)
		 * cursor so it is obvious the user can select text to add an entity.
		 *
		 * @return {boolean}
		 */
		isAddMode() {
			return fileViewerStore.addMode
		},
	},

	watch: {
		path: {
			immediate: true,
			handler() {
				this.load()
			},
		},

		/**
		 * Reload when the host points the viewer at another URL.
		 *
		 * @return {void}
		 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-the-split-view-is-mountable-by-another-app-req-ddarw-014
		 */
		url() {
			this.load()
		},
	},

	methods: {
		/**
		 * Push the current text selection into the viewer store, for the
		 * "add selection as entity" flow.
		 *
		 * @spec openspec/specs/document-preview/spec.md#requirement-format-specific-in-app-document-preview-req-ddprv-001
		 */
		captureSelection() {
			const text = window.getSelection()?.toString() || ''
			fileViewerStore.setSelection(text)
		},
	},
}

export const entityHighlighting = {
	computed: {
		/**
		 * Entities the sidebar asked to mark in the document. Read through a
		 * computed (not a store string-path) so the watcher fires reliably.
		 *
		 * @return {Array<{value: string, type: string}>}
		 */
		highlightEntities() {
			return fileViewerStore.highlightEntities || []
		},

		/**
		 * Pending selection to mark distinctly — only while in add mode.
		 *
		 * @return {string}
		 */
		pendingValue() {
			return fileViewerStore.addMode ? fileViewerStore.selection || '' : ''
		},
	},

	watch: {
		highlightEntities: {
			deep: true,
			handler() {
				this.scheduleHighlights()
			},
		},

		pendingValue() {
			this.scheduleHighlights()
		},
	},
}

export const htmlDocumentViewer = {
	mixins: [documentViewer, entityHighlighting],

	data() {
		return {
			loading: true,
			error: null,
			html: '',
		}
	},

	beforeUnmount() {
		clearDomHighlights(this.$refs.content)
	},

	methods: {
		/**
		 * Fetch the file as ArrayBuffer and convert it to HTML for display.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/document-preview/spec.md#requirement-format-specific-in-app-document-preview-req-ddprv-001
		 */
		async load() {
			this.loading = true
			this.error = null
			try {
				const [converter, arrayBuffer] = await Promise.all([
					this.loadConverter(),
					this.url
						? fetchUrlAsArrayBuffer(this.url)
						: fetchFileAsArrayBuffer(this.path),
				])
				this.html = await this.toHtml(converter, arrayBuffer)
			} catch (err) {
				console.error(this.$options.loadFailedLog, err)
				this.error = err.message || t('filinq', 'Failed to load document')
			} finally {
				this.loading = false
			}
			// Highlight only after `loading` is false: the content element
			// (ref="content") sits behind `v-else`, so it is not in the DOM
			// while loading. Scheduling the highlight pass here ensures the
			// re-render that mounts the element is queued before the
			// $nextTick highlight callback runs — otherwise `$refs.content`
			// is null and nothing is marked (notably on a cache-hit reopen,
			// where the entity list is pre-set and no later change retriggers
			// the highlight watcher).
			if (!this.error) {
				this.scheduleHighlights()
			}
		},

		/**
		 * Re-apply the entity highlights after the DOM has rendered the latest
		 * HTML or store change.
		 *
		 * @return {void}
		 */
		scheduleHighlights() {
			this.$nextTick(() => {
				applyDomHighlights(
					this.$refs.content,
					this.highlightEntities,
					this.pendingValue,
				)
			})
		},
	},
}
