<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/archive/2026-11-25-signing-field-validation/tasks.md#task-3.2
-->

<template>
	<NcModal
		:show="show"
		labelId="fill-in-and-sign-title"
		size="normal"
		@close="$emit('close')">
		<form class="fill-in-and-sign" novalidate @submit.prevent="submit">
			<h2 id="fill-in-and-sign-title">
				{{ t('filinq', 'Fill in and sign') }}
			</h2>
			<p>
				{{
					t(
						'filinq',
						'{document} asks you to fill in these fields. They are checked and sealed with your signature.',
						{ document: documentName },
					)
				}}
			</p>
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<NcTextField
				v-for="(field, index) in fields"
				:key="field.placement"
				:modelValue="values[field.placement] ?? ''"
				:label="fieldLabel(field, index)"
				:required="field.required"
				:error="Boolean(errors[field.placement])"
				:helperText="errors[field.placement] || hintOf(field)"
				:data-testid="`fill-in-field-${field.placement}`"
				@update:modelValue="setValue(field.placement, $event)" />
			<div class="fill-in-and-sign__actions">
				<NcButton variant="secondary" @click="$emit('close')">
					{{ t('filinq', 'Cancel') }}
				</NcButton>
				<NcButton variant="primary" type="submit" :disabled="busy">
					{{ t('filinq', 'Sign') }}
				</NcButton>
			</div>
		</form>
	</NcModal>
</template>

<script>
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcModal, NcNoteCard, NcTextField } from '@nextcloud/vue'
import { stepUpHint } from '../services/signerStepUp.js'
import {
	fieldErrorsOf,
	signBody,
	signErrorOf,
} from '../views/signing/fillInAndSign.js'

/**
 * The "Fill in and sign" dialog (signing-field-validation D7).
 *
 * It opens from the signing folder for a document with fields for this
 * signer. It sends the values with the sign act; the server checks them and
 * answers 422 with a message per refused field, which is shown on that
 * field. Nothing is signed until every value is accepted.
 */
export default {
	name: 'FillInAndSignDialog',
	components: { NcButton, NcModal, NcNoteCard, NcTextField },
	props: {
		show: {
			type: Boolean,
			default: false,
		},

		requestId: {
			type: String,
			required: true,
		},

		signerId: {
			type: String,
			required: true,
		},

		documentName: {
			type: String,
			default: '',
		},

		fields: {
			type: Array,
			required: true,
		},
	},

	emits: ['close', 'signed', 'stepUp'],
	data() {
		return {
			values: {},
			errors: {},
			error: '',
			busy: false,
		}
	},

	methods: {
		t,
		/**
		 * The label of a field, or a numbered one when the sender gave none.
		 *
		 * @param {object} field The field.
		 * @param {number} index Its place in the dialog.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-11-25-signing-field-validation/tasks.md#task-3.2
		 */
		fieldLabel(field, index) {
			return field.label || t('filinq', 'Field {number}', { number: index + 1 })
		},

		/**
		 * What the field expects, shown under it until the server refuses it.
		 *
		 * @param {object} field The field.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-11-25-signing-field-validation/tasks.md#task-3.2
		 */
		hintOf(field) {
			if (field.validation === 'iban-nl') {
				return t('filinq', 'A Dutch IBAN, for example NL91 ABNA 0417 1643 00')
			}
			if (field.validation === 'iban') {
				return t('filinq', 'An IBAN, for example NL91 ABNA 0417 1643 00')
			}
			return ''
		},

		/**
		 * Keep a typed value and clear that field's old message.
		 *
		 * @param {number} placement The field's placement position.
		 * @param {string} value What was typed.
		 * @spec openspec/changes/archive/2026-11-25-signing-field-validation/tasks.md#task-3.2
		 */
		setValue(placement, value) {
			this.values = { ...this.values, [placement]: value }
			if (this.errors[placement]) {
				const errors = { ...this.errors }
				delete errors[placement]
				this.errors = errors
			}
		},

		/**
		 * Send the values with the sign act.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-signing-field-validation/tasks.md#task-3.2
		 */
		async submit() {
			this.busy = true
			this.error = ''
			this.errors = {}
			try {
				const response = await axios.post(
					generateUrl('/apps/filinq/api/signing/requests/{id}/sign', {
						id: this.requestId,
					}),
					signBody(this.signerId, this.fields, this.values),
				)
				this.$emit('signed', response.data)
			} catch (err) {
				const hint = stepUpHint(err)
				if (hint) {
					this.$emit('stepUp', {
						requestId: this.requestId,
						signerId: this.signerId,
						stepUp: hint,
					})
					return
				}
				this.errors = fieldErrorsOf(err)
				this.error = signErrorOf(
					err,
					t('filinq', 'The document could not be signed.'),
				)
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.fill-in-and-sign {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
	padding: calc(var(--default-grid-baseline) * 4);
}

.fill-in-and-sign__actions {
	display: flex;
	justify-content: flex-end;
	gap: calc(var(--default-grid-baseline) * 2);
}
</style>
