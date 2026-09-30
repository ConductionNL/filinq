<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
-->

<template>
	<NcDialog
		:name="t('filinq', 'Send for signature')"
		:noClose="busy"
		size="normal"
		@closing="$emit('close')">
		<template #default>
			<p>
				{{
					t(
						'filinq',
						'This creates an ordinary signing request for one of the contract documents. The contract keeps a link to it and shows its status.',
					)
				}}
			</p>
			<NcSelect
				v-model="document"
				:options="documentOptions"
				label="label"
				:inputLabel="t('filinq', 'Document')"
				data-testid="contract-sign-document" />
			<NcTextField
				v-model="documentName"
				:label="t('filinq', 'Document name')"
				data-testid="contract-sign-name" />
			<NcSelect
				v-model="level"
				:options="levelOptions"
				label="label"
				:inputLabel="t('filinq', 'Signature level')" />
			<fieldset class="contract-sign__signers">
				<legend>{{ t('filinq', 'Signers') }}</legend>
				<div
					v-for="(signer, index) in signerRows"
					:key="index"
					class="contract-sign__row">
					<NcTextField
						v-model="signer.displayName"
						:label="t('filinq', 'Name')" />
					<NcTextField
						v-model="signer.email"
						type="email"
						:label="t('filinq', 'E-mail')" />
					<NcTextField
						v-model="signer.userId"
						:label="t('filinq', 'Nextcloud user')"
						data-testid="contract-sign-user" />
					<NcButton
						variant="tertiary"
						:disabled="signerRows.length === 1"
						:aria-label="
							t('filinq', 'Remove signer {number}', { number: index + 1 })
						"
						@click="signerRows.splice(index, 1)">
						{{ t('filinq', 'Remove') }}
					</NcButton>
				</div>
				<NcButton variant="secondary" @click="signerRows.push(emptySignerRow())">
					{{ t('filinq', 'Add signer') }}
				</NcButton>
			</fieldset>
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
		</template>
		<template #actions>
			<NcButton :disabled="busy" @click="$emit('close')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="!ready"
				data-testid="contract-sign-confirm"
				@click="onConfirm">
				{{ t('filinq', 'Send for signature') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcDialog,
	NcNoteCard,
	NcSelect,
	NcTextField,
} from '@nextcloud/vue'
import { linkSigningRequest } from '../services/contracts.js'
import { useSigningStore } from '../store/modules/signing.js'
import {
	emptySignerRow,
	signersAreComplete,
	toSigners,
} from '../views/signing/signerRows.js'

/**
 * Send one contract document for signature through the normal signing
 * request, then record the request on the contract. Signing itself is not
 * changed: the contract only keeps the reference.
 *
 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
 */
export default {
	name: 'SendContractForSignatureDialog',
	components: { NcButton, NcDialog, NcNoteCard, NcSelect, NcTextField },
	props: {
		/** The contract id. */
		contractId: { type: String, required: true },
		/** The contract title, the default document name. */
		contractTitle: { type: String, default: '' },
		/** The contract's document file ids. */
		documents: { type: Array, default: () => [] },
	},

	emits: ['close', 'sent'],
	data() {
		const documentOptions = this.documents.map((fileId) => ({
			id: String(fileId),
			label: t('filinq', 'File {id}', { id: fileId }),
		}))
		return {
			documentOptions,
			document: documentOptions[documentOptions.length - 1] || null,
			documentName: this.contractTitle,
			level: { id: 'SES', label: 'SES' },
			levelOptions: ['SES', 'AdES', 'QES'].map((id) => ({ id, label: id })),
			signerRows: [emptySignerRow()],
			busy: false,
			error: '',
		}
	},

	computed: {
		/**
		 * Whether the request can be sent: a document, a name and reachable signers.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		ready() {
			return (
				!this.busy
				&& Boolean(this.document)
				&& this.documentName.trim() !== ''
				&& signersAreComplete(this.signerRows)
			)
		},
	},

	methods: {
		t,
		emptySignerRow,
		/**
		 * Create the signing request, record it on the contract, hand the link back.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
		 */
		async onConfirm() {
			this.busy = true
			this.error = ''
			const created = await useSigningStore().createSigningRequest({
				documentFileId: this.document.id,
				documentName: this.documentName.trim(),
				signatureLevel: this.level.id,
				signingMode: 'sequential',
				signers: toSigners(this.signerRows),
			})
			const requestId = String(created?.id ?? created?.uuid ?? '')
			if (requestId === '') {
				this.busy = false
				this.error = t('filinq', 'The signing request could not be created.')
				return
			}
			const answer = await linkSigningRequest(this.contractId, requestId)
			this.busy = false
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.$emit('sent', answer.data)
		},
	},
}
</script>

<style scoped>
.contract-sign__signers {
	margin-top: calc(var(--default-grid-baseline) * 3);
	border: none;
	padding: 0;
}

.contract-sign__row {
	display: grid;
	grid-template-columns: repeat(3, minmax(0, 1fr)) auto;
	gap: calc(var(--default-grid-baseline) * 2);
	align-items: end;
	margin-bottom: calc(var(--default-grid-baseline) * 2);
}
</style>
