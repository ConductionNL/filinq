<template>
	<NcDialog
		:name="t('filinq', 'Create dossier')"
		:canClose="!submitting"
		size="normal"
		@closing="$emit('cancel')">
		<div class="dossier-dialog">
			<NcTextField
				ref="dossierInput"
				:modelValue="modelValue"
				:label="t('filinq', 'Dossier name')"
				:placeholder="t('filinq', 'e.g. Buurtinitiatieven 2026')"
				:disabled="submitting"
				:error="!!error"
				:helperText="error"
				@update:modelValue="$emit('update:modelValue', $event)"
				@keyup.enter="$emit('confirm')" />
			<NcNoteCard type="info">
				{{
					t(
						'filinq',
						'You uploaded multiple documents. Enter a title to automatically create a dossier from them. No title? Then they will stay as separate documents.',
					)
				}}
			</NcNoteCard>
		</div>
		<template #actions>
			<NcButton
				variant="tertiary"
				:disabled="submitting"
				@click="$emit('cancel')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="submitting"
				@click="$emit('confirm')">
				<template v-if="submitting" #icon>
					<NcLoadingIcon :size="18" />
				</template>
				{{ t('filinq', 'Continue to anonymization') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcDialog,
	NcLoadingIcon,
	NcNoteCard,
	NcTextField,
} from '@nextcloud/vue'

/**
 * Dossier-name prompt shown when two or more files are dropped on the
 * Filinq dashboard widget.
 *
 * Lives in `src/dialogs/` per ADR-004: a dialog is its own component, not
 * markup inlined in the surface that opens it. It owns no upload logic —
 * the parent keeps the pending files and performs the upload — so this
 * component is purely the naming step.
 *
 * @license EUPL-1.2
 * @copyright 2026 Conduction B.V. <info@conduction.nl>
 */
export default {
	name: 'DossierNameDialog',
	components: {
		NcButton,
		NcDialog,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
	},

	props: {
		/**
		 * The dossier name being typed. Empty means "keep the files separate".
		 */
		modelValue: {
			type: String,
			default: '',
		},

		/**
		 * True while the parent's upload is in flight — locks the inputs and
		 * suppresses closing so a half-finished upload cannot be abandoned.
		 */
		submitting: {
			type: Boolean,
			default: false,
		},

		/**
		 * Upload error to surface on the name field. Empty means no error.
		 */
		error: {
			type: String,
			default: '',
		},
	},

	emits: ['update:modelValue', 'confirm', 'cancel'],
	/**
	 * Focus the dossier-name field as the dialog appears.
	 *
	 * @return {void}
	 * @spec openspec/specs/dashboard/spec.md#requirement-nextcloud-dashboard-widgets-req-dash-02
	 */
	mounted() {
		// The dialog is mounted by a `v-if` at open time, so focusing here is
		// the same moment the parent used to focus it through a `$refs` hop.
		this.$nextTick(() => {
			this.$refs.dossierInput?.focus?.()
		})
	},

	methods: {
		t,
	},
}
</script>

<style scoped>
.dossier-dialog {
	display: flex;
	flex-direction: column;
	gap: 16px;
	padding: 20px;
}

.dossier-dialog :deep(.notecard) {
	margin: 0;
}
</style>
