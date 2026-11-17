<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/office-template-authoring/specs/template-management/spec.md
-->

<template>
	<div class="office-template-panel">
		<section class="office-template-panel__section">
			<h3>{{ t('filinq', 'Word file') }}</h3>
			<div class="office-template-panel__actions">
				<NcButton :href="sourceUrl" variant="secondary">
					{{ t('filinq', 'Download Word file') }}
				</NcButton>
				<NcButton
					:href="previewUrl"
					target="_blank"
					rel="noopener"
					variant="secondary">
					{{ t('filinq', 'Preview as PDF') }}
				</NcButton>
			</div>
			<div v-if="!readOnly" class="office-template-panel__upload">
				<label class="office-template-panel__label" :for="fileInputId">
					{{ t('filinq', 'Upload a new version (DOCX or ODT)') }}
				</label>
				<input
					:id="fileInputId"
					type="file"
					accept=".docx,.odt"
					@change="pick">
				<NcTextField v-model="changelog" :label="t('filinq', 'What changed')" />
				<NcButton
					variant="primary"
					:disabled="!file || uploading"
					@click="upload">
					{{ t('filinq', 'Upload new version') }}
				</NcButton>
			</div>
			<NcNoteCard v-if="uploadError" type="error">
				{{ uploadError }}
			</NcNoteCard>
			<NcNoteCard v-if="converted" type="info">
				{{ t('filinq', 'The ODT file was converted to DOCX. Check the preview to see that the layout survived.') }}
			</NcNoteCard>
		</section>

		<section class="office-template-panel__section">
			<h3>{{ t('filinq', 'Merge tags') }}</h3>
			<NcNoteCard v-if="report.notice" type="info">
				{{ report.notice }}
			</NcNoteCard>
			<NcNoteCard v-if="unknown.length > 0" type="warning">
				{{ n('filinq', '%n merge tag is not a field of the schema: {tags}', '%n merge tags are not fields of the schema: {tags}', unknown.length, { tags: unknown.join(', ') }) }}
			</NcNoteCard>
			<ul class="office-template-panel__tags">
				<li v-for="tag in tags" :key="tag.tag">
					<code>{{ '${' + tag.tag + '}' }}</code>
					<span class="office-template-panel__status" :class="['office-template-panel__status--' + tag.status]">{{ statusLabel(tag.status) }}</span>
				</li>
			</ul>
		</section>

		<section class="office-template-panel__section">
			<h3>{{ t('filinq', 'Field mapping') }}</h3>
			<FieldMapEditor :template="template" :readOnly="readOnly" @saved="$emit('updated', $event)" />
		</section>
	</div>
</template>

<script>
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { NcButton, NcNoteCard, NcTextField } from '@nextcloud/vue'
import FieldMapEditor from './FieldMapEditor.vue'
import { previewUrl, sourceUrl, uploadNewSource } from '../../services/officeTemplates.js'

export default {
	name: 'OfficeTemplatePanel',
	components: { FieldMapEditor, NcButton, NcNoteCard, NcTextField },
	props: {
		template: { type: Object, required: true },
		readOnly: { type: Boolean, default: false },
	},

	emits: ['updated'],
	data() {
		return {
			file: null,
			changelog: '',
			uploading: false,
			uploadError: '',
			converted: false,
			fileInputId: 'office-source-' + Math.random().toString(36).slice(2),
		}
	},

	computed: {
		/**
		 * report.
		 *
		 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
		 */
		report() {
			return this.template.tagReport || {}
		},

		/**
		 * unknown.
		 *
		 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
		 */
		unknown() {
			return this.report.unknown || []
		},

		/**
		 * tags.
		 *
		 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
		 */
		tags() {
			if (Array.isArray(this.report.tags) && this.report.tags.length > 0) {
				return this.report.tags
			}
			return (this.template.mergeFields || []).map((field) => ({ tag: field.path, status: 'unvalidated' }))
		},

		/**
		 * sourceUrl.
		 *
		 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
		 */
		sourceUrl() {
			return sourceUrl(this.template.id)
		},

		/**
		 * previewUrl.
		 *
		 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
		 */
		previewUrl() {
			return previewUrl(this.template.id)
		},
	},

	methods: {
		t,
		n,
		/**
		 * statusLabel.
		 *
		 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
		 */
		statusLabel(status) {
			return {
				known: t('filinq', 'Known field'),
				fragment: t('filinq', 'Text fragment'),
				unknown: t('filinq', 'Unknown'),
				unvalidated: t('filinq', 'Not checked'),
			}[status] || status
		},

		/**
		 * pick.
		 *
		 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
		 */
		pick(event) {
			this.file = (event.target.files && event.target.files[0]) || null
		},

		/**
		 * upload.
		 *
		 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
		 */
		async upload() {
			this.uploading = true
			this.uploadError = ''
			const result = await uploadNewSource(this.template.id, this.file, this.changelog)
			this.uploading = false
			if (!result.ok) {
				this.uploadError = result.error || t('filinq', 'The file could not be uploaded.')
				return
			}
			this.converted = Boolean(result.data.converted)
			this.file = null
			this.changelog = ''
			this.$emit('updated', result.data.template)
		},
	},
}
</script>

<style scoped>
.office-template-panel__section {
	margin-bottom: 24px;
}

.office-template-panel__actions,
.office-template-panel__upload {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	align-items: flex-end;
	margin-bottom: 12px;
}

.office-template-panel__label {
	width: 100%;
}

.office-template-panel__tags {
	list-style: none;
	padding: 0;
}

.office-template-panel__tags li {
	display: flex;
	gap: 12px;
	padding: 4px 0;
	border-bottom: 1px solid var(--color-border);
}

.office-template-panel__status {
	color: var(--color-text-maxcontrast);
}

.office-template-panel__status--unknown {
	color: var(--color-warning-text);
	font-weight: bold;
}
</style>
