<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Ask for the password of a locked PDF and unlock it into a copy beside the
original (intake-pdf-password-removal, task 2.1, REQ-PPR-001). The password
is held only while the dialog is open and is cleared on every close.

@spec openspec/changes/archive/2026-11-25-intake-pdf-password-removal/tasks.md#2-screens
-->

<template>
	<NcDialog
		v-if="show"
		:name="t('filinq', 'Remove password')"
		data-testid="pdf-password-modal"
		@closing="close">
		<form class="pdf-password-modal" @submit.prevent="submit">
			<p>
				{{ t('filinq', 'Type the password of the PDF. The original stays as it is; an unlocked copy is saved beside it.') }}
			</p>
			<NcPasswordField
				v-model="password"
				:label="t('filinq', 'Password')"
				autocomplete="off"
				data-testid="pdf-password-input" />
			<NcNoteCard v-if="error" type="error" data-testid="pdf-password-error">
				{{ error }}
			</NcNoteCard>
			<NcButton
				type="submit"
				variant="primary"
				:disabled="busy || password === ''"
				data-testid="pdf-password-submit">
				{{ busy ? t('filinq', 'Removing password…') : t('filinq', 'Remove password') }}
			</NcButton>
		</form>
	</NcDialog>
</template>

<script>
import { NcButton, NcDialog, NcNoteCard, NcPasswordField } from '@nextcloud/vue'
import { removeIntakePassword, removePassword, removePasswordError } from '../services/pdfPassword.js'

export default {
	name: 'PdfPasswordModal',
	components: { NcButton, NcDialog, NcNoteCard, NcPasswordField },
	props: {
		show: { type: Boolean, default: false },
		fileId: { type: [Number, String], default: null },
		/** An intake document: its file is unlocked and the document then names the copy. */
		intakeUuid: { type: String, default: '' },
	},

	emits: ['close', 'unlocked'],

	data() {
		return { password: '', busy: false, error: '' }
	},

	methods: {
		/**
		 * Unlock the PDF; on success hand the copy to the host and close.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-intake-pdf-password-removal/tasks.md#2-screens
		 */
		async submit() {
			if (this.busy || this.password === '' || (!this.fileId && !this.intakeUuid)) {
				return
			}
			this.busy = true
			this.error = ''
			try {
				const copy = this.intakeUuid
					? await removeIntakePassword(this.intakeUuid, this.password)
					: await removePassword(this.fileId, this.password)
				this.password = ''
				this.$emit('unlocked', copy)
				this.$emit('close')
			} catch (e) {
				this.error = removePasswordError(e)
			} finally {
				this.busy = false
			}
		},

		/**
		 * Forget the password and close.
		 *
		 * @spec openspec/changes/archive/2026-11-25-intake-pdf-password-removal/tasks.md#2-screens
		 */
		close() {
			this.password = ''
			this.error = ''
			this.$emit('close')
		},
	},
}
</script>

<style scoped>
.pdf-password-modal {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 8px 0;
}
</style>
