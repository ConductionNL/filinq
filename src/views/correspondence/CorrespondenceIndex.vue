<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/letter-correspondence-generation/tasks.md#task-5
-->

<template>
	<div class="correspondence-index">
		<div class="correspondence-index__header">
			<h2>{{ t('filinq', 'Letters & correspondence') }}</h2>
			<p class="correspondence-index__subtitle">
				{{
					t(
						'filinq',
						'Generate letters and correspondence from templates with merge data.',
					)
				}}
			</p>
		</div>

		<NcNoteCard v-if="store.error" type="error">
			{{ store.error }}
		</NcNoteCard>

		<div class="correspondence-index__form">
			<!-- Template selection -->
			<div class="correspondence-index__field">
				<label class="correspondence-index__label" for="corr-template-id">
					{{ t('filinq', 'Template ID') }} *
				</label>
				<NcTextField
					id="corr-template-id"
					v-model="store.templateId"
					:label="t('filinq', 'Template UUID')"
					:placeholder="t('filinq', 'Enter template UUID')"
					required />
			</div>

			<!-- Output format -->
			<div class="correspondence-index__field">
				<label class="correspondence-index__label">
					{{ t('filinq', 'Output format') }}
				</label>
				<div class="correspondence-index__radio-group">
					<label
						v-for="fmt in formats"
						:key="fmt.value"
						class="correspondence-index__radio-label"
						:class="{ 'correspondence-index__radio-label--disabled': fmt.disabled }"
						:title="fmt.reason">
						<input
							v-model="store.format"
							type="radio"
							:value="fmt.value"
							:disabled="fmt.disabled"
							:aria-describedby="fmt.disabled ? 'corr-format-reason-' + fmt.value : undefined" />
						{{ fmt.label }}
						<span
							v-if="fmt.disabled"
							:id="'corr-format-reason-' + fmt.value"
							class="correspondence-index__format-reason">
							({{ fmt.reason }})
						</span>
					</label>
				</div>
				<p v-if="formatsError" class="correspondence-index__format-reason">
					{{ formatsError }}
				</p>
			</div>

			<!-- Case reference -->
			<div class="correspondence-index__field">
				<label class="correspondence-index__label" for="corr-case-ref">
					{{ t('filinq', 'Case reference') }}
				</label>
				<NcTextField
					id="corr-case-ref"
					v-model="store.caseReference"
					:label="t('filinq', 'Case reference (optional)')"
					:placeholder="t('filinq', 'e.g. Z/2026/001')" />
			</div>

			<!-- Mode tabs -->
			<div class="correspondence-index__mode-tabs">
				<NcButton
					:variant="!batchMode ? 'primary' : 'secondary'"
					@click="batchMode = false">
					{{ t('filinq', 'Single recipient') }}
				</NcButton>
				<NcButton
					:variant="batchMode ? 'primary' : 'secondary'"
					@click="batchMode = true">
					{{ t('filinq', 'Batch (multiple recipients)') }}
				</NcButton>
			</div>

			<!-- Single mode: data refs -->
			<template v-if="!batchMode">
				<div class="correspondence-index__field">
					<label class="correspondence-index__label">
						{{ t('filinq', 'Data references') }}
					</label>
					<div
						v-for="(ref, idx) in store.dataRefs"
						:key="idx"
						class="correspondence-index__data-ref">
						<NcTextField
							v-model="ref.register"
							:label="t('filinq', 'Register')"
							:placeholder="t('filinq', 'e.g. brp')"
							class="correspondence-index__ref-field" />
						<NcTextField
							v-model="ref.schema"
							:label="t('filinq', 'Schema')"
							:placeholder="t('filinq', 'e.g. persoon')"
							class="correspondence-index__ref-field" />
						<NcTextField
							v-model="ref.id"
							:label="t('filinq', 'UUID')"
							:placeholder="t('filinq', 'Object UUID')"
							class="correspondence-index__ref-field" />
						<NcButton
							variant="tertiary"
							:aria-label="t('filinq', 'Remove data reference')"
							@click="removeDataRef(idx)">
							✕
						</NcButton>
					</div>
					<NcButton variant="secondary" @click="addDataRef">
						+ {{ t('filinq', 'Add data reference') }}
					</NcButton>
				</div>

				<div class="correspondence-index__actions">
					<NcButton
						variant="primary"
						:disabled="!canGenerate || store.loading"
						@click="generate">
						<template #icon>
							<NcLoadingIcon v-if="store.loading" :size="20" />
						</template>
						{{
							store.loading
								? t('filinq', 'Generating…')
								: t('filinq', 'Generate letter')
						}}
					</NcButton>
					<NcButton
						variant="secondary"
						:disabled="!canGenerate || printing"
						@click="sendToPrint">
						{{ t('filinq', 'Send to print') }}
					</NcButton>
				</div>
			</template>

			<!-- Batch mode -->
			<template v-else>
				<div class="correspondence-index__field">
					<label class="correspondence-index__label" for="corr-register">
						{{ t('filinq', 'Register') }}
					</label>
					<NcTextField
						id="corr-register"
						v-model="batchRegister"
						:label="t('filinq', 'Register slug')"
						:placeholder="t('filinq', 'e.g. brp')" />
				</div>
				<div class="correspondence-index__field">
					<label class="correspondence-index__label" for="corr-schema">
						{{ t('filinq', 'Schema') }}
					</label>
					<NcTextField
						id="corr-schema"
						v-model="batchSchema"
						:label="t('filinq', 'Schema slug')"
						:placeholder="t('filinq', 'e.g. persoon')" />
				</div>
				<div class="correspondence-index__field">
					<label class="correspondence-index__label" for="corr-recipients">
						{{ t('filinq', 'Recipient UUIDs') }} *
					</label>
					<textarea
						id="corr-recipients"
						v-model="store.recipientIdsText"
						class="correspondence-index__textarea"
						:placeholder="t('filinq', 'One UUID per line')"
						rows="8" />
					<p class="correspondence-index__hint">
						{{
							t('filinq', '{count} recipient(s)', {
								count: store.recipientIds.length,
							})
						}}
					</p>
				</div>

				<div class="correspondence-index__actions">
					<NcButton
						variant="primary"
						:disabled="!canGenerateBatch || store.loading"
						@click="generateBatch">
						<template #icon>
							<NcLoadingIcon v-if="store.loading" :size="20" />
						</template>
						{{
							store.loading
								? t('filinq', 'Sending…')
								: t('filinq', 'Generate batch')
						}}
					</NcButton>
					<NcButton
						variant="secondary"
						:disabled="!canGenerateBatch || printing"
						@click="sendToPrint">
						{{ t('filinq', 'Send to print') }}
					</NcButton>
				</div>

				<!-- Job status -->
				<div v-if="store.jobStatus" class="correspondence-index__job-status">
					<h3>{{ t('filinq', 'Batch job status') }}</h3>
					<p>
						{{
							t('filinq', 'Status: {status}', {
								status: store.jobStatus.status,
							})
						}}
					</p>
					<p v-if="store.jobStatus.total">
						{{
							t(
								'filinq',
								'{completed} / {total} completed, {errors} errors',
								{
									completed: store.jobStatus.completed || 0,
									total: store.jobStatus.total,
									errors: store.jobStatus.errors || 0,
								},
							)
						}}
					</p>
					<NcButton
						v-if="store.jobId && store.jobStatus.status !== 'completed'"
						variant="secondary"
						@click="store.pollJobStatus()">
						{{ t('filinq', 'Refresh status') }}
					</NcButton>
				</div>
			</template>

			<NcNoteCard v-if="printResult" :type="printResult.type">
				<p>{{ printResult.message }}</p>
				<router-link
					v-if="printResult.type === 'success'"
					:to="{ name: 'PrintJobs' }">
					{{ t('filinq', 'Open print jobs') }}
				</router-link>
			</NcNoteCard>
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
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcLoadingIcon, NcNoteCard, NcTextField } from '@nextcloud/vue'
import { fetchFormatMatrix, formatOptions, usableFormat } from '../../services/formatMatrix.js'
import { buildPrintRequest } from '../../services/printJobs.js'
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
			printing: false,
			printResult: null,
			formats: [],
			formatsError: '',
		}
	},

	async mounted() {
		await this.loadFormats()
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
			return (
				!!this.store.templateId
				&& this.store.dataRefs.length > 0
				&& this.store.dataRefs.every((r) => r.register && r.schema && r.id)
			)
		},

		/**
		 * True when the batch-generate form is valid.
		 *
		 * @return {boolean}
		 */
		canGenerateBatch() {
			return (
				!!this.store.templateId
				&& this.store.recipientIds.length > 0
				&& !!this.batchRegister
				&& !!this.batchSchema
			)
		},
	},

	methods: {
		/**
		 * Offer the formats the server can make now.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/multi-format-output/tasks.md#task-4.1
		 */
		async loadFormats() {
			try {
				this.formats = formatOptions(await fetchFormatMatrix('correspondence'))
				this.store.format = usableFormat(this.store.format, this.formats)
				this.formatsError = ''
			} catch (e) {
				this.formats = []
				this.formatsError = t('filinq', 'Could not load the output formats this server can make.')
			}
		},

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
			const filename =
				'brief-' + (this.store.caseReference || 'correspondentie')
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

		/**
		 * Send the letter, or one letter per recipient, to print as one job.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.4
		 */
		async sendToPrint() {
			const body = buildPrintRequest({
				templateId: this.store.templateId,
				batchMode: this.batchMode,
				dataRefs: this.store.dataRefs,
				register: this.batchRegister,
				schema: this.batchSchema,
				recipientIds: this.store.recipientIds,
				caseReference: this.store.caseReference,
			})
			this.printing = true
			this.printResult = null
			try {
				await axios.post(generateUrl('/apps/filinq/api/print/batch'), body)
				this.printResult = {
					type: 'success',
					message: t('filinq', 'Your letters went to print as one job.'),
				}
			} catch {
				this.printResult = {
					type: 'error',
					message: t('filinq', 'The letters could not be sent to print.'),
				}
			} finally {
				this.printing = false
			}
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

.correspondence-index__radio-label--disabled {
	color: var(--color-text-maxcontrast);
}

.correspondence-index__format-reason {
	color: var(--color-text-maxcontrast);
	font-size: var(--font-size-small, 13px);
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
	padding-inline-start: 16px;
}
</style>
