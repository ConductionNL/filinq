<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
-->

<template>
	<NcDialog :name="name" @closing="$emit('cancel')">
		<template #default>
			<p>{{ explanation }}</p>
			<NcTextField
				v-model="reason"
				:label="t('filinq', 'Reason')"
				:placeholder="placeholder"
				data-testid="final-reason-input" />
		</template>
		<template #actions>
			<CnDialogActions
				:canSubmit="reason.trim() !== ''"
				:confirmLabel="confirmLabel"
				@cancel="$emit('cancel')"
				@submit="$emit('confirm', reason.trim())" />
		</template>
	</NcDialog>
</template>

<script>
import { CnDialogActions } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcDialog, NcTextField } from '@nextcloud/vue'

export default {
	name: 'FinalDocumentReasonDialog',
	components: { CnDialogActions, NcDialog, NcTextField },

	props: {
		// The dialog title.
		name: { type: String, required: true },
		// The sentence above the field, saying what the reason is for.
		explanation: { type: String, required: true },
		// The placeholder in the field.
		placeholder: { type: String, default: '' },
		// The label on the confirming button.
		confirmLabel: { type: String, required: true },
	},

	emits: ['confirm', 'cancel'],

	data() {
		return { reason: '' }
	},

	methods: { t },
}
</script>
