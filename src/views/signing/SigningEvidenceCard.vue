<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<section class="signing-evidence" aria-labelledby="signing-evidence-title">
		<h3 id="signing-evidence-title">
			{{ t('filinq', 'Evidence document') }}
		</h3>
		<template v-if="finished">
			<p class="signing-evidence__hint">
				{{ t('filinq', 'One PDF with every signer, their method and the moment of every step, with the checksums of the document. It carries the organisation waarmerk.') }}
			</p>
			<div class="signing-evidence__actions">
				<NcButton variant="primary" :href="downloadUrl">
					{{ t('filinq', 'Download evidence document (pdf)') }}
				</NcButton>
				<NcButton :disabled="copying" @click="copy">
					{{ t('filinq', 'Copy to dossier') }}
				</NcButton>
			</div>
		</template>
		<p v-else class="signing-evidence__hint">
			{{ t('filinq', 'The evidence document is available once every signer has signed or declined.') }}
		</p>
	</section>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'
import { copyEvidence, evidenceUrl, TERMINAL_SIGNING_STATUSES } from '../../services/waarmerk.js'

// The evidence card of the signing request page (document-waarmerk-certification
// REQ-DDWMK-006), as the board filinq/FqOndertekenverzoek draws it.
export default {
	name: 'SigningEvidenceCard',
	components: { NcButton },

	props: {
		/** The signing request id. */
		requestId: { type: String, required: true },
		/** The signing request status. */
		status: { type: String, default: '' },
	},

	data() {
		return { copying: false }
	},

	computed: {
		/**
		 * Whether the request is in a terminal state.
		 *
		 * @return {boolean}
		 */
		finished() {
			return TERMINAL_SIGNING_STATUSES.includes(this.status)
		},

		/**
		 * The evidence download URL.
		 *
		 * @return {string}
		 */
		downloadUrl() {
			return evidenceUrl(this.requestId)
		},
	},

	methods: {
		t,

		/**
		 * Write the sealed evidence next to the signed document.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		async copy() {
			this.copying = true
			try {
				const result = await copyEvidence(this.requestId)
				showSuccess(t('filinq', 'Evidence document saved as {name}', { name: result.fileName }))
			} catch (error) {
				showError(error?.response?.data?.error || t('filinq', 'The evidence document could not be saved'))
			} finally {
				this.copying = false
			}
		},
	},
}
</script>

<style scoped>
.signing-evidence {
	margin: 20px 0;
	padding: 16px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
}

.signing-evidence__hint {
	color: var(--color-text-maxcontrast);
}

.signing-evidence__actions {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin-top: 8px;
}
</style>
