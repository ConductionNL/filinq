<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The office part of a template's detail page: download the DOCX source,
upload a new revision, read the tag report (unknown tags as a warning) and
map tags to properties of the bound schema.

@spec openspec/changes/office-template-authoring/tasks.md#4-2
-->

<template>
	<section class="office-panel" data-testid="office-template-panel">
		<h3>{{ t('filinq', 'Office document') }}</h3>
		<div class="office-panel__actions">
			<a
				class="button"
				:href="sourceUrl"
				data-testid="office-template-download">
				{{ t('filinq', 'Download source (DOCX)') }}
			</a>
			<label class="office-panel__upload">
				{{ t('filinq', 'Upload a new revision') }}
				<input
					type="file"
					accept=".docx,.odt"
					data-testid="office-template-revision"
					@change="upload($event.target.files[0] || null)" />
			</label>
		</div>

		<NcNoteCard v-if="report && !report.validated" type="info">
			{{
				t(
					'filinq',
					'The tags are not checked: the template has no bound schema.',
				)
			}}
		</NcNoteCard>
		<NcNoteCard
			v-if="unknown.length > 0"
			type="warning"
			data-testid="office-template-unknown-tags">
			{{
				t('filinq', 'These tags match nothing in schema {schema}: {tags}', {
					schema: template.boundSchema,
					tags: unknown.join(', '),
				})
			}}
		</NcNoteCard>

		<table v-if="tags.length > 0" class="office-panel__tags">
			<thead>
				<tr>
					<th scope="col">{{ t('filinq', 'Tag') }}</th>
					<th scope="col">{{ t('filinq', 'Check') }}</th>
					<th scope="col">{{ t('filinq', 'Filled from property') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="tag in tags" :key="tag">
					<td>
						<code>{{ tag }}</code>
					</td>
					<td>{{ checkLabel(tag) }}</td>
					<td>
						<NcTextField
							v-if="!tag.startsWith('fragment:')"
							:modelValue="fieldMap[tag] || ''"
							:label="t('filinq', 'Property for {tag}', { tag })"
							@update:modelValue="
								fieldMap = { ...fieldMap, [tag]: $event }
							" />
					</td>
				</tr>
			</tbody>
		</table>
		<NcButton
			v-if="tags.length > 0"
			variant="secondary"
			:disabled="busy"
			data-testid="office-template-save-mapping"
			@click="saveMapping">
			{{ t('filinq', 'Save mapping') }}
		</NcButton>
		<NcNoteCard v-if="message" :type="messageType">
			{{ message }}
		</NcNoteCard>
	</section>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcNoteCard, NcTextField } from '@nextcloud/vue'
import {
	errorText,
	officeSourceUrl,
	replaceOfficeSource,
	saveFieldMap,
	unknownTags,
} from '../services/officeTemplates.js'

export default {
	name: 'OfficeTemplatePanel',
	components: { NcButton, NcNoteCard, NcTextField },

	props: {
		template: {
			type: Object,
			required: true,
		},
	},

	emits: ['updated'],

	data() {
		return {
			fieldMap: { ...(this.template.fieldMap ?? {}) },
			busy: false,
			message: '',
			messageType: 'success',
		}
	},

	computed: {
		/**
		 * The download address of the source.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
		 */
		sourceUrl() {
			return officeSourceUrl(this.template.id)
		},

		/**
		 * The tag report of the last upload.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
		 */
		report() {
			return this.template.tagReport ?? null
		},

		/**
		 * The tags of the source.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
		 */
		tags() {
			return this.template.mergeFields ?? []
		},

		/**
		 * The tags that matched nothing.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
		 */
		unknown() {
			return unknownTags(this.template)
		},
	},

	methods: {
		t,
		/**
		 * What the check said about a tag.
		 *
		 * @param {string} tag The tag.
		 * @return {string} The label.
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
		 */
		checkLabel(tag) {
			if (tag.startsWith('fragment:')) {
				return t('filinq', 'Text fragment')
			}
			if (this.unknown.includes(tag)) {
				return t('filinq', 'Unknown')
			}
			return this.report?.validated
				? t('filinq', 'Known')
				: t('filinq', 'Not checked')
		},

		/**
		 * Upload a new revision of the source.
		 *
		 * @param {File|null} file The document.
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
		 */
		async upload(file) {
			if (!file) {
				return
			}
			await this.run(
				async () => {
					const result = await replaceOfficeSource(this.template.id, file)
					this.$emit('updated', result.template)
					return result.converted
						? t(
								'filinq',
								'New revision uploaded. It was converted from ODT; check the preview.',
							)
						: t('filinq', 'New revision uploaded.')
				},
				t('filinq', 'The revision could not be uploaded.'),
			)
		},

		/**
		 * Store the field mapping.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
		 */
		async saveMapping() {
			const fieldMap = Object.fromEntries(
				Object.entries(this.fieldMap).filter(
					([, value]) => value && value.trim() !== '',
				),
			)
			await this.run(
				async () => {
					this.$emit(
						'updated',
						await saveFieldMap(this.template.id, fieldMap),
					)
					return t('filinq', 'Mapping saved.')
				},
				t('filinq', 'The mapping could not be saved.'),
			)
		},

		/**
		 * Run a call and show its outcome.
		 *
		 * @param {Function} work Returns the success sentence.
		 * @param {string} fallback The sentence when it fails without one.
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
		 */
		async run(work, fallback) {
			this.busy = true
			this.message = ''
			try {
				this.message = await work()
				this.messageType = 'success'
			} catch (error) {
				this.message = errorText(error, fallback)
				this.messageType = 'error'
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.office-panel {
	display: flex;
	flex-direction: column;
	gap: 12px;
	margin: 16px 0;
}

.office-panel__actions {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
	align-items: center;
}

.office-panel__upload {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.office-panel__tags {
	width: 100%;
	border-collapse: collapse;
}

.office-panel__tags th,
.office-panel__tags td {
	padding: 8px;
	border-bottom: 1px solid var(--color-border);
	text-align: start;
}
</style>
