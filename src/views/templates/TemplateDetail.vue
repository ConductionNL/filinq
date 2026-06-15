<template>
	<div class="template-detail">
		<!-- Header bar -->
		<div class="template-detail__header">
			<NcButton type="tertiary" @click="handleBack">
				{{ t('docudesk', 'Back to templates') }}
			</NcButton>
			<h2 class="template-detail__title">
				{{ isNew ? t('docudesk', 'New template') : (form.name || t('docudesk', 'Edit template')) }}
			</h2>
			<div class="template-detail__header-actions">
				<span v-if="lockOwner && !isLockMine" class="template-detail__lock-warning">
					{{ t('docudesk', 'Locked by {user}', { user: lockOwner }) }}
				</span>
				<NcButton type="secondary" :disabled="saving" @click="handleBack">
					{{ t('docudesk', 'Cancel') }}
				</NcButton>
				<NcButton type="primary" :disabled="saving || (lockOwner && !isLockMine)" @click="saveTemplate">
					{{ saving ? t('docudesk', 'Saving…') : t('docudesk', 'Save') }}
				</NcButton>
			</div>
		</div>

		<!-- Tab navigation -->
		<div class="template-detail__tabs">
			<button :class="['template-detail__tab', { active: activeTab === 'edit' }]"
				@click="activeTab = 'edit'">
				{{ t('docudesk', 'Editor') }}
			</button>
			<button :class="['template-detail__tab', { active: activeTab === 'preview' }]"
				@click="loadPreview">
				{{ t('docudesk', 'Preview') }}
			</button>
			<button v-if="!isNew"
				:class="['template-detail__tab', { active: activeTab === 'versions' }]"
				@click="loadVersions">
				{{ t('docudesk', 'Versions') }}
			</button>
		</div>

		<!-- EDITOR TAB -->
		<div v-if="activeTab === 'edit'" class="template-detail__editor-panel">
			<!-- Metadata fields -->
			<div class="template-detail__meta">
				<NcTextField :value.sync="form.name"
					:label="t('docudesk', 'Name')"
					:required="true"
					class="template-detail__field" />
				<NcTextField :value.sync="form.namespace"
					:label="t('docudesk', 'Namespace')"
					:required="true"
					:disabled="!isNew"
					class="template-detail__field" />
				<NcTextField :value.sync="form.category"
					:label="t('docudesk', 'Category')"
					:placeholder="t('docudesk', 'e.g. beschikkingen, brieven')"
					class="template-detail__field" />
				<NcTextField :value.sync="form.tagsInput"
					:label="t('docudesk', 'Tags (comma-separated)')"
					:placeholder="t('docudesk', 'tag1, tag2, tag3')"
					class="template-detail__field" />
				<NcTextField :value.sync="form.description"
					:label="t('docudesk', 'Description')"
					class="template-detail__field" />
				<NcTextField :value.sync="form.changelog"
					:label="t('docudesk', 'Change note (optional)')"
					:placeholder="t('docudesk', 'Describe what changed...')"
					class="template-detail__field" />
			</div>

			<!-- WYSIWYG toolbar -->
			<div class="template-detail__toolbar">
				<button type="button"
					:title="t('docudesk', 'Bold')"
					class="template-detail__toolbar-btn"
					@mousedown.prevent="execFormat('bold')">
					<strong>B</strong>
				</button>
				<button type="button"
					:title="t('docudesk', 'Italic')"
					class="template-detail__toolbar-btn"
					@mousedown.prevent="execFormat('italic')">
					<em>I</em>
				</button>
				<button type="button"
					:title="t('docudesk', 'Underline')"
					class="template-detail__toolbar-btn"
					@mousedown.prevent="execFormat('underline')">
					<u>U</u>
				</button>
				<span class="template-detail__toolbar-sep" />
				<button type="button"
					:title="t('docudesk', 'Heading 1')"
					class="template-detail__toolbar-btn"
					@mousedown.prevent="execBlock('h1')">
					H1
				</button>
				<button type="button"
					:title="t('docudesk', 'Heading 2')"
					class="template-detail__toolbar-btn"
					@mousedown.prevent="execBlock('h2')">
					H2
				</button>
				<span class="template-detail__toolbar-sep" />
				<button type="button"
					:title="t('docudesk', 'Unordered list')"
					class="template-detail__toolbar-btn"
					@mousedown.prevent="execFormat('insertUnorderedList')">
					&#8226;&#8212;
				</button>
				<button type="button"
					:title="t('docudesk', 'Ordered list')"
					class="template-detail__toolbar-btn"
					@mousedown.prevent="execFormat('insertOrderedList')">
					1&#8212;
				</button>
				<span class="template-detail__toolbar-sep" />
				<button type="button"
					:title="t('docudesk', 'Insert merge field')"
					class="template-detail__toolbar-btn"
					@click="showMergeDialog = true">
					{{ t('docudesk', '{ }') }}
				</button>
				<button type="button"
					:title="t('docudesk', 'Insert conditional section')"
					class="template-detail__toolbar-btn"
					@click="showConditionalDialog = true">
					{{ t('docudesk', 'if…') }}
				</button>
			</div>

			<!-- Content-editable WYSIWYG area -->
			<div ref="editor"
				class="template-detail__content-editor"
				contenteditable="true"
				:aria-label="t('docudesk', 'Template content')"
				@input="syncFromEditor"
				v-html="editorHtml" />

			<!-- Raw HTML toggle -->
			<div class="template-detail__raw-toggle">
				<button type="button"
					class="template-detail__raw-btn"
					@click="showRaw = !showRaw">
					{{ showRaw ? t('docudesk', 'Hide HTML') : t('docudesk', 'Edit HTML') }}
				</button>
			</div>
			<textarea v-if="showRaw"
				:value="form.content"
				class="template-detail__raw-area"
				:aria-label="t('docudesk', 'Raw HTML')"
				@input="syncFromRaw" />
		</div>

		<!-- PREVIEW TAB -->
		<div v-else-if="activeTab === 'preview'" class="template-detail__preview-panel">
			<div class="template-detail__preview-header">
				<h3>{{ t('docudesk', 'Preview') }}</h3>
				<NcButton type="secondary" @click="loadPreview">
					{{ t('docudesk', 'Refresh preview') }}
				</NcButton>
			</div>
			<div class="template-detail__sample-data">
				<NcTextField :value.sync="sampleDataJson"
					:label="t('docudesk', 'Sample data (JSON)')"
					:placeholder="'{ &quot;name&quot;: &quot;Jan de Vries&quot; }'"
					class="template-detail__field" />
			</div>
			<NcLoadingIcon v-if="previewLoading" />
			<div v-else-if="previewError" class="template-detail__error">
				{{ previewError }}
			</div>
			<!-- eslint-disable-next-line vue/no-v-html -->
			<div v-else-if="previewHtml"
				class="template-detail__preview-output"
				v-html="previewHtml" />
			<NcEmptyContent v-else
				:name="t('docudesk', 'No preview yet')"
				:description="t('docudesk', 'Click \'Refresh preview\' to render the template.')" />
		</div>

		<!-- VERSIONS TAB -->
		<div v-else-if="activeTab === 'versions'" class="template-detail__versions-panel">
			<h3>{{ t('docudesk', 'Version history') }}</h3>
			<NcLoadingIcon v-if="versionsLoading" />
			<NcEmptyContent v-else-if="!versions.length"
				:name="t('docudesk', 'No versions yet')"
				:description="t('docudesk', 'Versions are saved automatically on each update.')" />
			<table v-else class="template-detail__versions-table">
				<thead>
					<tr>
						<th>{{ t('docudesk', 'Version') }}</th>
						<th>{{ t('docudesk', 'Editor') }}</th>
						<th>{{ t('docudesk', 'Change note') }}</th>
						<th>{{ t('docudesk', 'Actions') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="ver in versions" :key="ver.id">
						<td>{{ ver.version }}</td>
						<td>{{ ver.editor }}</td>
						<td>{{ ver.changelog || '-' }}</td>
						<td>
							<NcButton type="tertiary" @click="restoreVersion(ver)">
								{{ t('docudesk', 'Restore') }}
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Dialogs (extracted per ADR-004) -->
		<MergeFieldDialog v-if="showMergeDialog"
			@close="showMergeDialog = false"
			@insert="insertMergeField" />

		<ConditionalSectionDialog v-if="showConditionalDialog"
			@close="showConditionalDialog = false"
			@insert="insertConditionalSection" />

		<ConfirmRestoreVersionDialog v-if="restoreTarget"
			:version-number="restoreTarget ? restoreTarget.version : 0"
			@confirm="executeRestore"
			@cancel="restoreTarget = null" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcEmptyContent, NcLoadingIcon, NcTextField } from '@conduction/nextcloud-vue'
import { useTemplateStore } from '../../store/modules/template.js'
import ConditionalSectionDialog from '../../dialogs/ConditionalSectionDialog.vue'
import MergeFieldDialog from '../../dialogs/MergeFieldDialog.vue'
import ConfirmRestoreVersionDialog from '../../dialogs/ConfirmRestoreVersionDialog.vue'

export default {
	name: 'TemplateDetail',
	components: { NcButton, NcEmptyContent, NcLoadingIcon, NcTextField, ConditionalSectionDialog, MergeFieldDialog, ConfirmRestoreVersionDialog },
	data() {
		return {
			activeTab: 'edit',
			form: {
				name: '',
				namespace: '',
				description: '',
				content: '',
				category: '',
				tagsInput: '',
				changelog: '',
				format: 'A4',
				orientation: 'P',
			},
			// WYSIWYG
			editorHtml: '',
			showRaw: false,
			// Preview
			previewLoading: false,
			previewHtml: '',
			previewError: '',
			sampleDataJson: '{}',
			// Versions
			versions: [],
			versionsLoading: false,
			// Lock
			lockOwner: null,
			// Saving
			saving: false,
			// Dialogs
			showMergeDialog: false,
			showConditionalDialog: false,
			restoreTarget: null,
		}
	},
	computed: {
		/**
		 * Pinia template store accessor for the detail editor.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		templateStore() { return useTemplateStore() },
		isNew() { return this.templateStore.templateItem === null },
		isLockMine() {
			return this.lockOwner === null || this.lockOwner === this.currentUserId
		},
		/**
		 * Current Nextcloud user ID, used for lock ownership checks.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		currentUserId() {
			return window.OC?.currentUser || ''
		},
	},
	/**
	 * Hydrate the editor form from the selected template and acquire its edit lock.
	 *
	 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
	 */
	async mounted() {
		if (!this.isNew) {
			const tmpl = this.templateStore.templateItem
			this.form.name = tmpl.name || ''
			this.form.namespace = tmpl.namespace || ''
			this.form.description = tmpl.description || ''
			this.form.content = tmpl.content || ''
			this.form.category = tmpl.category || ''
			this.form.tagsInput = (tmpl.tags || []).join(', ')
			this.form.format = tmpl.format || 'A4'
			this.form.orientation = tmpl.orientation || 'P'
			this.editorHtml = tmpl.content || ''
			this.lockOwner = tmpl.lockedBy || null

			// Acquire lock.
			const locked = await this.templateStore.acquireLock(tmpl.id)
			if (locked) {
				this.lockOwner = locked.lockedBy || null
			}
		}
	},
	async beforeUnmount() {
		await this.releaseLockIfMine()
	},
	methods: {
		t,
		/**
		 * Release any held lock and navigate back to the template list.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async handleBack() {
			await this.releaseLockIfMine()
			this.$router.push({ name: 'Templates' })
		},
		/**
		 * Release the edit lock if the current user owns it.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async releaseLockIfMine() {
			const tmpl = this.templateStore.templateItem
			if (!this.isNew && tmpl && this.isLockMine) {
				await this.templateStore.releaseLock(tmpl.id)
			}
		},
		/**
		 * Apply an inline formatting command in the WYSIWYG editor.
		 *
		 * @param cmd
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		execFormat(cmd) {
			document.execCommand(cmd, false, null)
			this.syncFromEditor()
		},
		/**
		 * Apply a block-level format (heading) in the WYSIWYG editor.
		 *
		 * @param tag
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		execBlock(tag) {
			document.execCommand('formatBlock', false, tag)
			this.syncFromEditor()
		},
		/**
		 * Sync the form content from the WYSIWYG editor's HTML.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		syncFromEditor() {
			if (this.$refs.editor) {
				this.form.content = this.$refs.editor.innerHTML
			}
		},
		/**
		 * Sync the form content from the raw HTML textarea.
		 *
		 * @param event
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		syncFromRaw(event) {
			this.form.content = event.target.value
			this.editorHtml = event.target.value
		},
		/**
		 * Render a live preview of the current template content with sample data.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async loadPreview() {
			this.activeTab = 'preview'
			this.previewLoading = true
			this.previewError = ''
			let sampleData = {}
			try {
				sampleData = JSON.parse(this.sampleDataJson || '{}')
			} catch {
				// Ignore JSON parse error; use empty data.
			}
			try {
				this.previewHtml = await this.templateStore.previewTemplate(
					this.form.content,
					sampleData,
				)
			} catch (err) {
				this.previewError = err.message || t('docudesk', 'Preview failed')
			} finally {
				this.previewLoading = false
			}
		},
		/**
		 * Load the version history for the current template.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async loadVersions() {
			this.activeTab = 'versions'
			if (this.isNew) return
			this.versionsLoading = true
			const result = await this.templateStore.fetchVersions(this.templateStore.templateItem.id)
			this.versions = result?.results || []
			this.versionsLoading = false
		},
		/**
		 * Open the restore confirmation dialog for a version.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		restoreVersion(ver) {
			this.restoreTarget = ver
		},
		/**
		 * Execute restore after user confirmed in the dialog.
		 *
		 * @param ver
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async executeRestore() {
			if (!this.restoreTarget) return
			const ver = this.restoreTarget
			this.restoreTarget = null
			const result = await this.templateStore.restoreVersion(
				this.templateStore.templateItem.id,
				ver.id,
			)
			if (result) {
				this.form.content = result.content || ''
				this.editorHtml = result.content || ''
				this.activeTab = 'edit'
			}
		},
		/**
		 * Persist the template (create or update), release the lock and return to the list.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		async saveTemplate() {
			this.saving = true
			const tags = this.form.tagsInput
				.split(',')
				.map(s => s.trim())
				.filter(Boolean)
			const payload = {
				name: this.form.name,
				namespace: this.form.namespace,
				description: this.form.description,
				content: this.form.content,
				category: this.form.category,
				tags,
				format: this.form.format,
				orientation: this.form.orientation,
				_changelog: this.form.changelog || null,
			}
			try {
				if (this.isNew) {
					await this.templateStore.createTemplate(payload)
				} else {
					await this.templateStore.updateTemplate(
						this.templateStore.templateItem.id,
						payload,
					)
				}
				await this.releaseLockIfMine()
				this.$router.push({ name: 'Templates' })
				await this.templateStore.fetchTemplates()
			} finally {
				this.saving = false
			}
		},
		/**
		 * Insert a merge-field token at the cursor in the editor.
		 *
		 * @param fieldName
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		insertMergeField(fieldName) {
			const token = `{{ ${fieldName} }}`
			document.execCommand('insertText', false, token)
			this.syncFromEditor()
		},
		/**
		 * Insert a conditional section block (data-condition attributes) at the cursor.
		 *
		 * @param root0
		 * @param root0.field
		 * @param root0.op
		 * @param root0.value
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		insertConditionalSection({ field, op, value }) {
			const opAttr = `data-condition-field="${field}" data-condition-op="${op}"`
			const valAttr = (op !== 'is_empty' && op !== 'is_not_empty') ? ` data-condition-value="${value}"` : ''
			const html = `<div ${opAttr}${valAttr}>{{ ${field} }}</div>`
			document.execCommand('insertHTML', false, html)
			this.syncFromEditor()
		},
	},
}
</script>

<style scoped>
.template-detail {
	display: flex;
	flex-direction: column;
	height: 100%;
	padding: 16px;
}

.template-detail__header {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 16px;
}

.template-detail__title {
	flex: 1;
	margin: 0;
}

.template-detail__header-actions {
	display: flex;
	align-items: center;
	gap: 8px;
}

.template-detail__lock-warning {
	color: var(--color-warning);
	font-size: 13px;
}

.template-detail__tabs {
	display: flex;
	gap: 4px;
	border-bottom: 2px solid var(--color-border);
	margin-bottom: 16px;
}

.template-detail__tab {
	background: none;
	border: none;
	padding: 8px 16px;
	cursor: pointer;
	border-bottom: 2px solid transparent;
	margin-bottom: -2px;
	color: var(--color-text-lighter);
}

.template-detail__tab.active {
	border-bottom-color: var(--color-primary);
	color: var(--color-primary);
	font-weight: bold;
}

.template-detail__meta {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 12px;
	margin-bottom: 12px;
}

.template-detail__field {
	width: 100%;
}

.template-detail__toolbar {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
	padding: 6px 8px;
	background: var(--color-background-dark);
	border: 1px solid var(--color-border);
	border-bottom: none;
	border-radius: 4px 4px 0 0;
}

.template-detail__toolbar-btn {
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: 4px;
	padding: 2px 8px;
	cursor: pointer;
	min-width: 30px;
}

.template-detail__toolbar-btn:hover {
	background: var(--color-background-hover);
}

.template-detail__toolbar-sep {
	width: 1px;
	background: var(--color-border);
	margin: 0 4px;
}

.template-detail__content-editor {
	min-height: 300px;
	max-height: 500px;
	overflow-y: auto;
	border: 1px solid var(--color-border);
	border-radius: 0 0 4px 4px;
	padding: 12px;
	background: var(--color-main-background);
	font-family: inherit;
	outline: none;
	line-height: 1.5;
}

.template-detail__content-editor:focus {
	border-color: var(--color-primary);
}

.template-detail__raw-toggle {
	margin-top: 8px;
}

.template-detail__raw-btn {
	background: none;
	border: none;
	color: var(--color-primary);
	cursor: pointer;
	text-decoration: underline;
	font-size: 13px;
	padding: 0;
}

.template-detail__raw-area {
	width: 100%;
	height: 200px;
	font-family: monospace;
	font-size: 13px;
	margin-top: 8px;
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: 4px;
	box-sizing: border-box;
}

.template-detail__preview-header {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 12px;
}

.template-detail__preview-header h3 {
	margin: 0;
	flex: 1;
}

.template-detail__sample-data {
	margin-bottom: 12px;
}

.template-detail__preview-output {
	border: 1px solid var(--color-border);
	border-radius: 4px;
	padding: 16px;
	min-height: 200px;
	background: white;
	color: black;
}

.template-detail__versions-table {
	width: 100%;
	border-collapse: collapse;
}

.template-detail__versions-table th,
.template-detail__versions-table td {
	padding: 8px 12px;
	border-bottom: 1px solid var(--color-border);
	text-align: left;
}

.template-detail__error {
	color: var(--color-error);
	padding: 12px;
	border: 1px solid var(--color-error);
	border-radius: 4px;
}

.template-detail__editor-panel,
.template-detail__preview-panel,
.template-detail__versions-panel {
	flex: 1;
	overflow: auto;
}
</style>
