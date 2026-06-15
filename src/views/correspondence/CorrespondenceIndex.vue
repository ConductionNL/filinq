<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/letter-correspondence-generation/tasks.md#task-5
-->

<template>
	<div class="correspondence-index">
		<div class="correspondence-index__header">
			<h2>{{ t('docudesk', 'Brieven & correspondentie') }}</h2>
			<p class="correspondence-index__subtitle">
				{{ t('docudesk', 'Generate letters and correspondence from templates with merge data.') }}
			</p>
		</div>

		<NcNoteCard v-if="store.error" type="error">
			{{ store.error }}
		</NcNoteCard>

		<div class="correspondence-index__form">
			<!-- Template selection -->
			<div class="correspondence-index__field">
				<label class="correspondence-index__label" for="corr-template-id">
					{{ t('docudesk', 'Template ID') }} *
				</label>
				<NcTextField id="corr-template-id"
					:value.sync="store.templateId"
					:label="t('docudesk', 'Template UUID')"
					:placeholder="t('docudesk', 'Enter template UUID')"
					required />
			</div>

			<!-- Output format -->
			<div class="correspondence-index__field">
				<label class="correspondence-index__label">
					{{ t('docudesk', 'Output format') }}
				</label>
				<div class="correspondence-index__radio-group">
					<label v-for="fmt in formats"
						:key="fmt.value"
						class="correspondence-index__radio-label">
						<input v-model="store.format"
							type="radio"
							:value="fmt.value">
						{{ fmt.label }}
					</label>
				</div>
			</div>

			<!-- Case reference -->
			<div class="correspondence-index__field">
				<label class="correspondence-index__label" for="corr-case-ref">
					{{ t('docudesk', 'Case reference') }}
				</label>
				<NcTextField id="corr-case-ref"
					:value.sync="store.caseReference"
					:label="t('docudesk', 'Case reference (optional)')"
					:placeholder="t('docudesk', 'e.g. Z/2026/001')" />
			</div>

			<!-- Mode tabs -->
			<div class="correspondence-index__mode-tabs">
				<NcButton :type="!batchMode ? 'primary' : 'secondary'" @click="batchMode = false">
					{{ t('docudesk', 'Single recipient') }}
				</NcButton>
				<NcButton :type="batchMode ? 'primary' : 'secondary'" @click="batchMode = true">
					{{ t('docudesk', 'Batch (multiple recipients)') }}
				</NcButton>
			</div>

			<!-- Single mode: data refs -->
			<template v-if="!batchMode">
				<div class="correspondence-index__field">
					<label class="correspondence-index__label">
						{{ t('docudesk', 'Data references') }}
					</label>
					<div v-for="(ref, idx) in store.dataRefs"
						:key="idx"
						class="correspondence-index__data-ref">
						<NcTextField :value.sync="ref.register"
							:label="t('docudesk', 'Register')"
							:placeholder="t('docudesk', 'e.g. brp')"
							class="correspondence-index__ref-field" />
						<NcTextField :value.sync="ref.schema"
							:label="t('docudesk', 'Schema')"
							:placeholder="t('docudesk', 'e.g. persoon')"
							class="correspondence-index__ref-field" />
						<NcTextField :value.sync="ref.id"
							:label="t('docudesk', 'UUID')"
							:placeholder="t('docudesk', 'Object UUID')"
							class="correspondence-index__ref-field" />
						<NcButton type="tertiary"
							:aria-label="t('docudesk', 'Remove data reference')"
							@click="removeDataRef(idx)">
							✕
						</NcButton>
					</div>
					<NcButton type="secondary" @click="addDataRef">
						+ {{ t('docudesk', 'Add data reference') }}
					</NcButton>
				</div>

				<div class="correspondence-index__actions">
					<NcButton type="primary"
						:disabled="!canGenerate || store.loading"
						@click="generate">
						<template #icon>
							<NcLoadingIcon v-if="store.loading" :size="20" />
						</template>
						{{ store.loading ? t('docudesk', 'Generating…') : t('docudesk', 'Generate letter') }}
					</NcButton>
				</div>
			</template>

			<!-- Batch mode -->
			<template v-else>
				<div class="correspondence-index__field">
					<label class="correspondence-index__label" for="corr-register">
						{{ t('docudesk', 'Register') }}
					</label>
					<NcTextField id="corr-register"
						v-model="batchRegister"
						:label="t('docudesk', 'Register slug')"
						:placeholder="t('docudesk', 'e.g. brp')" />
				</div>
				<div class="correspondence-index__field">
					<label class="correspondence-index__label" for="corr-schema">
						{{ t('docudesk', 'Schema') }}
					</label>
					<NcTextField id="corr-schema"
						v-model="batchSchema"
						:label="t('docudesk', 'Schema slug')"
						:placeholder="t('docudesk', 'e.g. persoon')" />
				</div>
				<div class="correspondence-index__field">
					<label class="correspondence-index__label" for="corr-recipients">
						{{ t('docudesk', 'Recipient UUIDs') }} *
					</label>
					<textarea id="corr-recipients"
						v-model="store.recipientIdsText"
						class="correspondence-index__textarea"
						:placeholder="t('docudesk', 'One UUID per line')"
						rows="8" />
					<p class="correspondence-index__hint">
						{{ t('docudesk', '{count} recipient(s)', { count: store.recipientIds.length }) }}
					</p>
				</div>

				<div class="correspondence-index__actions">
					<NcButton type="primary"
						:disabled="!canGenerateBatch || store.loading"
						@click="generateBatch">
						<template #icon>
							<NcLoadingIcon v-if="store.loading" :size="20" />
						</template>
						{{ store.loading ? t('docudesk', 'Sending…') : t('docudesk', 'Generate batch') }}
					</NcButton>
				</div>

				<!-- Job status -->
				<div v-if="store.jobStatus" class="correspondence-index__job-status">
					<h3>{{ t('docudesk', 'Batch job status') }}</h3>
					<p>
						{{ t('docudesk', 'Status: {status}', { status: store.jobStatus.status }) }}
					</p>
					<p v-if="store.jobStatus.total">
						{{ t('docudesk', '{completed} / {total} completed, {errors} errors', {
							completed: store.jobStatus.completed || 0,
							total: store.jobStatus.total,
							errors: store.jobStatus.errors || 0,
						}) }}
					</p>
					<NcButton v-if="store.jobId && store.jobStatus.status !== 'completed'"
						type="secondary"
						@click="store.pollJobStatus()">
						{{ t('docudesk', 'Refresh status') }}
					</NcButton>
				</div>
			</template>

			<!-- Warnings -->
			<div v-if="store.warnings.length" class="correspondence-index__warnings">
				<NcNoteCard type="warning">
					<ul>
						<li v-for="(w, i) in store.warnings" :key="i">
							{{ w }}
						</li>
					</ul>
				</NcNoteCard>
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcLoadingIcon,
	NcNoteCard,
	NcTextField,
} from '@nextcloud/vue'
import { useCorrespondenceStore } from '../../store/modules/correspondence.js'

