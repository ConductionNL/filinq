<template>
	<div class="signing-request-form">
		<h2>{{ t('filinq', 'New Signing Request') }}</h2>
		<NcNoteCard type="info" class="signing-request-form__notice">
			{{
				t(
					'filinq',
					'This composes a draft signing request only. Sending for signature, provider binding and signer-identity assertion are not available yet — the signature level below records your intent but is not yet cryptographically enforced.',
				)
			}}
		</NcNoteCard>
		<div class="form-group">
			<label for="signing-request-document-file-id">{{
				t('filinq', 'Document File ID')
			}}</label>
			<input
				id="signing-request-document-file-id"
				v-model="form.documentFileId"
				type="text" />
		</div>
		<div class="form-group">
			<label for="signing-request-document-name">{{
				t('filinq', 'Document Name')
			}}</label>
			<input
				id="signing-request-document-name"
				v-model="form.documentName"
				type="text" />
		</div>
		<div class="form-group">
			<label>{{ t('filinq', 'Signature Level') }}</label>
			<select v-model="form.signatureLevel">
				<option value="SES">
					{{ t('filinq', 'SES - Simple') }}
				</option>
				<option value="AdES">
					{{ t('filinq', 'AdES - Advanced') }}
				</option>
				<option value="QES">
					{{ t('filinq', 'QES - Qualified') }}
				</option>
			</select>
		</div>
		<div class="form-group">
			<NcSelect
				v-model="form.requiredAssurance"
				:inputLabel="t('filinq', 'Identity check for signers')"
				:options="assuranceOptions"
				:clearable="false"
				label="label"
				:reduce="(option) => option.id" />
			<p class="signers__hint">
				{{ assuranceHint }}
			</p>
		</div>
		<div class="form-group">
			<label>{{ t('filinq', 'Signing Mode') }}</label>
			<select v-model="form.signingMode">
				<option value="sequential">
					{{ t('filinq', 'Sequential') }}
				</option>
				<option value="parallel">
					{{ t('filinq', 'Parallel') }}
				</option>
			</select>
		</div>
		<fieldset class="signers">
			<legend>{{ t('filinq', 'Signers') }}</legend>
			<p class="signers__hint">
				{{
					t(
						'filinq',
						'Give each signer an e-mail address or a Nextcloud user. In sequential mode they sign in this order.',
					)
				}}
			</p>
			<div
				v-for="(signer, index) in signerRows"
				:key="index"
				class="signers__row">
				<div class="form-group">
					<label :for="`signer-${index}-name`">{{
						t('filinq', 'Name')
					}}</label>
					<input
						:id="`signer-${index}-name`"
						v-model="signer.displayName"
						type="text" />
				</div>
				<div class="form-group">
					<label :for="`signer-${index}-email`">{{
						t('filinq', 'E-mail')
					}}</label>
					<input
						:id="`signer-${index}-email`"
						v-model="signer.email"
						type="email"
						autocomplete="off" />
				</div>
				<div class="form-group">
					<label :for="`signer-${index}-user`">{{
						t('filinq', 'Nextcloud user')
					}}</label>
					<input
						:id="`signer-${index}-user`"
						v-model="signer.userId"
						type="text" />
				</div>
				<NcButton
					variant="tertiary"
					:disabled="signerRows.length === 1"
					:aria-label="
						t('filinq', 'Remove signer {number}', { number: index + 1 })
					"
					@click="removeSigner(index)">
					{{ t('filinq', 'Remove') }}
				</NcButton>
			</div>
			<NcButton variant="secondary" @click="addSigner">
				{{ t('filinq', 'Add signer') }}
			</NcButton>
		</fieldset>
		<div class="form-group">
			<NcCheckboxRadioSwitch
				v-model="placingFields"
				type="switch"
				:disabled="!form.documentFileId">
				{{ t('filinq', 'Place fields on the document') }}
			</NcCheckboxRadioSwitch>
		</div>
		<FieldPlacementEditor
			v-if="placingFields && form.documentFileId"
			v-model="fieldPlacements"
			:fileId="form.documentFileId"
			:signers="signerLabels" />
		<div class="signing-request-form__actions">
			<NcButton variant="primary" :disabled="!canSubmit" @click="submit">
				{{ t('filinq', 'Create Signing Request') }}
			</NcButton>
			<NcButton
				variant="secondary"
				:disabled="!form.documentFileId || !form.documentName"
				@click="bulkOpen = true">
				{{ t('filinq', 'Send to many from a list') }}
			</NcButton>
			<NcButton
				variant="secondary"
				:disabled="!signersAreComplete(signerRows)"
				@click="envelopeOpen = true">
				{{ t('filinq', 'Send several documents together') }}
			</NcButton>
		</div>
		<BulkSendModal
			v-if="bulkOpen"
			:show="bulkOpen"
			:settings="bulkSettings"
			@close="bulkOpen = false" />
		<SigningEnvelopeModal
			v-if="envelopeOpen"
			:show="envelopeOpen"
			:settings="envelopeSettings"
			@close="envelopeOpen = false" />
	</div>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcNoteCard,
	NcSelect,
} from '@nextcloud/vue'
import BulkSendModal from '../../modals/BulkSendModal.vue'
import SigningEnvelopeModal from '../../modals/SigningEnvelopeModal.vue'
import FieldPlacementEditor from './FieldPlacementEditor.vue'
import { assuranceFloor, assuranceLevelsFrom } from '../../services/signerStepUp.js'
import { useSigningStore } from '../../store/modules/signing.js'
import { dropSignerPlacements, toRequestPlacements } from './fieldPlacement.js'
import { emptySignerRow, signersAreComplete, toSigners } from './signerRows.js'

