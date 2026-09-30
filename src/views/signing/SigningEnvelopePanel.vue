<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-batch-and-envelope-surfaces-are-first-class-ui-req-ddbsf-005
@visual exclude Not a page: a panel inside the signing request detail page, shown only for a request that belongs to an envelope.
	Its behaviour is covered by the envelope e2e in tests/e2e/spec-coverage/bulk-signing-field-builder.spec.ts.
-->

<template>
	<section
		v-if="envelope"
		class="signing-envelope-panel"
		aria-labelledby="signing-envelope-panel-title">
		<h3 id="signing-envelope-panel-title">
			{{ t('filinq', 'Envelope: {title}', { title: envelope.title }) }}
		</h3>
		<p>
			<strong>{{ t('filinq', 'Status') }}</strong
			>: {{ statusLabel(envelope.status) }}
		</p>
		<table class="signing-envelope-panel__members">
			<thead>
				<tr>
					<th scope="col">
						{{ t('filinq', 'Document') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Status') }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="member in envelope.members" :key="member.id">
					<td>
						<router-link
							:to="{
								name: 'SigningRequestDetail',
								params: { id: member.id },
							}">
							{{ member.documentName }}
						</router-link>
					</td>
					<td>{{ member.status }}</td>
				</tr>
			</tbody>
		</table>
		<p v-if="summary" role="status">
			{{
				t('filinq', 'You signed {count} documents.', {
					count: summary.signed,
				})
			}}
		</p>
		<NcNoteCard v-if="summary && summary.refused.length" type="warning">
			<p>{{ t('filinq', 'These documents were not signed:') }}</p>
			<ul>
				<li v-for="(reason, index) in summary.refused" :key="index">
					{{ reason }}
				</li>
			</ul>
		</NcNoteCard>
		<NcNoteCard v-if="error" type="error">
			{{ error }}
		</NcNoteCard>
		<div class="signing-envelope-panel__actions">
			<NcButton
				v-if="canSignAll(envelope, uid)"
				variant="primary"
				:disabled="busy"
				@click="signAll">
				{{ t('filinq', 'Sign all documents') }}
			</NcButton>
			<NcButton
				v-if="canCancel(envelope, uid)"
				variant="tertiary"
				:disabled="busy"
				@click="cancel">
				{{ t('filinq', 'Cancel the envelope') }}
			</NcButton>
		</div>
	</section>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcNoteCard } from '@nextcloud/vue'
import {
	canCancel,
	cancelEnvelope,
	canSignAll,
	envelopeError,
	envelopeStatusLabel,
	fetchEnvelope,
	signAllInEnvelope,
	summariseSignAll,
} from '../../services/signingEnvelope.js'

/**
 * The envelope a signing request belongs to (REQ-DDBSF-004/005): its
 * documents with their statuses, the rolled-up status, "sign all" for a
 * signer and cancel for the sender.
 */
export default {
	name: 'SigningEnvelopePanel',
	components: { NcButton, NcNoteCard },
	props: {
		envelopeId: { type: String, required: true },
	},

	emits: ['changed'],

	data() {
		return {
			envelope: null,
			summary: null,
			busy: false,
			error: null,
			uid: getCurrentUser()?.uid || '',
		}
	},

	watch: {
		envelopeId: {
			immediate: true,
			/**
			 * Load the envelope whenever the request changes.
			 *
			 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-batch-and-envelope-surfaces-are-first-class-ui-req-ddbsf-005
			 */
			handler() {
				this.load()
			},
		},
	},

	methods: {
		t,
		canCancel,
		canSignAll,
		statusLabel: envelopeStatusLabel,

		/**
		 * Read the envelope.
		 *
		 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-batch-and-envelope-surfaces-are-first-class-ui-req-ddbsf-005
		 */
		async load() {
			this.error = null
			try {
				this.envelope = await fetchEnvelope(this.envelopeId)
			} catch (error) {
				this.envelope = null
				this.error = envelopeError(error)
			}
		},

		/**
		 * Sign every document still waiting for the user.
		 *
		 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
		 */
		async signAll() {
			this.busy = true
			this.error = null
			try {
				const outcome = await signAllInEnvelope(this.envelopeId)
				this.envelope = outcome.envelope
				this.summary = summariseSignAll(outcome.results)
				this.$emit('changed')
			} catch (error) {
				this.error = envelopeError(error)
			} finally {
				this.busy = false
			}
		},

		/**
		 * Cancel the envelope.
		 *
		 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
		 */
		async cancel() {
			this.busy = true
			this.error = null
			try {
				this.envelope = await cancelEnvelope(this.envelopeId)
				this.$emit('changed')
			} catch (error) {
				this.error = envelopeError(error)
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.signing-envelope-panel {
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: calc(var(--default-grid-baseline) * 3);
	margin-bottom: calc(var(--default-grid-baseline) * 5);
}

.signing-envelope-panel__members {
	width: 100%;
	border-collapse: collapse;
	margin-bottom: calc(var(--default-grid-baseline) * 3);
}

.signing-envelope-panel__members th,
.signing-envelope-panel__members td {
	padding: calc(var(--default-grid-baseline) * 2);
	text-align: start;
	border-bottom: 1px solid var(--color-border);
}

.signing-envelope-panel__actions {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 2);
}
</style>
