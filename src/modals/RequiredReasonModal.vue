<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Ask for a reason that cannot be left empty, then hand it to the caller's
submit. The dialog stays open with the error when the submit is refused,
so the reason typed is not lost.

@spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
-->

<template>
	<CnFormDialog
		ref="formDialog"
		:schema="schema"
		:item="{ reason: '' }"
		:dialogTitle="dialogTitle"
		data-testid="required-reason-modal"
		@confirm="submitReason"
		@close="close" />
</template>

<script>
import { CnFormDialog } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'

/**
 * The form: one required reason.
 *
 * @param {string} label The field's label.
 * @param {string} description What the reason is for.
 * @return {object} The schema.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function reasonFormSchema(label, description) {
	return {
		required: ['reason'],
		properties: {
			reason: {
				type: 'string',
				format: 'textarea',
				title: label,
				description,
				minLength: 1,
				order: 1,
			},
		},
	}
}

/**
 * The reason as typed, without surrounding space.
 *
 * @param {object} formData The form values.
 * @return {string} The reason, empty when none was given.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function reasonOf(formData) {
	return typeof formData?.reason === 'string' ? formData.reason.trim() : ''
}

export default {
	name: 'RequiredReasonModal',
	components: { CnFormDialog },

	props: {
		/** The dialog's title. */
		dialogTitle: { type: String, required: true },
		/** The reason field's label. */
		label: { type: String, required: true },
		/** What the reason is for, under the field. */
		description: { type: String, default: '' },
		/**
		 * Called with the reason; answers `{ ok, error }`. The dialog closes
		 * on `ok` and shows the error otherwise.
		 */
		submit: { type: Function, required: true },
	},

	emits: ['close', 'done'],

	computed: {
		/**
		 * @return {object} The form schema.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		schema() {
			return reasonFormSchema(this.label, this.description)
		},
	},

	methods: {
		t,

		/**
		 * Close without a reason.
		 *
		 * @return {void}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		close() {
			this.$emit('close')
		},

		/**
		 * Refuse an empty reason before anything is sent; otherwise submit it.
		 *
		 * @param {object} formData The form values.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		async submitReason(formData) {
			const reason = reasonOf(formData)
			if (reason === '') {
				this.$refs.formDialog?.setResult({
					error: t('filinq', 'A reason is required.'),
				})
				return
			}

			const result = await this.submit(reason)
			if (result?.ok) {
				this.$refs.formDialog?.setResult({ success: true })
				this.$emit('done', result.data, reason)
				return
			}
			this.$refs.formDialog?.setResult({
				error:
					result?.error
					|| t('filinq', 'This could not be saved. Try again later.'),
			})
		},
	},
}
</script>
