<template>
	<NcSettingsSection
		:name="t('filinq', 'Signer identity')"
		:description="
			t(
				'filinq',
				'Choose how signers prove who they are. DigiD, eHerkenning and iDIN run through an identity broker you connect here.',
			)
		">
		<NcLoadingIcon v-if="loading" :size="32" />
		<template v-else>
			<div class="signer-identity__field">
				<NcSelect
					v-model="provider"
					:inputLabel="t('filinq', 'Identity provider')"
					:options="providerOptions"
					:clearable="false"
					label="label"
					:reduce="(option) => option.id" />
				<p class="signer-identity__hint">
					{{
						t(
							'filinq',
							'With the Nextcloud login a signer reaches assurance low. Requests that ask for more need the identity broker.',
						)
					}}
				</p>
			</div>

			<fieldset class="signer-identity__broker">
				<legend>{{ t('filinq', 'Identity broker') }}</legend>
				<NcTextField
					v-model="form.issuer"
					:label="t('filinq', 'Issuer')"
					placeholder="https://" />
				<NcTextField
					v-model="form.clientId"
					:label="t('filinq', 'Client ID')" />
				<NcTextField
					v-model="form.authorizationEndpoint"
					:label="t('filinq', 'Authorization endpoint')"
					placeholder="https://" />
				<NcTextField
					v-model="form.tokenEndpoint"
					:label="t('filinq', 'Token endpoint')"
					placeholder="https://" />
				<NcTextField
					v-model="form.redirectUri"
					:label="t('filinq', 'Redirect URI')" />
				<NcTextField v-model="form.scopes" :label="t('filinq', 'Scopes')" />
				<NcTextField
					v-model="form.credentialRef"
					:label="t('filinq', 'Credential reference')"
					:helperText="
						t(
							'filinq',
							'The ID of the client secret in the OpenRegister credential broker. Filinq stores this reference, never the secret.',
						)
					" />
				<label class="signer-identity__label" for="signer-identity-acr">
					{{ t('filinq', 'Extra acr mapping (JSON)') }}
				</label>
				<textarea
					id="signer-identity-acr"
					v-model="form.acrMapping"
					class="signer-identity__acr"
					rows="4"
					:placeholder="acrPlaceholder" />
				<p class="signer-identity__hint">
					{{
						t(
							'filinq',
							"The DigiD and eHerkenning levels are mapped already. Add your broker's own values here, such as its iDIN value. An unknown value counts as low.",
						)
					}}
				</p>
			</fieldset>

			<div class="signer-identity__field">
				<NcTextField
					v-model="form.evidenceMaxAgeMinutes"
					type="number"
					:label="
						t('filinq', 'Minutes a login stays valid for signing')
					" />
			</div>

			<div class="signer-identity__field">
				<NcSelect
					v-model="form.guardianMinimumAssurance"
					:inputLabel="
						t('filinq', 'Minimum assurance for a parent or guardian')
					"
					:options="assuranceOptions"
					:clearable="false"
					label="label"
					:reduce="(option) => option.id" />
				<p class="signer-identity__hint">
					{{
						t(
							'filinq',
							'A parent or guardian must reach at least this level, whatever the request asks. Low changes nothing.',
						)
					}}
				</p>
			</div>

			<NcButton variant="primary" :disabled="saving" @click="save">
				{{ t('filinq', 'Save signer identity settings') }}
			</NcButton>
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
	NcSelect,
	NcSettingsSection,
	NcTextField,
} from '@nextcloud/vue'

// The signer identity rails panel (signer-identity-rails REQ-DDSIR-005).
// It talks to its own admin-only endpoint, so the settings page's big
// settings payload stays untouched. The broker's secret never passes
// through here: the admin enters the credential reference only.
const FIELDS = [
	'issuer',
	'clientId',
	'authorizationEndpoint',
	'tokenEndpoint',
	'redirectUri',
	'scopes',
	'credentialRef',
	'acrMapping',
	'evidenceMaxAgeMinutes',
	'guardianMinimumAssurance',
]

export default {
	name: 'SignerIdentitySettings',
	components: {
		NcButton,
		NcLoadingIcon,
		NcSelect,
		NcSettingsSection,
		NcTextField,
	},

	data() {
		return {
			loading: true,
			saving: false,
			provider: 'nextcloud-session',
			form: Object.fromEntries(FIELDS.map((field) => [field, ''])),
		}
	},

	computed: {
		providerOptions() {
			return [
				{ id: 'nextcloud-session', label: t('filinq', 'Nextcloud login') },
				{
					id: 'oidc-broker',
					label: t('filinq', 'Identity broker (DigiD, eHerkenning, iDIN)'),
				},
			]
		},

		assuranceOptions() {
			return [
				{ id: 'low', label: t('filinq', 'Low') },
				{ id: 'substantial', label: t('filinq', 'Substantial') },
				{ id: 'high', label: t('filinq', 'High') },
			]
		},

		acrPlaceholder() {
			return '{"urn:your-broker:idin": {"means": "idin", "assurance": "substantial"}}'
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		t,

		url() {
			return generateUrl('/apps/filinq/api/settings/signer-identity')
		},

		apply(data) {
			this.provider = data.provider || 'nextcloud-session'
			for (const field of FIELDS) {
				this.form[field] =
					data[field] === undefined || data[field] === null
						? ''
						: String(data[field])
			}
		},

		async load() {
			try {
				const { data } = await axios.get(this.url())
				this.apply(data)
			} catch {
				showError(t('filinq', 'Could not load the signer identity settings'))
			} finally {
				this.loading = false
			}
		},

		async save() {
			this.saving = true
			try {
				const { data } = await axios.put(this.url(), {
					provider: this.provider,
					...this.form,
				})
				this.apply(data)
				showSuccess(t('filinq', 'Signer identity settings saved'))
			} catch {
				showError(
					t(
						'filinq',
						'Check the signer identity settings: one value is not valid',
					),
				)
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style scoped>
.signer-identity__field,
.signer-identity__broker {
	margin-bottom: calc(var(--default-grid-baseline) * 4);
	max-width: 640px;
}

.signer-identity__broker {
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: calc(var(--default-grid-baseline) * 3);
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
}

.signer-identity__hint {
	color: var(--color-text-maxcontrast);
}

.signer-identity__label {
	font-weight: bold;
}

.signer-identity__acr {
	width: 100%;
	font-family: monospace;
	color: var(--color-main-text);
	background-color: var(--color-main-background);
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius);
}
</style>
