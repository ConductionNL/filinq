<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The intake channels page: the scanner profiles that say where a scanner
writes and how a batch landing there is cut, and a form to add one. Profiles
are instance settings, not objects, so the page reads and declares them
through the scan profile routes; declaring is for administrators only.

@spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
-->

<template>
	<div>
		<CnIndexPage
			:title="t('filinq', 'Intake channels')"
			:description="
				t(
					'filinq',
					'A scanner profile names the folder a scanner writes to and how a batch that lands there is cut into documents.',
				)
			"
			:showTitle="true"
			:objects="profiles"
			:columns="tableColumns"
			:loading="loading"
			v-bind="$options.listOnlyIndexPage"
			:showAdd="false"
			rowKey="id"
			:emptyText="t('filinq', 'No scanner profiles yet. Add one below.')"
			data-testid="intake-channels-index"
			@refresh="load">
			<template #below-header>
				<h3>{{ t('filinq', 'Scanner profiles') }}</h3>
				<form
					class="intake-channels__form"
					data-testid="scan-profile-form"
					@submit.prevent="add">
					<NcTextField
						v-model="form.label"
						:label="t('filinq', 'Name')"
						:error="Boolean(errors.label)"
						:helperText="errors.label ? t('filinq', 'Give the profile a name.') : ''" />
					<NcTextField
						v-model="form.watchedFolder"
						:label="t('filinq', 'Watched folder')"
						:error="Boolean(errors.watchedFolder)"
						:helperText="
							errors.watchedFolder
								? t('filinq', 'Name the folder the scanner writes to.')
								: ''
						" />
					<NcTextField
						v-model="form.resolution"
						type="number"
						:label="t('filinq', 'Resolution (dpi)')"
						:error="Boolean(errors.resolution)"
						:helperText="
							errors.resolution
								? t('filinq', 'Choose a resolution from 75 to 1200 dpi.')
								: ''
						" />
					<NcSelect
						v-model="form.colourMode"
						:inputLabel="t('filinq', 'Colour mode')"
						:options="colourOptions"
						:clearable="false"
						label="label"
						:reduce="(option) => option.id" />
					<NcSelect
						v-model="form.separatorMode"
						:inputLabel="t('filinq', 'Separator')"
						:options="separatorOptions"
						:clearable="false"
						label="label"
						:reduce="(option) => option.id" />
					<NcButton
						variant="primary"
						type="submit"
						:disabled="saving"
						data-testid="scan-profile-add">
						{{ t('filinq', 'Add scanner profile') }}
					</NcButton>
				</form>
			</template>

			<template #column-resolution="{ row }">
				{{ t('filinq', '{dpi} dpi', { dpi: row.resolution }) }}
			</template>

			<template #column-colourMode="{ row }">
				{{ optionLabel(colourOptions, row.colourMode) }}
			</template>

			<template #column-separatorMode="{ row }">
				{{ optionLabel(separatorOptions, row.separatorMode) }}
			</template>
		</CnIndexPage>
	</div>
</template>

<script>
import { CnIndexPage } from '@conduction/nextcloud-vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcSelect, NcTextField } from '@nextcloud/vue'
import { LIST_ONLY_INDEX_PAGE } from '../../constants/indexPage.js'
import {
	declareScanProfiles,
	emptyProfileForm,
	profileFormErrors,
	withNewProfile,
} from '../../services/scanProfiles.js'
import { listScanProfiles } from '../../services/separatorSheets.js'

export default {
	name: 'IntakeChannels',
	components: { CnIndexPage, NcButton, NcSelect, NcTextField },
	listOnlyIndexPage: LIST_ONLY_INDEX_PAGE,
	data() {
		return {
			profiles: [],
			loading: false,
			saving: false,
			form: emptyProfileForm(),
			errors: {},
		}
	},

	computed: {
		/**
		 * @return {Array<object>} The profile table's columns.
		 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
		 */
		tableColumns() {
			return [
				{ key: 'label', label: t('filinq', 'Name') },
				{ key: 'watchedFolder', label: t('filinq', 'Watched folder') },
				{ key: 'resolution', label: t('filinq', 'Resolution') },
				{ key: 'colourMode', label: t('filinq', 'Colour mode') },
				{ key: 'separatorMode', label: t('filinq', 'Separator') },
			]
		},

		/**
		 * @return {Array<object>} The colour modes.
		 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
		 */
		colourOptions() {
			return [
				{ id: 'colour', label: t('filinq', 'Colour') },
				{ id: 'greyscale', label: t('filinq', 'Greyscale') },
				{ id: 'blackAndWhite', label: t('filinq', 'Black and white') },
			]
		},

		/**
		 * @return {Array<object>} The separator modes.
		 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
		 */
		separatorOptions() {
			return [
				{ id: 'qr', label: t('filinq', 'Separator sheet with QR code') },
				{ id: 'blankPage', label: t('filinq', 'Blank page') },
				{ id: 'none', label: t('filinq', 'None: one document per batch') },
			]
		},
	},

	/**
	 * Read the profiles.
	 *
	 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
	 */
	mounted() {
		this.load()
	},

	methods: {
		t,
		/**
		 * Read the declared profiles.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
		 */
		async load() {
			this.loading = true
			try {
				this.profiles = await listScanProfiles()
			} catch {
				showError(t('filinq', 'The scanner profiles could not be read.'))
			} finally {
				this.loading = false
			}
		},

		/**
		 * The label of an option, or its id when it is not known.
		 *
		 * @param {Array<object>} options The options.
		 * @param {string} id The value.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
		 */
		optionLabel(options, id) {
			return options.find((option) => option.id === id)?.label ?? id ?? ''
		},

		/**
		 * Add the profile in the form and declare the list.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
		 */
		async add() {
			this.errors = profileFormErrors(this.form)
			if (Object.keys(this.errors).length > 0) {
				return
			}
			this.saving = true
			try {
				this.profiles = await declareScanProfiles(
					withNewProfile(this.profiles, this.form),
				)
				this.form = emptyProfileForm()
				showSuccess(t('filinq', 'Scanner profile added'))
			} catch (err) {
				showError(
					err?.response?.data?.error
						|| t('filinq', 'The scanner profile could not be added.'),
				)
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style scoped>
.intake-channels__form {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 2);
	align-items: flex-end;
	margin-block-end: calc(var(--default-grid-baseline) * 4);
}
</style>
