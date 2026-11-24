<!--
  SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  SPDX-License-Identifier: EUPL-1.2

  HtmlDocumentEditor: the editing surface over a document's HTML, shared by
  the template page and the draft editor (generate-draft-and-attended-edit
  D7). A toolbar for inline and block formatting, a contenteditable area and
  a raw-HTML toggle. The HTML is sanitised before it is mounted. Extra
  toolbar buttons (the template page's merge field and condition) go in the
  `toolbarExtra` slot; a draft has no merge fields left, so it offers none.

  v-model: `modelValue` is the HTML; every edit emits `update:modelValue`.
  The area is re-mounted only when the value changes from outside (a load, a
  restore, the raw-HTML box), never on its own typing, so the caret stays put.
-->
<template>
	<div class="html-document-editor">
		<div class="html-document-editor__toolbar" role="toolbar" :aria-label="t('filinq', 'Formatting')">
			<button
				type="button"
				:title="t('filinq', 'Bold')"
				:aria-label="t('filinq', 'Bold')"
				class="html-document-editor__toolbar-btn"
				:disabled="readonly"
				@mousedown.prevent="execFormat('bold')">
				<strong>B</strong>
			</button>
			<button
				type="button"
				:title="t('filinq', 'Italic')"
				:aria-label="t('filinq', 'Italic')"
				class="html-document-editor__toolbar-btn"
				:disabled="readonly"
				@mousedown.prevent="execFormat('italic')">
				<em>I</em>
			</button>
			<button
				type="button"
				:title="t('filinq', 'Underline')"
				:aria-label="t('filinq', 'Underline')"
				class="html-document-editor__toolbar-btn"
				:disabled="readonly"
				@mousedown.prevent="execFormat('underline')">
				<u>U</u>
			</button>
			<span class="html-document-editor__toolbar-sep" />
			<button
				type="button"
				:title="t('filinq', 'Heading 1')"
				:aria-label="t('filinq', 'Heading 1')"
				class="html-document-editor__toolbar-btn"
				:disabled="readonly"
				@mousedown.prevent="execBlock('h1')">
				H1
			</button>
			<button
				type="button"
				:title="t('filinq', 'Heading 2')"
				:aria-label="t('filinq', 'Heading 2')"
				class="html-document-editor__toolbar-btn"
				:disabled="readonly"
				@mousedown.prevent="execBlock('h2')">
				H2
			</button>
			<span class="html-document-editor__toolbar-sep" />
			<button
				type="button"
				:title="t('filinq', 'Unordered list')"
				:aria-label="t('filinq', 'Unordered list')"
				class="html-document-editor__toolbar-btn"
				:disabled="readonly"
				@mousedown.prevent="execFormat('insertUnorderedList')">
				&#8226;&#8212;
			</button>
			<button
				type="button"
				:title="t('filinq', 'Ordered list')"
				:aria-label="t('filinq', 'Ordered list')"
				class="html-document-editor__toolbar-btn"
				:disabled="readonly"
				@mousedown.prevent="execFormat('insertOrderedList')">
				1&#8212;
			</button>
			<template v-if="$slots['toolbarExtra']">
				<span class="html-document-editor__toolbar-sep" />
				<!-- @slot Extra toolbar buttons, such as the template page's merge field and condition. -->
				<slot name="toolbarExtra" />
			</template>
		</div>

		<!-- eslint-disable-next-line vue/no-v-html -->
		<div
			ref="editor"
			class="html-document-editor__content"
			:contenteditable="readonly ? 'false' : 'true'"
			role="textbox"
			aria-multiline="true"
			:aria-label="ariaLabel"
			:aria-readonly="readonly ? 'true' : 'false'"
			@input="sync"
			v-html="safeHtml" />

		<div class="html-document-editor__raw-toggle">
			<button
				type="button"
				class="html-document-editor__raw-btn"
				:aria-expanded="showRaw ? 'true' : 'false'"
				@click="showRaw = !showRaw">
				{{ showRaw ? t('filinq', 'Hide HTML') : t('filinq', 'Edit HTML') }}
			</button>
		</div>
		<textarea
			v-if="showRaw"
			:value="modelValue"
			class="html-document-editor__raw-area"
			:aria-label="t('filinq', 'Raw HTML')"
			:readonly="readonly"
			@input="syncFromRaw" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { sanitizeHtml } from '../utils/sanitizeHtml.js'

export default {
	name: 'HtmlDocumentEditor',

	props: {
		/** The document's HTML (v-model). */
		modelValue: {
			type: String,
			default: '',
		},

		/** The accessible name of the editing area. */
		ariaLabel: {
			type: String,
			default: '',
		},

		/** Show the HTML without letting it be changed, as for a finished draft. */
		readonly: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['update:modelValue'],

	data() {
		return {
			// What the area is mounted with; follows outside changes only.
			mountedHtml: this.modelValue,
			// The last value this editor emitted, to tell its own echo apart.
			emittedHtml: this.modelValue,
			showRaw: false,
		}
	},

	computed: {
		/**
		 * The mounted HTML, sanitised before it reaches `v-html`.
		 *
		 * @return {string}
		 * @spec openspec/changes/archive/2026-11-20-generate-draft-and-attended-edit/tasks.md#task-3.1
		 */
		safeHtml() {
			return sanitizeHtml(this.mountedHtml)
		},
	},

	watch: {
		/**
		 * Re-mount the area when the value changes from outside.
		 *
		 * @param {string} value The new HTML.
		 * @spec openspec/changes/archive/2026-11-20-generate-draft-and-attended-edit/tasks.md#task-3.1
		 */
		modelValue(value) {
			if (value !== this.emittedHtml) {
				this.mountedHtml = value
				this.emittedHtml = value
			}
		},
	},

	methods: {
		t,

		/**
		 * Apply an inline formatting command at the selection.
		 *
		 * @param {string} cmd The `execCommand` command.
		 * @spec openspec/changes/archive/2026-11-20-generate-draft-and-attended-edit/tasks.md#task-3.1
		 */
		execFormat(cmd) {
			if (this.readonly) {
				return
			}
			document.execCommand(cmd, false, null)
			this.sync()
		},

		/**
		 * Apply a block format, such as a heading, at the selection.
		 *
		 * @param {string} tag The block tag.
		 * @spec openspec/changes/archive/2026-11-20-generate-draft-and-attended-edit/tasks.md#task-3.1
		 */
		execBlock(tag) {
			if (this.readonly) {
				return
			}
			document.execCommand('formatBlock', false, tag)
			this.sync()
		},

		/**
		 * Emit the area's current HTML. A host that inserts content itself
		 * (the template page's merge field) calls this afterwards.
		 *
		 * @spec openspec/changes/archive/2026-11-20-generate-draft-and-attended-edit/tasks.md#task-3.1
		 */
		sync() {
			if (!this.$refs.editor || this.readonly) {
				return
			}
			this.emittedHtml = this.$refs.editor.innerHTML
			this.$emit('update:modelValue', this.emittedHtml)
		},

		/**
		 * Take the raw-HTML box's value and re-mount the area with it.
		 *
		 * @param {Event} event The textarea's input event.
		 * @spec openspec/changes/archive/2026-11-20-generate-draft-and-attended-edit/tasks.md#task-3.1
		 */
		syncFromRaw(event) {
			if (this.readonly) {
				return
			}
			const value = event.target.value
			this.mountedHtml = value
			this.emittedHtml = value
			this.$emit('update:modelValue', value)
		},
	},
}
</script>

<style scoped>
.html-document-editor__toolbar {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
	padding: 6px 8px;
	background: var(--color-background-dark);
	border: 1px solid var(--color-border);
	border-bottom: none;
	border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.html-document-editor__toolbar :deep(button),
.html-document-editor__toolbar-btn {
	background: var(--color-main-background);
	color: var(--color-main-text);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: 2px 8px;
	cursor: pointer;
	min-width: 30px;
}

.html-document-editor__toolbar-btn:hover:not(:disabled) {
	background: var(--color-background-hover);
}

.html-document-editor__toolbar-btn:disabled {
	cursor: default;
	opacity: 0.5;
}

.html-document-editor__toolbar-sep {
	width: 1px;
	background: var(--color-border);
	margin: 0 4px;
}

.html-document-editor__content {
	min-height: 300px;
	max-height: 500px;
	overflow-y: auto;
	border: 1px solid var(--color-border);
	border-radius: 0 0 var(--border-radius) var(--border-radius);
	padding: 12px;
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-family: inherit;
	outline: none;
	line-height: 1.5;
}

.html-document-editor__content:focus {
	border-color: var(--color-primary-element);
	box-shadow: 0 0 0 1px var(--color-primary-element);
}

.html-document-editor__raw-toggle {
	margin-top: 8px;
}

.html-document-editor__raw-btn {
	background: none;
	border: none;
	color: var(--color-primary-element);
	cursor: pointer;
	text-decoration: underline;
	font-size: var(--default-font-size);
	padding: 0;
}

.html-document-editor__raw-area {
	width: 100%;
	height: 200px;
	font-family: monospace;
	font-size: var(--default-font-size);
	margin-top: 8px;
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	box-sizing: border-box;
}
</style>
