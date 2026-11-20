<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Share one document, or several as one archive, by a read-only link that
runs out. Asks the preflight first: Nextcloud's link rules, the labels,
and what a bundle would hold, so nothing is built before the clerk knows.

@spec openspec/changes/share-document-links/tasks.md#task-3.1
-->

<template>
	<NcModal
		v-if="show"
		:show="show"
		:name="title"
		size="normal"
		@close="close">
		<div class="share-by-link" data-testid="share-by-link">
			<h2 class="share-by-link__title">
				{{ title }}
			</h2>

			<NcLoadingIcon v-if="loading" :size="32" />

			<template v-else-if="result">
				<NcNoteCard type="success">
					{{
						t('filinq', 'The link works until {date}.', {
							date: result.expiresAt,
						})
					}}
				</NcNoteCard>
				<NcNoteCard v-if="result.expiryShortened" type="info">
					{{
						t(
							'filinq',
							'Nextcloud allows links for at most {days} days, so the expiry was set to {date}.',
							{ days: preflight.maxDays, date: result.expiresAt },
						)
					}}
				</NcNoteCard>
				<NcTextField
					:modelValue="result.url"
					:label="t('filinq', 'Link')"
					:labelOutside="true"
					readonly
					data-testid="share-by-link-url" />
				<p v-if="excludedNames.length > 0" class="share-by-link__hint">
					{{
						t('filinq', 'Left out of the bundle: {names}', {
							names: excludedNames.join(', '),
						})
					}}
				</p>
				<div class="share-by-link__buttons">
					<NcButton variant="primary" data-testid="share-by-link-copy" @click="copyLink">
						{{ copied ? t('filinq', 'Copied') : t('filinq', 'Copy link') }}
					</NcButton>
					<NcButton variant="tertiary" @click="close">
						{{ t('filinq', 'Close') }}
					</NcButton>
				</div>
			</template>

			<template v-else>
				<NcNoteCard v-if="error" type="error" data-testid="share-by-link-error">
					{{ error }}
				</NcNoteCard>

				<p class="share-by-link__hint">
					{{
						t(
							'filinq',
							'Whoever has the link can open it until it runs out. You can take it back at any time on the Shared links page.',
						)
					}}
				</p>

				<template v-if="preflight">
					<NcTextField
						v-if="isBundle"
						v-model="name"
						:label="t('filinq', 'Name of the bundle')"
						:labelOutside="true"
						maxlength="120"
						data-testid="share-by-link-name" />

					<p v-if="bundle" class="share-by-link__bundle" data-testid="share-by-link-bundle">
						{{
							n(
								'filinq',
								'The bundle holds %n file of {size} MB.',
								'The bundle holds %n files of {size} MB.',
								bundle.files,
								{ size: shareMegabytes(bundle.bytes) },
							)
						}}
					</p>
					<NcNoteCard
						v-if="bundle && bundle.exceedsCeiling"
						type="warning"
						data-testid="share-by-link-ceiling">
						{{
							t(
								'filinq',
								'The bundle goes over the ceiling of {size} MB. Left out: {names}',
								{
									size: shareMegabytes(bundle.ceilingBytes),
									names: ceilingNames.join(', '),
								},
							)
						}}
					</NcNoteCard>

					<NcTextField
						v-model="expiresOn"
						type="date"
						:label="t('filinq', 'Link works until')"
						:labelOutside="true"
						:min="tomorrow"
						:max="latest || undefined"
						data-testid="share-by-link-expiry" />
					<p v-if="latest" class="share-by-link__hint">
						{{
							t(
								'filinq',
								'Nextcloud allows links for at most {days} days.',
								{ days: preflight.maxDays },
							)
						}}
					</p>

					<NcTextField
						v-model="password"
						type="password"
						:label="
							preflight.passwordRequired
								? t('filinq', 'Password (required by Nextcloud)')
								: t('filinq', 'Password (optional)')
						"
						:labelOutside="true"
						autocomplete="new-password"
						data-testid="share-by-link-password" />

					<NcTextField
						v-model="note"
						:label="t('filinq', 'Who is the link for?')"
						:labelOutside="true"
						maxlength="500"
						data-testid="share-by-link-note" />

					<template v-if="labels.length > 0">
						<NcNoteCard type="warning" data-testid="share-by-link-label">
							{{
								n(
									'filinq',
									'This document is labelled "{labels}".',
									'These documents are labelled "{labels}".',
									fileIds.length,
									{ labels: labels.join('", "') },
								)
							}}
						</NcNoteCard>
						<NcCheckboxRadioSwitch
							v-model="labelConfirmed"
							data-testid="share-by-link-confirm">
							{{
								t(
									'filinq',
									'I confirm that this may leave the organisation by link.',
								)
							}}
						</NcCheckboxRadioSwitch>
					</template>
				</template>

				<div class="share-by-link__buttons">
					<NcButton
						variant="primary"
						:disabled="!shareable || sharing"
						data-testid="share-by-link-submit"
						@click="share">
						{{ sharing ? t('filinq', 'Sharing…') : t('filinq', 'Share by link') }}
					</NcButton>
					<NcButton variant="tertiary" @click="close">
						{{ t('filinq', 'Cancel') }}
					</NcButton>
				</div>
			</template>
		</div>
	</NcModal>
