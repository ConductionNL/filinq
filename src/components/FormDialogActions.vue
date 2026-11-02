<!--
  SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  SPDX-License-Identifier: EUPL-1.2

  The buttons of a record form dialog: cancel, and create or save, which is
  disabled while saving or while the form cannot be submitted. Goes in the
  dialog's #actions slot.
-->
<template>
	<NcButton variant="tertiary" @click="$emit('cancel')">
		{{ t('filinq', 'Cancel') }}
	</NcButton>
	<NcButton
		variant="primary"
		:disabled="saving || !canSubmit"
		@click="$emit('submit')">
		<template v-if="saving" #icon>
			<NcLoadingIcon :size="20" />
		</template>
		{{ editing ? t('filinq', 'Save') : t('filinq', 'Create') }}
	</NcButton>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'

export default {
	name: 'FormDialogActions',
	components: {
		NcButton,
		NcLoadingIcon,
	},

	props: {
		/** Whether a save is in flight. */
		saving: {
			type: Boolean,
			default: false,
		},

		/** Whether the form holds enough to submit. */
		canSubmit: {
			type: Boolean,
			default: false,
		},

		/** Whether the dialog edits an existing record (Save) or creates one (Create). */
		editing: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['cancel', 'submit'],

	methods: {
		t,
	},
}
</script>
