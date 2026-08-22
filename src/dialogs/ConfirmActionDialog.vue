<template>
	<NcDialog :name="name" :canClose="!busy" @closing="$emit('cancel')">
		<template #default>
			<p class="confirm-action-dialog__message">
				{{ message }}
			</p>
		</template>
		<template #actions>
			<NcButton :disabled="busy" @click="$emit('cancel')">
				{{ cancelLabel || t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton :variant="variant" :disabled="busy" @click="$emit('confirm')">
				{{ confirmLabel || t('filinq', 'Delete') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { NcButton, NcDialog } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'

/**
 * Generic "are you sure?" dialog.
 *
 * Replaces `window.confirm()`, which is unstyled, untranslatable, not
 * theme-aware and — on a browser where the user has ticked "prevent this page
 * from creating additional dialogs" — silently returns false, so the action it
 * guards can never be taken. The dialog is a real focus-trapped NcDialog, and
 * the caller performs the destructive action only on `@confirm`.
 *
 * Lives in src/dialogs/ per ADR-004: NcDialog markup is never written inline
 * in a parent component.
 */
export default {
	name: 'ConfirmActionDialog',
	components: { NcButton, NcDialog },
	props: {
		/** Dialog title. */
		name: {
			type: String,
			required: true,
		},

		/** Body text explaining what is about to happen. */
		message: {
			type: String,
			default: '',
		},

		/** Label of the confirming button. Defaults to "Delete". */
		confirmLabel: {
			type: String,
			default: '',
		},

		/** Label of the dismissing button. Defaults to "Cancel". */
		cancelLabel: {
			type: String,
			default: '',
		},

		/** NcButton variant for the confirming button. */
		variant: {
			type: String,
			default: 'error',
		},

		/** Disable both buttons while the confirmed action is in flight. */
		busy: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['confirm', 'cancel'],
	methods: { t },
}
</script>

<style scoped>
.confirm-action-dialog__message {
	padding: 8px 0;
	white-space: pre-line;
}
</style>
