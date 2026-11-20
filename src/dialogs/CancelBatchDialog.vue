<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Confirms cancelling a running batch. A cancel stops the batch between files; the outputs already made stay.

@spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md
-->

<template>
	<NcDialog
		:name="t('filinq', 'Cancel this batch?')"
		@closing="$emit('cancel')">
		<template #default>
			<p>{{ t('filinq', 'The file in progress is finished, and no further file is started. Files already anonymized are kept.') }}</p>
		</template>
		<template #actions>
			<CnDialogActions
				:confirmLabel="t('filinq', 'Cancel batch')"
				:canSubmit="!busy"
				@cancel="$emit('cancel')"
				@submit="$emit('confirm')" />
		</template>
	</NcDialog>
</template>

<script>
import { CnDialogActions } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcDialog } from '@nextcloud/vue'

export default {
	name: 'CancelBatchDialog',
	components: { CnDialogActions, NcDialog },

	props: {
		// True while the request is on its way, so it cannot be sent twice.
		busy: { type: Boolean, default: false },
	},

	emits: ['confirm', 'cancel'],

	methods: { t },
}
</script>
