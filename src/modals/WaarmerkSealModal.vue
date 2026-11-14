<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
-->

<template>
	<NcModal
		:show="show"
		:name="title"
		@close="close">
		<div class="waarmerk-seal" data-testid="waarmerk-seal">
			<h2>{{ title }}</h2>
			<template v-if="!result">
				<p>{{ explanation }}</p>
				<p class="waarmerk-seal__muted">
					{{ t('filinq', 'A waarmerk is an organisation seal, not a qualified electronic seal.') }}
				</p>
				<NcNoteCard v-if="error" type="error">
					{{ error }}
				</NcNoteCard>
				<div class="waarmerk-seal__actions">
					<NcButton @click="close">
						{{ t('filinq', 'Cancel') }}
					</NcButton>
					<NcButton variant="primary" :disabled="busy" data-testid="waarmerk-seal-confirm" @click="run">
						<template v-if="busy" #icon>
							<NcLoadingIcon :size="20" />
						</template>
						{{ mode === 'certificate' ? t('filinq', 'Create certificate') : t('filinq', 'Add waarmerk') }}
					</NcButton>
				</div>
			</template>
			<template v-else>
				<NcNoteCard type="success">
					{{ t('filinq', 'Saved as {name} next to the original. The original is unchanged.', { name: result.fileName }) }}
				</NcNoteCard>
				<dl class="waarmerk-seal__facts">
					<dt>{{ t('filinq', 'Verification code') }}</dt>
					<dd class="waarmerk-seal__mono">
						{{ result.waarmerk && result.waarmerk.verificationCode }}
					</dd>
					<dt>{{ t('filinq', 'Sealed on') }}</dt>
					<dd>{{ result.waarmerk && result.waarmerk.sealedAt }}</dd>
				</dl>
				<div class="waarmerk-seal__actions">
					<NcButton variant="primary" @click="close">
						{{ t('filinq', 'Close') }}
					</NcButton>
				</div>
			</template>
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcModal, NcNoteCard } from '@nextcloud/vue'
import { certifyRun, sealFile } from '../services/waarmerk.js'

// "Waarmerk toevoegen" and the anonymisation certificate (document-waarmerk-certification
// REQ-DDWMK-002/003). The server writes the sealed copy next to the original.
export default {
	name: 'WaarmerkSealModal',
	components: { NcButton, NcLoadingIcon, NcModal, NcNoteCard },

	props: {
		/** Whether the modal is open. */
		show: { type: Boolean, default: false },
		/** `seal` a PDF, or create the `certificate` of an anonymisation run. */
		mode: { type: String, default: 'seal' },
		/** The file to seal (mode seal). */
		fileId: { type: Number, default: 0 },
		/** The anonymisation run (mode certificate). */
		linkId: { type: String, default: '' },
		/** The document name, for the explanation. */
		fileName: { type: String, default: '' },
	},

	emits: ['close', 'sealed'],

	data() {
		return { busy: false, error: '', result: null }
	},

	computed: {
		/**
		 * The modal title.
		 *
		 * @return {string}
		 */
		title() {
			return this.mode === 'certificate'
				? t('filinq', 'Anonymisation certificate')
				: t('filinq', 'Add waarmerk')
		},

		/**
		 * What will happen.
		 *
		 * @return {string}
		 */
		explanation() {
			return this.mode === 'certificate'
				? t('filinq', 'Creates a PDF certificate of this anonymisation: what was replaced per type of data, when, by which method and by whom. It contains no personal data from the document. The certificate gets the organisation waarmerk.')
				: t('filinq', 'Adds a waarmerk page with a verification code and QR code to a copy of {name}. Anyone holding the document can check it.', { name: this.fileName })
		},
	},

	methods: {
		t,

		/**
		 * Seal or certify.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		async run() {
			this.busy = true
			this.error = ''
			try {
				this.result = this.mode === 'certificate'
					? await certifyRun(this.linkId)
					: await sealFile(this.fileId)
				this.$emit('sealed', this.result)
			} catch (error) {
				this.error = error?.response?.data?.error || t('filinq', 'The waarmerk could not be added')
			} finally {
				this.busy = false
			}
		},

		/**
		 * Close and reset.
		 *
		 * @return {void}
		 */
		close() {
			this.result = null
			this.error = ''
			this.$emit('close')
		},
	},
}
</script>

<style scoped>
.waarmerk-seal {
	padding: 20px;
}

.waarmerk-seal__muted {
	color: var(--color-text-maxcontrast);
}

.waarmerk-seal__facts {
	display: grid;
	grid-template-columns: max-content 1fr;
	gap: 4px 16px;
}

.waarmerk-seal__mono {
	font-family: var(--font-face-monospace, monospace);
	word-break: break-all;
}

.waarmerk-seal__actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	margin-top: 16px;
}
</style>
