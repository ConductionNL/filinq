<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Model filling: the task-processing provider that would read the documents,
the switch (off on a fresh install, and disabled while no text model is
there), and the profiles that declare which fields of a record schema a
model may suggest (extraction-model-filled-fields REQ-EMF-001, REQ-EMF-004).

@spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
-->

<template>
	<NcSettingsSection
		:name="t('filinq', 'Model filling')"
		:description="
			t(
				'filinq',
				'Let a text model suggest the fields of a record from one of its documents. A person decides every field before anything is written.',
			)
		"
		data-testid="settings-model-filling">
		<NcLoadingIcon v-if="loading" :size="32" />
		<template v-else>
			<NcNoteCard
				v-if="status.provider"
				type="info"
				data-testid="model-filling-provider">
				{{ t('filinq', 'The documents are read by {provider}. If that provider is not on this server, the document text leaves it.', { provider: status.provider }) }}
			</NcNoteCard>
			<NcNoteCard v-else type="warning" data-testid="model-filling-no-model">
				{{ t('filinq', 'No text model is available on this server.') }}
			</NcNoteCard>

			<NcCheckboxRadioSwitch
				type="switch"
				:modelValue="status.enabled"
				:disabled="!status.provider || switching"
				data-testid="model-filling-switch"
				@update:modelValue="toggle">
				{{ t('filinq', 'Fill fields from documents') }}
			</NcCheckboxRadioSwitch>

			<h3 class="model-filling__heading">
				{{ t('filinq', 'Field profiles') }}
			</h3>
			<p v-if="profiles.length === 0" data-testid="model-filling-no-profiles">
				{{ t('filinq', 'No profile declares fields yet.') }}
			</p>
			<table v-else class="model-filling__profiles">
				<thead>
					<tr>
						<th scope="col">
							{{ t('filinq', 'Register') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Schema') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Fields') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Active') }}
						</th>
						<th scope="col">
							<span class="hidden-visually">{{ t('filinq', 'Actions') }}</span>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="profile in profiles" :key="idOf(profile)">
						<td>{{ profile.register }}</td>
						<td>{{ profile.schema }}</td>
						<td>{{ (profile.fields || []).join(', ') }}</td>
						<td>{{ profile.active ? t('filinq', 'Yes') : t('filinq', 'No') }}</td>
						<td>
							<NcButton variant="tertiary" @click="edit(profile)">
								{{ t('filinq', 'Edit') }}
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>

			<form class="model-filling__form" data-testid="model-filling-form" @submit.prevent="save">
				<h3 class="model-filling__heading">
					{{ editing ? t('filinq', 'Edit profile') : t('filinq', 'New profile') }}
				</h3>
				<NcTextField v-model="form.register" :label="t('filinq', 'Register')" />
				<NcTextField v-model="form.schema" :label="t('filinq', 'Schema')" />
				<NcTextField
					v-model="form.fields"
					:label="t('filinq', 'Fields')"
					:helperText="t('filinq', 'The schema\'s field names, separated by commas. A BSN field cannot be filled by a model.')" />
				<NcCheckboxRadioSwitch v-model="form.active" type="switch">
					{{ t('filinq', 'Active') }}
				</NcCheckboxRadioSwitch>
				<NcNoteCard v-if="error" type="error">
					{{ error }}
				</NcNoteCard>
				<div class="model-filling__buttons">
					<NcButton type="submit" variant="primary" :disabled="saving || !canSave">
						{{ t('filinq', 'Save profile') }}
					</NcButton>
					<NcButton v-if="editing" variant="tertiary" @click="reset">
						{{ t('filinq', 'Cancel') }}
					</NcButton>
				</div>
			</form>
		</template>
	</NcSettingsSection>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcLoadingIcon,
	NcNoteCard,
	NcSettingsSection,
	NcTextField,
} from '@nextcloud/vue'
import {
	fetchFieldFillStatus,
	idOf,
	listFieldProfiles,
	saveFieldProfile,
	setFieldFillEnabled,
} from '../../services/fieldFill.js'

