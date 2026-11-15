<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Confirms withdrawing a signing request. Withdrawing ends it for every
signatory and cannot be undone, so it is never one click.

@spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
-->

<template>
	<NcDialog
		:name="t('filinq', 'Withdraw this signing request?')"
		@closing="$emit('cancel')">
		<template #default>
			<p>
				{{
					t(
						'filinq',
						'Every signatory loses access, and nobody can sign it any more. This cannot be undone.',
					)
				}}
			</p>
		</template>
		<template #actions>
			<CnDialogActions
				:confirmLabel="t('filinq', 'Withdraw')"
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
	name: 'CancelSigningRequestDialog',
	components: { CnDialogActions, NcDialog },

	props: {
		// True while the withdrawal is on its way, so it cannot be sent twice.
		busy: { type: Boolean, default: false },
	},

	emits: ['confirm', 'cancel'],

	methods: { t },
}
</script>
