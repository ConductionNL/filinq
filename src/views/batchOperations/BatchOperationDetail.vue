<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

One anonymisation batch as an operator follows it: phase, progress,
throughput and the throttled flag, polled while the batch runs; cancel and
resume behind a confirmation; and the files picked for the QA sample with
their review outcome.

@spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-per-batch-progress-reporting-req-ddras-003
-->

<template>
	<div class="batch-operation">
		<NcLoadingIcon v-if="loading && !batch" :size="44" />
		<NcNoteCard v-else-if="!batch" type="error">
			{{ error || t('filinq', 'This batch is not there, or you cannot see it.') }}
		</NcNoteCard>
		<template v-else>
			<div class="batch-operation__header">
				<h3>{{ t('filinq', 'Anonymisation batch') }}</h3>
				<div class="batch-operation__actions">
					<NcButton
						v-if="canCancel(batch.batchStatus)"
						:disabled="busy"
						data-testid="batch-cancel"
						@click="dialog = 'cancel'">
						{{ t('filinq', 'Cancel batch') }}
					</NcButton>
					<NcButton
						v-if="canResume(batch.batchStatus)"
						variant="primary"
						:disabled="busy"
						data-testid="batch-resume"
						@click="dialog = 'resume'">
						{{ t('filinq', 'Resume batch') }}
					</NcButton>
				</div>
			</div>

			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>

			<dl class="batch-operation__facts">
				<dt>{{ t('filinq', 'Phase') }}</dt>
				<dd data-testid="batch-phase">
					{{ batch.batchStatus }}
				</dd>
				<dt>{{ t('filinq', 'Progress') }}</dt>
				<dd data-testid="batch-progress">
					{{ percent }}%
				</dd>
				<dt>{{ t('filinq', 'Files') }}</dt>
				<dd>
					{{ t('filinq', '{succeeded} succeeded, {failed} failed, {pending} pending', counts) }}
				</dd>
				<dt>{{ t('filinq', 'Documents per hour') }}</dt>
				<dd data-testid="batch-throughput">
					{{ batch.documentsPerHour === null || batch.documentsPerHour === undefined ? '—' : batch.documentsPerHour }}
				</dd>
			</dl>

			<NcNoteCard v-if="batch.throttled" type="warning" data-testid="batch-throttled">
				{{ t('filinq', 'This batch is throttled: the last run used its time budget on few files.') }}
			</NcNoteCard>

			<template v-if="samples.length > 0">
				<h3>{{ t('filinq', 'QA sample') }}</h3>
				<ul class="batch-operation__samples">
					<li v-for="sample in samples" :key="sample.fileId" data-testid="batch-qa-sample">
						<router-link :to="{ name: 'Anonymization', query: { fileId: String(sample.fileId) } }">
							{{ sample.fileName }}
						</router-link>
						<span class="batch-operation__badge" :class="`batch-operation__badge--${sample.outcome}`">
							{{ outcomeLabel(sample.outcome) }}
						</span>
					</li>
				</ul>
			</template>
		</template>

		<CancelBatchDialog
			v-if="dialog === 'cancel'"
			:busy="busy"
			@cancel="dialog = null"
			@confirm="act(cancelBatch)" />
		<ResumeBatchDialog
			v-if="dialog === 'resume'"
			:busy="busy"
			@cancel="dialog = null"
			@confirm="act(resumeBatch)" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import CancelBatchDialog from '../../dialogs/CancelBatchDialog.vue'
import ResumeBatchDialog from '../../dialogs/ResumeBatchDialog.vue'
import {
	canCancel,
	cancelBatch,
	canResume,
	getBatchStatus,
	qaSamples,
	resumeBatch,
	RUNNING,
} from '../../services/batchOperations.js'

/**
 * How often a running batch is polled, in milliseconds.
 */
const POLL_MS = 10000

