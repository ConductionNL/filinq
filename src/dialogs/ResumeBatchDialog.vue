<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Confirms resuming an errored or cancelled batch. Only the files that did not succeed go back in the queue.

@spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md
-->

<template>
	<NcDialog
		:name="t('filinq', 'Resume this batch?')"
		@closing="$emit('cancel')">
		<template #default>
			<p>{{ t('filinq', 'Only the files that did not succeed are processed again. Files already anonymized are not anonymized a second time.') }}</p>
		</template>
		<template #actions>
			<CnDialogActions
				:confirmLabel="t('filinq', 'Resume batch')"
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
	name: 'ResumeBatchDialog',
	components: { CnDialogActions, NcDialog },

	props: {
		// True while the request is on its way, so it cannot be sent twice.
		busy: { type: Boolean, default: false },
	},

	emits: ['confirm', 'cancel'],

	methods: { t },
}
</script>
