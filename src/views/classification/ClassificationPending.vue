<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The suggestions waiting for a person: a document type with its confidence,
a sender and a dossier for each document that came in. Nothing is applied
until somebody confirms it; a correction is kept beside the suggestion, and
a rejection changes nothing. Only files the reader can open are listed.

@spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
-->

<template>
	<div>
		<CnIndexPage
			:title="t('filinq', 'Classification suggestions')"
			:description="
				t(
					'filinq',
					'Filinq suggests a document type, a sender and a dossier for each document that comes in. Nothing changes until you confirm a suggestion.',
				)
			"
			:showTitle="true"
			:objects="rows"
			:columns="tableColumns"
			:loading="loading"
			:selectable="false"
			:showAdd="false"
			:showEditAction="false"
			:showCopyAction="false"
			:showDeleteAction="false"
			:showMassImport="false"
			:showMassExport="false"
			:showMassCopy="false"
			:showMassDelete="false"
			:showViewToggle="false"
			rowKey="uuid"
			:emptyText="
				t(
					'filinq',
					'No suggestions are waiting. New documents in the intake get one as soon as their text is read.',
				)
			"
			:refreshing="refreshing"
			data-testid="classification-pending-index"
			@refresh="refresh">
			<template #below-header>
				<div class="classification-pending__bar">
					<NcButton
						variant="primary"
						:disabled="busy || rows.length === 0"
						data-testid="classification-confirm-all"
						@click="confirmShown">
						{{ t('filinq', 'Confirm all shown') }}
					</NcButton>
				</div>
			</template>

			<template #column-suggestedDocumentType="{ row }">
				<CnStatusBadge
					:label="typeLabel(row.suggestedDocumentType)"
					:colorMap="{
						[typeLabel(row.suggestedDocumentType)]: 'primary',
					}" />
				<span class="classification-pending__muted">
					{{ confidenceText(row.documentTypeConfidence) }}
				</span>
			</template>

			<template #column-correspondent="{ row }">
				{{ correspondentText(row) }}
			</template>

			<template #column-suggestedDossier="{ row }">
				<span v-if="row.suggestedDossier">{{
					dossierLabel(row.suggestedDossier)
				}}</span>
				<span v-else class="classification-pending__muted">{{
					t('filinq', 'No dossier')
				}}</span>
			</template>

			<template #row-actions="{ row }">
				<div class="classification-pending__actions">
					<NcButton
						variant="primary"
						:disabled="busy"
						data-testid="classification-confirm"
						@click="decide(row, 'confirm')">
						{{ t('filinq', 'Confirm') }}
					</NcButton>
					<NcButton
						variant="secondary"
						:disabled="busy"
						data-testid="classification-correct"
						@click="correcting = row">
						{{ t('filinq', 'Correct') }}
					</NcButton>
					<NcButton
						variant="tertiary"
						:disabled="busy"
						data-testid="classification-reject"
						@click="decide(row, 'reject')">
						{{ t('filinq', 'Reject') }}
					</NcButton>
				</div>
			</template>
		</CnIndexPage>

		<ClassificationCorrectModal
			:record="correcting"
			:dossiers="dossiers"
			:busy="busy"
			@close="correcting = null"
			@confirm="confirmCorrected" />
	</div>
</template>

<script>
import { CnIndexPage, CnStatusBadge } from '@conduction/nextcloud-vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'
import ClassificationCorrectModal from '../../modals/ClassificationCorrectModal.vue'
import {
	confidenceText,
	confirm,
	confirmAll,
	confirmBody,
	correspondentText,
	dossierOptions,
	listPending,
	reject,
	typeLabel,
} from '../../services/classification.js'

