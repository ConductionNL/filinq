<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<main class="waarmerk-verify" :aria-busy="checking ? 'true' : 'false'">
		<h1 class="waarmerk-verify__title">
			{{ t('filinq', 'Check a waarmerk') }}
		</h1>
		<p class="waarmerk-verify__intro">
			{{ t('filinq', 'This page checks whether a document carries a valid waarmerk of the organisation that sealed it. Choose your copy of the document: it is checked on your own device and is not sent.') }}
		</p>

		<section class="waarmerk-verify__card" :class="'waarmerk-verify__card--' + page.status" aria-labelledby="waarmerk-seal">
			<h2 id="waarmerk-seal">
				{{ statusTitle }}
			</h2>
			<dl v-if="page.status !== 'unknown'" class="waarmerk-verify__facts">
				<dt>{{ t('filinq', 'Organisation') }}</dt>
				<dd>{{ page.organisation }}</dd>
				<dt>{{ t('filinq', 'Sealed on') }}</dt>
				<dd>{{ page.sealedAt }}</dd>
				<dt>{{ t('filinq', 'Document') }}</dt>
				<dd>{{ page.documentName }}</dd>
				<template v-if="page.status === 'revoked'">
					<dt>{{ t('filinq', 'Revoked on') }}</dt>
					<dd>{{ page.revokedAt }}</dd>
					<dt>{{ t('filinq', 'Reason') }}</dt>
					<dd>{{ page.revocationReason }}</dd>
				</template>
			</dl>
			<p class="waarmerk-verify__code">
				{{ t('filinq', 'Verification code') }}: <span class="waarmerk-verify__mono">{{ page.code }}</span>
			</p>
		</section>

		<section v-if="page.status === 'active'" class="waarmerk-verify__check" aria-labelledby="waarmerk-check">
			<h2 id="waarmerk-check">
				{{ t('filinq', 'Check your copy') }}
			</h2>
			<label class="waarmerk-verify__label" for="waarmerk-file">
				{{ t('filinq', 'Choose the document (PDF)') }}
			</label>
			<input
				id="waarmerk-file"
				type="file"
				accept="application/pdf,.pdf"
				class="waarmerk-verify__file"
				:disabled="checking"
				@change="check">
			<div v-if="result" class="waarmerk-verify__result" :class="'waarmerk-verify__result--' + result.status" role="status">
				<strong>{{ resultTitle }}</strong>
				<p>{{ resultText }}</p>
				<p v-if="result.status === 'valid' && result.certificateExpired">
					{{ t('filinq', 'The organisation certificate has expired since. The seal was made while it was valid, so it still holds.') }}
				</p>
			</div>
		</section>

		<p class="waarmerk-verify__note">
			{{ t('filinq', 'A waarmerk is an organisation seal, not a qualified electronic seal. It confirms that the document, including its waarmerk page, is unchanged since it was sealed.') }}
		</p>
	</main>
</template>

<script>
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { sha256Hex } from './sha256.js'

export default {
	name: 'WaarmerkVerifyPage',

	data() {
		return {
			page: loadState('filinq', 'waarmerk', { status: 'unknown', code: '' }),
			checking: false,
			result: null,
		}
	},

	computed: {
		/**
		 * The heading for the seal's status.
		 *
		 * @return {string} The heading.
		 */
		statusTitle() {
			if (this.page.status === 'active') {
				return t('filinq', 'This waarmerk exists')
			}
			if (this.page.status === 'revoked') {
				return t('filinq', 'This waarmerk has been revoked')
			}
			return t('filinq', 'No waarmerk is known with this code')
		},

		/**
		 * The heading for the check result.
		 *
		 * @return {string} The heading.
		 */
		resultTitle() {
			return {
				valid: t('filinq', 'Valid'),
				'hash-mismatch': t('filinq', 'Not the sealed document'),
				revoked: t('filinq', 'Revoked'),
				unknown: t('filinq', 'Could not be verified'),
			}[this.result.status] || t('filinq', 'Could not be verified')
		},

		/**
		 * The explanation for the check result.
		 *
		 * @return {string} The text.
		 */
		resultText() {
			return {
				valid: t('filinq', 'Your copy is exactly the document that was sealed.'),
				'hash-mismatch': t('filinq', 'Your copy differs from the sealed document. It may have been changed after sealing.'),
				revoked: t('filinq', 'The organisation revoked this waarmerk.'),
				unknown: t('filinq', 'The waarmerk could not be confirmed.'),
			}[this.result.status] || t('filinq', 'The waarmerk could not be confirmed.')
		},
	},

	methods: {
		t,

		/**
		 * Hash the chosen file locally and verify the hash with the code.
		 *
		 * @param {Event} event The change event of the file input.
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		async check(event) {
			const file = event?.target?.files?.[0]
			if (!file) {
				return
			}
			this.checking = true
			this.result = null
			try {
				const sha256 = await sha256Hex(file)
				const { data } = await axios.post(generateUrl('/apps/filinq/api/waarmerk/verify'), { code: this.page.code, sha256 })
				this.result = data
			} catch {
				this.result = { status: 'unknown' }
			} finally {
				this.checking = false
			}
		},
	},
}
</script>

<style scoped>
.waarmerk-verify {
	max-width: 720px;
	margin: 0 auto;
	padding: 24px 16px;
	color: var(--color-main-text);
	background: var(--color-main-background);
	line-height: 1.5;
}

.waarmerk-verify__title {
	font-size: 1.75em;
	margin-bottom: 8px;
}

.waarmerk-verify__card,
.waarmerk-verify__check {
	border: 2px solid var(--color-border-dark);
	border-radius: var(--border-radius-large);
	padding: 16px;
	margin: 16px 0;
}

.waarmerk-verify__card--active {
	border-color: var(--color-success);
}

.waarmerk-verify__card--revoked,
.waarmerk-verify__card--unknown {
	border-color: var(--color-error);
}

.waarmerk-verify__facts {
	display: grid;
	grid-template-columns: max-content 1fr;
	gap: 4px 16px;
}

.waarmerk-verify__facts dt {
	font-weight: bold;
}

.waarmerk-verify__mono {
	font-family: var(--font-face-monospace, monospace);
	word-break: break-all;
}

.waarmerk-verify__label {
	display: block;
	font-weight: bold;
	margin-bottom: 4px;
}

.waarmerk-verify__file:focus-visible {
	outline: 2px solid var(--color-primary-element);
	outline-offset: 2px;
}

.waarmerk-verify__result {
	margin-top: 12px;
	padding: 12px;
	border-left: 4px solid var(--color-border-dark);
	background: var(--color-background-hover);
}

.waarmerk-verify__result--valid {
	border-left-color: var(--color-success);
}

.waarmerk-verify__result--hash-mismatch,
.waarmerk-verify__result--revoked,
.waarmerk-verify__result--unknown {
	border-left-color: var(--color-error);
}

.waarmerk-verify__note {
	color: var(--color-text-maxcontrast);
	font-size: 0.9em;
}
</style>
