<!--
  - SPDX-License-Identifier: EUPL-1.2
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  -
  - Public, account-free signature verification portal
  - (openspec/changes/signature-verification-portal). Reached by scanning
  - the QR stamped on a signed/waarmerked DocuDesk PDF — no Nextcloud
  - session, no file-read access.
  -
  - Honest trust model (design.md D2 / REQ-DDSVP-002): content integrity and
  - signer identity are rendered as DISTINCT guarantees. The signer name is
  - NEVER shown as an unqualified "verified" identity while a signature's
  - `identityBound` flag is false — it is labelled "as asserted, not
  - cryptographically bound" instead.
  -->
<template>
	<div class="dd-verify-portal">
		<header class="dd-verify-portal__header">
			<h1>{{ t('docudesk', 'Document verification') }}</h1>
		</header>

		<main class="dd-verify-portal__body">
			<NcLoadingIcon v-if="loading" :size="32" />

			<NcNoteCard v-else-if="isUnknown" type="warning">
				{{ t('docudesk', 'This verification link is unknown or no longer valid.') }}
			</NcNoteCard>

			<div v-else-if="verdict" class="dd-verify-result">
				<section class="dd-verify-result__summary" :class="summaryClass">
					<h2>{{ summaryHeading }}</h2>
					<p v-if="verdict.fileName">
						{{ t('docudesk', 'Document') }}: <strong>{{ verdict.fileName }}</strong>
					</p>
				</section>

				<section
					v-for="(signature, index) in verdict.signatures"
					:key="index"
					class="dd-verify-signature"
				>
					<h3>
						{{ t('docudesk', 'Signature {n}', { n: index + 1 }) }}
						<span class="dd-badge" :class="'dd-badge--' + signature.status">
							{{ statusLabel(signature.status) }}
						</span>
					</h3>
					<p>
						{{ t('docudesk', 'Level') }}: {{ signature.level }}
						·
						{{ t('docudesk', 'Method') }}: {{ signature.method }}
					</p>
					<p>
						{{ t('docudesk', 'Signer (as asserted)') }}: <strong>{{ signature.signerAsserted }}</strong>
					</p>
					<NcNoteCard v-if="!signature.identityBound" type="info" class="dd-verify-signature__identity-note">
						{{ t('docudesk', 'Signer identity is asserted, not yet cryptographically bound to this signature. Content integrity is verified independently — see status above.') }}
					</NcNoteCard>
					<p v-else class="dd-verify-signature__identity-bound">
						{{ t('docudesk', 'Signer identity is cryptographically bound to this signature.') }}
					</p>
				</section>

				<section v-if="verdict.waarmerkRef" class="dd-verify-waarmerk">
					<h3>{{ t('docudesk', 'Waarmerk seal') }}</h3>
					<p>{{ t('docudesk', 'This document also carries a waarmerk seal.') }}</p>
				</section>

				<section v-if="verdict.audit" class="dd-verify-audit">
					<h3>{{ t('docudesk', 'Audit summary') }}</h3>
					<p>
						{{ n('docudesk', '%n step recorded', '%n steps recorded', verdict.audit.steps) }}
						<span v-if="verdict.audit.lastAt">
							· {{ t('docudesk', 'last action on {date}', { date: formatDate(verdict.audit.lastAt) }) }}
						</span>
					</p>
				</section>

				<section class="dd-verify-local-check">
					<h3>{{ t('docudesk', 'Check your own copy (optional)') }}</h3>
					<p>{{ t('docudesk', 'Select the file on your device to compare it locally — nothing is uploaded.') }}</p>
					<input type="file" @change="onLocalFileSelected">
					<p v-if="localCheckResult === 'match'" class="dd-badge dd-badge--verified">
						{{ t('docudesk', 'Your copy matches the recorded content hash.') }}
					</p>
					<p v-else-if="localCheckResult === 'mismatch'" class="dd-badge dd-badge--invalid">
						{{ t('docudesk', 'Your copy does NOT match the recorded content hash.') }}
					</p>
					<p v-else-if="localCheckResult === 'checking'">
						{{ t('docudesk', 'Checking…') }}
					</p>
				</section>
			</div>

			<NcNoteCard v-else type="error">
				{{ t('docudesk', 'Verification could not be loaded.') }}
			</NcNoteCard>
		</main>

		<footer class="dd-verify-portal__footer">
			<p>{{ t('docudesk', 'Provided by DocuDesk — this page never displays or offers a download of the document itself.') }}</p>
		</footer>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'

