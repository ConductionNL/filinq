<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The notes on one waiting intake document, for colleagues. A note is a
Nextcloud comment on the document's file, so it is the same note the Files
sidebar shows, and who may read or add one is whoever may open the file.
Intake has no viewer to sit beside, so the notes open in this dialog.

@spec openspec/changes/work-document-notes/specs/document-notes/spec.md#requirement-notes-sit-beside-the-document-on-filinqs-screens-req-wdn-002
-->

<template>
	<NcDialog
		:name="title"
		data-testid="intake-notes-dialog"
		@closing="$emit('close')">
		<CnNotesCard
			v-if="fileId"
			:fileId="fileId"
			chromeless
			data-testid="intake-notes-card" />
		<p v-else class="intake-notes__none">
			{{ t('filinq', 'This document has no file to leave notes on.') }}
		</p>
	</NcDialog>
</template>

<script>
import { CnNotesCard } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcDialog } from '@nextcloud/vue'

export default {
	name: 'IntakeNotesDialog',

	components: {
		CnNotesCard,
		NcDialog,
	},

	props: {
		/** The intake document whose file carries the notes. */
		document: {
			type: Object,
			required: true,
		},
	},

	emits: ['close'],

	computed: {
		/**
		 * The file the notes belong to.
		 *
		 * @return {string|null} The file id, or null when the document has none.
		 * @spec openspec/changes/work-document-notes/specs/document-notes/spec.md#requirement-a-note-is-a-nextcloud-comment-on-the-file-req-wdn-001
		 */
		fileId() {
			const file = Number(this.document?.file || 0)
			return file > 0 ? String(file) : null
		},

		/**
		 * The dialog title, naming the document.
		 *
		 * @return {string} The title.
		 */
		title() {
			const name = this.document?.fileName || this.document?.subject || ''
			return name
				? t('filinq', 'Notes on {name}', { name })
				: t('filinq', 'Notes')
		},
	},

	methods: {
		t,
	},
}
</script>

<style scoped>
.intake-notes__none {
	color: var(--color-text-maxcontrast);
}
</style>
