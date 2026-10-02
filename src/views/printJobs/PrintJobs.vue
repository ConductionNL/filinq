<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2
@spec openspec/specs/print-preview/spec.md
-->
<template>
	<div class="print-jobs">
		<div class="print-jobs__header">
			<h2>{{ t('filinq', 'Print jobs') }}</h2>
			<p class="print-jobs__subtitle">
				{{ t('filinq', 'Letters you sent to print, newest first.') }}
			</p>
		</div>
		<NcNoteCard v-if="error" type="error">
			{{ error }}
		</NcNoteCard>
		<NcLoadingIcon v-if="loading && jobs.length === 0" :size="32" />
		<NcEmptyContent
			v-else-if="!loading && jobs.length === 0 && !error"
			:name="t('filinq', 'No print jobs yet')"
			:description="
				t('filinq', 'Send letters to print from Letters & correspondence.')
			" />
		<table v-else class="print-jobs__table">
			<thead>
				<tr>
					<th scope="col">
						{{ t('filinq', 'Sent') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'File name') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Letters') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Status') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Download') }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="job in jobs" :key="job.uuid" :data-status="job.status">
					<td>{{ formatTime(job.requestedAt) }}</td>
					<td>{{ job.filename }}</td>
					<td>
						{{
							t('filinq', '{rendered} of {total}', {
								rendered: job.rendered || 0,
								total: job.total || 0,
							})
						}}
					</td>
					<td>
						<span class="print-jobs__status">{{
							statusLabel(job.status)
						}}</span>
						<span class="print-jobs__changed">
							{{ formatTime(job.statusChangedAt) }}
						</span>
						<span v-if="job.statusDetails" class="print-jobs__details">
							{{ job.statusDetails }}
						</span>
					</td>
					<td>
						<a
							v-if="canDownload(job)"
							:href="downloadUrl(job)"
							:aria-label="
								t('filinq', 'Download {name}', {
									name: job.filename,
								})
							">
							{{ t('filinq', 'Download') }}
						</a>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcEmptyContent, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import { canDownload, printJobStatusLabel } from '../../services/printJobs.js'

/**
 * How often the page reads the list again, in milliseconds. A print service
 * reports back through the status endpoint; once a minute is often enough to
 * see it (design D3).
 */
const REFRESH_MS = 60000

export default {
	name: 'PrintJobs',
	components: { NcEmptyContent, NcLoadingIcon, NcNoteCard },

	data() {
		return { jobs: [], loading: false, error: '', timer: null }
	},

	/**
	 * Load the jobs and read them again once a minute.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.4
	 */
	mounted() {
		this.load()
		this.timer = setInterval(this.load, REFRESH_MS)
	},

	/**
	 * Stop reading the list when the page closes.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.4
	 */
	beforeUnmount() {
		clearInterval(this.timer)
	},

	methods: {
		t,
		canDownload,

		/**
		 * Read the caller's own jobs.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.4
		 */
		async load() {
			this.loading = true
			try {
				const response = await axios.get(
					generateUrl('/apps/filinq/api/print/jobs'),
				)
				this.jobs = response.data.results || []
				this.error = ''
			} catch {
				this.error = t('filinq', 'Could not load your print jobs.')
			} finally {
				this.loading = false
			}
		},

		/**
		 * The label of a status.
		 *
		 * @param {string} status The stored status
		 * @return {string} The label.
		 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.4
		 */
		statusLabel(status) {
			return printJobStatusLabel(status)
		},

		/**
		 * The download address of a job.
		 *
		 * @param {object} job The job
		 * @return {string} The URL.
		 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.4
		 */
		downloadUrl(job) {
			return generateUrl('/apps/filinq/api/print/jobs/{id}/download', {
				id: job.uuid,
			})
		},

		/**
		 * A stored time in the reader's locale.
		 *
		 * @param {string} value An ISO date-time
		 * @return {string} The time, or an empty string.
		 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.4
		 */
		formatTime(value) {
			if (!value) {
				return ''
			}
			return new Date(value).toLocaleString()
		},
	},
}
</script>

<style scoped>
.print-jobs {
	max-width: 1000px;
	padding: 24px;
}

.print-jobs__header {
	margin-bottom: 24px;
}

.print-jobs__subtitle,
.print-jobs__changed,
.print-jobs__details {
	color: var(--color-text-maxcontrast);
}

.print-jobs__table {
	width: 100%;
	border-collapse: collapse;
}

.print-jobs__table th,
.print-jobs__table td {
	text-align: start;
	padding: 8px;
	border-bottom: 1px solid var(--color-border);
	vertical-align: top;
}

.print-jobs__status {
	display: block;
	font-weight: bold;
}

.print-jobs__changed,
.print-jobs__details {
	display: block;
	font-size: 0.9em;
}
</style>
