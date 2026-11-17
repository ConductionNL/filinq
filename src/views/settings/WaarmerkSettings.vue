<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<NcSettingsSection
		:name="t('filinq', 'Waarmerk (organisation seal)')"
		:description="
			t(
				'filinq',
				'The organisation certificate Filinq seals documents with. This is an organisation seal, not a qualified electronic seal.',
			)
		">
		<NcLoadingIcon v-if="loading" :size="32" />
		<template v-else>
			<dl v-if="summary.certificate" class="waarmerk-settings__facts">
				<dt>{{ t('filinq', 'Subject') }}</dt>
				<dd>{{ summary.certificate.subject }}</dd>
				<dt>{{ t('filinq', 'Fingerprint (SHA-256)') }}</dt>
				<dd class="waarmerk-settings__mono">
					{{ summary.certificate.fingerprint }}
				</dd>
				<dt>{{ t('filinq', 'Valid until') }}</dt>
				<dd>{{ summary.certificate.validTo }}</dd>
			</dl>
			<NcNoteCard v-if="summary.certificate && summary.certificate.expired" type="error">
				{{ t('filinq', 'The certificate has expired. Sealing is refused until a valid certificate is configured.') }}
			</NcNoteCard>
			<NcNoteCard v-if="summary.certificateError" type="error">
				{{ summary.certificateError }}
			</NcNoteCard>
			<NcNoteCard v-if="!summary.configured && !summary.certificateError" type="info">
				{{ t('filinq', 'No organisation certificate is configured yet.') }}
			</NcNoteCard>

			<label class="waarmerk-settings__label" for="waarmerk-certificate">
				{{ t('filinq', 'Organisation certificate (PEM)') }}
			</label>
			<textarea
				id="waarmerk-certificate"
				v-model="form.certificate"
				class="waarmerk-settings__pem"
				rows="6"
				:placeholder="pemPlaceholder" />
			<p class="waarmerk-settings__hint">
				{{ t('filinq', 'The public certificate only. Leave empty to keep the current one.') }}
			</p>

			<div class="waarmerk-settings__field">
				<NcSelect
					v-model="form.custody"
					:inputLabel="t('filinq', 'Where the private key is held')"
					:options="custodyOptions"
					:clearable="false"
					label="label"
					:reduce="(option) => option.id" />
			</div>

			<NcTextField
				v-if="form.custody === 'broker'"
				v-model="form.credentialRef"
				:label="t('filinq', 'Credential reference')"
				:helperText="
					t(
						'filinq',
						'The ID of the private key in the OpenRegister credential broker. Filinq stores this reference, never the key.',
					)
				" />
			<template v-else>
				<NcNoteCard type="warning">
					{{ t('filinq', 'Local custody (interim): the key is stored encrypted in this Nextcloud. Move it to the credential broker when you can.') }}
				</NcNoteCard>
				<label class="waarmerk-settings__label" for="waarmerk-local-key">
					{{ t('filinq', 'Private key (PEM), only to replace the stored key') }}
				</label>
				<textarea
					id="waarmerk-local-key"
					v-model="form.localKey"
					class="waarmerk-settings__pem"
					rows="4"
					autocomplete="off" />
				<p class="waarmerk-settings__hint">
					{{ summary.localKeyStored ? t('filinq', 'A key is stored. It is never shown again.') : t('filinq', 'No key is stored yet.') }}
				</p>
			</template>

			<div class="waarmerk-settings__actions">
				<NcButton variant="primary" :disabled="saving" @click="save">
					{{ t('filinq', 'Save waarmerk settings') }}
				</NcButton>
				<NcButton :disabled="testing || !summary.configured" @click="testSeal">
					{{ t('filinq', 'Test seal') }}
				</NcButton>
			</div>
			<p v-if="testResult" class="waarmerk-settings__hint" role="status">
				{{ testResult }}
			</p>
		</template>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import {
	NcButton,
	NcLoadingIcon,
	NcNoteCard,
	NcSelect,
	NcSettingsSection,
	NcTextField,
} from '@nextcloud/vue'

