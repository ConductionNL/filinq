<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
-->

<template>
	<main class="public-verification" data-testid="public-verification">
		<h1>{{ t('filinq', 'Check a signed document') }}</h1>
		<NcLoadingIcon v-if="loading" :size="32" />
		<template v-else>
			<NcNoteCard :type="headline.type" data-testid="verification-verdict">
				{{ headline.text }}
			</NcNoteCard>

			<template v-if="verdict.status !== 'unknown'">
				<dl class="public-verification__facts">
					<dt>{{ t('filinq', 'Document') }}</dt>
					<dd data-testid="verification-file-name">
						{{ verdict.fileName }}
					</dd>
					<template v-if="verdict.recordedAt">
						<dt>{{ t('filinq', 'Recorded on') }}</dt>
						<dd>{{ verdict.recordedAt }}</dd>
					</template>
					<dt>{{ t('filinq', 'Signatures') }}</dt>
					<dd data-testid="verification-audit">
						{{ audit }}
					</dd>
				</dl>

				<h2>{{ t('filinq', 'Signatures') }}</h2>
				<ul class="public-verification__signatures">
					<li
						v-for="(signature, index) in verdict.signatures"
						:key="index"
						class="public-verification__signature"
						data-testid="verification-signature">
						<span
							class="public-verification__chip"
							:class="`public-verification__chip--${signature.status}`">
							{{ statusLabel(signature.status) }}
						</span>
						<span data-testid="verification-signer">{{ signer(signature) }}</span>
						<span v-if="signature.level" class="public-verification__level">
							{{ t('filinq', 'Level: {level}', { level: signature.level }) }}
						</span>
					</li>
				</ul>

				<p v-if="seal" data-testid="verification-seal">
					{{ seal }}
				</p>

				<h2>{{ t('filinq', 'Compare your copy') }}</h2>
				<p class="public-verification__hint">
					{{ t('filinq', 'Pick your copy of the document to check it is the one that was signed. The file stays on your device; nothing is uploaded.') }}
				</p>
				<label class="public-verification__pick">
					<span>{{ t('filinq', 'Your copy') }}</span>
					<input
						type="file"
						data-testid="verification-copy"
						@change="compareCopy">
				</label>
				<NcNoteCard
					v-if="match !== null"
					:type="match ? 'success' : 'error'"
					data-testid="verification-copy-result">
					{{ match ? t('filinq', 'Your copy is the document that was signed.') : t('filinq', 'Your copy is not the document that was signed.') }}
				</NcNoteCard>
			</template>
		</template>
	</main>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import {
	auditLine,
	copyMatches,
	fetchVerdict,
	sealLine,
	sha256Hex,
	signatureStatusLabel,
	signerLine,
	UNKNOWN_VERDICT,
	verdictHeadline,
} from './verdict.js'

/**
 * The public page a QR stamp points at: what one signed document's
 * signatures guarantee, for a visitor with no account. It shows the file
 * name only, serves no document and offers no download.
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
 */
export default {
	name: 'PublicSignatureVerification',
	components: { NcLoadingIcon, NcNoteCard },
	props: {
		/** The token from the page's address. */
		token: { type: String, default: '' },
	},

	data() {
		return {
			loading: true,
			verdict: { ...UNKNOWN_VERDICT },
			match: null,
		}
	},

	computed: {
		/**
		 * The headline for the overall status.
		 *
		 * @return {{type: string, text: string}}
		 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
		 */
		headline() {
			return verdictHeadline(this.verdict.status)
		},

		/**
		 * The audit summary.
		 *
		 * @return {string}
		 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
		 */
		audit() {
			return auditLine(this.verdict.audit)
		},

		/**
		 * The seal line, or ''.
		 *
		 * @return {string}
		 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
		 */
		seal() {
			return sealLine(this.verdict.seal)
		},
	},

	/**
	 * Fetch the verdict for the token in the link.
	 *
	 * @return {Promise<void>}
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
	 */
	async mounted() {
		this.verdict = await fetchVerdict(this.token)
		this.loading = false
	},

	methods: {
		t,
		statusLabel: signatureStatusLabel,
		signer: signerLine,

		/**
		 * Hash the picked copy locally and compare it with the record.
		 *
		 * @param {Event} event The file input's change.
		 * @return {Promise<void>}
		 * @spec openspec/changes/signature-verification-portal/tasks.md#task-3.1
		 */
		async compareCopy(event) {
			const file = event?.target?.files?.[0]
			if (!file) {
				this.match = null
				return
			}
			try {
				this.match = copyMatches(await sha256Hex(file), this.verdict.contentHash)
			} catch {
				this.match = null
			}
		},
	},
}
</script>

<style scoped>
.public-verification {
	max-width: 720px;
	margin: 0 auto;
	padding: calc(var(--default-grid-baseline) * 6) calc(var(--default-grid-baseline) * 4);
	color: var(--color-main-text);
}

.public-verification__facts {
	display: grid;
	grid-template-columns: max-content 1fr;
	gap: calc(var(--default-grid-baseline) * 2) calc(var(--default-grid-baseline) * 4);
}

.public-verification__facts dt {
	font-weight: bold;
}

.public-verification__signatures {
	list-style: none;
	padding: 0;
}

.public-verification__signature {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 3);
	align-items: center;
	padding: calc(var(--default-grid-baseline) * 2) 0;
	border-bottom: 1px solid var(--color-border);
}

.public-verification__chip {
	padding: 0 calc(var(--default-grid-baseline) * 2);
	border-radius: var(--border-radius-pill);
	border: 1px solid var(--color-border-dark);
	background: var(--color-background-dark);
	color: var(--color-main-text);
}

.public-verification__chip--verified {
	border-color: var(--color-success);
	color: var(--color-success-text);
}

.public-verification__chip--invalid {
	border-color: var(--color-error);
	color: var(--color-error-text);
}

.public-verification__level,
.public-verification__hint {
	color: var(--color-text-maxcontrast);
}

.public-verification__pick {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	margin-bottom: calc(var(--default-grid-baseline) * 3);
}
</style>
