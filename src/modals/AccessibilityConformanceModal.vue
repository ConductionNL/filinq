<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The PDF/UA-1 report of one document: the state it shows (the validator's
verdict, or the quick heuristic check labelled as such), the Matterhorn
checkpoints that failed, what to do, a way to check again, and, for a PDF
Filinq generated, a way to make it accessible. It describes a conformance
verdict and never claims the document is certified.

@spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
-->

<template>
	<NcModal
		v-if="show"
		:show="show"
		:name="t('filinq', 'PDF/UA report')"
		size="normal"
		@close="$emit('close')">
		<div class="pdfua-report" data-testid="pdfua-report">
			<h2 class="pdfua-report__title">
				{{ t('filinq', 'PDF/UA report') }}
			</h2>
			<NcLoadingIcon v-if="loading" :size="32" />
			<template v-else>
				<NcNoteCard v-if="error" type="error">
					{{ error }}
				</NcNoteCard>
				<NcNoteCard
					v-if="!conformance.validatorAvailable"
					type="warning"
					data-testid="pdfua-not-validated">
					{{
						t(
							'filinq',
							'The PDF/UA validator is not installed on the server, so this document was not checked for PDF/UA conformance.',
						)
					}}
				</NcNoteCard>

				<p
					class="pdfua-report__state"
					:class="{
						'pdfua-report__state--pass':
							conformance.state === 'conformant',
						'pdfua-report__state--fail':
							conformance.state === 'non-conformant',
					}"
					data-testid="pdfua-state">
					<span class="pdfua-report__source">{{
						sourceLabel(conformance.source)
					}}</span>
					{{ stateText(conformance) }}
				</p>

				<template v-if="report">
					<p v-if="advice" class="pdfua-report__advice">
						{{ advice }}
					</p>
					<table
						v-if="failedChecks.length > 0"
						class="pdfua-report__checks"
						data-testid="pdfua-failed-checks">
						<caption class="hidden-visually">
							{{ t('filinq', 'Matterhorn checkpoints that failed') }}
						</caption>
						<thead>
							<tr>
								<th scope="col">
									{{ t('filinq', 'Clause') }}
								</th>
								<th scope="col">
									{{ t('filinq', 'Test') }}
								</th>
								<th scope="col">
									{{ t('filinq', 'Checkpoint') }}
								</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="failed in failedChecks" :key="failed.checkpoint">
								<td>{{ failed.clause }}</td>
								<td>{{ failed.testNumber }}</td>
								<td>{{ failed.checkpoint }}</td>
							</tr>
						</tbody>
					</table>
					<p class="pdfua-report__meta">
						{{
							t('filinq', 'Checked by {validator} on {date}', {
								validator: report.validatorVersion,
								date: formatDate(report.validatedAt),
							})
						}}
					</p>
				</template>

				<section
					v-if="heuristicFindings.length > 0"
					class="pdfua-report__heuristics"
					data-testid="pdfua-heuristics">
					<h3>{{ t('filinq', 'Quick check') }}</h3>
					<ul class="pdfua-report__heuristic-list">
						<li v-for="finding in heuristicFindings" :key="finding.checkId">
							{{ t('filinq', finding.message || '', finding.params || {}) }}
						</li>
					</ul>
				</section>

				<NcNoteCard
					v-if="regenerated"
					:type="regenerated.replaced ? 'success' : 'warning'"
					data-testid="pdfua-regenerated">
					{{
						regenerated.replaced
							? t(
								'filinq',
								'A new accessible version was stored. It conforms to PDF/UA-1 according to the validator.',
							)
							: t(
								'filinq',
								'The new copy still does not conform to PDF/UA-1, so it was discarded. The document is unchanged.',
							)
					}}
				</NcNoteCard>

				<div class="pdfua-report__actions">
					<NcButton
						v-if="regenerationOffered"
						variant="secondary"
						:disabled="busy"
						data-testid="pdfua-regenerate"
						@click="regenerate">
						<template #icon>
							<NcLoadingIcon v-if="regenerating" :size="18" />
						</template>
						{{
							regenerating
								? t('filinq', 'Making accessible…')
								: t('filinq', 'Make accessible')
						}}
					</NcButton>
					<NcButton
						variant="primary"
						:disabled="!conformance.validatorAvailable || busy"
						data-testid="pdfua-check"
						@click="check">
						<template #icon>
							<NcLoadingIcon v-if="checking" :size="18" />
						</template>
						{{
							checking
								? t('filinq', 'Checking…')
								: t('filinq', 'Check against PDF/UA')
						}}
					</NcButton>
				</div>
			</template>
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcModal, NcNoteCard } from '@nextcloud/vue'
import {
	checkAccessibility,
	fetchAccessibility,
	offersRegeneration,
	regenerateAccessible,
	sourceLabel,
	stateText,
} from '../services/accessibilityConformance.js'
import { guidanceText } from '../services/conformance.js'

