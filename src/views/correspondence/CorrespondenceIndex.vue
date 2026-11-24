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
						:class="{
							'correspondence-index__radio-label--disabled':
								fmt.disabled,
						}"
						:title="fmt.reason">
						<input
							v-model="store.format"
							type="radio"
							:value="fmt.value"
							:disabled="fmt.disabled"
							:aria-describedby="
								fmt.disabled
									? 'corr-format-reason-' + fmt.value
									: undefined
							" />
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
						v-if="templateEditable"
						variant="secondary"
						:disabled="!canGenerate || opening || store.loading"
						data-testid="correspondence-open-for-editing"
						@click="openForEditing">
						{{ opening ? t('filinq', 'Opening…') : t('filinq', 'Open for editing') }}
					</NcButton>
					<NcButton
						variant="secondary"
						:disabled="!canGenerate || printing"
						@click="sendToPrint">
						{{ t('filinq', 'Send to print') }}
					</NcButton>
					<NcButton
						variant="secondary"
						:disabled="!canGenerate || store.loading"
						data-testid="correspondence-send-email"
						@click="openSendDialog">
						{{ t('filinq', 'Send by email') }}
					</NcButton>
				</div>
				<NcNoteCard v-if="openError" type="error" data-testid="correspondence-open-error">
					{{ openError }}
				</NcNoteCard>
				<NcNoteCard
					v-if="store.lastSent"
					type="success"
					data-testid="correspondence-sent">
					{{ t('filinq', 'Sent to {address}', { address: store.lastSent.to }) }}
				</NcNoteCard>
				<CorrespondenceSendDialog
					v-if="sendDialogOpen"
					:request="sendRequestBody"
					:saving="store.loading"
					:error="sendError"
					@confirm="sendByEmail"
					@cancel="sendDialogOpen = false" />
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
import CorrespondenceSendDialog from '../../dialogs/CorrespondenceSendDialog.vue'
import { createDraft, templateEditability } from '../../services/drafts.js'
import {
	fetchFormatMatrix,
	formatOptions,
	usableFormat,
} from '../../services/formatMatrix.js'
import { buildPrintRequest } from '../../services/printJobs.js'
import { useCorrespondenceStore } from '../../store/modules/correspondence.js'

export default {
	name: 'CorrespondenceIndex',

	components: {
		CorrespondenceSendDialog,
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
			// Open for editing (generate-draft-and-attended-edit REQ-GDA-001):
			// hidden for a template with a plain-language version (REQ-GDA-005).
			templateEditable: true,
			opening: false,
			openError: '',
			// letter-email-sending: the send-by-email confirm dialog.
			sendDialogOpen: false,
			sendError: '',
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
		 * The letter the send dialog previews and the store sends.
		 *
		 * @return {object}
		 */
		sendRequestBody() {
			return this.store.sendBody
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

	watch: {
		/**
		 * Recheck whether the chosen template may be opened for editing.
		 *
		 * @spec openspec/changes/archive/2026-11-20-generate-draft-and-attended-edit/tasks.md#task-3.3
		 */
		'store.templateId': function() {
			this.checkEditability()
		},
	},

	async mounted() {
		await this.loadFormats()
		await this.checkEditability()
	},

	methods: {
		/**
		 * Offer the formats the server can make now.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-4.1
		 */
		async loadFormats() {
			try {
				this.formats = formatOptions(
					await fetchFormatMatrix('correspondence'),
				)
				this.store.format = usableFormat(this.store.format, this.formats)
				this.formatsError = ''
			} catch {
				this.formats = []
				this.formatsError = t(
					'filinq',
					'Could not load the output formats this server can make.',
				)
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
		 * Whether the chosen template may be opened for editing. A template
		 * with a plain-language version is generated directly; one that cannot
		 * be read keeps the action, and the server refuses a plain one.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-20-generate-draft-and-attended-edit/tasks.md#task-3.3
		 */
		async checkEditability() {
			const templateId = this.store.templateId
			const { editable } = await templateEditability(templateId)
			if (templateId === this.store.templateId) {
				this.templateEditable = editable || !templateId
			}
		},

		/**
		 * Render the letter into a draft and open it in the draft editor.
		 * Nothing is filed or logged until the clerk finishes it.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-20-generate-draft-and-attended-edit/tasks.md#task-3.3
		 */
		async openForEditing() {
			this.opening = true
			this.openError = ''
			const result = await createDraft({
				templateId: this.store.templateId,
				dataRefs: this.store.dataRefs,
				options: {
					format: this.store.format,
					huisstijlId: this.store.huisstijlId || undefined,
					caseReference: this.store.caseReference || undefined,
				},
				origin: 'correspondence',
			})
			this.opening = false
			if (!result.ok) {
				if (result.status === 409) {
					this.templateEditable = false
				}
				this.openError = result.status === 409
					? t('filinq', 'This template has a plain-language version. Generate it directly.')
					: (result.error || t('filinq', 'The letter could not be opened for editing.'))
				return
			}
			this.$router.push({ name: 'DraftEditor', params: { id: String(result.data?.id || '') } })
		},

		/**
		 * Open the send-by-email dialog for the current letter.
		 *
		 * @return {void}
		 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-3
		 */
		openSendDialog() {
			this.sendError = ''
			this.sendDialogOpen = true
		},

		/**
		 * Send the letter the officer confirmed; a refusal keeps the dialog open with its reason.
		 *
		 * @param {{attachPdf: boolean}} choice What the dialog confirmed.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-3
		 */
		async sendByEmail(choice) {
			this.sendError = ''
			try {
				await this.store.sendByEmail(Boolean(choice?.attachPdf))
				this.sendDialogOpen = false
			} catch (err) {
				this.sendError = err.message
			}
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
