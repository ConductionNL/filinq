<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Import a ZIP of office templates (and a fragments/ folder of text
fragments): start the job, follow it, read the report per file, and map the
tags that matched nothing to a property of the bound schema.

@spec openspec/changes/office-template-authoring/tasks.md#4-3
-->

<template>
	<NcModal
		:show="true"
		:name="t('filinq', 'Import templates')"
		size="large"
		@close="$emit('close')">
		<div class="template-import" data-testid="template-import-modal">
			<h2>{{ t('filinq', 'Import templates') }}</h2>

			<form v-if="!job" class="template-import__start" @submit.prevent="start">
				<p class="template-import__hint">
					{{
						t(
							'filinq',
							'A ZIP with DOCX or ODT templates. Text files in a fragments folder become text fragments.',
						)
					}}
				</p>
				<label class="template-import__file">
					{{ t('filinq', 'ZIP archive') }}
					<input
						type="file"
						accept=".zip"
						data-testid="template-import-file"
						@change="file = $event.target.files[0] || null" />
				</label>
				<NcTextField
					v-model="fields.namespace"
					:label="t('filinq', 'Namespace')"
					required />
				<NcTextField
					v-model="fields.boundRegister"
					:label="t('filinq', 'Bound register')" />
				<NcTextField
					v-model="fields.boundSchema"
					:label="t('filinq', 'Bound schema')" />
				<NcButton
					type="submit"
					variant="primary"
					:disabled="busy || !file || !fields.namespace"
					data-testid="template-import-start">
					{{ t('filinq', 'Start import') }}
				</NcButton>
			</form>

			<div v-else class="template-import__job">
				<p data-testid="template-import-status">
					{{ statusText }}
				</p>
				<NcLoadingIcon v-if="busyJob" />
				<table
					v-if="job.report && job.report.length > 0"
					class="template-import__report">
					<thead>
						<tr>
							<th scope="col">{{ t('filinq', 'File') }}</th>
							<th scope="col">{{ t('filinq', 'Kind') }}</th>
							<th scope="col">{{ t('filinq', 'Status') }}</th>
							<th scope="col">{{ t('filinq', 'Tags') }}</th>
							<th scope="col">{{ t('filinq', 'Unmapped tags') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="row in job.report" :key="row.file">
							<td>{{ row.file }}</td>
							<td>
								{{
									row.kind === 'fragment'
										? t('filinq', 'Text fragment')
										: t('filinq', 'Template')
								}}
							</td>
							<td>
								{{
									row.status === 'failed'
										? row.reason
										: t('filinq', 'Imported')
								}}
							</td>
							<td>{{ row.tags }}</td>
							<td>
								<div
									v-for="tag in row.unknownTags"
									:key="tag"
									class="template-import__mapping">
									<NcTextField
										:modelValue="mapping(row.objectId, tag)"
										:label="
											t('filinq', 'Property for {tag}', {
												tag,
											})
										"
										@update:modelValue="
											setMapping(row.objectId, tag, $event)
										" />
								</div>
								<NcButton
									v-if="
										row.unknownTags && row.unknownTags.length > 0
									"
									variant="secondary"
									:disabled="saving === row.objectId"
									:data-testid="
										'template-import-map-' + row.objectId
									"
									@click="saveMapping(row)">
									{{ t('filinq', 'Save mapping') }}
								</NcButton>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<NcNoteCard v-if="saved" type="success">
				{{ saved }}
			</NcNoteCard>
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcLoadingIcon,
	NcModal,
	NcNoteCard,
	NcTextField,
} from '@nextcloud/vue'
import {
	errorText,
	importBusy,
	importStatus,
	saveFieldMap,
	startImport,
} from '../services/officeTemplates.js'

export default {
	name: 'TemplateImportModal',
	components: { NcButton, NcLoadingIcon, NcModal, NcNoteCard, NcTextField },

	emits: ['close', 'imported'],

	data() {
		return {
			file: null,
			fields: { namespace: 'filinq', boundRegister: '', boundSchema: '' },
			job: null,
			busy: false,
			timer: null,
			mappings: {},
			saving: null,
			error: '',
			saved: '',
		}
	},

	computed: {
		/**
		 * Whether the job is still running.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
		 */
		busyJob() {
			return importBusy(this.job)
		},

		/**
		 * One sentence about the job.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
		 */
		statusText() {
			if (this.busyJob) {
				return t('filinq', 'Importing {done} of {total} files', {
					done: this.job.imported + this.job.failed,
					total: this.job.totalFiles,
				})
			}
			if (this.job.status === 'failed') {
				return this.job.error || t('filinq', 'The import failed.')
			}
			return t('filinq', '{imported} imported, {failed} failed', {
				imported: this.job.imported,
				failed: this.job.failed,
			})
		},
	},

	beforeUnmount() {
		clearTimeout(this.timer)
	},

	methods: {
		t,
		/**
		 * Upload the ZIP and follow the job.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
		 */
		async start() {
			this.busy = true
			this.error = ''
			try {
				this.job = await startImport(this.file, this.fields)
				this.follow()
			} catch (error) {
				this.error = errorText(
					error,
					t('filinq', 'The import could not be started.'),
				)
			} finally {
				this.busy = false
			}
		},

		/**
		 * Read the job again in two seconds while it runs.
		 *
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
		 */
		follow() {
			if (!this.busyJob) {
				this.$emit('imported', this.job)
				return
			}
			this.timer = setTimeout(async () => {
				try {
					this.job = await importStatus(this.job.uuid)
				} catch (error) {
					this.error = errorText(
						error,
						t('filinq', 'The import could not be read.'),
					)
					return
				}
				this.follow()
			}, 2000)
		},

		/**
		 * The property typed for a tag of a template.
		 *
		 * @param {string} templateId The template.
		 * @param {string} tag The tag.
		 * @return {string} The property path.
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
		 */
		mapping(templateId, tag) {
			return this.mappings[templateId]?.[tag] ?? ''
		},

		/**
		 * Remember the property typed for a tag.
		 *
		 * @param {string} templateId The template.
		 * @param {string} tag The tag.
		 * @param {string} value The property path.
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
		 */
		setMapping(templateId, tag, value) {
			this.mappings = {
				...this.mappings,
				[templateId]: { ...(this.mappings[templateId] ?? {}), [tag]: value },
			}
		},

		/**
		 * Store the typed mappings of one template as its fieldMap.
		 *
		 * @param {object} row The report row.
		 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
		 */
		async saveMapping(row) {
			const fieldMap = Object.fromEntries(
				Object.entries(this.mappings[row.objectId] ?? {}).filter(
					([, value]) => value.trim() !== '',
				),
			)
			this.saving = row.objectId
			this.error = ''
			this.saved = ''
			try {
				const template = await saveFieldMap(row.objectId, fieldMap)
				row.unknownTags = template.tagReport?.unknown ?? []
				this.saved = t('filinq', 'Mapping saved for {file}', {
					file: row.file,
				})
			} catch (error) {
				this.error = errorText(
					error,
					t('filinq', 'The mapping could not be saved.'),
				)
			} finally {
				this.saving = null
			}
		},
	},
}
</script>

<style scoped>
.template-import {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 20px;
}

.template-import__start {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.template-import__hint {
	color: var(--color-text-maxcontrast);
}

.template-import__file {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.template-import__report {
	width: 100%;
	border-collapse: collapse;
}

.template-import__report th,
.template-import__report td {
	padding: 8px;
	border-bottom: 1px solid var(--color-border);
	text-align: start;
	vertical-align: top;
}

.template-import__mapping {
	margin-bottom: 4px;
}
</style>