export default {
	name: 'ClassificationPending',
	components: {
		ClassificationCorrectModal,
		CnIndexPage,
		CnStatusBadge,
		NcButton,
	},

	data() {
		return {
			rows: [],
			dossiers: [],
			loading: true,
			refreshing: false,
			busy: false,
			correcting: null,
		}
	},

	computed: {
		/**
		 * The columns of the suggestion table.
		 *
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
		 */
		tableColumns() {
			return [
				{ key: 'fileName', label: t('filinq', 'Document') },
				{ key: 'suggestedDocumentType', label: t('filinq', 'Type') },
				{ key: 'correspondent', label: t('filinq', 'Sender') },
				{ key: 'suggestedDossier', label: t('filinq', 'Dossier') },
			]
		},
	},

	/**
	 * Load the suggestions and the dossiers a suggestion can be filed in.
	 *
	 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
	 */
	mounted() {
		this.load()
		dossierOptions().then((options) => {
			this.dossiers = options
		})
	},

	methods: {
		t,
		confidenceText,
		correspondentText,
		typeLabel,

		/**
		 * Load the waiting suggestions.
		 *
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
		 */
		async load() {
			const result = await listPending()
			this.loading = false
			if (!result.ok) {
				showError(t('filinq', 'The suggestions could not be loaded.'))
				return
			}
			this.rows = result.data.results || []
		},

		/**
		 * Reload the list on the refresh action.
		 *
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
		 */
		async refresh() {
			this.refreshing = true
			await this.load()
			this.refreshing = false
		},

		/**
		 * The name of a suggested dossier, or its id when it is not in the list.
		 *
		 * @param {string} id The dossier uuid.
		 * @return {string} The label.
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
		 */
		dossierLabel(id) {
			return this.dossiers.find((option) => option.id === id)?.label || id
		},

		/**
		 * Confirm a suggestion as it is, or reject it.
		 *
		 * @param {object} row The suggestion.
		 * @param {string} action confirm or reject.
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
		 */
		async decide(row, action) {
			this.busy = true
			const result =
				action === 'confirm'
					? await confirm(row.fileId)
					: await reject(row.fileId)
			this.busy = false
			this.report(
				result,
				action === 'confirm'
					? t('filinq', 'Confirmed')
					: t('filinq', 'Rejected. The document stays as it is.'),
			)
		},

		/**
		 * Confirm the suggestion with the person's corrections.
		 *
		 * @param {object} choices From the correction pickers.
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
		 */
		async confirmCorrected(choices) {
			const row = this.correcting
			this.busy = true
			const result = await confirm(row.fileId, confirmBody(row, choices))
			this.busy = false
			this.correcting = null
			this.report(result, t('filinq', 'Confirmed with your corrections'))
		},

		/**
		 * Confirm every suggestion on the page as suggested.
		 *
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
		 */
		async confirmShown() {
			this.busy = true
			const { confirmed, failed } = await confirmAll(this.rows)
			this.busy = false
			if (failed > 0) {
				showError(
					t(
						'filinq',
						'{count} suggestions could not be confirmed. Someone may have decided them already.',
						{ count: failed },
					),
				)
			}
			if (confirmed > 0) {
				showSuccess(
					t('filinq', 'Suggestions confirmed: {count}', {
						count: confirmed,
					}),
				)
			}
			await this.load()
		},

		/**
		 * Show the outcome of a decision and reload.
		 *
		 * @param {object} result The call's answer.
		 * @param {string} success The message on success.
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
		 */
		async report(result, success) {
			if (!result.ok) {
				showError(
					result.status === 409
						? t('filinq', 'Someone already decided this suggestion.')
						: t('filinq', 'The decision could not be saved. Try again.'),
				)
			} else {
				showSuccess(success)
			}
			await this.load()
		},
	},
}
</script>

<style scoped>
.classification-pending__bar {
	display: flex;
	justify-content: flex-end;
	margin-block-end: 12px;
}

.classification-pending__actions {
	display: flex;
	gap: 8px;
}

.classification-pending__muted {
	color: var(--color-text-maxcontrast);
	margin-inline-start: 8px;
}
</style>