export default {
	name: 'BatchOperationDetail',
	components: { CancelBatchDialog, NcButton, NcLoadingIcon, NcNoteCard, ResumeBatchDialog },

	props: {
		/**
		 * The batch's object id, handed in by the detail page (`@objectId`).
		 * Falls back to the route's `:id` when mounted without it.
		 */
		objectId: {
			type: String,
			default: '',
		},
	},

	data() {
		return {
			batch: null,
			loading: false,
			busy: false,
			error: '',
			dialog: null,
			timer: null,
		}
	},

	computed: {
		/**
		 * The batch shown: the detail page's object id, else the route's.
		 *
		 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-per-batch-progress-reporting-req-ddras-003
		 */
		batchId() {
			return String(this.objectId || this.$route.params.id || '')
		},

		/**
		 * How far the batch is, in percent.
		 *
		 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-per-batch-progress-reporting-req-ddras-003
		 */
		percent() {
			return this.batch.progressPercent ?? this.batch.progress ?? 0
		},

		/**
		 * The succeeded, failed and pending file counts.
		 *
		 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-per-batch-progress-reporting-req-ddras-003
		 */
		counts() {
			return {
				succeeded: this.batch.filesSucceeded ?? 0,
				failed: this.batch.filesFailed ?? 0,
				pending: this.batch.filesPending ?? 0,
			}
		},

		/**
		 * The files picked for the QA sample, with their outcome.
		 *
		 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-sampling-qa-routed-to-human-review-req-ddras-007
		 */
		samples() {
			return qaSamples(this.batch?.files)
		},
	},

	mounted() {
		this.load()
	},

	beforeUnmount() {
		clearTimeout(this.timer)
	},

	methods: {
		t,
		canCancel,
		canResume,
		cancelBatch,
		resumeBatch,

		/**
		 * Read the batch status, then poll again while it runs.
		 *
		 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-per-batch-progress-reporting-req-ddras-003
		 */
		async load() {
			this.loading = true
			try {
				this.batch = await getBatchStatus(this.batchId)
			} catch {
				this.error = t('filinq', 'The batch could not be loaded.')
			} finally {
				this.loading = false
			}
			this.schedule()
		},

		/**
		 * Poll again after POLL_MS while the batch is still running.
		 *
		 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-per-batch-progress-reporting-req-ddras-003
		 */
		schedule() {
			clearTimeout(this.timer)
			if (this.batch && RUNNING.includes(this.batch.batchStatus)) {
				this.timer = setTimeout(() => this.load(), POLL_MS)
			}
		},

		/**
		 * Run cancel or resume, close the dialog and reload the status.
		 *
		 * @param {function(string): Promise<object>} action cancelBatch or resumeBatch.
		 *
		 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
		 */
		async act(action) {
			this.busy = true
			this.error = ''
			try {
				await action(this.batchId)
				this.dialog = null
				await this.load()
			} catch {
				this.error = t('filinq', 'The batch could not be changed in its current state.')
			} finally {
				this.busy = false
			}
		},

		/**
		 * The words for a QA sample's review outcome.
		 *
		 * @param {string} outcome confirmed, corrected or pending.
		 * @return {string}
		 *
		 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-sampling-qa-routed-to-human-review-req-ddras-007
		 */
		outcomeLabel(outcome) {
			if (outcome === 'confirmed') {
				return t('filinq', 'Confirmed')
			}
			if (outcome === 'corrected') {
				return t('filinq', 'Corrected')
			}
			return t('filinq', 'Awaiting review')
		},
	},
}
</script>

<style scoped>
.batch-operation {
	padding: calc(var(--default-grid-baseline) * 4);
}

.batch-operation__header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: var(--default-grid-baseline);
}

.batch-operation__actions {
	display: flex;
	gap: var(--default-grid-baseline);
}

.batch-operation__facts {
	display: grid;
	grid-template-columns: max-content 1fr;
	gap: var(--default-grid-baseline) calc(var(--default-grid-baseline) * 4);
}

.batch-operation__facts dt {
	color: var(--color-text-maxcontrast);
}

.batch-operation__samples li {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
	align-items: center;
}

.batch-operation__badge {
	border-radius: var(--border-radius-pill);
	padding: 0 calc(var(--default-grid-baseline) * 2);
	background: var(--color-background-dark);
	color: var(--color-main-text);
}

.batch-operation__badge--confirmed {
	background: var(--color-success);
	color: var(--color-primary-element-text);
}

.batch-operation__badge--corrected {
	background: var(--color-warning);
	color: var(--color-main-text);
}
</style>
