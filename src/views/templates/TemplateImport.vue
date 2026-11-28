<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
-->

<template>
	<div class="template-import">
		<h2>{{ t('filinq', 'Import Word templates') }}</h2>

		<section class="template-import__section">
			<h3>{{ t('filinq', 'One template') }}</h3>
			<NcButton variant="primary" @click="showUpload = true">
				{{ t('filinq', 'Upload a Word template') }}
			</NcButton>
		</section>

		<section class="template-import__section">
			<h3>{{ t('filinq', 'A whole collection') }}</h3>
			<p class="template-import__help">
				{{ t('filinq', 'A ZIP file, or a folder in your files, of DOCX and ODT templates. Text files in a folder named fragments become text fragments. The first folder of a template becomes its category.') }}
			</p>
			<form class="template-import__form" @submit.prevent="start">
				<label for="template-import-zip">{{ t('filinq', 'ZIP file') }}</label>
				<input
					id="template-import-zip"
					type="file"
					accept=".zip"
					@change="pick">
				<NcTextField v-model="fields.path" :label="t('filinq', 'Or a folder in your files')" placeholder="Sjablonen/Huisstijl" />
				<NcTextField v-model="fields.namespace" :label="t('filinq', 'Namespace')" required />
				<NcTextField v-model="fields.boundRegister" :label="t('filinq', 'Register to check the tags against')" />
				<NcTextField v-model="fields.boundSchema" :label="t('filinq', 'Schema to check the tags against')" />
				<NcButton
					type="submit"
					variant="primary"
					:disabled="running || (!file && fields.path.trim() === '')">
					{{ t('filinq', 'Start import') }}
				</NcButton>
			</form>
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
		</section>

		<section v-if="job" class="template-import__section" aria-live="polite">
			<h3>{{ t('filinq', 'Progress') }}</h3>
			<p>
				{{ t('filinq', '{imported} imported, {failed} failed, {skipped} skipped of {total} files', { imported: job.imported || 0, failed: job.failed || 0, skipped: job.skipped || 0, total: job.totalFiles || 0 }) }}
				<NcLoadingIcon v-if="running" :size="20" class="template-import__spinner" />
			</p>
			<table v-if="report.length > 0" class="template-import__report">
				<thead>
					<tr>
						<th scope="col">
							{{ t('filinq', 'File') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Result') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Merge tags') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Unknown tags') }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="row in report" :key="row.file">
						<td>{{ row.file }}</td>
						<td>{{ resultLabel(row) }}</td>
						<td>{{ row.tags ?? '' }}</td>
						<td>
							<template v-if="(row.unknown || []).length > 0">
								{{ row.unknown.join(', ') }}
								<NcButton variant="tertiary" @click="openMapping(row)">
									{{ t('filinq', 'Map tags') }}
								</NcButton>
							</template>
						</td>
					</tr>
				</tbody>
			</table>
		</section>

		<section v-if="mapping" class="template-import__section">
			<h3>{{ t('filinq', 'Map the unknown tags of {file}', { file: mapping.file }) }}</h3>
			<FieldMapEditor :template="mapping.template" @saved="mappingSaved" />
		</section>

		<OfficeTemplateUploadModal
			v-if="showUpload"
			:namespace="fields.namespace"
			@close="showUpload = false"
			@created="created" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcNoteCard, NcTextField } from '@nextcloud/vue'
import FieldMapEditor from '../../components/templates/FieldMapEditor.vue'
import OfficeTemplateUploadModal from '../../modals/OfficeTemplateUploadModal.vue'
import { fetchImport, FINISHED_IMPORT, startImport } from '../../services/officeTemplates.js'

/**
 * How long to wait between two progress checks, in milliseconds.
 */
const POLL_MS = 2000

export default {
	name: 'TemplateImport',
	components: { FieldMapEditor, NcButton, NcLoadingIcon, NcNoteCard, NcTextField, OfficeTemplateUploadModal },
	data() {
		return {
			file: null,
			fields: { path: '', namespace: 'filinq', boundRegister: '', boundSchema: '' },
			job: null,
			running: false,
			error: '',
			mapping: null,
			showUpload: false,
			timer: null,
		}
	},

	computed: {
		/**
		 * report.
		 *
		 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
		 */
		report() {
			return (this.job && this.job.report) || []
		},
	},

	beforeUnmount() {
		clearTimeout(this.timer)
	},

	methods: {
		t,
		/**
		 * pick.
		 *
		 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
		 */
		pick(event) {
			this.file = (event.target.files && event.target.files[0]) || null
		},

		/**
		 * resultLabel.
		 *
		 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
		 */
		resultLabel(row) {
			if (row.status === 'failed') {
				return t('filinq', 'Failed: {reason}', { reason: row.reason || '' })
			}
			if (row.status === 'skipped') {
				return t('filinq', 'Skipped')
			}
			return row.kind === 'fragment' ? t('filinq', 'Text fragment') : t('filinq', 'Template')
		},

		/**
		 * start.
		 *
		 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
		 */
		async start() {
			this.error = ''
			this.mapping = null
			const fields = { ...this.fields }
			if (this.file) {
				delete fields.path
			}
			const result = await startImport(this.file, fields)
			if (!result.ok) {
				this.error = result.error || t('filinq', 'The import could not be started.')
				return
			}
			this.job = result.data
			this.running = true
			this.poll()
		},

		/**
		 * poll.
		 *
		 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
		 */
		async poll() {
			const result = await fetchImport(this.job.id)
			if (result.ok) {
				this.job = result.data
			}
			if (!result.ok || FINISHED_IMPORT.includes(this.job.status)) {
				this.running = false
				if (!result.ok) {
					this.error = result.error || t('filinq', 'The progress of the import could not be read.')
				}
				return
			}
			this.timer = setTimeout(() => this.poll(), POLL_MS)
		},

		/**
		 * openMapping.
		 *
		 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
		 */
		openMapping(row) {
			this.mapping = {
				file: row.file,
				template: { id: row.id, tagReport: { unknown: row.unknown || [] }, fieldMap: row.fieldMap || {} },
			}
		},

		/**
		 * mappingSaved.
		 *
		 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
		 */
		mappingSaved(template) {
			const row = this.report.find((line) => line.id === template.id)
			if (row) {
				row.fieldMap = template.fieldMap || {}
			}
			this.mapping = null
		},

		/**
		 * created.
		 *
		 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
		 */
		created(result) {
			this.showUpload = false
			const id = result && result.template && result.template.id
			if (id) {
				this.$router.push({ name: 'TemplateDetail', params: { id } })
			}
		},
	},
}
</script>

<style scoped>
.template-import {
	padding: 16px 24px;
	max-width: 960px;
}

.template-import__section {
	margin-bottom: 24px;
}

.template-import__help {
	color: var(--color-text-maxcontrast);
}

.template-import__form {
	display: flex;
	flex-direction: column;
	gap: 12px;
	max-width: 480px;
}

.template-import__report {
	width: 100%;
	border-collapse: collapse;
}

.template-import__report th,
.template-import__report td {
	text-align: start;
	padding: 4px 8px;
	border-bottom: 1px solid var(--color-border);
}

.template-import__spinner {
	display: inline-block;
	vertical-align: middle;
}
</style>
