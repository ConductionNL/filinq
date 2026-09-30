<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
-->

<template>
	<NcModal :show="show" labelId="bulk-send-title" size="normal" @close="close">
		<div class="bulk-send">
			<h2 id="bulk-send-title">
				{{ t('filinq', 'Send to many recipients') }}
			</h2>

			<template v-if="!batch">
				<p>
					{{
						t(
							'filinq',
							'Upload a .csv or .xlsx list with a column "email" or "userId", and optionally "name". Each row gets its own signing request for {document}. You see a report first; nothing is sent until you confirm.',
							{ document: settings.documentName },
						)
					}}
				</p>
				<div class="bulk-send__field">
					<label for="bulk-send-title-input">{{
						t('filinq', 'Name of this send')
					}}</label>
					<input
						id="bulk-send-title-input"
						v-model="title"
						type="text"
						maxlength="255" />
				</div>
				<div class="bulk-send__field">
					<label for="bulk-send-file">{{
						t('filinq', 'Recipient list')
					}}</label>
					<input
						id="bulk-send-file"
						type="file"
						accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
						@change="pick" />
				</div>
			</template>

			<template v-else>
				<p class="bulk-send__summary" role="status">
					{{
						t(
							'filinq',
							'{accepted} of {total} rows can be sent. {rejected} rows are left out.',
							{
								accepted: batch.acceptedRows,
								total: batch.totalRows,
								rejected: (batch.rejectedRows || []).length,
							},
						)
					}}
				</p>
				<p v-if="batch.status !== 'ready'" class="bulk-send__progress">
					{{ statusLabel(batch.status) }}:
					{{
						t('filinq', '{done} of {accepted} rows processed', {
							done: batch.processedRows || 0,
							accepted: batch.acceptedRows,
						})
					}}
				</p>
				<table
					v-if="(batch.rejectedRows || []).length"
					class="bulk-send__rejected">
					<caption>
						{{
							t('filinq', 'Rows left out')
						}}
					</caption>
					<thead>
						<tr>
							<th scope="col">
								{{ t('filinq', 'Row') }}
							</th>
							<th scope="col">
								{{ t('filinq', 'Reason') }}
							</th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-for="rejected in batch.rejectedRows"
							:key="rejected.row + rejected.reason">
							<td>{{ rejected.row }}</td>
							<td>{{ reasonLabel(rejected) }}</td>
						</tr>
					</tbody>
				</table>
			</template>

			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>

			<div class="bulk-send__actions">
				<NcButton variant="secondary" @click="close">
					{{ t('filinq', 'Close') }}
				</NcButton>
				<NcButton
					v-if="!batch"
					variant="primary"
					:disabled="busy || !file"
					@click="upload">
					{{ t('filinq', 'Check the list') }}
				</NcButton>
				<NcButton
					v-if="batch && batch.status === 'ready'"
					variant="primary"
					:disabled="busy || !batch.acceptedRows"
					@click="confirm">
					{{
						t('filinq', 'Send to {count} recipients', {
							count: batch.acceptedRows,
						})
					}}
				</NcButton>
				<NcButton
					v-if="batch && !isFinished(batch)"
					variant="tertiary"
					:disabled="busy"
					@click="cancel">
					{{ t('filinq', 'Cancel this send') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcModal, NcNoteCard } from '@nextcloud/vue'
import {
	cancelBatch,
	confirmBatch,
	errorMessage,
	fetchBatch,
	isFinished,
	reasonLabel,
	statusLabel,
	uploadList,
} from '../services/bulkSend.js'

/**
 * Bulk send (REQ-DDBSF-002): upload a list, read the report, confirm, follow
 * progress. The document and its level come from the signing request form.
 */
export default {
	name: 'BulkSendModal',
	components: { NcButton, NcModal, NcNoteCard },
	props: {
		show: { type: Boolean, default: false },
		settings: { type: Object, required: true },
	},

	emits: ['close'],

	data() {
		return {
			title: '',
			file: null,
			batch: null,
			busy: false,
			error: null,
			timer: null,
		}
	},

	beforeUnmount() {
		clearTimeout(this.timer)
	},

	methods: {
		t,
		isFinished,
		reasonLabel,
		statusLabel,

		/**
		 * Keep the chosen file.
		 *
		 * @param {Event} event The change event
		 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
		 */
		pick(event) {
			this.file = event.target.files?.[0] || null
		},

		/**
		 * Upload the list and show the report.
		 *
		 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
		 */
		async upload() {
			await this.run(async () => {
				this.batch = await uploadList(this.file, {
					...this.settings,
					title: this.title,
				})
			})
		},

		/**
		 * Confirm and follow progress.
		 *
		 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
		 */
		async confirm() {
			await this.run(async () => {
				this.batch = await confirmBatch(this.batch.uuid)
				this.follow()
			})
		},

		/**
		 * Cancel the batch.
		 *
		 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
		 */
		async cancel() {
			await this.run(async () => {
				clearTimeout(this.timer)
				this.batch = await cancelBatch(this.batch.uuid)
			})
		},

		/**
		 * Read the batch again every two seconds until it is finished.
		 *
		 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
		 */
		follow() {
			clearTimeout(this.timer)
			if (!this.show || isFinished(this.batch)) {
				return
			}
			this.timer = setTimeout(async () => {
				try {
					this.batch = await fetchBatch(this.batch.uuid)
				} catch (error) {
					this.error = errorMessage(error)
					return
				}
				this.follow()
			}, 2000)
		},

		/**
		 * Run one call with the busy flag and the error note.
		 *
		 * @param {function(): Promise<void>} call The call
		 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
		 */
		async run(call) {
			this.busy = true
			this.error = null
			try {
				await call()
			} catch (error) {
				this.error = errorMessage(error)
			} finally {
				this.busy = false
			}
		},

		/**
		 * Stop following and close; a confirmed batch keeps sending on the server.
		 *
		 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
		 */
		close() {
			clearTimeout(this.timer)
			this.batch = null
			this.file = null
			this.error = null
			this.$emit('close')
		},
	},
}
</script>

<style scoped>
.bulk-send {
	padding: calc(var(--default-grid-baseline) * 5);
}

.bulk-send__field {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	margin-block: calc(var(--default-grid-baseline) * 3);
}

.bulk-send__rejected {
	width: 100%;
	margin-block: calc(var(--default-grid-baseline) * 3);
	border-collapse: collapse;
}

.bulk-send__rejected th,
.bulk-send__rejected td {
	padding: var(--default-grid-baseline);
	border-bottom: 1px solid var(--color-border);
	text-align: start;
}

.bulk-send__actions {
	display: flex;
	justify-content: flex-end;
	gap: calc(var(--default-grid-baseline) * 2);
	margin-block-start: calc(var(--default-grid-baseline) * 4);
}
</style>
