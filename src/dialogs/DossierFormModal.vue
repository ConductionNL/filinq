<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcDialog,
	NcLoadingIcon,
	NcNoteCard,
	NcSelect,
	NcTextArea,
	NcTextField,
} from '@nextcloud/vue'

/**
 * A blank dossier form.
 *
 * @return {object} The empty form state.
 */
function blankForm() {
	return {
		name: '',
		description: '',
		bases: [],
	}
}

/**
 * Create-dossier dialog.
 *
 * Own file per ADR-004: a dialog written inline in its parent couples it to
 * that parent's lifecycle and cannot be reused. This one is also opened by the
 * auto-dossier flow on multi-upload, which is exactly the reuse an inline
 * dialog would have blocked.
 */
export default {
	name: 'DossierFormModal',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
		NcLoadingIcon,
		NcNoteCard,
		NcSelect,
		NcTextArea,
		NcTextField,
	},

	props: {
		open: { type: Boolean, required: true },
		saving: { type: Boolean, default: false },
		formError: { type: String, default: '' },
		/** Prefilled name — the auto-dossier flow seeds this from the upload. */
		prefillName: { type: String, default: '' },
		/** Show the "select every legal basis" toggle (auto-dossier flow). */
		showSelectAllBases: { type: Boolean, default: false },
	},

	emits: ['update:open', 'submit', 'cancel'],

	data() {
		return {
			form: blankForm(),
			selectAllBases: false,
			baseOptions: [],
			loadingBases: false,
		}
	},

	watch: {
		open(isOpen) {
			if (isOpen) {
				this.form = blankForm()
				this.form.name = this.prefillName
				this.selectAllBases = false
				this.loadBases()
			}
		},

		selectAllBases(all) {
			// "Alle grondslagen geselecteerd": on, every canonical basis is
			// preselected; off, the operator's own selection is restored to
			// empty rather than silently kept.
			this.form.bases = all ? this.baseOptions.map((b) => b.slug) : []
		},
	},

	methods: {
		t,

		/**
		 * Load the legal-bases vocabulary from OpenRegister.
		 *
		 * Read from the register rather than hardcoded: the six canonical Woo
		 * Art. 5 grounds are seed data, and a tenant may add its own.
		 *
		 * @return {Promise<void>} Nothing.
		 */
		async loadBases() {
			this.loadingBases = true
			try {
				const { default: axios } = await import('@nextcloud/axios')
				const { generateUrl } = await import('@nextcloud/router')
				const response = await axios.get(
					generateUrl('/apps/openregister/api/objects/filinq/base'),
				)
				const rows = response.data?.results || response.data || []
				this.baseOptions = rows.map((row) => ({
					slug: row['@self']?.slug || row.slug || '',
					label: row.name || row.slug || '',
				}))
			} catch {
				// A vocabulary that cannot be read must not block creating a
				// dossier — bases are optional and can be set afterwards.
				this.baseOptions = []
			} finally {
				this.loadingBases = false
			}
		},

		submit() {
			if (this.form.name.trim() === '') {
				return
			}
			this.$emit('submit', {
				name: this.form.name.trim(),
				description: this.form.description,
				bases: this.form.bases,
			})
		},

		cancel() {
			this.$emit('cancel')
			this.$emit('update:open', false)
		},
	},
}
</script>

<template>
	<NcDialog
		:open="open"
		:name="t('filinq', 'New dossier')"
		size="normal"
		@update:open="$emit('update:open', $event)">
		<div class="dossier-form">
			<NcNoteCard v-if="formError" type="error">
				{{ formError }}
			</NcNoteCard>

			<NcTextField
				v-model="form.name"
				:label="t('filinq', 'Name')"
				:placeholder="t('filinq', 'For example: Woo request 2026-014')"
				required />

			<NcTextArea
				v-model="form.description"
				:label="t('filinq', 'Description')"
				:placeholder="
					t('filinq', 'What this dossier is for, and who asked for it.')
				" />

			<NcCheckboxRadioSwitch
				v-if="showSelectAllBases"
				:modelValue="selectAllBases"
				type="switch"
				@update:modelValue="selectAllBases = $event">
				{{ t('filinq', 'Select every legal basis') }}
			</NcCheckboxRadioSwitch>

			<NcSelect
				v-model="form.bases"
				:inputLabel="t('filinq', 'Legal bases')"
				:options="baseOptions.map((b) => b.slug)"
				:loading="loadingBases"
				multiple
				:placeholder="t('filinq', 'Optional, you can set these later')" />
		</div>

		<template #actions>
			<NcButton variant="tertiary" :disabled="saving" @click="cancel">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="saving || form.name.trim() === ''"
				@click="submit">
				<template v-if="saving" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ t('filinq', 'Create dossier') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style scoped>
.dossier-form {
	display: flex;
	flex-direction: column;
	gap: 0.75rem;
	padding-block-end: 0.5rem;
}
</style>
