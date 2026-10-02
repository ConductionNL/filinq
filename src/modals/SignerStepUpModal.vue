<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
-->

<template>
	<NcModal
		:show="show"
		labelId="signer-step-up-title"
		size="normal"
		@close="$emit('close')">
		<div class="signer-step-up">
			<h2 id="signer-step-up-title">
				{{ t('filinq', 'Confirm who you are') }}
			</h2>
			<p>
				{{
					t(
						'filinq',
						'This document asks for assurance {level}. Your Nextcloud login is not enough for it.',
						{ level: levelLabel },
					)
				}}
			</p>
			<p>
				{{
					t(
						'filinq',
						'Log in once more with DigiD, eHerkenning or your bank. You come back here and sign.',
					)
				}}
			</p>
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<div class="signer-step-up__actions">
				<NcButton variant="secondary" @click="$emit('close')">
					{{ t('filinq', 'Cancel') }}
				</NcButton>
				<NcButton variant="primary" :disabled="busy" @click="start">
					{{ t('filinq', 'Confirm my identity') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcModal, NcNoteCard } from '@nextcloud/vue'
import { startStepUp } from '../services/signerStepUp.js'

/**
 * The step-up dialog of the signer identity rails (REQ-DDSIR-003).
 *
 * It opens when a signing act answered 403 with a step-up hint. It asks the
 * server to start the configured provider for this request and signer, and
 * sends the browser to the broker. The broker's callback returns the signer
 * to the request page, which re-attempts the signature.
 */
export default {
	name: 'SignerStepUpModal',
	components: { NcButton, NcModal, NcNoteCard },
	props: {
		show: { type: Boolean, default: false },
		requestId: { type: String, required: true },
		signerId: { type: String, required: true },
		requiredAssurance: { type: String, default: 'substantial' },
	},

	emits: ['close', 'ready'],

	data() {
		return { busy: false, error: null }
	},

	computed: {
		/**
		 * The assurance, in words.
		 *
		 * @return {string} The label.
		 *
		 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
		 */
		levelLabel() {
			const labels = {
				low: t('filinq', 'low'),
				substantial: t('filinq', 'substantial'),
				high: t('filinq', 'high'),
			}
			return labels[this.requiredAssurance] || this.requiredAssurance
		},
	},

	methods: {
		t,

		/**
		 * Start the step-up and follow the provider's challenge.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
		 */
		async start() {
			this.busy = true
			this.error = null
			try {
				const data = await startStepUp(this.requestId, this.signerId)
				if (data.type === 'redirect' && data.url) {
					window.location.assign(data.url)
					return
				}
				this.$emit('ready')
			} catch {
				this.error = t(
					'filinq',
					'The identity check could not start. Ask your administrator to check the identity broker.',
				)
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.signer-step-up {
	padding: calc(var(--default-grid-baseline) * 4);
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 3);
}

.signer-step-up__actions {
	display: flex;
	justify-content: flex-end;
	gap: calc(var(--default-grid-baseline) * 2);
}
</style>
