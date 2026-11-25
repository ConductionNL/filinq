<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Maps the folders of a mail archive to dossiers before the import starts. A
folder left without a dossier goes to the intake inbox, where a person sorts
its mails one by one. The dialog does not judge whether the archivist may
write to a dossier: the server refuses the whole mapping when they may not,
and the dialog shows that answer.

@spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#4-1
-->

<template>
	<NcDialog
		:name="t('filinq', 'Map the folders to dossiers')"
		data-testid="mail-archive-mapping-dialog"
		@closing="$emit('cancel')">
		<template #default>
			<p>
				{{
					t(
						'filinq',
						'Mails in a folder with a dossier are filed into that dossier. Mails in a folder without one go to the intake inbox.',
					)
				}}
			</p>
			<NcTextField
				v-if="folders.length > 8"
				v-model="search"
				:label="t('filinq', 'Find a folder')"
				data-testid="mail-archive-mapping-search" />
			<div
				v-for="folder in visibleFolders"
				:key="folder.archiveFolder"
				class="mail-archive-mapping__row"
				data-testid="mail-archive-mapping-row">
				<NcSelect
					v-model="picked[folder.archiveFolder]"
					class="mail-archive-mapping__select"
					:inputLabel="folderLabel(folder)"
					:options="dossierOptions"
					:placeholder="t('filinq', 'Intake inbox')"
					:disabled="saving"
					:clearable="true" />
			</div>
			<p
				v-if="folders.length === 0"
				data-testid="mail-archive-mapping-empty">
				{{ t('filinq', 'This archive holds no messages.') }}
			</p>
			<p
				v-if="error"
				class="mail-archive-mapping__error"
				data-testid="mail-archive-mapping-error">
				{{ error }}
			</p>
		</template>
		<template #actions>
			<CnDialogActions
				:saving="saving"
				:canSubmit="true"
				:confirmLabel="t('filinq', 'Start import')"
				@cancel="$emit('cancel')"
				@submit="confirm" />
		</template>
	</NcDialog>
</template>

<script>
import { CnDialogActions } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcDialog, NcSelect, NcTextField } from '@nextcloud/vue'
import { mappingPayload } from '../services/mailArchiveImport.js'

export default {
	name: 'MailArchiveMappingDialog',
	components: { CnDialogActions, NcDialog, NcSelect, NcTextField },

	props: {
		// The import in `mapping`, with its folderMap.
		archiveImport: { type: Object, required: true },
		// Options of { id, label } for the dossiers.
		dossierOptions: { type: Array, default: () => [] },
		// True while the parent is waiting for the server.
		saving: { type: Boolean, default: false },
		// What the server said when it refused.
		error: { type: String, default: '' },
	},

	emits: ['confirm', 'cancel'],

	data() {
		const picked = {}
		for (const folder of this.archiveImport.folderMap || []) {
			picked[folder.archiveFolder] = null
		}
		return { picked, search: '' }
	},

	computed: {
		folders() {
			return this.archiveImport.folderMap || []
		},

		visibleFolders() {
			const needle = this.search.trim().toLowerCase()
			if (needle === '') {
				return this.folders
			}
			return this.folders.filter((folder) =>
				folder.archiveFolder.toLowerCase().includes(needle),
			)
		},
	},

	methods: {
		t,

		folderLabel(folder) {
			return t('filinq', '{folder} ({count} messages)', {
				folder: folder.archiveFolder,
				count: folder.messages ?? 0,
			})
		},

		confirm() {
			this.$emit('confirm', mappingPayload(this.folders, this.picked))
		},
	},
}
</script>

<style scoped>
.mail-archive-mapping__row {
	margin-block: calc(var(--default-grid-baseline) * 2);
}

.mail-archive-mapping__select {
	width: 100%;
}

.mail-archive-mapping__error {
	color: var(--color-error-text);
}
</style>
