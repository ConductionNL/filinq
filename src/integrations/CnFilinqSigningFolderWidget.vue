<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The signing folder as a dashboard leaf: the first documents waiting for the
signer's signature, across every record, and a link to the full folder where
they are read and signed. Plain markup only, because the leaves bundle loads on
other apps' pages and carries no component library.

@spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
-->

<template>
	<div class="filinq-signing-leaf" data-testid="filinq-signing-folder-leaf">
		<p v-if="loading" class="filinq-signing-leaf__state">
			{{ t('filinq', 'Reading the signing folder') }}
		</p>
		<p
			v-else-if="error"
			class="filinq-signing-leaf__state filinq-signing-leaf__state--error"
			role="alert">
			{{ t('filinq', 'The signing folder could not be read.') }}
		</p>
		<p v-else-if="total === 0" class="filinq-signing-leaf__state">
			{{ t('filinq', 'Nothing is waiting for your signature.') }}
		</p>
		<template v-else>
			<p class="filinq-signing-leaf__heading">
				{{
					n(
						'filinq',
						'%n document is waiting for your signature',
						'%n documents are waiting for your signature',
						total,
					)
				}}
			</p>
			<ul class="filinq-signing-leaf__list">
				<li v-for="entry in entries" :key="entry.requestId">
					<span>{{
						entry.documentName || t('filinq', 'Untitled document')
					}}</span>
					<span
						v-if="deadline(entry)"
						class="filinq-signing-leaf__deadline">
						{{
							t('filinq', 'sign by {date}', { date: deadline(entry) })
						}}
					</span>
				</li>
			</ul>
		</template>
		<a class="filinq-signing-leaf__link" :href="folderUrl">
			{{ t('filinq', 'Open the signing folder') }}
		</a>
	</div>
</template>

<script>
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import {
	fetchSigningFolderPreview,
	formatDeadline,
	signingFolderPageUrl,
} from '../services/signingFolderLeaf.js'

/**
 * The first entries of the signing folder, read on every mount.
 *
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */
export default {
	name: 'CnFilinqSigningFolderWidget',

	data() {
		return {
			entries: [],
			total: 0,
			loading: true,
			error: false,
			folderUrl: signingFolderPageUrl(),
		}
	},

	/**
	 * Read the first entries of the signing folder.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
	 */
	async mounted() {
		try {
			const preview = await fetchSigningFolderPreview()
			this.entries = preview.entries
			this.total = preview.total
		} catch {
			this.error = true
		} finally {
			this.loading = false
		}
	},

	methods: {
		t,
		n,

		/**
		 * The entry's deadline as a local date.
		 *
		 * @param {object} entry A folder entry.
		 * @return {string} The date, or '' when there is none.
		 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
		 */
		deadline(entry) {
			return formatDeadline(entry?.deadline)
		},
	},
}
</script>

<style scoped>
.filinq-signing-leaf {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.filinq-signing-leaf__heading {
	font-weight: bold;
	margin: 0;
}

.filinq-signing-leaf__list {
	margin: 0;
	padding-inline-start: 20px;
}

.filinq-signing-leaf__deadline,
.filinq-signing-leaf__state {
	color: var(--color-text-maxcontrast);
}

.filinq-signing-leaf__deadline {
	margin-inline-start: 8px;
}

.filinq-signing-leaf__state {
	margin: 0;
}

.filinq-signing-leaf__state--error {
	color: var(--color-error);
}

.filinq-signing-leaf__link {
	color: var(--color-primary-element);
	text-decoration: underline;
}
</style>
