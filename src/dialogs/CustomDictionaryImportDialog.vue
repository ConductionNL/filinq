<!--
	SPDX-License-Identifier: EUPL-1.2
	SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>

	CSV / newline-list term import dialog (custom-dictionary-recognition,
	REQ-DDCDR-005). Parsing is server-side only — this dialog just collects
	either a pasted newline list or an uploaded CSV file and posts it as-is;
	it never trims/dedupes/splits client-side.
-->
<script>
import {
	NcButton,
	NcDialog,
	NcLoadingIcon,
	NcTextArea,
} from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'CustomDictionaryImportDialog',
	components: {
		NcButton,
		NcDialog,
		NcLoadingIcon,
		NcTextArea,
	},

	props: {
		importing: { type: Boolean, default: false },
		importError: { type: String, default: '' },
		result: { type: Object, default: null },
	},

	emits: ['submit', 'cancel'],
	data() {
		return {
			pastedContent: '',
			selectedFile: null,
		}
	},

	computed: {
		canSubmit() {
			return this.selectedFile !== null || this.pastedContent.trim() !== ''
		},
	},

	methods: {
		t,
		onFileChange(event) {
			this.selectedFile = event.target.files?.[0] || null
		},

		submit() {
			if (!this.canSubmit) return
			if (this.selectedFile !== null) {
				this.$emit('submit', { file: this.selectedFile })
				return
			}

			this.$emit('submit', { content: this.pastedContent, format: 'newline' })
		},

		onClose() {
			this.$emit('cancel')
		},
	},
}
</script>

<template>
	<NcDialog
		:name="t('filinq', 'Import terms')"
		:open="true"
		size="normal"
		@update:open="onClose">
		<div class="custom-dictionary-import">
			<p class="custom-dictionary-import__hint">
				{{
					t(
						'filinq',
						'Upload a CSV file (first column = term, optional second column = label) or paste a newline-separated list below. Blank lines and duplicates (case-insensitive) are skipped automatically.',
					)
				}}
			</p>

			<label
				class="custom-dictionary-import__file-label"
				for="custom-dictionary-import-file">
				{{ t('filinq', 'CSV file') }}
			</label>
			<input
				id="custom-dictionary-import-file"
				type="file"
				accept=".csv,text/csv"
				@change="onFileChange" />

			<div class="custom-dictionary-import__divider">
				{{ t('filinq', 'or paste a newline-separated list') }}
			</div>

			<NcTextArea
				v-model="pastedContent"
				:disabled="selectedFile !== null"
				:label="t('filinq', 'Terms (one per line)')"
				:placeholder="
					t('filinq', 'Operatie Zilverreiger\nDossier Karekiet')
				"
				:rows="6" />

			<div v-if="result" class="custom-dictionary-import__result">
				{{
					t(
						'filinq',
						'{added} added, {skipped} skipped, {total} total.',
						result,
					)
				}}
			</div>

			<div v-if="importError" class="custom-dictionary-import__error">
				{{ importError }}
			</div>
		</div>

		<template #actions>
			<NcButton variant="tertiary" @click="onClose">
				{{ t('filinq', 'Close') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="importing || !canSubmit"
				@click="submit">
				<template v-if="importing" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ t('filinq', 'Import') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style scoped>
.custom-dictionary-import {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 8px;
}

.custom-dictionary-import__hint {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	margin: 0;
}

.custom-dictionary-import__file-label {
	font-size: 13px;
	font-weight: bold;
	color: var(--color-text-maxcontrast);
}

.custom-dictionary-import__divider {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	text-align: center;
	margin: 4px 0;
}

.custom-dictionary-import__result {
	background: var(--color-success, #d1f7d6);
	color: var(--color-text-maxcontrast, #333);
	padding: 8px 12px;
	border-radius: var(--border-radius);
	font-size: 13px;
}

.custom-dictionary-import__error {
	background: var(--color-error, #ffd1d1);
	color: var(--color-text-maxcontrast, #333);
	padding: 8px 12px;
	border-radius: var(--border-radius);
	font-size: 13px;
}
</style>