export default {
	name: 'PublicVerificationPage',
	components: { NcLoadingIcon, NcNoteCard },
	data() {
		return {
			token: loadState('docudesk', 'verifyToken', ''),
			loading: true,
			verdict: null,
			localCheckResult: null,
		}
	},
	computed: {
		isUnknown() {
			return !this.loading && (!this.verdict || this.verdict.status === 'unknown')
		},
		summaryClass() {
			return this.verdict && this.verdict.isValid ? 'dd-verify-result__summary--ok' : 'dd-verify-result__summary--attention'
		},
		summaryHeading() {
			if (this.verdict && this.verdict.isValid) {
				return this.t('docudesk', 'Content integrity verified')
			}
			return this.t('docudesk', 'Verification could not confirm content integrity')
		},
	},
	async mounted() {
		await this.fetchVerdict()
	},
	methods: {
		async fetchVerdict() {
			this.loading = true
			try {
				const response = await axios.get(generateUrl(`/apps/docudesk/api/verify/${this.token}`))
				this.verdict = response.data
			} catch {
				// Fail closed — render the same "unknown" state as a genuinely
				// unknown token (no distinct error state that could act as an
				// oracle for e.g. rate-limit-vs-not-found).
				this.verdict = null
			} finally {
				this.loading = false
			}
		},
		statusLabel(status) {
			if (status === 'verified') return this.t('docudesk', 'Content verified')
			if (status === 'invalid') return this.t('docudesk', 'Tamper detected')
			return this.t('docudesk', 'Not yet verifiable')
		},
		formatDate(value) {
			try {
				return new Date(value).toLocaleDateString()
			} catch {
				return value
			}
		},
		/**
		 * Optional client-side WebCrypto re-hash of a locally selected file
		 * (REQ-DDSVP-001 "MAY offer... without uploading it") — the file
		 * never leaves the browser.
		 *
		 * @param {Event} event The file input change event.
		 * @return {Promise<void>}
		 */
		async onLocalFileSelected(event) {
			const file = event.target.files && event.target.files[0]
			if (!file || !this.verdict || !this.verdict.contentHash) {
				return
			}

			this.localCheckResult = 'checking'
			try {
				const buffer = await file.arrayBuffer()
				const digest = await window.crypto.subtle.digest('SHA-256', buffer)
				const hex = Array.from(new Uint8Array(digest))
					.map((b) => b.toString(16).padStart(2, '0'))
					.join('')
				this.localCheckResult = hex === this.verdict.contentHash ? 'match' : 'mismatch'
			} catch {
				this.localCheckResult = null
			}
		},
	},
}
</script>

<style scoped>
.dd-verify-portal {
	max-width: 720px;
	margin: 0 auto;
	padding: 32px 16px;
	color: var(--color-main-text, #222);
}

.dd-verify-portal__header h1 {
	font-size: 1.5rem;
	margin-bottom: 24px;
}

.dd-verify-result__summary {
	padding: 16px;
	border-radius: var(--border-radius, 8px);
	margin-bottom: 24px;
	border: 1px solid var(--color-border, #ccc);
}

.dd-verify-result__summary--ok {
	border-color: var(--color-success, #46ba61);
}

.dd-verify-result__summary--attention {
	border-color: var(--color-warning, #e9a927);
}

.dd-verify-signature,
.dd-verify-waarmerk,
.dd-verify-audit,
.dd-verify-local-check {
	padding: 16px 0;
	border-top: 1px solid var(--color-border, #ccc);
}

.dd-verify-signature__identity-note {
	margin-top: 8px;
}

.dd-verify-signature__identity-bound {
	font-weight: bold;
}

.dd-badge {
	display: inline-block;
	padding: 2px 10px;
	border-radius: 999px;
	font-size: 0.85rem;
	font-weight: 600;
}

.dd-badge--verified {
	background: var(--color-success, #46ba61);
	color: #fff;
}

.dd-badge--unverifiable {
	background: var(--color-warning, #e9a927);
	color: #222;
}

.dd-badge--invalid {
	background: var(--color-error, #e9322d);
	color: #fff;
}

.dd-verify-portal__footer {
	margin-top: 32px;
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast, #777);
}
</style>
