<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The classification of the open document: what Filinq suggested and, once a
person decided, what they chose. While the suggestion waits, the reader can
correct the type, sender and dossier and confirm, or reject it. Renders
nothing for a document without a suggestion.

@spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-2
-->

<template>
	<section
		v-if="record"
		class="classification-card"
		:aria-label="t('filinq', 'Classification')"
		data-testid="classification-card">
		<h3 class="classification-card__title">
			{{ t('filinq', 'Classification') }}
			<CnStatusBadge
				:label="statusLabel(record.status)"
				:colorMap="statusColors" />
		</h3>

		<template v-if="record.status === 'suggested'">
			<p class="classification-card__hint">
				{{
					t(
						'filinq',
						'Suggested as {type} ({confidence}). Nothing changes until you confirm.',
						{
							type: typeLabel(record.suggestedDocumentType),
							confidence: confidenceText(
								record.documentTypeConfidence,
							),
						},
					)
				}}
			</p>
			<ClassificationChoices
				:key="record.uuid"
				:record="record"
				:dossiers="dossiers"
				@change="choices = $event" />
			<div class="classification-card__actions">
				<NcButton
					variant="primary"
					:disabled="busy"
					data-testid="classification-card-confirm"
					@click="decide('confirm')">
					{{ t('filinq', 'Confirm') }}
				</NcButton>
				<NcButton
					variant="tertiary"
					:disabled="busy"
					data-testid="classification-card-reject"
					@click="decide('reject')">
					{{ t('filinq', 'Reject') }}
				</NcButton>
			</div>
		</template>

		<dl v-else class="classification-card__facts">
			<dt>{{ t('filinq', 'Suggested') }}</dt>
			<dd>{{ typeLabel(record.suggestedDocumentType) }}</dd>
			<template v-if="record.status === 'confirmed'">
				<dt>{{ t('filinq', 'Confirmed') }}</dt>
				<dd>{{ typeLabel(record.confirmedDocumentType) }}</dd>
				<dt>{{ t('filinq', 'Sender') }}</dt>
				<dd>
					{{ record.confirmedCorrespondent?.name || t('filinq', 'None') }}
				</dd>
				<dt>{{ t('filinq', 'Dossier') }}</dt>
				<dd>{{ filingText }}</dd>
			</template>
			<dt>{{ t('filinq', 'Decided by') }}</dt>
			<dd>{{ record.confirmedBy }}</dd>
		</dl>
	</section>
</template>

<script>
import { CnStatusBadge } from '@conduction/nextcloud-vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'
import ClassificationChoices from './ClassificationChoices.vue'
import {
	confidenceText,
	confirm,
	confirmBody,
	dossierOptions,
	loadForFile,
	reject,
	statusLabel,
	typeLabel,
} from '../services/classification.js'

export default {
	name: 'ClassificationCard',
	components: { ClassificationChoices, CnStatusBadge, NcButton },

	props: {
		fileId: {
			type: [Number, String],
			default: null,
		},
	},

	data() {
		return {
			record: null,
			dossiers: [],
			choices: {},
			busy: false,
		}
	},

	computed: {
		/**
		 * Badge colours per status label.
		 *
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-2
		 */
		statusColors() {
			return {
				[statusLabel('suggested')]: 'warning',
				[statusLabel('confirmed')]: 'success',
				[statusLabel('rejected')]: 'default',
			}
		},

		/**
		 * Where the document was filed, as a person reads it.
		 *
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-2
		 */
		filingText() {
			if (!this.record?.confirmedDossier) {
				return t('filinq', 'Not filed')
			}
			const name =
				this.dossiers.find(
					(option) => option.id === this.record.confirmedDossier,
				)?.label || this.record.confirmedDossier
			return this.record.filing === 'moved'
				? t('filinq', 'Moved into {dossier}', { dossier: name })
				: t(
						'filinq',
						'Recorded for {dossier}; the file was not moved because you cannot open its folder',
						{ dossier: name },
					)
		},
	},

	watch: {
		fileId: {
			immediate: true,
			/**
			 * Load the classification of the document that is open now.
			 *
			 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-2
			 */
			handler() {
				this.load()
			},
		},
	},

	methods: {
		t,
		confidenceText,
		statusLabel,
		typeLabel,

		/**
		 * Load the open document's classification.
		 *
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-2
		 */
		async load() {
			this.record = null
			if (!this.fileId) {
				return
			}
			const result = await loadForFile(this.fileId)
			this.record = result.ok ? result.data : null
			if (this.record && this.dossiers.length === 0) {
				this.dossiers = await dossierOptions()
			}
		},

		/**
		 * Confirm with the chosen values, or reject.
		 *
		 * @param {string} action confirm or reject.
		 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-2
		 */
		async decide(action) {
			this.busy = true
			const result =
				action === 'confirm'
					? await confirm(
							this.fileId,
							confirmBody(this.record, this.choices),
						)
					: await reject(this.fileId)
			this.busy = false
			if (!result.ok) {
				showError(t('filinq', 'The decision could not be saved. Try again.'))
				return
			}
			this.record = result.data
			showSuccess(
				action === 'confirm'
					? t('filinq', 'Confirmed')
					: t('filinq', 'Rejected. The document stays as it is.'),
			)
		},
	},
}
</script>

<style scoped>
.classification-card {
	margin: 16px 12px;
	padding: 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
}

.classification-card__title {
	display: flex;
	align-items: center;
	gap: 8px;
	margin: 0 0 8px;
}

.classification-card__hint {
	color: var(--color-text-maxcontrast);
	margin-block-end: 12px;
}

.classification-card__actions {
	display: flex;
	gap: 8px;
	margin-block-start: 12px;
}

.classification-card__facts {
	display: grid;
	grid-template-columns: auto 1fr;
	gap: 4px 12px;
	margin: 0;
}

.classification-card__facts dt {
	color: var(--color-text-maxcontrast);
}

.classification-card__facts dd {
	margin: 0;
}
</style>