</template>

<script>
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcLoadingIcon,
	NcModal,
	NcNoteCard,
	NcTextField,
} from '@nextcloud/vue'
import {
	canShare,
	createShare,
	latestExpiry,
	preflightShare,
	shareFileIds,
	shareLabels,
	shareMegabytes,
} from '../services/documentShare.js'

export default {
	name: 'ShareByLinkDialog',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		NcModal,
		NcNoteCard,
		NcTextField,
	},

	props: {
		/** Whether the dialog is open. */
		show: {
			type: Boolean,
			default: false,
		},

		/** The chosen documents' file ids; two or more become one bundle. */
		documents: {
			type: Array,
			default: () => [],
		},
	},

	emits: ['close', 'shared'],

	data() {
		return {
			loading: false,
			sharing: false,
			copied: false,
			error: '',
			preflight: null,
			result: null,
			name: '',
			expiresOn: '',
			password: '',
			note: '',
			labelConfirmed: false,
		}
	},

	computed: {
		fileIds() {
			return shareFileIds(this.documents)
		},

		isBundle() {
			return this.fileIds.length > 1
		},

		title() {
			return this.isBundle
				? n('filinq', 'Share %n document by link', 'Share %n documents by link', this.fileIds.length)
				: t('filinq', 'Share by link')
		},

		bundle() {
			return this.preflight ? this.preflight.bundle : null
		},

		labels() {
			return shareLabels(this.preflight)
		},

		latest() {
			return latestExpiry(this.preflight ? this.preflight.maxDays : null)
		},

		tomorrow() {
			const today = new Date()
			return new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate() + 1))
				.toISOString()
				.slice(0, 10)
		},

		ceilingNames() {
			const excluded = this.bundle && Array.isArray(this.bundle.excluded) ? this.bundle.excluded : []
			return excluded.filter((entry) => entry.reason === 'ceiling').map((entry) => entry.name)
		},

		excludedNames() {
			const excluded = this.result && this.result.manifest ? this.result.manifest.excluded || [] : []
			return excluded.map((entry) => entry.name || String(entry.fileId))
		},

		shareable() {
			return canShare({
				preflight: this.preflight,
				expiresOn: this.expiresOn,
				password: this.password,
				name: this.name,
				labelConfirmed: this.labelConfirmed,
				fileCount: this.fileIds.length,
			})
		},
	},

	watch: {
		show: {
			immediate: true,
			handler(open) {
				if (open) {
					this.start()
				}
			},
		},
	},

	methods: {
		t,
		n,
		shareMegabytes,

		/**
		 * Reset the dialog and ask the preflight.
		 *
		 * @return {Promise<void>}
		 */
		async start() {
			Object.assign(this, {
				error: '',
				preflight: null,
				result: null,
				name: '',
				password: '',
				note: '',
				labelConfirmed: false,
				copied: false,
				loading: true,
			})
			const outcome = await preflightShare(this.fileIds)
			this.loading = false
			if (!outcome.ok) {
				this.error = outcome.error || t('filinq', 'The link could not be prepared. Try again later.')
				return
			}
			this.preflight = outcome.data
			this.expiresOn = outcome.data.defaultExpiresAt || ''
		},

		/**
		 * Make the link; a refusal stays in the dialog with Nextcloud's reason.
		 *
		 * @return {Promise<void>}
		 */
		async share() {
			this.sharing = true
			this.error = ''
			const outcome = await createShare({
				fileIds: this.fileIds,
				name: this.name,
				expiresOn: this.expiresOn,
				password: this.password,
				note: this.note,
				labelConfirmed: this.labelConfirmed,
			})
			this.sharing = false
			if (!outcome.ok) {
				this.error = outcome.error || t('filinq', 'The link could not be made. Try again later.')
				return
			}
			this.result = outcome.data
			this.$emit('shared', outcome.data.share)
		},

		/**
		 * Copy the link to the clipboard.
		 *
		 * @return {Promise<void>}
		 */
		async copyLink() {
			try {
				await navigator.clipboard.writeText(this.result.url)
				this.copied = true
			} catch {
				this.error = t('filinq', 'The link could not be copied. Select it and copy it by hand.')
			}
		},

		close() {
			this.$emit('close')
		},
	},
}
</script>

<style scoped>
.share-by-link {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 16px 20px;
}

.share-by-link__title {
	margin: 0;
}

.share-by-link__hint,
.share-by-link__bundle {
	margin: 0;
	color: var(--color-text-maxcontrast);
}

.share-by-link__buttons {
	display: flex;
	gap: 8px;
	justify-content: flex-end;
}
</style>
