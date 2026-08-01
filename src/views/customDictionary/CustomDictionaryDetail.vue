<!--
	SPDX-License-Identifier: EUPL-1.2
	SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>

	Custom dictionary detail/edit view (custom-dictionary-recognition,
	REQ-DDCDR-006): dictionary metadata (edited via the shared form dialog),
	a term table with add/remove, and CSV/newline import.
-->
<template>
	<div class="custom-dictionary-detail">
		<div class="custom-dictionary-detail__header">
			<NcButton variant="tertiary" @click="handleBack">
				{{ t('docudesk', 'Back to custom dictionaries') }}
			</NcButton>
			<h2 class="custom-dictionary-detail__title">
				<span class="custom-dictionary-detail__swatch" :style="{ backgroundColor: dictionary.colour || '#0082C9' }" />
				{{ displayValue(dictionary.label, t('docudesk', 'Custom dictionary')) }}
			</h2>
			<div class="custom-dictionary-detail__header-actions">
				<NcButton variant="secondary" @click="openEditDialog">
					{{ t('docudesk', 'Edit') }}
				</NcButton>
			</div>
		</div>

		<NcLoadingIcon v-if="customDictionaryStore.loading && !dictionary.label" :size="32" />

		<template v-else>
			<div class="custom-dictionary-detail__meta">
				<p v-if="dictionary.description" class="custom-dictionary-detail__description">
					{{ displayValue(dictionary.description) }}
				</p>
				<div class="custom-dictionary-detail__badges">
					<CnStatusBadge
						:label="matchModeLabel(dictionary.matchMode)"
						:color-map="matchModeColorMap" />
					<CnStatusBadge
						:label="dictionary.active === false ? t('docudesk', 'Inactive') : t('docudesk', 'Active')"
						:color-map="activeColorMap" />
					<span class="custom-dictionary-detail__term-count">
						{{ t('docudesk', '{count} terms', { count: terms.length }) }}
					</span>
				</div>
			</div>

			<div class="custom-dictionary-detail__terms-panel">
				<div class="custom-dictionary-detail__terms-header">
					<h3>{{ t('docudesk', 'Terms') }}</h3>
					<div class="custom-dictionary-detail__terms-actions">
						<NcButton variant="secondary" @click="importDialogOpen = true">
							{{ t('docudesk', 'Import…') }}
						</NcButton>
					</div>
				</div>

				<div class="custom-dictionary-detail__add-term">
					<NcTextField
						v-model="newTermValue"
						:label="t('docudesk', 'New term value')"
						:placeholder="t('docudesk', 'e.g. Operatie Zilverreiger')"
						@keyup.enter="addTerm" />
					<NcTextField
						v-model="newTermLabel"
						:label="t('docudesk', 'Display label (optional)')" />
					<NcButton variant="primary" :disabled="!newTermValue.trim() || addingTerm" @click="addTerm">
						{{ t('docudesk', 'Add term') }}
					</NcButton>
				</div>

				<NcLoadingIcon v-if="customDictionaryStore.termsLoading" :size="24" />
				<NcEmptyContent v-else-if="!terms.length"
					:name="t('docudesk', 'No terms yet')"
					:description="t('docudesk', 'Add a term above, or import a CSV / newline list.')" />
				<table v-else class="custom-dictionary-detail__terms-table">
					<thead>
						<tr>
							<th>{{ t('docudesk', 'Value') }}</th>
							<th>{{ t('docudesk', 'Label') }}</th>
							<th>{{ t('docudesk', 'Actions') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="term in terms" :key="term['@self']?.id || term.id">
							<td>{{ term.value }}</td>
							<td>{{ displayValue(term.label, '-') }}</td>
							<td>
								<NcButton variant="tertiary" @click="removeTerm(term)">
									<template #icon>
										<Delete :size="20" />
									</template>
									{{ t('docudesk', 'Remove') }}
								</NcButton>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</template>

		<CustomDictionaryFormDialog
			v-if="editDialogOpen"
			:editing-record="dictionary"
			:saving="savingMeta"
			:form-error="metaError"
			@submit="onEditSubmit"
			@cancel="editDialogOpen = false" />

		<CustomDictionaryImportDialog
			v-if="importDialogOpen"
			:importing="importing"
			:import-error="importError"
			:result="importResult"
			@submit="onImportSubmit"
			@cancel="closeImportDialog" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { CnStatusBadge, NcButton, NcEmptyContent, NcLoadingIcon, NcTextField } from '@conduction/nextcloud-vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import { customDictionaryStore } from '../../store/store.js'
import { resolveI18nValue } from '../../utils/registerI18n.js'
import CustomDictionaryFormDialog from '../../dialogs/CustomDictionaryFormDialog.vue'
import CustomDictionaryImportDialog from '../../dialogs/CustomDictionaryImportDialog.vue'

export default {
	name: 'CustomDictionaryDetail',
	components: {
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		NcTextField,
		CnStatusBadge,
		Delete,
		CustomDictionaryFormDialog,
		CustomDictionaryImportDialog,
	},
	data() {
		return {
			customDictionaryStore,
			editDialogOpen: false,
			savingMeta: false,
			metaError: '',
			newTermValue: '',
			newTermLabel: '',
			addingTerm: false,
			importDialogOpen: false,
			importing: false,
			importError: '',
			matchModeColorMap: {
				exact: 'error',
				caseInsensitive: 'primary',
				wordBoundary: 'success',
			},
			activeColorMap: {
				[t('docudesk', 'Active')]: 'success',
				[t('docudesk', 'Inactive')]: 'default',
			},
		}
	},
	computed: {
		dictionaryId() {
			return this.$route.params.id
		},
		dictionary() {
			return customDictionaryStore.dictionaryItem || {}
		},
		terms() {
			return customDictionaryStore.terms
		},
		importResult() {
			return customDictionaryStore.importResult
		},
	},
	async mounted() {
		await customDictionaryStore.fetchDictionary(this.dictionaryId)
		await customDictionaryStore.fetchTerms(this.dictionaryId)
	},
	beforeUnmount() {
		customDictionaryStore.clearDictionaryItem()
	},
	methods: {
		t,
		/**
		 * Resolve a register-i18n field (label/description) for display.
		 *
		 * @param {string|object|null} value    The raw field value.
		 * @param {string}             fallback Shown when nothing resolves.
		 * @return {string} The displayable string.
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md
		 */
		displayValue(value, fallback = '') {
			return resolveI18nValue(value, fallback)
		},
		matchModeLabel(mode) {
			const labels = {
				exact: t('docudesk', 'Exact'),
				caseInsensitive: t('docudesk', 'Case-insensitive'),
				wordBoundary: t('docudesk', 'Word boundary'),
			}
			return labels[mode] || mode || t('docudesk', 'Case-insensitive')
		},
		handleBack() {
			this.$router.push({ name: 'CustomDictionary' })
		},
		openEditDialog() {
			this.metaError = ''
			this.editDialogOpen = true
		},
		async onEditSubmit(formData) {
			this.savingMeta = true
			this.metaError = ''
			try {
				await customDictionaryStore.updateDictionary(this.dictionaryId, formData)
				this.editDialogOpen = false
			} catch (err) {
				this.metaError = err.response?.data?.error || err.message || t('docudesk', 'Save failed')
			} finally {
				this.savingMeta = false
			}
		},
		async addTerm() {
			const value = this.newTermValue.trim()
			if (!value) return
			this.addingTerm = true
			try {
				await customDictionaryStore.createTerm(this.dictionaryId, {
					value,
					label: this.newTermLabel.trim() || null,
				})
				this.newTermValue = ''
				this.newTermLabel = ''
			} catch (err) {
				console.error('Failed to add term:', err)
			} finally {
				this.addingTerm = false
			}
		},
		async removeTerm(term) {
			const id = term['@self']?.id || term.id
			// eslint-disable-next-line no-alert
			if (!window.confirm(t('docudesk', 'Remove "{value}"?', { value: term.value }))) {
				return
			}
			try {
				await customDictionaryStore.deleteTerm(this.dictionaryId, id)
			} catch (err) {
				console.error('Failed to remove term:', err)
			}
		},
		closeImportDialog() {
			this.importDialogOpen = false
			this.importError = ''
		},
		async onImportSubmit(payload) {
			this.importing = true
			this.importError = ''
			try {
				await customDictionaryStore.importTerms(this.dictionaryId, payload)
			} catch (err) {
				this.importError = err.response?.data?.error || err.message || t('docudesk', 'Import failed')
			} finally {
				this.importing = false
			}
		},
	},
}
</script>

<style scoped>
.custom-dictionary-detail {
	padding: 16px;
}

.custom-dictionary-detail__header {
	display: flex;
	align-items: center;
	gap: 16px;
	margin-bottom: 16px;
	flex-wrap: wrap;
}

.custom-dictionary-detail__title {
	display: inline-flex;
	align-items: center;
	gap: 10px;
	margin: 0;
	flex: 1;
}

.custom-dictionary-detail__swatch {
	display: inline-block;
	width: 16px;
	height: 16px;
	border-radius: 50%;
	border: 1px solid var(--color-border);
}

.custom-dictionary-detail__header-actions {
	display: flex;
	gap: 8px;
}

.custom-dictionary-detail__meta {
	margin-bottom: 24px;
}

.custom-dictionary-detail__description {
	color: var(--color-text-maxcontrast);
	margin: 0 0 8px 0;
}

.custom-dictionary-detail__badges {
	display: flex;
	align-items: center;
	gap: 12px;
	flex-wrap: wrap;
}

.custom-dictionary-detail__term-count {
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}

.custom-dictionary-detail__terms-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 12px;
}

.custom-dictionary-detail__add-term {
	display: flex;
	align-items: flex-end;
	gap: 8px;
	margin-bottom: 16px;
	flex-wrap: wrap;
}

.custom-dictionary-detail__terms-table {
	width: 100%;
	border-collapse: collapse;
}

.custom-dictionary-detail__terms-table th,
.custom-dictionary-detail__terms-table td {
	text-align: left;
	padding: 8px 12px;
	border-bottom: 1px solid var(--color-border);
}
</style>
