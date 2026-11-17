<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The activity of the open document: every recorded action on it, oldest first,
each with who did it and when, read from OpenRegister's audit trail of the
document's record. Renders nothing while no document is open.

@spec openspec/changes/specify-capability-rows/tasks.md#task-2.3
-->

<template>
	<section
		v-if="fileId"
		class="document-audit-trail"
		:aria-label="t('filinq', 'Activity')"
		data-testid="document-audit-trail">
		<h3 class="document-audit-trail__title">
			{{ t('filinq', 'Activity') }}
		</h3>
		<NcLoadingIcon v-if="loading" :size="20" />
		<p v-else-if="failed" class="document-audit-trail__note">
			{{ t('filinq', 'The activity of this document could not be loaded.') }}
		</p>
		<p v-else-if="entries.length === 0" class="document-audit-trail__note">
			{{ t('filinq', 'No activity recorded for this document yet.') }}
		</p>
		<ol v-else class="document-audit-trail__list">
			<li
				v-for="(entry, index) in entries"
				:key="index"
				class="document-audit-trail__entry">
				<span class="document-audit-trail__action">{{ actionLabel(entry.action) }}</span>
				<span class="document-audit-trail__meta">
					{{ actorLabel(entry) }} ·
					<time :datetime="entry.created">{{ moment(entry.created) }}</time>
				</span>
			</li>
		</ol>
	</section>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcLoadingIcon } from '@nextcloud/vue'
import {
	actionLabel,
	actorLabel,
	fetchDocumentTrail,
} from '../services/documentAuditTrail.js'

export default {
	name: 'DocumentAuditTrail',

	components: { NcLoadingIcon },

	props: {
		/** The Nextcloud file id of the open document, null when none is open. */
		fileId: { type: [Number, String], default: null },
	},

	data() {
		return { entries: [], loading: false, failed: false, request: 0 }
	},

	watch: {
		fileId: {
			immediate: true,
			handler() {
				this.load()
			},
		},
	},

	methods: {
		t,
		actionLabel,
		actorLabel,

		/**
		 * Read the trail of the open document; a late answer for a document
		 * that is no longer open is dropped.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.3
		 */
		async load() {
			const request = ++this.request
			this.entries = []
			this.failed = false
			if (!this.fileId) {
				this.loading = false
				return
			}
			this.loading = true
			const outcome = await fetchDocumentTrail(this.fileId)
			if (request !== this.request) {
				return
			}
			this.entries = outcome.entries
			this.failed = !outcome.ok
			this.loading = false
		},

		/**
		 * The moment of an entry in the reader's locale.
		 *
		 * @param {string} created The ISO moment.
		 * @return {string} The moment to show.
		 */
		moment(created) {
			const date = new Date(created)
			return Number.isNaN(date.getTime()) ? String(created ?? '') : date.toLocaleString()
		},
	},
}
</script>

<style scoped>
.document-audit-trail {
	border-top: 1px solid var(--color-border);
	margin-top: 1rem;
	padding-top: 1rem;
}

.document-audit-trail__title {
	font-size: 0.9rem;
	margin: 0 0 0.5rem;
}

.document-audit-trail__note {
	color: var(--color-text-maxcontrast);
	margin: 0;
}

.document-audit-trail__list {
	list-style: none;
	margin: 0;
	padding: 0;
}

.document-audit-trail__entry {
	display: flex;
	flex-direction: column;
	padding: 0.25rem 0;
}

.document-audit-trail__meta {
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
}
</style>
