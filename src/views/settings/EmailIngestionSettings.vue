<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Email ingestion settings: which folders are watched inboxes and which dossier
each one files into. Filinq never connects to a mailbox itself; the section
says who delivers the .eml files instead.

@spec openspec/changes/email-ingestion/tasks.md#2-5
-->

<template>
	<NcSettingsSection
		:name="t('filinq', 'Email ingestion')"
		:description="
			t(
				'filinq',
				'Every few minutes filinq files the .eml files in a watched folder into the dossier of that folder, with a PDF copy beside them.',
			)
		">
		<NcLoadingIcon v-if="loading" :size="32" />
		<template v-else>
			<p class="email-settings__boundary" data-testid="email-settings-boundary">
				{{
					t(
						'filinq',
						'Filinq does not connect to a mailbox and stores no mailbox account. Put .eml files in the folder yourself, mount a mail archive there, or let an Integriq flow that holds the mailbox connection write each message as a .eml file into the folder. A message delivered twice is filed once.',
					)
				}}
			</p>

			<div
				v-for="(row, index) in inboxes"
				:key="index"
				class="email-settings__row">
				<NcTextField
					v-model="row.folderId"
					type="number"
					:label="t('filinq', 'Inbox folder ID')"
					:helperText="
						t(
							'filinq',
							'The file ID of the folder, shown in the Files app under the folder details.',
						)
					" />
				<NcTextField
					v-model="row.dossierRef"
					:label="t('filinq', 'Dossier ID')" />
				<NcButton
					variant="tertiary"
					:aria-label="t('filinq', 'Remove this inbox')"
					@click="inboxes.splice(index, 1)">
					{{ t('filinq', 'Remove') }}
				</NcButton>
			</div>

			<NcButton
				variant="secondary"
				data-testid="email-settings-add"
				@click="inboxes.push({ folderId: '', dossierRef: '' })">
				{{ t('filinq', 'Add an inbox') }}
			</NcButton>

			<div class="email-settings__row">
				<NcTextField
					v-model="filesPerTick"
					type="number"
					:label="t('filinq', 'Emails per run')"
					:helperText="
						t(
							'filinq',
							'At most this many emails are filed per run, so a large export is filed over several runs.',
						)
					" />
			</div>

			<NcButton
				variant="primary"
				:disabled="saving"
				data-testid="email-settings-save"
				@click="save">
				{{ t('filinq', 'Save email ingestion settings') }}
			</NcButton>
		</template>
	</NcSettingsSection>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcLoadingIcon,
	NcSettingsSection,
	NcTextField,
} from '@nextcloud/vue'
import { loadSettings, saveSettings } from '../../services/emailIngestion.js'

export default {
	name: 'EmailIngestionSettings',
	components: {
		NcButton,
		NcLoadingIcon,
		NcSettingsSection,
		NcTextField,
	},

	data() {
		return {
			loading: true,
			saving: false,
			inboxes: [],
			filesPerTick: 25,
		}
	},

	async mounted() {
		const result = await loadSettings()
		this.loading = false
		if (!result.ok) {
			showError(t('filinq', 'The email ingestion settings could not be loaded.'))
			return
		}
		this.inboxes = result.data.inboxes.map((row) => ({
			folderId: String(row.folderId),
			dossierRef: row.dossierRef,
		}))
		this.filesPerTick = result.data.filesPerTick
	},

	methods: {
		t,

		async save() {
			this.saving = true
			const result = await saveSettings(this.inboxes, this.filesPerTick)
			this.saving = false
			if (!result.ok) {
				showError(
					t(
						'filinq',
						'Every inbox needs a folder and a dossier, and a folder can be the inbox of one dossier only.',
					),
				)
				return
			}
			this.filesPerTick = result.data.filesPerTick
			showSuccess(t('filinq', 'Email ingestion settings saved'))
		},
	},
}
</script>

<style scoped>
.email-settings__boundary {
	max-width: 900px;
	margin-block-end: 12px;
	color: var(--color-text-maxcontrast);
}

.email-settings__row {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
	align-items: flex-end;
	margin-block: 8px;
}
</style>
