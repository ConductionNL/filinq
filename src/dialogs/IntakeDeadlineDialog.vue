<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Move a waiting document's handling deadline. The reason is required and stays
on the document.

@spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-every-waiting-document-has-a-handling-deadline-req-iws-001
-->

<template>
	<NcDialog :name="t('filinq', 'Change deadline')" @closing="$emit('cancel')">
		<template #default>
			<p>{{ explanation }}</p>
			<NcTextField
				v-model="deadline"
				type="date"
				:label="t('filinq', 'New deadline')"
				data-testid="intake-deadline-input" />
			<NcTextField
				v-model="reason"
				:label="t('filinq', 'Reason')"
				:placeholder="t('filinq', 'Waiting for the attachment')"
				data-testid="intake-deadline-reason" />
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
		</template>
		<template #actions>
			<CnDialogActions
				:canSubmit="canSubmit"
				:confirmLabel="t('filinq', 'Change deadline')"
				@cancel="$emit('cancel')"
				@submit="$emit('confirm', { deadline, reason: reason.trim() })" />
		</template>
	</NcDialog>
</template>

<script>
import { CnDialogActions } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcDialog, NcNoteCard, NcTextField } from '@nextcloud/vue'

export default {
	name: 'IntakeDeadlineDialog',
	components: { CnDialogActions, NcDialog, NcNoteCard, NcTextField },

	props: {
		// The waiting document whose deadline moves.
		document: { type: Object, required: true },
		// Whether the move is being saved.
		saving: { type: Boolean, default: false },
		// What the server answered when it refused.
		error: { type: String, default: '' },
	},

	emits: ['confirm', 'cancel'],

	data() {
		return {
			deadline: String(this.document?.handlingDeadline || '').slice(0, 10),
			reason: '',
		}
	},

	computed: {
		explanation() {
			return t(
				'filinq',
				'Pick the new deadline for "{subject}" and say why it moves. The reason stays with the document.',
				{ subject: this.document?.subject || t('filinq', 'this document') },
			)
		},

		canSubmit() {
			return !this.saving
				&& /^\d{4}-\d{2}-\d{2}$/.test(this.deadline)
				&& this.reason.trim() !== ''
		},
	},

	methods: { t },
}
</script>
