<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Mail archive import settings: whether readpst is installed on the server, so
an admin knows whether Outlook PST archives can be imported, and how many
messages one background run takes.

@spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-the-container-format-is-detected-and-split-on-the-server-req-mai-002
-->

<template>
	<NcSettingsSection
		:name="t('filinq', 'Mail archive import')"
		:description="
			t(
				'filinq',
				'MBOX and ZIP archives are split by filinq itself. Outlook PST archives need the readpst tool (libpst) on the server. No message is sent to an outside service.',
			)
		">
		<NcLoadingIcon v-if="loading" :size="32" />
		<template v-else>
			<NcNoteCard
				:type="readpstInstalled ? 'success' : 'warning'"
				data-testid="mail-archive-readpst-status">
				{{
					readpstInstalled
						? t('filinq', 'readpst is installed: PST archives can be imported.')
						: t('filinq', 'readpst is not installed on this server: a PST import fails at once. MBOX and ZIP archives still work.')
				}}
			</NcNoteCard>

			<NcTextField
				v-model="messagesPerRun"
				type="number"
				:label="t('filinq', 'Messages per run')"
				:helperText="
					t(
						'filinq',
						'At most this many messages of an archive are handled per background run, so a large archive is imported over many runs.',
					)
				" />

			<NcButton
				variant="primary"
				:disabled="saving"
				data-testid="mail-archive-settings-save"
				@click="save">
				{{ t('filinq', 'Save mail archive import settings') }}
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
	NcNoteCard,
	NcSettingsSection,
	NcTextField,
} from '@nextcloud/vue'
import {
	loadImportSettings,
	saveImportSettings,
} from '../../services/mailArchiveImport.js'

export default {
	name: 'MailArchiveImportSettings',
	components: {
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		NcSettingsSection,
		NcTextField,
	},

	data() {
		return {
			loading: true,
			saving: false,
			readpstInstalled: false,
			messagesPerRun: '200',
		}
	},

	/**
	 * Load the settings and the readpst status.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-the-container-format-is-detected-and-split-on-the-server-req-mai-002
	 */
	async mounted() {
		const result = await loadImportSettings()
		this.loading = false
		if (!result.ok) {
			showError(
				t('filinq', 'The mail archive import settings could not be loaded.'),
			)
			return
		}
		this.readpstInstalled = result.data.readpstInstalled === true
		this.messagesPerRun = String(result.data.messagesPerRun)
	},

	methods: {
		t,

		/**
		 * Save the messages per run.
		 *
		 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-a-large-archive-drains-in-resumable-units-req-mai-004
		 */
		async save() {
			this.saving = true
			const result = await saveImportSettings(this.messagesPerRun)
			this.saving = false
			if (!result.ok) {
				showError(
					t('filinq', 'The mail archive import settings could not be saved.'),
				)
				return
			}
			this.messagesPerRun = String(result.data.messagesPerRun)
			showSuccess(t('filinq', 'Mail archive import settings saved'))
		},
	},
}
</script>
