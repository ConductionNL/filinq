<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The draft editor (/drafts/:id, generate-draft-and-attended-edit): the
rendered document a clerk edits before it is stored, on the editing surface
the template page uses. Save keeps the text, Finish files and logs it through
the same path as a direct generation and closes the draft, Discard removes
it. A wizard run still being answered continues in the wizard. Another
clerk's draft reads as not found: OpenRegister's private scope answers 404.

@spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
-->

<template>
	<div class="draft-editor" data-testid="draft-editor">
		<div class="draft-editor__header">
			<NcButton variant="tertiary" @click="toList">
				{{ t('filinq', 'Back to drafts') }}
			</NcButton>
			<h2 class="draft-editor__title">
				{{ draft && draft.templateName ? draft.templateName : t('filinq', 'Draft') }}
			</h2>
		</div>

		<NcLoadingIcon v-if="loading" />

		<NcEmptyContent
			v-else-if="!draft"
			data-testid="draft-not-found"
			:name="t('filinq', 'This draft was not found')"
			:description="t('filinq', 'It may have been finished, discarded or removed after 30 days, or it is not yours.')" />

		<template v-else>
			<p class="draft-editor__meta" data-testid="draft-meta">
				{{ metaLine }}
			</p>

			<NcNoteCard v-if="finished" type="success" data-testid="draft-finished">
				{{ t('filinq', 'This draft is finished. The document is stored and logged; the text can no longer be changed.') }}
			</NcNoteCard>
			<NcNoteCard v-for="warning in warnings" :key="warning" type="warning">
				{{ warning }}
			</NcNoteCard>
			<NcNoteCard v-if="errorText" type="error" data-testid="draft-error">
				{{ errorText }}
			</NcNoteCard>

			<HtmlDocumentEditor
				v-model="html"
				:readonly="finished"
				:aria-label="t('filinq', 'Document text')" />

			<div v-if="!finished" class="draft-editor__actions">
				<NcButton
					:disabled="busy || !dirty"
					data-testid="draft-save"
					@click="save">
					{{ t('filinq', 'Save') }}
				</NcButton>
				<NcButton
					variant="primary"
					:disabled="busy"
					data-testid="draft-finish"
					@click="finish">
					{{ finishing ? t('filinq', 'Finishing…') : t('filinq', 'Finish') }}
				</NcButton>
				<NcButton
					variant="error"
					:disabled="busy"
					data-testid="draft-discard"
					@click="confirmDiscard = true">
					{{ t('filinq', 'Discard') }}
				</NcButton>
			</div>
		</template>

		<CnConfirmDialog
			v-if="confirmDiscard"
			:dialogTitle="t('filinq', 'Discard this draft?')"
			:message="t('filinq', 'The edited text is removed. Nothing was stored from it.')"
			:confirmLabel="t('filinq', 'Discard')"
			variant="error"
			@confirm="discard"
			@close="confirmDiscard = false" />
	</div>
</template>

<script>
import { CnConfirmDialog } from '@conduction/nextcloud-vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcEmptyContent, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import HtmlDocumentEditor from '../../components/HtmlDocumentEditor.vue'
import {
	discardDraft,
	finishDraft,
	isOpen,
	loadDraft,
	resumeRoute,
	saveDraft,
} from '../../services/drafts.js'

/**
 * Hand a produced document to the browser as a download.
 *
 * @param {Blob} blob The document.
 * @param {string} filename Its name.
 * @return {void}
 */
function download(blob, filename) {
	const url = URL.createObjectURL(blob)
	const link = document.createElement('a')
	link.href = url
	link.download = filename
	document.body.appendChild(link)
	link.click()
	document.body.removeChild(link)
	URL.revokeObjectURL(url)
}