export default {
	name: 'CorrespondenceIndex',

	components: {
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
	},

	data() {
		return {
			batchMode: false,
			batchRegister: '',
			batchSchema: '',
			formats: [
				{ value: 'pdf', label: t('docudesk', 'PDF') },
				{ value: 'docx', label: t('docudesk', 'DOCX (editable)') },
				{ value: 'html', label: t('docudesk', 'HTML') },
				{ value: 'email', label: t('docudesk', 'Email body') },
			],
		}
	},

	computed: {
		/**
		 * Correspondence Pinia store.
		 *
		 * @return {object}
		 */
		store() {
			return useCorrespondenceStore()
		},

		/**
		 * True when the single-generate form is valid.
		 *
		 * @return {boolean}
		 */
		canGenerate() {
			return !!this.store.templateId && this.store.dataRefs.length > 0
				&& this.store.dataRefs.every((r) => r.register && r.schema && r.id)
		},

		/**
		 * True when the batch-generate form is valid.
		 *
		 * @return {boolean}
		 */
		canGenerateBatch() {
			return !!this.store.templateId && this.store.recipientIds.length > 0
				&& !!this.batchRegister && !!this.batchSchema
		},
	},

	methods: {
		t,

		/**
		 * Add a blank data reference row.
		 *
		 * @return {void}
		 */
		addDataRef() {
			this.store.dataRefs.push({ register: '', schema: '', id: '' })
		},

		/**
		 * Remove a data reference row by index.
		 *
		 * @param {number} idx Row index.
		 * @return {void}
		 */
		removeDataRef(idx) {
			this.store.dataRefs.splice(idx, 1)
		},

		/**
		 * Trigger single generation and download.
		 *
		 * @return {Promise<void>}
		 */
		async generate() {
			const filename = 'brief-' + (this.store.caseReference || 'correspondentie')
			await this.store.generate(filename)
		},

		/**
		 * Trigger batch generation.
		 *
		 * @return {Promise<void>}
		 */
		async generateBatch() {
			await this.store.generateBatch(this.batchRegister, this.batchSchema)
		},
	},
}
</script>

<style scoped>
.correspondence-index {
	max-width: 800px;
	padding: 24px;
}

.correspondence-index__header {
	margin-bottom: 24px;
}

.correspondence-index__subtitle {
	color: var(--color-text-maxcontrast);
	margin-top: 4px;
}

.correspondence-index__form {
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.correspondence-index__field {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.correspondence-index__label {
	font-weight: 600;
	color: var(--color-main-text);
}

.correspondence-index__radio-group {
	display: flex;
	gap: 16px;
	flex-wrap: wrap;
}

.correspondence-index__radio-label {
	display: flex;
	align-items: center;
	gap: 6px;
	cursor: pointer;
}

.correspondence-index__mode-tabs {
	display: flex;
	gap: 8px;
}

.correspondence-index__data-ref {
	display: flex;
	gap: 8px;
	align-items: flex-end;
	margin-bottom: 8px;
}

.correspondence-index__ref-field {
	flex: 1;
}

.correspondence-index__textarea {
	width: 100%;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: 8px;
	font-family: monospace;
	background: var(--color-main-background);
	color: var(--color-main-text);
	resize: vertical;
}

.correspondence-index__hint {
	color: var(--color-text-maxcontrast);
	font-size: 12px;
}

.correspondence-index__actions {
	display: flex;
	gap: 12px;
}

.correspondence-index__job-status {
	background: var(--color-background-dark);
	border-radius: var(--border-radius);
	padding: 16px;
}

.correspondence-index__warnings ul {
	margin: 0;
	padding-left: 16px;
}
</style>
