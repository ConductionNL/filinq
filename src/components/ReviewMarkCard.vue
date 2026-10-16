<!--
  SPDX-FileCopyrightText: 2026 Conduction B.V.
  SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<section class="review-mark" :aria-label="t('filinq', 'Check before anonymising')">
		<h3 class="review-mark__title">
			{{ t('filinq', 'Check before anonymising') }}
		</h3>
		<p class="review-mark__state" :class="{ 'review-mark__state--done': checked }" aria-live="polite">
			<CheckDecagram v-if="checked" :size="16" aria-hidden="true" />
			{{ stateLabel }}
		</p>
		<p v-if="!checked" class="review-mark__hint">
			{{
				t(
					'filinq',
					'Look at every finding, then mark the document as checked. Anonymising is possible after that.',
				)
			}}
		</p>
		<NcButton
			v-if="!checked"
			wide
			variant="secondary"
			:disabled="busy || disabled"
			@click="$emit('confirm')">
			<template #icon>
				<NcLoadingIcon v-if="busy" :size="20" />
				<CheckDecagram v-else :size="20" />
			</template>
			{{ t('filinq', 'Mark as checked') }}
		</NcButton>
		<p v-if="error" class="review-mark__error" role="alert">
			{{ error }}
		</p>
	</section>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import CheckDecagram from 'vue-material-design-icons/CheckDecagram.vue'

/**
 * The human check before a redacted copy is written.
 *
 * Shows whether the findings on screen were checked, by whom and when, and
 * offers the reviewer the one action that records it. The parent owns the
 * call; this component only asks for it.
 *
 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-4
 */
export default {
	name: 'ReviewMarkCard',
	components: { CheckDecagram, NcButton, NcLoadingIcon },
	props: {
		/** The mark recorded this session, or null. */
		mark: {
			type: Object,
			default: null,
		},

		/** Whether the mark covers the findings as they are now. */
		checked: {
			type: Boolean,
			default: false,
		},

		/** True while the mark is being recorded. */
		busy: {
			type: Boolean,
			default: false,
		},

		/** True when there is nothing to check yet. */
		disabled: {
			type: Boolean,
			default: false,
		},

		/** A refusal or failure to show, or empty. */
		error: {
			type: String,
			default: '',
		},
	},

	emits: ['confirm'],

	computed: {
		/**
		 * Who checked and when, or that nobody did.
		 *
		 * @return {string}
		 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-4
		 */
		stateLabel() {
			if (!this.checked || !this.mark) {
				return t('filinq', 'Not checked yet')
			}
			return t('filinq', 'Checked by {user} on {date}', {
				user: this.mark.checkedBy,
				date: this.formatMoment(this.mark.checkedAt),
			})
		},
	},

	methods: {
		t,

		/**
		 * A readable local date and time for an ISO moment.
		 *
		 * @param {string} iso The moment.
		 * @return {string}
		 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-4
		 */
		formatMoment(iso) {
			const date = new Date(iso)
			if (!iso || Number.isNaN(date.getTime())) {
				return ''
			}
			return date.toLocaleString(undefined, {
				dateStyle: 'medium',
				timeStyle: 'short',
			})
		},
	},
}
</script>

<style scoped>
.review-mark {
	display: flex;
	flex-direction: column;
	gap: 8px;
	padding: 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	background: var(--color-main-background);
}

.review-mark__title {
	margin: 0;
	font-size: 1rem;
	font-weight: 600;
}

.review-mark__state {
	display: flex;
	align-items: center;
	gap: 6px;
	margin: 0;
	color: var(--color-text-maxcontrast);
}

.review-mark__state--done {
	color: var(--color-success-text);
}

.review-mark__hint {
	margin: 0;
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
}

.review-mark__error {
	margin: 0;
	color: var(--color-error-text);
}
</style>
