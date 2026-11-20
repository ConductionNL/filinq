<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The settings of the active organisation: its objection period, its
anonymisation profile and its default huisstijl, each inheriting the
instance setting when left empty. Every member reads them; only an admin of
the organisation (or an instance admin) can save, which the server enforces.

@spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
-->

<template>
	<div class="organisation-settings" data-testid="organisation-settings">
		<CnPageHeader
			:title="t('filinq', 'Organisation settings')"
			:description="
				t(
					'filinq',
					'Settings that apply to your active organisation only. A field left empty uses the setting of the whole instance.',
				)
			"
			icon="OfficeBuildingCog" />

		<CnSettingsSection
			:name="organisationLabel"
			:loading="loading"
			:error="loadFailed"
			:errorMessage="t('filinq', 'The organisation settings could not be loaded.')"
			:empty="!loading && !loadFailed && !organisation"
			:emptyMessage="
				t(
					'filinq',
					'No active organisation. Choose an organisation in OpenRegister to see its settings.',
				)
			">
			<p
				v-if="!canEdit"
				class="organisation-settings__readonly"
				data-testid="organisation-settings-readonly">
				{{
					t(
						'filinq',
						'Only an admin of this organisation can change these settings.',
					)
				}}
			</p>

			<form class="organisation-settings__form" @submit.prevent="save">
				<NcTextField
					v-model="form.consentPeriodDays"
					type="number"
					min="1"
					max="365"
					:disabled="!canEdit || saving"
					:label="t('filinq', 'Objection period (days)')"
					:helperText="
						t(
							'filinq',
							'Empty uses the instance period of {days} days. The Woo asks for at least four weeks.',
							{ days: instanceDays },
						)
					"
					data-testid="organisation-settings-period" />

				<NcSelect
					v-model="form.anonymize"
					:inputLabel="t('filinq', 'Anonymise')"
					:options="anonymizeOptions"
					:multiple="true"
					:disabled="!canEdit || saving"
					data-testid="organisation-settings-anonymize" />

				<NcSelect
					v-model="form.keepVisible"
					:inputLabel="t('filinq', 'Keep visible')"
					:options="keepVisibleOptions"
					:multiple="true"
					:disabled="!canEdit || saving"
					data-testid="organisation-settings-keep-visible" />
				<p class="organisation-settings__hint">
					{{
						t(
							'filinq',
							'Leave both lists empty to use the instance anonymisation profile.',
						)
					}}
				</p>

				<NcSelect
					v-model="form.defaultHuisstijl"
					:inputLabel="t('filinq', 'Default huisstijl')"
					:options="huisstijlOptions"
					label="label"
					:reduce="(option) => option.id"
					:clearable="true"
					:placeholder="t('filinq', 'No default huisstijl')"
					:disabled="!canEdit || saving"
					data-testid="organisation-settings-huisstijl" />

				<NcTextArea
					v-model="form.notes"
					:label="t('filinq', 'Notes')"
					:disabled="!canEdit || saving"
					data-testid="organisation-settings-notes" />

				<div v-if="canEdit" class="organisation-settings__actions">
					<NcButton
						variant="primary"
						type="submit"
						:disabled="saving"
						data-testid="organisation-settings-save">
						{{ t('filinq', 'Save') }}
					</NcButton>
				</div>
			</form>
		</CnSettingsSection>
	</div>
</template>

<script>
import { CnPageHeader, CnSettingsSection } from '@conduction/nextcloud-vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcSelect, NcTextArea, NcTextField } from '@nextcloud/vue'
import {
	ENTITY_CATEGORIES,
	loadHuisstijlOptions,
	loadOrganisationSettings,
	saveOrganisationSettings,
	toForm,
} from '../../services/organisationSettings.js'

export default {
	name: 'OrganisationSettings',
	components: {
		CnPageHeader,
		CnSettingsSection,
		NcButton,
		NcSelect,
		NcTextArea,
		NcTextField,
	},

	data() {
		return {
			loading: true,
			loadFailed: false,
			saving: false,
			organisation: null,
			canEdit: false,
			instanceDays: 28,
			huisstijlOptions: [],
			form: toForm(null),
		}
	},

	computed: {
		/**
		 * The section name: the organisation it covers.
		 *
		 * @return {string}
		 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
		 */
		organisationLabel() {
			if (!this.organisation) {
				return t('filinq', 'Organisation')
			}
			return this.organisation.name || this.organisation.uuid
		},

		/**
		 * Categories not already kept visible.
		 *
		 * @return {string[]}
		 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
		 */
		anonymizeOptions() {
			return ENTITY_CATEGORIES.filter(
				(category) => !this.form.keepVisible.includes(category),
			)
		},

		/**
		 * Categories not already anonymised.
		 *
		 * @return {string[]}
		 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
		 */
		keepVisibleOptions() {
			return ENTITY_CATEGORIES.filter(
				(category) => !this.form.anonymize.includes(category),
			)
		},
	},

	/**
	 * Load the settings and, for an editor, the huisstijl options.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
	 */
	async mounted() {
		await this.load()
	},

	methods: {
		t,

		/**
		 * Read the active organisation's settings.
		 *
		 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
		 */
		async load() {
			this.loading = true
			const result = await loadOrganisationSettings()
			this.loading = false
			if (!result.ok) {
				this.loadFailed = true
				return
			}
			this.apply(result.data)
			if (this.organisation) {
				this.huisstijlOptions = await loadHuisstijlOptions()
			}
		},

		/**
		 * Take a view from the server into the page.
		 *
		 * @param {object} view The view.
		 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
		 */
		apply(view) {
			this.organisation = view?.organisation || null
			this.canEdit = Boolean(view?.canEdit)
			this.instanceDays = view?.instanceDefaults?.consentPeriodDays ?? 28
			this.form = toForm(view?.settings || null)
		},

		/**
		 * Save the overrides.
		 *
		 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
		 */
		async save() {
			this.saving = true
			const result = await saveOrganisationSettings(this.form)
			this.saving = false
			if (!result.ok) {
				showError(
					result.status === 403
						? t('filinq', 'Only an admin of this organisation can change these settings.')
						: result.error || t('filinq', 'The organisation settings could not be saved.'),
				)
				return
			}
			this.apply(result.data)
			showSuccess(t('filinq', 'Organisation settings saved'))
		},
	},
}
</script>

<style scoped>
.organisation-settings {
	padding: 0 16px 16px;
}

.organisation-settings__form {
	display: flex;
	flex-direction: column;
	gap: 12px;
	max-width: 640px;
}

.organisation-settings__readonly,
.organisation-settings__hint {
	color: var(--color-text-maxcontrast);
}

.organisation-settings__actions {
	display: flex;
	justify-content: flex-end;
}
</style>
