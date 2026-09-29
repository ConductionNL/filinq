<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The PDF/A conformance report of one document: the verdict veraPDF gave,
the rules and fonts behind it, what to do, and a way to check again.

@spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
-->

<template>
	<NcModal
		v-if="show"
		:show="show"
		:name="t('filinq', 'PDF/A report')"
		size="normal"
		@close="$emit('close')">
		<div class="conformance-report" data-testid="conformance-report">
			<h2 class="conformance-report__title">
				{{ t('filinq', 'PDF/A report') }}
			</h2>
			<NcLoadingIcon v-if="loading" :size="32" />
			<template v-else>
				<NcNoteCard v-if="error" type="error">
					{{ error }}
				</NcNoteCard>
				<NcNoteCard v-else-if="!available" type="warning">
					{{
						t(
							'filinq',
							'The PDF/A validator is not installed on the server, so this document cannot be checked.',
						)
					}}
				</NcNoteCard>

				<section
					v-for="entry in entries"
					:key="entry.subject"
					class="conformance-report__entry"
					:data-testid="'conformance-' + entry.subject">
					<h3>{{ entry.heading }}</h3>
					<p
						class="conformance-report__verdict"
						:class="{
							'conformance-report__verdict--pass':
								entry.report.compliant,
						}">
						{{ verdict(entry.report) }}
					</p>
					<p
						v-if="advice(entry.report)"
						class="conformance-report__advice">
						{{ advice(entry.report) }}
					</p>
					<div
						v-if="entry.report.fontsNotEmbedded.length > 0"
						class="conformance-report__fonts">
						<strong>{{ t('filinq', 'Fonts not embedded') }}</strong>
						<span>{{ entry.report.fontsNotEmbedded.join(', ') }}</span>
					</div>
					<table
						v-if="entry.report.failedRules.length > 0"
						class="conformance-report__rules">
						<thead>
							<tr>
								<th scope="col">
									{{ t('filinq', 'Standard') }}
								</th>
								<th scope="col">
									{{ t('filinq', 'Clause') }}
								</th>
								<th scope="col">
									{{ t('filinq', 'Test') }}
								</th>
								<th scope="col">
									{{ t('filinq', 'Places') }}
								</th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="rule in entry.report.failedRules"
								:key="rule.ruleId">
								<td>{{ rule.specification }}</td>
								<td>{{ rule.clause }}</td>
								<td>{{ rule.testNumber }}</td>
								<td>{{ rule.checksFailed }}</td>
							</tr>
						</tbody>
					</table>
					<p class="conformance-report__meta">
						{{
							t('filinq', 'Checked by {validator} on {date}', {
								validator: entry.report.validatorVersion,
								date: formatDate(entry.report.validatedAt),
							})
						}}
					</p>
				</section>

				<p
					v-if="available && entries.length === 0"
					class="conformance-report__empty">
					{{
						t(
							'filinq',
							'This document has not been checked against PDF/A yet.',
						)
					}}
				</p>

				<div class="conformance-report__actions">
					<NcButton
						variant="primary"
						:disabled="!available || checking"
						data-testid="conformance-check"
						@click="check">
						<template #icon>
							<NcLoadingIcon v-if="checking" :size="18" />
						</template>
						{{
							checking
								? t('filinq', 'Checking…')
								: t('filinq', 'Check against PDF/A')
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
	checkConformance,
	fetchConformance,
	guidanceText,
	verdictText,
} from '../services/conformance.js'

export default {
	name: 'ConformanceReportModal',
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
			available: true,
			reports: {},
			error: '',
		}
	},

	computed: {
		/**
		 * The reports to show: the file's own, then its PDF/A-3 conversion's.
		 *
		 * @return {Array<{subject: string, heading: string, report: object}>} The entries.
		 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
		 */
		entries() {
			const headings = {
				file: t('filinq', 'This file'),
				conversionOutput: t('filinq', 'Its PDF/A-3 conversion'),
			}
			return ['file', 'conversionOutput']
				.filter((subject) => this.reports[subject])
				.map((subject) => ({
					subject,
					heading: headings[subject],
					report: {
						failedRules: [],
						fontsNotEmbedded: [],
						...this.reports[subject],
					},
				}))
		},
	},

	watch: {
		show: {
			immediate: true,
			/**
			 * Load the reports each time the modal opens.
			 *
			 * @param {boolean} open Whether it is open.
			 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
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

		/**
		 * Read the stored reports.
		 *
		 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
		 */
		async load() {
			this.loading = true
			this.error = ''
			try {
				const { available, reports } = await fetchConformance(this.fileId)
				this.available = available
				this.reports = reports
			} catch {
				this.error = t('filinq', 'The PDF/A report could not be loaded.')
			} finally {
				this.loading = false
			}
		},

		/**
		 * Run veraPDF now and show the new report.
		 *
		 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
		 */
		async check() {
			this.checking = true
			this.error = ''
			try {
				const report = await checkConformance(this.fileId)
				this.reports = { ...this.reports, file: report }
			} catch (e) {
				this.error =
					e?.response?.data?.error
					|| t(
						'filinq',
						'The document could not be checked against PDF/A.',
					)
			} finally {
				this.checking = false
			}
		},

		/**
		 * The verdict line.
		 *
		 * @param {object} report The report.
		 * @return {string} The verdict.
		 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
		 */
		verdict(report) {
			return verdictText(report)
		},

		/**
		 * The advice for a report.
		 *
		 * @param {object} report The report.
		 * @return {string} The advice, '' for none.
		 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
		 */
		advice(report) {
			return guidanceText(report.guidance)
		},

		/**
		 * A date for people.
		 *
		 * @param {string} value An ISO date-time.
		 * @return {string} The local date and time.
		 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
		 */
		formatDate(value) {
			const date = new Date(value)
			return Number.isNaN(date.getTime()) ? '' : date.toLocaleString()
		},
	},
}
</script>

<style scoped>
.conformance-report {
	padding: 16px 20px;
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.conformance-report__entry {
	display: flex;
	flex-direction: column;
	gap: 8px;
	padding-top: 8px;
	border-top: 1px solid var(--color-border);
}

.conformance-report__verdict {
	font-weight: bold;
	color: var(--color-error-text);
}

.conformance-report__verdict--pass {
	color: var(--color-success-text);
}

.conformance-report__fonts {
	display: flex;
	gap: 8px;
	flex-wrap: wrap;
}

.conformance-report__rules {
	width: 100%;
	border-collapse: collapse;
}

.conformance-report__rules th,
.conformance-report__rules td {
	text-align: start;
	padding: 4px 8px;
	border-bottom: 1px solid var(--color-border);
}

.conformance-report__meta {
	color: var(--color-text-maxcontrast);
}

.conformance-report__actions {
	display: flex;
	justify-content: flex-end;
}
</style>
