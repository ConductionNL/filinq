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
			<NcButton @click="$emit('cancel')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="reason.trim() === ''"
				data-testid="final-reason-confirm"
				@click="$emit('confirm', reason.trim())">
				{{ confirmLabel }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { NcButton, NcDialog, NcTextField } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'FinalDocumentReasonDialog',
	components: { NcButton, NcDialog, NcTextField },

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