// The waarmerk certificate panel (document-waarmerk-certification
// REQ-DDWMK-001). It talks to its own admin-only endpoint. The server never
// returns key material: a typed local key is sent once and the field is
// cleared after saving.
export default {
	name: 'WaarmerkSettings',
	components: {
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		NcSelect,
		NcSettingsSection,
		NcTextField,
	},

	data() {
		return {
			loading: true,
			saving: false,
			testing: false,
			testResult: '',
			summary: { configured: false, certificate: null, certificateError: null, localKeyStored: false },
			form: { certificate: '', custody: 'broker', credentialRef: '', localKey: '' },
		}
	},

	computed: {
		/**
		 * The custody modes, in words.
		 *
		 * @return {Array<object>} Id and label per mode.
		 *
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		custodyOptions() {
			return [
				{ id: 'broker', label: t('filinq', 'Credential broker (OpenRegister)') },
				{ id: 'local', label: t('filinq', 'Local custody (interim)') },
			]
		},

		/**
		 * The PEM placeholder.
		 *
		 * @return {string} The first line of a PEM certificate.
		 *
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		pemPlaceholder() {
			return '-----BEGIN CERTIFICATE-----'
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		t,

		/**
		 * The panel's endpoint.
		 *
		 * @param {string} suffix An optional sub path.
		 * @return {string} The URL.
		 *
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		url(suffix = '') {
			return generateUrl('/apps/filinq/api/waarmerk/settings' + suffix)
		},

		/**
		 * Show what the server stored.
		 *
		 * @param {object} data The summary.
		 * @return {void}
		 *
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		apply(data) {
			this.summary = data
			this.form.custody = data.custody === 'local' ? 'local' : 'broker'
			this.form.credentialRef = data.credentialRef || ''
			this.form.certificate = ''
			this.form.localKey = ''
		},

		/**
		 * Load the summary.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		async load() {
			try {
				const { data } = await axios.get(this.url())
				this.apply(data)
			} catch {
				showError(t('filinq', 'Could not load the waarmerk settings'))
			} finally {
				this.loading = false
			}
		},

		/**
		 * Save the certificate, reference and custody; a typed key is sent once.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		async save() {
			this.saving = true
			const payload = { custody: this.form.custody, credentialRef: this.form.credentialRef }
			if (this.form.certificate.trim() !== '') {
				payload.certificate = this.form.certificate
			}
			if (this.form.custody === 'local' && this.form.localKey.trim() !== '') {
				payload.localKey = this.form.localKey
			}
			try {
				const { data } = await axios.put(this.url(), payload)
				this.apply(data)
				showSuccess(t('filinq', 'Waarmerk settings saved'))
			} catch (error) {
				this.form.localKey = ''
				showError(error?.response?.data?.error || t('filinq', 'Could not save the waarmerk settings'))
			} finally {
				this.saving = false
			}
		},

		/**
		 * Seal and verify a throwaway document on the server; nothing is stored.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		async testSeal() {
			this.testing = true
			try {
				const { data } = await axios.post(this.url('/test'))
				this.testResult = data.ok
					? t('filinq', 'The test seal was made and verified.')
					: t('filinq', 'The test seal could not be verified.')
			} catch (error) {
				this.testResult = error?.response?.data?.error || t('filinq', 'The test seal failed.')
			} finally {
				this.testing = false
			}
		},
	},
}
</script>

<style scoped>
.waarmerk-settings__facts {
	display: grid;
	grid-template-columns: max-content 1fr;
	gap: 4px 16px;
	margin-bottom: 12px;
}

.waarmerk-settings__facts dt {
	color: var(--color-text-maxcontrast);
}

.waarmerk-settings__mono {
	font-family: var(--font-face-monospace, monospace);
	word-break: break-all;
}

.waarmerk-settings__label {
	display: block;
	margin-top: 12px;
	font-weight: bold;
}

.waarmerk-settings__pem {
	width: 100%;
	max-width: 640px;
	font-family: var(--font-face-monospace, monospace);
}

.waarmerk-settings__hint {
	color: var(--color-text-maxcontrast);
	margin: 4px 0 12px;
}

.waarmerk-settings__field {
	max-width: 400px;
	margin: 12px 0;
}

.waarmerk-settings__actions {
	display: flex;
	gap: 8px;
	margin-top: 16px;
}
</style>
