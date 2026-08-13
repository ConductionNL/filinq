<template>
	<NcDialog
		:name="dialogName"
		:can-close="!submitting"
		size="normal"
		@closing="$emit('cancel')">
		<div class="dossier-dialog">
			<!-- Single file: read-only filename, no dossier name -->
			<div v-if="singleFile" class="single-file">
				<span class="single-file__label">{{
					t('docudesk', 'Document')
				}}</span>
				<span class="single-file__name">{{ fileName }}</span>
				<NcNoteCard v-if="error" type="error">
					{{ error }}
				</NcNoteCard>
			</div>
			<!-- Multiple files: dossier name input -->
			<template v-else>
				<NcTextField
					ref="dossierInput"
					:model-value="dossierName"
					:label="t('docudesk', 'Dossier name')"
					:placeholder="t('docudesk', 'e.g. Buurtinitiatieven 2026')"
					:disabled="submitting"
					:error="!!error"
					:helper-text="error"
					@update:model-value="$emit('update:dossierName', $event)"
					@keyup.enter="$emit('confirm')" />
				<NcNoteCard type="info">
					{{
						t(
							'docudesk',
							'You uploaded multiple documents. Enter a title to automatically create a dossier from them. No title? Then they will stay as separate documents.',
						)
					}}
				</NcNoteCard>
			</template>

			<!-- Grondslagen toggle: drives whether entities are editable in the viewer -->
			<NcCheckboxRadioSwitch
				:model-value="grondslagen"
				type="switch"
				:disabled="submitting"
				@update:model-value="$emit('update:grondslagen', $event)">
				{{ t('docudesk', 'Establish legal grounds (grondslagen)') }}
			</NcCheckboxRadioSwitch>
			<NcNoteCard type="info">
				{{
					t(
						'docudesk',
						'When enabled, you can review and adjust the legal grounds for each detected entity before anonymizing. When disabled, default grounds are applied and you can anonymize right away.',
					)
				}}
			</NcNoteCard>
		</div>
		<template #actions>
			<NcButton
				variant="tertiary"
				:disabled="submitting"
				@click="$emit('cancel')">
				{{ t('docudesk', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="submitting"
				@click="$emit('confirm')">
				<template v-if="submitting" #icon>
					<NcLoadingIcon :size="18" />
				</template>
				{{ t('docudesk', 'Continue to anonymization') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcDialog,
	NcLoadingIcon,
	NcNoteCard,
	NcTextField,
} from '@nextcloud/vue'

/**
 * Upload-confirmation dialog for the DocuDesk anonymisation surface.
 *
 * Two shapes behind one step: a single document shows its read-only name,
 * two or more offer a dossier title. Both always confirm the grondslagen
 * choice, which is what decides whether the viewer lets the user edit the
 * legal grounds per detected entity.
 *
 * Lives in `src/dialogs/` per ADR-004 — the parent owns the pending files
 * and the upload, this component owns only what the user is asked.
 *
 * @license EUPL-1.2
 * @copyright 2026 Conduction B.V. <info@conduction.nl>
 */
export default {
	name: 'AnonymizationUploadDialog',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
	},
	props: {
		/**
		 * True when exactly one document was picked — no dossier name is asked.
		 */
		singleFile: {
			type: Boolean,
			default: false,
		},
		/**
		 * Name of that single document. Ignored unless `singleFile` is true.
		 */
		fileName: {
			type: String,
			default: '',
		},
		/**
		 * The dossier title being typed. Empty means "keep them separate".
		 */
		dossierName: {
			type: String,
			default: '',
		},
		/**
		 * Whether the user wants to establish legal grounds per entity.
		 */
		grondslagen: {
			type: Boolean,
			default: true,
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
		 * Upload error to surface. Empty means no error.
		 */
		error: {
			type: String,
			default: '',
		},
	},
	emits: ['update:dossierName', 'update:grondslagen', 'confirm', 'cancel'],
	computed: {
		/**
		 * Title of the upload modal: single-file vs dossier wording.
		 *
		 * @return {string} Localised dialog title.
		 * @spec openspec/specs/anonymization/spec.md#requirement-file-upload-to-user-scoped-folder-req-anon-01
		 */
		dialogName() {
			return this.singleFile
				? t('docudesk', 'Anonymize document')
				: t('docudesk', 'Create dossier')
		},
	},
	/**
	 * Focus the dossier-name field as the dialog appears.
	 *
	 * @return {void}
	 * @spec openspec/specs/anonymization/spec.md#requirement-file-upload-to-user-scoped-folder-req-anon-01
	 */
	mounted() {
		// The dialog is mounted by a `v-if` at open time, so focusing here is
		// the same moment the parent used to focus it through a `$refs` hop.
		// Absent in single-file mode, where there is no name field.
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

.single-file {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.single-file__label {
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
}

.single-file__name {
	font-weight: 600;
	color: var(--color-main-text);
	word-break: break-word;
}
</style>