export default {
	name: 'SigningRequestForm',
	components: {
		BulkSendModal,
		FieldPlacementEditor,
		NcButton,
		NcCheckboxRadioSwitch,
		NcNoteCard,
		NcSelect,
		SigningEnvelopeModal,
	},

	data() {
		return {
			form: {
				documentFileId: '',
				documentName: '',
				signatureLevel: 'SES',
				signingMode: 'sequential',
				requiredAssurance: 'low',
			},

			signerRows: [emptySignerRow()],
			bulkOpen: false,
			envelopeOpen: false,
			placingFields: false,
			fieldPlacements: [],
		}
	},

	computed: {
		/**
		 * The name each signer row goes by, for the placement editor.
		 *
		 * @return {Array<string>}
		 *
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		signerLabels() {
			return this.signerRows.map(
				(row) => row.displayName || row.email || row.userId,
			)
		},

		/**
		 * The document settings a bulk send applies to every row.
		 *
		 * @return {object}
		 *
		 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
		 */
		bulkSettings() {
			return {
				documentFileId: this.form.documentFileId,
				documentName: this.form.documentName,
				signatureLevel: this.form.signatureLevel,
				signingMode: this.form.signingMode,
			}
		},

		/**
		 * What every document of an envelope shares, and the form's document as its first.
		 *
		 * @return {object}
		 *
		 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
		 */
		envelopeSettings() {
			return {
				documentFileId: this.form.documentFileId,
				documentName: this.form.documentName,
				signatureLevel: this.form.signatureLevel,
				signingMode: this.form.signingMode,
				requiredAssurance: this.form.requiredAssurance,
				signers: toSigners(this.signerRows),
			}
		},

		/**
		 * The identity levels this signature level allows: its floor and up.
		 *
		 * @return {Array<object>} Id and label per level.
		 *
		 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
		 */
		assuranceOptions() {
			const labels = {
				low: t('filinq', 'Low: a Nextcloud login is enough'),
				substantial: t(
					'filinq',
					'Substantial: DigiD Midden, eHerkenning EH3 or iDIN',
				),

				high: t('filinq', 'High: DigiD Hoog or eHerkenning EH4'),
			}
			return assuranceLevelsFrom(this.form.signatureLevel).map((id) => ({
				id,
				label: labels[id],
			}))
		},

		/**
		 * The floor the chosen signature level sets.
		 *
		 * @return {string} The hint.
		 *
		 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
		 */
		assuranceHint() {
			return t(
				'filinq',
				'{level} needs at least {floor}. Signers below it confirm their identity before they sign.',
				{
					level: this.form.signatureLevel,
					floor: t('filinq', assuranceFloor(this.form.signatureLevel)),
				},
			)
		},

		/**
		 * The document is named and every signer row can be reached (#1209).
		 *
		 * @return {boolean}
		 * @spec openspec/changes/signing-accept-only-recipient/specs/signing-accept-only/spec.md
		 */
		canSubmit() {
			return (
				Boolean(this.form.documentFileId)
				&& Boolean(this.form.documentName)
				&& signersAreComplete(this.signerRows)
			)
		},
	},

	watch: {
		/**
		 * Raise the identity level when a stronger signature level needs it.
		 *
		 * @return {void}
		 *
		 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
		 */
		'form.signatureLevel': function () {
			const allowed = assuranceLevelsFrom(this.form.signatureLevel)
			if (!allowed.includes(this.form.requiredAssurance)) {
				this.form.requiredAssurance = allowed[0]
			}
		},
	},

	methods: {
		t,
		signersAreComplete,
		/**
		 * Add an empty signer row at the end.
		 *
		 * @spec openspec/changes/signing-accept-only-recipient/specs/signing-accept-only/spec.md
		 */
		addSigner() {
			this.signerRows.push(emptySignerRow())
		},

		/**
		 * Remove one signer row; the last row stays.
		 *
		 * @param {number} index The row to remove.
		 * @spec openspec/changes/signing-accept-only-recipient/specs/signing-accept-only/spec.md
		 */
		removeSigner(index) {
			if (this.signerRows.length > 1) {
				this.signerRows.splice(index, 1)
				this.fieldPlacements = dropSignerPlacements(
					this.fieldPlacements,
					index,
				)
			}
		},

		/**
		 * Validate and submit the new signing request form. Drafts a
		 * signingRequest record only (POST signing#createRequest) — this
		 * does not send for signature, bind a provider, or assert signer
		 * identity, so it stays within the non-trust-bearing composition
		 * this restored page is scoped to (design.md D4).
		 *
		 * @spec openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-signing-authoring-and-verify-are-reachable-with-trust-actions-gated-req-ddosr-004
		 */
		async submit() {
			const signingStore = useSigningStore()
			const result = await signingStore.createSigningRequest({
				...this.form,
				signers: toSigners(this.signerRows),
				...(this.placingFields && this.fieldPlacements.length
					? { fieldPlacements: toRequestPlacements(this.fieldPlacements) }
					: {}),
			})
			if (result) {
				showSuccess(t('filinq', 'Signing request created'))
				this.$router.push({ name: 'SigningRequests' })
			} else {
				showError(t('filinq', 'Failed to create signing request'))
			}
		},
	},
}
</script>

<style scoped>
.signing-request-form__actions {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 2);
}

.signing-request-form {
	padding: 20px;
	max-width: 600px;
}

.signing-request-form__notice {
	margin-bottom: 16px;
}

.signers {
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: 12px;
	margin-bottom: 16px;
}

.signers__hint {
	margin: 0 0 12px 0;
	color: var(--color-text-maxcontrast);
}

.signers__row {
	display: flex;
	flex-wrap: wrap;
	align-items: flex-end;
	gap: 8px;
	margin-bottom: 8px;
}

.signers__row .form-group {
	flex: 1 1 160px;
	margin-bottom: 0;
}

.form-group {
	display: flex;
	flex-direction: column;
	gap: 4px;
	margin-bottom: 16px;
}

.form-group label {
	font-weight: bold;
	color: var(--color-main-text);
}

.form-group input,
.form-group select {
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
}
</style>