const EMPTY = () => ({ register: '', schema: '', fields: '', active: false, declaringApp: 'filinq' })

export default {
	name: 'ModelFillingSettings',
	components: { NcButton, NcCheckboxRadioSwitch, NcLoadingIcon, NcNoteCard, NcSettingsSection, NcTextField },

	data() {
		return {
			loading: true,
			switching: false,
			saving: false,
			status: { enabled: false, provider: null, available: false },
			profiles: [],
			form: EMPTY(),
			editing: '',
			error: '',
		}
	},

	computed: {
		/**
		 * The form's field names, trimmed and without blanks.
		 *
		 * @return {Array<string>}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		fieldNames() {
			return String(this.form.fields || '').split(',').map((f) => f.trim()).filter(Boolean)
		},

		/**
		 * A profile can be saved once it names a register, a schema and at least one field.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		canSave() {
			return this.form.register.trim() !== '' && this.form.schema.trim() !== '' && this.fieldNames.length > 0
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		t,
		idOf,

		/**
		 * Read the status and the profiles.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		async load() {
			this.loading = true
			try {
				const [status, profiles] = await Promise.all([fetchFieldFillStatus(), listFieldProfiles()])
				this.status = { ...this.status, ...status }
				this.profiles = profiles
			} catch {
				this.error = t('filinq', 'The model filling settings could not be read.')
			} finally {
				this.loading = false
			}
		},

		/**
		 * Switch model filling on or off.
		 *
		 * @param {boolean} enabled On or off.
		 * @return {Promise<void>}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		async toggle(enabled) {
			if (!this.status.provider) {
				return
			}
			this.switching = true
			this.error = ''
			try {
				this.status = { ...this.status, ...(await setFieldFillEnabled(enabled === true)) }
			} catch (error) {
				this.error = error?.response?.data?.error || t('filinq', 'The switch could not be saved.')
			} finally {
				this.switching = false
			}
		},

		/**
		 * Load a profile into the form.
		 *
		 * @param {object} profile The profile.
		 * @return {void}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		edit(profile) {
			this.error = ''
			this.editing = idOf(profile)
			this.form = {
				register: String(profile.register || ''),
				schema: String(profile.schema || ''),
				fields: (profile.fields || []).join(', '),
				active: profile.active === true,
				declaringApp: String(profile.declaringApp || 'filinq'),
			}
		},

		/**
		 * Empty the form and leave edit mode.
		 *
		 * @return {void}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		reset() {
			this.editing = ''
			this.form = EMPTY()
			this.error = ''
		},

		/**
		 * Save the profile in the form; the server refuses a BSN field.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		async save() {
			if (!this.canSave) {
				return
			}
			this.saving = true
			this.error = ''
			try {
				await saveFieldProfile({
					declaringApp: this.form.declaringApp,
					register: this.form.register.trim(),
					schema: this.form.schema.trim(),
					fields: this.fieldNames,
					active: this.form.active === true,
				}, this.editing)
				this.reset()
				this.profiles = await listFieldProfiles()
			} catch (error) {
				this.error = error?.response?.data?.error || t('filinq', 'The profile could not be saved.')
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style scoped>
.model-filling__heading {
	font-size: 1em;
	font-weight: bold;
	margin: 16px 0 8px;
}

.model-filling__profiles {
	width: 100%;
	border-collapse: collapse;
}

.model-filling__profiles th,
.model-filling__profiles td {
	text-align: start;
	padding: 4px 8px 4px 0;
	border-bottom: 1px solid var(--color-border);
}

.model-filling__form {
	display: flex;
	flex-direction: column;
	gap: 8px;
	max-width: 600px;
}

.model-filling__buttons {
	display: flex;
	gap: 8px;
}
</style>
