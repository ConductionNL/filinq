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
				{{ t('filinq', 'Back to custom dictionaries') }}
			</NcButton>
			<h2 class="custom-dictionary-detail__title">
				<span
					class="custom-dictionary-detail__swatch"
					:style="{ backgroundColor: dictionary.colour || '#0082C9' }" />
				{{
					displayValue(dictionary.label, t('filinq', 'Custom dictionary'))
				}}
			</h2>
			<div class="custom-dictionary-detail__header-actions">
				<NcButton variant="secondary" @click="openEditDialog">
					{{ t('filinq', 'Edit') }}
				</NcButton>
			</div>
		</div>

		<NcLoadingIcon
			v-if="customDictionaryStore.loading && !dictionary.label"
			:size="32" />

		<template v-else>
			<div class="custom-dictionary-detail__meta">
				<p
					v-if="dictionary.description"
					class="custom-dictionary-detail__description">
					{{ displayValue(dictionary.description) }}
				</p>
				<div class="custom-dictionary-detail__badges">
					<CnStatusBadge
						:label="matchModeLabel(dictionary.matchMode)"
						:colorMap="matchModeColorMap" />
					<CnStatusBadge
						:label="
							dictionary.active === false
								? t('filinq', 'Inactive')
								: t('filinq', 'Active')
						"
						:colorMap="activeColorMap" />
					<span class="custom-dictionary-detail__term-count">
						{{ t('filinq', '{count} terms', { count: terms.length }) }}
					</span>
				</div>
			</div>

			<div class="custom-dictionary-detail__terms-panel">
				<div class="custom-dictionary-detail__terms-header">
					<h3>{{ t('filinq', 'Terms') }}</h3>
					<div class="custom-dictionary-detail__terms-actions">
						<NcButton
							variant="secondary"
							@click="importDialogOpen = true">
							{{ t('filinq', 'Import…') }}
						</NcButton>
					</div>
				</div>

				<div class="custom-dictionary-detail__add-term">
					<NcTextField
						v-model="newTermValue"
						:label="t('filinq', 'New term value')"
						:placeholder="t('filinq', 'e.g. Operatie Zilverreiger')"
						@keyup.enter="addTerm" />
					<NcTextField
						v-model="newTermLabel"
						:label="t('filinq', 'Display label (optional)')" />
					<NcButton
						variant="primary"
						:disabled="!newTermValue.trim() || addingTerm"
						@click="addTerm">
						{{ t('filinq', 'Add term') }}
					</NcButton>
				</div>

				<NcLoadingIcon
					v-if="customDictionaryStore.termsLoading"
					:size="24" />
				<NcEmptyContent
					v-else-if="!terms.length"
					:name="t('filinq', 'No terms yet')"
					:description="
						t(
							'filinq',
							'Add a term above, or import a CSV / newline list.',
						)
					" />
				<table v-else class="custom-dictionary-detail__terms-table">
					<thead>
						<tr>
							<th scope="col">{{ t('filinq', 'Value') }}</th>
							<th scope="col">{{ t('filinq', 'Label') }}</th>
							<th scope="col">{{ t('filinq', 'Actions') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-for="term in terms"
							:key="term['@self']?.id || term.id">
							<td>{{ term.value }}</td>
							<td>{{ displayValue(term.label, '-') }}</td>
							<td>
								<NcButton
									variant="tertiary"
									@click="removeTerm(term)">
									<template #icon>
										<Delete :size="20" />
									</template>
									{{ t('filinq', 'Remove') }}
								</NcButton>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</template>

		<CustomDictionaryFormDialog
			v-if="editDialogOpen"
			:editingRecord="dictionary"
			:saving="savingMeta"
			:formError="metaError"
			@submit="onEditSubmit"
			@cancel="editDialogOpen = false" />

		<CustomDictionaryImportDialog
			v-if="importDialogOpen"
			:importing="importing"
			:importError="importError"
			:result="importResult"
			@submit="onImportSubmit"
			@cancel="closeImportDialog" />

		<!--
			Removal confirmation. Replaces window.confirm(): the removal below
			runs only from @confirm, so an explicit confirmation is still
			required before a term disappears.
		-->
		<ConfirmActionDialog
			v-if="removeTarget"
			:name="t('filinq', 'Remove term')"
			:message="removeMessage"
			:confirmLabel="t('filinq', 'Remove')"
			:busy="removing"
			@confirm="executeRemoveTerm"
			@cancel="cancelRemoveTerm" />
	</div>
</template>

<script>
import {
	CnStatusBadge,
	NcButton,
	NcEmptyContent,
	NcLoadingIcon,
	NcTextField,
} from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import Delete from 'vue-material-design-icons/Delete.vue'
import ConfirmActionDialog from '../../dialogs/ConfirmActionDialog.vue'
import CustomDictionaryFormDialog from '../../dialogs/CustomDictionaryFormDialog.vue'
import CustomDictionaryImportDialog from '../../dialogs/CustomDictionaryImportDialog.vue'
import { customDictionaryStore } from '../../store/store.js'
import { resolveI18nValue } from '../../utils/registerI18n.js'

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
		ConfirmActionDialog,
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
			removeTarget: null, // term awaiting removal confirmation, or null
			removing: false,
			matchModeColorMap: {
				exact: 'error',
				caseInsensitive: 'primary',
				wordBoundary: 'success',
			},

			activeColorMap: {
				[t('filinq', 'Active')]: 'success',
				[t('filinq', 'Inactive')]: 'default',
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

		/**
		 * Body text of the term-removal confirmation dialog.
		 *
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md
		 */
		removeMessage() {
			return t('filinq', 'Remove "{value}"?', {
				value: this.removeTarget?.value || '',
			})
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

		/**
		 * Translated label for a dictionary match mode, falling back to the raw
		 * value and finally to the schema default.
		 *
		 * @param {string} mode Stored match mode.
		 * @return {string} Displayable label.
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md#requirement-custom-dictionary-admin-ui-req-ddcdr-006
		 */
		matchModeLabel(mode) {
			const labels = {
				exact: t('filinq', 'Exact'),
				caseInsensitive: t('filinq', 'Case-insensitive'),
				wordBoundary: t('filinq', 'Word boundary'),
			}
			return labels[mode] || mode || t('filinq', 'Case-insensitive')
		},

		handleBack() {
			this.$router.push({ name: 'CustomDictionary' })
		},

		openEditDialog() {
			this.metaError = ''
			this.editDialogOpen = true
		},

		/**
		 * Persist edited dictionary metadata through the organisation-gated
		 * management API.
		 *
		 * @param {object} formData Dialog form payload.
		 * @return {Promise<void>}
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md#requirement-organisation-gated-dictionary-and-term-management-api-req-ddcdr-004
		 */
		async onEditSubmit(formData) {
			this.savingMeta = true
			this.metaError = ''
			try {
				await customDictionaryStore.updateDictionary(
					this.dictionaryId,
					formData,
				)
				this.editDialogOpen = false
			} catch (err) {
				this.metaError =
					err.response?.data?.error
					|| err.message
					|| t('filinq', 'Save failed')
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

		/**
		 * Ask for confirmation before removing a term.
		 *
		 * Opens ConfirmActionDialog; nothing is removed here.
		 *
		 * @param {object} term - The term to remove.
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md
		 */
		removeTerm(term) {
			this.removeTarget = term
		},

		/**
		 * Dismiss the removal confirmation without removing anything.
		 *
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md
		 */
		cancelRemoveTerm() {
			this.removeTarget = null
		},

		/**
		 * Remove the confirmed term. Reachable only from the dialog's
		 *
		 * @confirm, so a term is never removed without an explicit
		 * confirmation.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md
		 */
		async executeRemoveTerm() {
			const term = this.removeTarget
			if (!term) {
				return
			}
			const id = term['@self']?.id || term.id
			this.removing = true
			try {
				await customDictionaryStore.deleteTerm(this.dictionaryId, id)
				this.removeTarget = null
			} catch (err) {
				console.error('Failed to remove term:', err)
			} finally {
				this.removing = false
			}
		},

		closeImportDialog() {
			this.importDialogOpen = false
			this.importError = ''
		},

		/**
		 * Import terms into this dictionary from a CSV upload or newline-
		 * separated list.
		 *
		 * @param {object} payload Import dialog payload.
		 * @return {Promise<void>}
		 * @spec openspec/specs/custom-dictionary-recognition/spec.md#requirement-csv-and-newline-term-import-req-ddcdr-005
		 */
		async onImportSubmit(payload) {
			this.importing = true
			this.importError = ''
			try {
				await customDictionaryStore.importTerms(this.dictionaryId, payload)
			} catch (err) {
				this.importError =
					err.response?.data?.error
					|| err.message
					|| t('filinq', 'Import failed')
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
