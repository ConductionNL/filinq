<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
-->

<template>
	<NcModal
		:show="show"
		labelId="signing-envelope-title"
		size="normal"
		@close="close">
		<div class="signing-envelope">
			<h2 id="signing-envelope-title">
				{{ t('filinq', 'Send several documents together') }}
			</h2>

			<template v-if="!envelope">
				<p>
					{{
						t(
							'filinq',
							'The signers on the form sign every document in one go and get one notification. Each document keeps its own signed file and audit trail.',
						)
					}}
				</p>
				<div class="signing-envelope__field">
					<label for="signing-envelope-name">{{
						t('filinq', 'Name of this envelope')
					}}</label>
					<input
						id="signing-envelope-name"
						v-model="title"
						type="text"
						maxlength="255" />
				</div>
				<fieldset class="signing-envelope__documents">
					<legend>{{ t('filinq', 'Documents') }}</legend>
					<div
						v-for="(row, index) in rows"
						:key="index"
						class="signing-envelope__row">
						<div class="signing-envelope__field">
							<label :for="`envelope-document-${index}-id`">{{
								t('filinq', 'Document File ID')
							}}</label>
							<input
								:id="`envelope-document-${index}-id`"
								v-model="row.documentFileId"
								type="text" />
						</div>
						<div class="signing-envelope__field">
							<label :for="`envelope-document-${index}-name`">{{
								t('filinq', 'Document Name')
							}}</label>
							<input
								:id="`envelope-document-${index}-name`"
								v-model="row.documentName"
								type="text" />
						</div>
						<NcButton
							variant="tertiary"
							:disabled="rows.length <= 2"
							:aria-label="
								t('filinq', 'Remove document {number}', {
									number: index + 1,
								})
							"
							@click="rows.splice(index, 1)">
							{{ t('filinq', 'Remove') }}
						</NcButton>
					</div>
					<NcButton variant="secondary" @click="addRow">
						{{ t('filinq', 'Add document') }}
					</NcButton>
				</fieldset>
			</template>

			<template v-else>
				<p role="status">
					{{
						t(
							'filinq',
							'Envelope sent: {count} documents are waiting for signatures.',
							{ count: envelope.documentCount },
						)
					}}
				</p>
				<ul class="signing-envelope__members">
					<li v-for="member in envelope.members" :key="member.id">
						<router-link
							:to="{
								name: 'SigningRequestDetail',
								params: { id: member.id },
							}">
							{{ member.documentName }}
						</router-link>
					</li>
				</ul>
			</template>

			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>

			<div class="signing-envelope__actions">
				<NcButton
					v-if="!envelope"
					variant="primary"
					:disabled="busy || documents.length < 2"
					@click="send">
					{{
						t('filinq', 'Send {count} documents', {
							count: documents.length,
						})
					}}
				</NcButton>
				<NcButton variant="tertiary" @click="close">
					{{ t('filinq', 'Close') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcModal, NcNoteCard } from '@nextcloud/vue'
import {
	createEnvelope,
	envelopeError,
	toDocuments,
} from '../services/signingEnvelope.js'

/**
 * Envelope creation (REQ-DDBSF-004/005): several documents, the signers and
 * level of the signing request form, one ceremony.
 */
export default {
	name: 'SigningEnvelopeModal',
	components: { NcButton, NcModal, NcNoteCard },
	props: {
		show: { type: Boolean, default: false },
		/**
		 * What every document shares: signers, signatureLevel, signingMode, requiredAssurance,
		 * and the form's document as the first row.
		 */
		settings: { type: Object, required: true },
	},

	emits: ['close'],

	data() {
		return {
			title: '',
			rows: [
				{
					documentFileId: this.settings.documentFileId || '',
					documentName: this.settings.documentName || '',
				},
				{ documentFileId: '', documentName: '' },
			],

			envelope: null,
			busy: false,
			error: null,
		}
	},

	computed: {
		/**
		 * The documents that will be sent.
		 *
		 * @return {Array<object>}
		 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
		 */
		documents() {
			return toDocuments(this.rows)
		},
	},

	methods: {
		t,

		/**
		 * Add an empty document row.
		 *
		 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
		 */
		addRow() {
			this.rows.push({ documentFileId: '', documentName: '' })
		},

		/**
		 * Create the envelope and show its documents.
		 *
		 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
		 */
		async send() {
			this.busy = true
			this.error = null
			try {
				this.envelope = await createEnvelope({
					title: this.title,
					documents: this.documents,
					signers: this.settings.signers,
					signatureLevel: this.settings.signatureLevel,
					signingMode: this.settings.signingMode,
					requiredAssurance: this.settings.requiredAssurance,
				})
			} catch (error) {
				this.error = envelopeError(error)
			} finally {
				this.busy = false
			}
		},

		/**
		 * Close the dialog.
		 *
		 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
		 */
		close() {
			this.$emit('close')
		},
	},
}
</script>

<style scoped>
.signing-envelope {
	padding: calc(var(--default-grid-baseline) * 5);
}

.signing-envelope__field {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	margin-bottom: calc(var(--default-grid-baseline) * 3);
	flex: 1 1 160px;
}

.signing-envelope__field input {
	padding: calc(var(--default-grid-baseline) * 2);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
}

.signing-envelope__documents {
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: calc(var(--default-grid-baseline) * 3);
	margin-bottom: calc(var(--default-grid-baseline) * 4);
}

.signing-envelope__row {
	display: flex;
	flex-wrap: wrap;
	align-items: flex-end;
	gap: calc(var(--default-grid-baseline) * 2);
}

.signing-envelope__actions {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 2);
}
</style>
