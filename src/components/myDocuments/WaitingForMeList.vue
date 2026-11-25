<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<section class="waiting-for-me" data-testid="waiting-for-me">
		<div class="waiting-for-me__choice" role="group" :aria-label="t('filinq', 'Show')">
			<NcButton
				:variant="view === 'mine' ? 'primary' : 'secondary'"
				:aria-pressed="view === 'mine' ? 'true' : 'false'"
				data-testid="view-mine"
				@click="choose('mine')">
				{{ t('filinq', 'My records') }}
			</NcButton>
			<NcButton
				:variant="view === 'waiting' ? 'primary' : 'secondary'"
				:aria-pressed="view === 'waiting' ? 'true' : 'false'"
				data-testid="view-waiting"
				@click="choose('waiting')">
				{{ t('filinq', 'Waiting for me') }}
			</NcButton>
		</div>
		<p v-if="loading">
			{{ t('filinq', 'Loading…') }}
		</p>
		<p v-else-if="error" class="waiting-for-me__error" role="alert">
			{{ error }}
		</p>
		<template v-else-if="view === 'waiting'">
			<p v-if="waiting.length === 0">
				{{ t('filinq', 'Nothing is waiting for you') }}
			</p>
			<ul v-else>
				<li v-for="row in waiting" :key="row.key" class="waiting-for-me__item">
					<span>{{ row.name }}</span>
					<span class="waiting-for-me__reason">{{ row.reason }}</span>
					<span v-if="row.since" class="waiting-for-me__since">{{ row.since }}</span>
				</li>
			</ul>
		</template>
		<template v-else>
			<p v-if="records.length === 0">
				{{ t('filinq', 'You have not created any document records yet') }}
			</p>
			<ul v-else>
				<li v-for="row in records" :key="row.key" class="waiting-for-me__item">
					<span>{{ row.name }}</span>
					<span class="waiting-for-me__reason">{{ row.status }}</span>
					<span class="waiting-for-me__since">
						{{ n('filinq', 'On %n record', 'On %n records', row.linkedTo) }}
					</span>
				</li>
			</ul>
		</template>
	</section>
</template>

<script>
import axios from '@nextcloud/axios'
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton } from '@nextcloud/vue'
import { recordRows, waitingRows } from './waitingWork.js'

export default {
	name: 'WaitingForMeList',
	components: { NcButton },

	data() {
		return {
			view: 'mine',
			records: [],
			waiting: [],
			loading: true,
			error: '',
		}
	},

	/**
	 * Read the records the reader created.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
	 */
	mounted() {
		this.load()
	},

	methods: {
		t,
		n,

		/**
		 * Switch between My records and Waiting for me, and read that list.
		 *
		 * @param {string} view `mine` or `waiting`.
		 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
		 */
		choose(view) {
			this.view = view
			this.load()
		},

		/**
		 * Read the list on show.
		 *
		 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
		 */
		async load() {
			this.loading = true
			this.error = ''
			try {
				if (this.view === 'waiting') {
					const { data } = await axios.get(generateUrl('/apps/filinq/api/documents/waiting'))
					this.waiting = waitingRows(data.results)
				} else {
					const { data } = await axios.get(generateUrl('/apps/filinq/api/case-documents/mine'))
					this.records = recordRows(data.results)
				}
			} catch {
				this.error = t('filinq', 'The list could not be loaded')
			} finally {
				this.loading = false
			}
		},
	},
}
</script>

<style scoped>
.waiting-for-me {
	margin-top: 24px;
}

.waiting-for-me__choice {
	display: flex;
	gap: 8px;
	margin-bottom: 12px;
}

.waiting-for-me__item {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 12px;
	padding: 4px 0;
	border-bottom: 1px solid var(--color-border);
}

.waiting-for-me__reason,
.waiting-for-me__since {
	color: var(--color-text-maxcontrast);
}

.waiting-for-me__error {
	color: var(--color-error-text);
}
</style>