export default {
	name: 'AccessibilityConformanceModal',
	components: { NcButton, NcLoadingIcon, NcModal, NcNoteCard },

	props: {
		show: { type: Boolean, default: false },
		fileId: { type: Number, default: 0 },
	},

	emits: ['close'],

	data() {
		return {
			loading: false,
			checking: false,
			regenerating: false,
			report: null,
			conformance: {
				source: 'heuristic',
				state: 'looks-tagged',
				validated: false,
				validatorAvailable: true,
			},

			heuristicFindings: [],
			regenerable: false,
			regenerated: null,
			error: '',
		}
	},

	computed: {
		/**
		 * The failed Matterhorn checkpoints of the stored report.
		 *
		 * @return {Array<object>} The checkpoints.
		 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
		 */
		failedChecks() {
			return this.report?.failedChecks || []
		},

		/**
		 * The advice for the stored report.
		 *
		 * @return {string} The advice, '' for none.
		 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-remediation-guidance-is-honest-and-never-claims-certification-req-ddpum-003
		 */
		advice() {
			return guidanceText(this.report?.guidance || '')
		},

		/**
		 * Whether the card offers to make the document accessible.
		 *
		 * @return {boolean} True for a PDF Filinq generated that did not pass.
		 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
		 */
		regenerationOffered() {
			return (
				this.conformance.validatorAvailable
				&& offersRegeneration(this.report, this.regenerable)
			)
		},

		/**
		 * Whether a check or a regeneration is running.
		 *
		 * @return {boolean} True while one runs.
		 */
		busy() {
			return this.checking || this.regenerating
		},
	},

	watch: {
		show: {
			immediate: true,
			/**
			 * Load the state each time the modal opens.
			 *
			 * @param {boolean} open Whether it is open.
			 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
			 */
			handler(open) {
				if (open && this.fileId) {
					this.load()
				}
			},
		},
	},

	methods: {
		t,
		stateText,
		sourceLabel,

		/**
		 * Read the stored report and the state it surfaces.
		 *
		 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-validator-verdict-is-the-authoritative-conformance-heuristics-remain-the-floor-req-ddpum-004
		 */
		async load() {
			this.loading = true
			this.error = ''
			try {
				const state = await fetchAccessibility(this.fileId)
				this.report = state.report
				this.conformance = state.conformance
				this.heuristicFindings = state.heuristicFindings
				this.regenerable = state.regenerable
			} catch {
				this.error = t('filinq', 'The PDF/UA report could not be loaded.')
			} finally {
				this.loading = false
			}
		},

		/**
		 * Run the PDF/UA-1 check now and show the new state.
		 *
		 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
		 */
		async check() {
			this.checking = true
			this.error = ''
			this.regenerated = null
			try {
				await checkAccessibility(this.fileId)
				await this.load()
			} catch (e) {
				this.error =
					e?.response?.data?.error
					|| t('filinq', 'The document could not be checked against PDF/UA.')
			} finally {
				this.checking = false
			}
		},

		/**
		 * Generate the document again as PDF/UA; it is kept only when it passes.
		 *
		 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
		 */
		async regenerate() {
			this.regenerating = true
			this.error = ''
			try {
				this.regenerated = await regenerateAccessible(this.fileId)
				if (this.regenerated.replaced) {
					await this.load()
				}
			} catch (e) {
				this.error =
					e?.response?.data?.error
					|| t('filinq', 'The document could not be made accessible. Try again later.')
			} finally {
				this.regenerating = false
			}
		},

		/**
		 * A date for people.
		 *
		 * @param {string} value An ISO date-time.
		 * @return {string} The local date and time.
		 */
		formatDate(value) {
			const date = new Date(value)
			return Number.isNaN(date.getTime()) ? '' : date.toLocaleString()
		},
	},
}
</script>

<style scoped>
.pdfua-report {
	padding: 16px 20px;
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.pdfua-report__state {
	font-weight: bold;
	display: flex;
	gap: 8px;
	align-items: baseline;
	flex-wrap: wrap;
}

.pdfua-report__state--pass {
	color: var(--color-success-text);
}

.pdfua-report__state--fail {
	color: var(--color-error-text);
}

.pdfua-report__source {
	padding: 0 6px;
	border: 1px solid var(--color-border-maxcontrast);
	border-radius: var(--border-radius-pill);
	color: var(--color-text-maxcontrast);
	font-weight: normal;
}

.pdfua-report__advice,
.pdfua-report__meta {
	color: var(--color-text-maxcontrast);
}

.pdfua-report__checks {
	width: 100%;
	border-collapse: collapse;
}

.pdfua-report__checks th,
.pdfua-report__checks td {
	text-align: start;
	padding: 4px 8px;
	border-bottom: 1px solid var(--color-border);
}

.pdfua-report__heuristics {
	padding-top: 8px;
	border-top: 1px solid var(--color-border);
}

.pdfua-report__heuristic-list {
	list-style: disc;
	padding-inline-start: 20px;
}

.pdfua-report__actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	flex-wrap: wrap;
}
</style>
