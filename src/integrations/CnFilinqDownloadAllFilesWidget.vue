<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
  -->
<template>
	<div class="filinq-archive-leaf">
		<p v-if="loading" class="filinq-archive-leaf__state">
			{{ t('filinq', 'Looking up documents') }}
		</p>
		<p
			v-else-if="error"
			class="filinq-archive-leaf__state filinq-archive-leaf__state--error">
			{{ t('filinq', 'The documents could not be loaded') }}
		</p>
		<p v-else-if="preflight.files === 0" class="filinq-archive-leaf__state">
			{{ t('filinq', 'No documents yet') }}
		</p>
		<div v-else class="filinq-archive-leaf__body">
			<p>
				{{
					t('filinq', '{count} files, {size} MB', {
						count: preflight.files,
						size: size(preflight.bytes),
					})
				}}
			</p>
			<p
				v-if="warn"
				role="alert"
				class="filinq-archive-leaf__state filinq-archive-leaf__state--warning">
				{{
					t(
						'filinq',
						'These files are larger than the limit of {limit} MB. Files past the limit are left out and listed in the manifest.',
						{ limit: size(preflight.ceilingBytes) },
					)
				}}
			</p>
			<button type="button" :disabled="busy" @click="download">
				{{
					busy
						? t('filinq', 'Building the archive')
						: t('filinq', 'Download all files')
				}}
			</button>

			<div v-if="result" role="status">
				<p
					v-if="result.failed"
					class="filinq-archive-leaf__state filinq-archive-leaf__state--error">
					{{
						t('filinq', 'The archive could not be built: {reason}', {
							reason: result.reason,
						})
					}}
				</p>
				<template v-else>
					<p>
						{{
							t(
								'filinq',
								'The archive holds {count} files and a manifest.',
								{ count: result.included },
							)
						}}
						<a v-if="result.link" :href="result.link">{{
							t('filinq', 'Open the archive')
						}}</a>
					</p>
					<div v-for="group in leftOut" :key="group.reason">
						<p class="filinq-archive-leaf__heading">
							{{ group.label }}
						</p>
						<ul class="filinq-archive-leaf__list">
							<li v-for="name in group.names" :key="name">
								{{ name }}
							</li>
						</ul>
					</div>
				</template>
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import {
	archiveFileId,
	excludedByReason,
	megabytes,
	needsWarning,
} from '../services/archiveBundle.js'

/**
 * Download every file filinq holds for one object as one archive.
 *
 * Asks the preflight first, so a handler whose files exceed the ceiling is
 * told before the job starts, then builds the archive and lists every file
 * that was left out, with the reason.
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
export default {
	name: 'CnFilinqDownloadAllFilesWidget',
	props: {
		/** The host object's register slug. */
		register: { type: String, default: '' },
		/** The host object's schema slug. */
		schema: { type: String, default: '' },
		/** The host object's id. */
		objectId: { type: String, default: '' },
	},

	data() {
		return {
			preflight: {
				files: 0,
				bytes: 0,
				ceilingBytes: 0,
				exceedsCeiling: false,
			},

			loading: true,
			error: false,
			busy: false,
			result: null,
		}
	},

	computed: {
		/**
		 * Whether to warn before the job starts.
		 *
		 * @return {boolean} True when the files exceed the ceiling.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		warn() {
			return needsWarning(this.preflight)
		},

		/**
		 * The left-out files, grouped under a sentence per reason.
		 *
		 * @return {Array<{reason: string, label: string, names: string[]}>} Non-empty groups.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		leftOut() {
			if (this.result === null || this.result.failed === true) {
				return []
			}
			const labels = {
				ceiling: t(
					'filinq',
					'Left out because the archive reached the size limit',
				),

				permission: t('filinq', 'Left out because you may not open them'),
				missing: t('filinq', 'Left out because the file no longer exists'),
				other: t('filinq', 'Left out for another reason'),
			}
			return Object.entries(this.result.excluded)
				.filter(([, names]) => names.length > 0)
				.map(([reason, names]) => ({ reason, label: labels[reason], names }))
		},
	},

	/**
	 * Ask the preflight when the leaf mounts.
	 *
	 * @return {void}
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	mounted() {
		this.load()
	},

	methods: {
		/**
		 * A size in megabytes.
		 *
		 * @param {number} bytes The size.
		 * @return {string} The size in MB.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		size(bytes) {
			return megabytes(bytes)
		},

		/**
		 * The host object as query parameters.
		 *
		 * @return {URLSearchParams} The parameters.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		params() {
			return new URLSearchParams({
				register: this.register,
				schema: this.schema,
				id: this.objectId,
			})
		},

		/**
		 * Read the preflight.
		 *
		 * @return {Promise<void>} Resolves once `preflight` is set.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		async load() {
			if (this.objectId === '') {
				this.loading = false
				return
			}
			try {
				const response = await fetch(
					generateUrl('/apps/filinq/api/case-archive/preflight')
						+ '?'
						+ this.params().toString(),
					{
						headers: {
							requesttoken: this.requestToken(),
							Accept: 'application/json',
						},
					},
				)
				if (response.ok === false) {
					this.error = true
					return
				}
				this.preflight = await response.json()
			} catch {
				this.error = true
			} finally {
				this.loading = false
			}
		},

		/**
		 * Build the archive and read the manifest back.
		 *
		 * @return {Promise<void>} Resolves once `result` is set.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		async download() {
			this.busy = true
			this.result = null
			try {
				const response = await fetch(
					generateUrl('/apps/filinq/api/case-archive/manifest')
						+ '?'
						+ this.params().toString(),
					{
						method: 'POST',
						headers: {
							requesttoken: this.requestToken(),
							Accept: 'application/json',
						},
					},
				)
				const body = await response.json().catch(() => ({}))
				if (response.ok === false) {
					this.result = {
						failed: true,
						reason: body.error || t('filinq', 'no reason was given'),
					}
					return
				}
				const fileId = archiveFileId(body)
				this.result = {
					failed: false,
					included: Array.isArray(body.included)
						? body.included.length
						: 0,

					excluded: excludedByReason(body),
					link: fileId > 0 ? generateUrl('/f/{fileId}', { fileId }) : '',
				}
			} catch {
				this.result = {
					failed: true,
					reason: t('filinq', 'the server could not be reached'),
				}
			} finally {
				this.busy = false
			}
		},

		/**
		 * The current page's request token, read from the DOM because this
		 * widget renders inside another app's page.
		 *
		 * @return {string} The token, or ''.
		 *
		 * @spec openspec/specs/document-creatie-sjablonen/spec.md
		 */
		requestToken() {
			const head = document.getElementsByTagName('head')[0]
			return (head && head.getAttribute('data-requesttoken')) || ''
		},
	},
}
</script>

<style scoped>
.filinq-archive-leaf__body {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.filinq-archive-leaf__list {
	margin: 0;
	padding-inline-start: 20px;
}

.filinq-archive-leaf__heading {
	font-weight: bold;
	margin: 0;
}

.filinq-archive-leaf__state {
	color: var(--color-text-maxcontrast);
	margin: 0;
}

.filinq-archive-leaf__state--warning {
	color: var(--color-warning-text, var(--color-main-text));
}

.filinq-archive-leaf__state--error {
	color: var(--color-error);
}
</style>
