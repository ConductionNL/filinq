<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Detection provider settings: which engine finds personal data for each
organisation, the local Hermiq agentflow the language model runs in, and a
test that says whether it can be used now. An organisation not opted in keeps
OpenRegister's own detection.

@spec openspec/changes/archive/2026-11-24-llm-entity-detection-provider/tasks.md#task-4.2
-->

<template>
	<NcSettingsSection
		:name="t('filinq', 'Entity detection provider')"
		:description="
			t(
				'filinq',
				'Choose per organisation which engine finds personal data in documents. When the language model cannot run, the default detection is used and the review says so.',
			)
		">
		<NcLoadingIcon v-if="loading" :size="32" />
		<template v-else>
			<div class="detection-settings__row">
				<NcSelect
					v-if="organisations.length > 0"
					v-model="organisation"
					:inputLabel="t('filinq', 'Organisation')"
					:options="organisations"
					:clearable="false"
					label="name"
					:reduce="(option) => option.uuid"
					data-testid="detection-settings-organisation" />
				<NcTextField
					v-else
					v-model="organisation"
					:label="t('filinq', 'Organisation ID')"
					:helperText="
						t(
							'filinq',
							'The organisations could not be listed. Enter the ID of the organisation.',
						)
					" />
				<NcSelect
					v-model="provider"
					:inputLabel="t('filinq', 'Detection provider')"
					:options="providerOptions"
					:clearable="false"
					label="label"
					:reduce="(option) => option.id"
					data-testid="detection-settings-provider" />
			</div>

			<div class="detection-settings__row">
				<NcTextField
					v-model="endpoint"
					:label="t('filinq', 'Hermiq endpoint')"
					:helperText="
						t(
							'filinq',
							'The address of the Hermiq instance that runs the agentflow.',
						)
					" />
				<NcTextField
					v-model="agentflow"
					:label="t('filinq', 'Agentflow')" />
			</div>

			<NcCheckboxRadioSwitch
				v-model="requireLocal"
				type="switch"
				data-testid="detection-settings-require-local">
				{{ t('filinq', 'Only allow an endpoint on this server or the local network') }}
			</NcCheckboxRadioSwitch>

			<div class="detection-settings__actions">
				<NcButton
					variant="primary"
					:disabled="saving"
					data-testid="detection-settings-save"
					@click="save">
					{{ t('filinq', 'Save detection settings') }}
				</NcButton>
				<NcButton
					variant="secondary"
					:disabled="testing"
					data-testid="detection-settings-test"
					@click="test">
					{{ t('filinq', 'Test the connection') }}
				</NcButton>
			</div>

			<NcNoteCard
				v-if="testResult"
				:type="testResult.available ? 'success' : 'warning'"
				data-testid="detection-settings-test-result">
				{{ testText }}
			</NcNoteCard>
		</template>
	</NcSettingsSection>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcLoadingIcon,
	NcNoteCard,
	NcSelect,
	NcSettingsSection,
	NcTextField,
} from '@nextcloud/vue'
import {
	loadDetectionSettings,
	loadOrganisations,
	providerFor,
	providerLabel,
	saveDetectionSettings,
	testDetectionProvider,
	testResultText,
} from '../../services/detectionProvider.js'

export default {
	name: 'DetectionProviderSettings',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
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
			organisations: [],
			organisation: '',
			providers: {},
			available: ['default', 'llm'],
			provider: 'default',
			endpoint: '',
			agentflow: '',
			requireLocal: true,
			testResult: null,
		}
	},

	computed: {
		/**
		 * The provider picker's options.
		 *
		 * @return {Array<{id: string, label: string}>} The options.
		 */
		providerOptions() {
			return this.available.map((id) => ({ id, label: providerLabel(id) }))
		},

		/**
		 * The sentence for the last connectivity test.
		 *
		 * @return {string} The sentence.
		 */
		testText() {
			return testResultText(this.testResult)
		},
	},

	watch: {
		/**
		 * Show the chosen organisation's provider.
		 *
		 * @param {string} organisation The organisation uuid.
		 */
		organisation(organisation) {
			this.provider = providerFor(this.providers, organisation)
		},
	},

	/**
	 * Load the settings and the organisations.
	 *
	 * @spec openspec/changes/archive/2026-11-24-llm-entity-detection-provider/tasks.md#task-4.2
	 */
	async mounted() {
		const [settings, organisations] = await Promise.all([
			loadDetectionSettings(),
			loadOrganisations(),
		])
		this.loading = false
		if (!settings.ok) {
			showError(t('filinq', 'The detection settings could not be loaded.'))
			return
		}
		this.applySettings(settings.data)
		if (organisations.ok) {
			this.organisations = organisations.data.organisations
			this.organisation = organisations.data.active
				?? this.organisations[0]?.uuid
				?? ''
		}
		this.provider = providerFor(this.providers, this.organisation)
	},

	methods: {
		t,

		/**
		 * Take the settings as the server stored them.
		 *
		 * @param {object} data The settings.
		 */
		applySettings(data) {
			this.providers = data.providers || {}
			if (Array.isArray(data.available) && data.available.length > 0) {
				this.available = data.available
			}
			this.endpoint = data.endpoint ?? ''
			this.agentflow = data.agentflow ?? ''
			this.requireLocal = data.requireLocal !== false
		},

		/**
		 * Save the chosen organisation's provider and the Hermiq settings.
		 *
		 * @spec openspec/changes/archive/2026-11-24-llm-entity-detection-provider/tasks.md#task-4.2
		 */
		async save() {
			const fields = {
				endpoint: this.endpoint.trim(),
				agentflow: this.agentflow.trim(),
				requireLocal: this.requireLocal,
			}
			const organisation = String(this.organisation ?? '').trim()
			if (organisation !== '') {
				fields.organisation = organisation
				fields.provider = this.provider
			}
			this.saving = true
			const result = await saveDetectionSettings(fields)
			this.saving = false
			if (!result.ok) {
				showError(
					result.status === 400
						? t('filinq', 'The detection settings were not saved: check that the endpoint is an http or https address.')
						: t('filinq', 'The detection settings could not be saved.'),
				)
				return
			}
			this.applySettings(result.data)
			this.provider = providerFor(this.providers, organisation)
			showSuccess(t('filinq', 'Detection settings saved'))
		},

		/**
		 * Test whether the language model provider can be used now.
		 *
		 * @spec openspec/changes/archive/2026-11-24-llm-entity-detection-provider/tasks.md#task-4.2
		 */
		async test() {
			this.testing = true
			const result = await testDetectionProvider()
			this.testing = false
			if (!result.ok) {
				this.testResult = null
				showError(t('filinq', 'The connection could not be tested.'))
				return
			}
			this.testResult = result.data
		},
	},
}
</script>

<style scoped>
.detection-settings__row {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
	align-items: flex-end;
	margin-block: 8px;
}

.detection-settings__actions {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin-block: 12px;
}
</style>
