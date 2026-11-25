<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<section class="due-for-review" data-testid="due-for-review">
		<h3>{{ t('filinq', 'Due for review') }}</h3>
		<p v-if="loading">
			{{ t('filinq', 'Loading…') }}
		</p>
		<p v-else-if="error" class="due-for-review__error" role="alert">
			{{ error }}
		</p>
		<p v-else-if="documents.length === 0">
			{{ t('filinq', 'No documents are due for review') }}
		</p>
		<ul v-else>
			<li v-for="document in documents" :key="uuidOf(document)" class="due-for-review__item">
				<span>{{ document.templateName || document.filePath || uuidOf(document) }}</span>
				<span class="due-for-review__date">{{ t('filinq', 'Review by {date}', { date: document.reviewDate || '' }) }}</span>
				<NcButton
					v-if="isMine(document)"
					variant="secondary"
					:disabled="busy"
					:data-testid="'mark-reviewed-' + uuidOf(document)"
					@click="markReviewed(document)">
					{{ t('filinq', 'Mark reviewed') }}
				</NcButton>
			</li>
		</ul>
	</section>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton } from '@nextcloud/vue'

export default {
	name: 'DueForReviewList',
	components: { NcButton },

	data() {
		return {
			documents: [],
			loading: true,
			busy: false,
			error: '',
		}
	},

	/**
	 * Read what is due for review.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#task-2.4
	 */
	mounted() {
		this.load()
	},

	methods: {
		t,

		/**
		 * The id of a generated document, wherever OpenRegister put it.
		 *
		 * @param {object} document The document.
		 * @return {string} The uuid.
		 *
		 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#task-2.4
		 */
		uuidOf(document) {
			return document.uuid || document.id || document['@self']?.id || ''
		},

		/**
		 * Whether the reader owns the document, so may mark it reviewed.
		 *
		 * @param {object} document The document.
		 * @return {boolean}
		 */
		isMine(document) {
			return Boolean(document.owner) && document.owner === getCurrentUser()?.uid
		},

		/**
		 * Read the documents due for review.
		 *
		 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#task-2.4
		 */
		async load() {
			this.loading = true
			this.error = ''
			try {
				const { data } = await axios.get(generateUrl('/apps/filinq/api/documents/due-for-review'))
				this.documents = data.results ?? []
			} catch {
				this.error = t('filinq', 'The documents due for review could not be loaded')
			} finally {
				this.loading = false
			}
		},

		/**
		 * Mark one document reviewed; it leaves the list.
		 *
		 * @param {object} document The document.
		 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#task-2.4
		 */
		async markReviewed(document) {
			this.busy = true
			this.error = ''
			try {
				await axios.post(generateUrl('/apps/filinq/api/documents/{uuid}/reviewed', { uuid: this.uuidOf(document) }))
				await this.load()
			} catch (e) {
				this.error = e?.response?.data?.error ?? t('filinq', 'The document could not be marked reviewed')
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.due-for-review {
	margin-top: 24px;
}

.due-for-review__item {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 4px 0;
	border-bottom: 1px solid var(--color-border);
}

.due-for-review__date {
	color: var(--color-text-maxcontrast);
}

.due-for-review__error {
	color: var(--color-error-text);
}
</style>