export default {
	name: 'DraftEditor',

	components: {
		CnConfirmDialog,
		HtmlDocumentEditor,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		NcNoteCard,
	},

	data() {
		return {
			loading: true,
			draft: null,
			html: '',
			savedHtml: '',
			saving: false,
			finishing: false,
			errorText: '',
			confirmDiscard: false,
		}
	},

	computed: {
		/**
		 * The draft id from the route.
		 *
		 * @return {string}
		 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
		 */
		id() {
			return String(this.$route?.params?.id || '')
		},

		/**
		 * Whether the draft is closed.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
		 */
		finished() {
			return this.draft?.stage === 'finished'
		},

		/**
		 * Whether the text differs from what was last saved.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
		 */
		dirty() {
			return this.html !== this.savedHtml
		},

		/**
		 * Whether a save, finish or discard is under way.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
		 */
		busy() {
			return this.saving || this.finishing
		},

		/**
		 * The warnings the render gave.
		 *
		 * @return {Array<string>}
		 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
		 */
		warnings() {
			return Array.isArray(this.draft?.warnings) ? this.draft.warnings.map(String) : []
		},

		/**
		 * When the draft last changed and when it is removed.
		 *
		 * @return {string}
		 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
		 */
		metaLine() {
			const changed = this.draft?.['@self']?.updated || ''
			const expires = this.draft?.expiresAt || ''
			const when = (value) => (value ? new Date(value).toLocaleString() : '')
			if (this.finished) {
				return t('filinq', 'Finished. Removed on {date}.', { date: when(expires) })
			}
			if (changed) {
				return t('filinq', 'Last change {changed}. Removed on {date} unless saved again.', { changed: when(changed), date: when(expires) })
			}
			return t('filinq', 'Removed on {date} unless saved again.', { date: when(expires) })
		},
	},

	async mounted() {
		await this.load()
	},

	methods: {
		t,

		/**
		 * Read the draft; a wizard run still being answered continues in the wizard.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
		 */
		async load() {
			this.loading = true
			const result = await loadDraft(this.id)
			this.loading = false
			if (!result.ok) {
				this.draft = null
				if (result.status !== 404 && result.status !== 403) {
					showError(t('filinq', 'The draft could not be loaded.'))
				}
				return
			}
			if (result.data?.stage === 'answering') {
				this.$router.replace(resumeRoute(result.data))
				return
			}
			this.draft = result.data
			this.html = String(result.data?.html || '')
			this.savedHtml = this.html
		},

		/**
		 * Save the edited text.
		 *
		 * @return {Promise<boolean>} True when saved.
		 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
		 */
		async save() {
			if (!this.draft || !isOpen(this.draft)) {
				return false
			}
			this.saving = true
			this.errorText = ''
			const result = await saveDraft(this.draft, { html: this.html })
			this.saving = false
			if (!result.ok) {
				this.errorText = t('filinq', 'The draft could not be saved.')
				return false
			}
			this.draft = { ...this.draft, ...(result.data || {}), html: this.html }
			this.savedHtml = this.html
			showSuccess(t('filinq', 'Draft saved.'))
			return true
		},

		/**
		 * Save what changed, then file and log the document and close the draft.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
		 */
		async finish() {
			if (this.dirty && !(await this.save())) {
				return
			}
			this.finishing = true
			this.errorText = ''
			const result = await finishDraft(this.id)
			this.finishing = false
			if (!result.ok) {
				this.errorText = result.status === 422
					? t('filinq', 'Some wizard answers were not accepted. Open the wizard again to correct them.')
					: (result.error || t('filinq', 'The document could not be finished.'))
				return
			}
			if (result.data) {
				download(result.data, result.filename)
			}
			this.draft = { ...this.draft, stage: 'finished' }
			showSuccess(t('filinq', 'The document is stored.'))
		},

		/**
		 * Remove the draft and go back to the list.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
		 */
		async discard() {
			this.confirmDiscard = false
			const result = await discardDraft(this.id)
			if (!result.ok) {
				this.errorText = t('filinq', 'The draft could not be discarded.')
				return
			}
			showSuccess(t('filinq', 'Draft discarded.'))
			this.toList()
		},

		/**
		 * Go to the drafts list.
		 *
		 * @return {void}
		 * @spec openspec/changes/generate-draft-and-attended-edit/tasks.md#task-3.2
		 */
		toList() {
			this.$router.push({ name: 'Drafts' })
		},
	},
}
</script>

<style scoped>
.draft-editor {
	padding: calc(var(--default-grid-baseline) * 4);
	max-width: 960px;
}

.draft-editor__header {
	display: flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 3);
	margin-bottom: calc(var(--default-grid-baseline) * 2);
}

.draft-editor__title {
	margin: 0;
}

.draft-editor__meta {
	color: var(--color-text-maxcontrast);
	margin-bottom: calc(var(--default-grid-baseline) * 3);
}

.draft-editor__actions {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
	margin-top: calc(var(--default-grid-baseline) * 4);
}
</style>
