<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

One suggestion on an arriving document, a registry answer or a party read out
of its text, with what the clerk can do with it: accept it as it is, correct it
first, or dismiss it. The card never decides anything: it emits the choice and
the fields, and the dialog sends them.

@spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-person-accepts-or-dismisses-a-registry-suggestion-req-ibr-004
-->

<template>
	<div class="intake-suggestion" data-testid="intake-suggestion">
		<p class="intake-suggestion__title">
			<strong>{{ title }}</strong>
		</p>
		<p
			v-if="state === 'accepted'"
			class="intake-suggestion__state"
			role="status"
			data-testid="intake-suggestion-accepted">
			{{ t('filinq', 'Accepted') }}
		</p>
		<p
			v-else-if="state === 'dismissed'"
			class="intake-suggestion__state"
			role="status"
			data-testid="intake-suggestion-dismissed">
			{{ t('filinq', 'Dismissed') }}
		</p>
		<template v-else>
			<dl v-if="!correcting" class="intake-suggestion__fields">
				<template v-for="key in shownKeys" :key="key">
					<dt>{{ labels[key] || key }}</dt>
					<dd :data-testid="`intake-suggestion-field-${key}`">{{ fields[key] }}</dd>
				</template>
			</dl>
			<div v-else class="intake-suggestion__form">
				<NcTextField
					v-for="key in editableKeys"
					:key="key"
					:modelValue="draft[key] || ''"
					:label="labels[key] || key"
					:disabled="saving"
					:data-testid="`intake-suggestion-input-${key}`"
					@update:modelValue="setDraft(key, $event)" />
			</div>
			<div class="intake-suggestion__actions">
				<NcButton
					variant="primary"
					:disabled="saving"
					data-testid="intake-suggestion-accept"
					@click="accept">
					{{ correcting ? t('filinq', 'Accept the correction') : acceptLabel }}
				</NcButton>
				<NcButton
					v-if="!correcting"
					:disabled="saving"
					data-testid="intake-suggestion-correct"
					@click="startCorrecting">
					{{ t('filinq', 'Correct') }}
				</NcButton>
				<NcButton
					variant="tertiary"
					:disabled="saving"
					data-testid="intake-suggestion-dismiss"
					@click="$emit('dismiss')">
					{{ t('filinq', 'Dismiss the suggestion') }}
				</NcButton>
			</div>
		</template>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcTextField } from '@nextcloud/vue'

export default {
	name: 'IntakeSuggestionCard',
	components: { NcButton, NcTextField },

	props: {
		// What the suggestion is, for example "KvK: Voorbeeld B.V.".
		title: { type: String, required: true },
		// The suggested fields, by key.
		fields: { type: Object, required: true },
		// The fields a clerk may correct, in the order the form asks them.
		editableKeys: { type: Array, required: true },
		// The label of each field.
		labels: { type: Object, default: () => ({}) },
		// The label of the accept button.
		acceptLabel: { type: String, required: true },
		// '' while open, else `accepted` or `dismissed`.
		state: { type: String, default: '' },
		// True while the dialog is waiting for the server.
		saving: { type: Boolean, default: false },
	},

	emits: ['accept', 'dismiss'],

	data() {
		return {
			correcting: false,
			draft: {},
		}
	},

	computed: {
		shownKeys() {
			return this.editableKeys.filter((key) => typeof this.fields[key] === 'string' && this.fields[key] !== '')
		},
	},

	methods: {
		t,

		startCorrecting() {
			this.draft = { ...this.fields }
			this.correcting = true
		},

		setDraft(key, value) {
			this.draft = { ...this.draft, [key]: value }
		},

		accept() {
			this.$emit('accept', this.correcting ? { ...this.draft } : { ...this.fields })
		},
	},
}
</script>

<style scoped>
.intake-suggestion {
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: calc(var(--default-grid-baseline) * 3);
	margin-block-end: calc(var(--default-grid-baseline) * 2);
}

.intake-suggestion__title {
	margin: 0 0 calc(var(--default-grid-baseline) * 2);
}

.intake-suggestion__fields {
	display: grid;
	grid-template-columns: max-content 1fr;
	gap: var(--default-grid-baseline) calc(var(--default-grid-baseline) * 3);
	margin: 0 0 calc(var(--default-grid-baseline) * 2);
}

.intake-suggestion__fields dt {
	color: var(--color-text-maxcontrast);
}

.intake-suggestion__fields dd {
	margin: 0;
}

.intake-suggestion__form {
	display: grid;
	gap: calc(var(--default-grid-baseline) * 2);
	margin-block-end: calc(var(--default-grid-baseline) * 2);
}

.intake-suggestion__actions {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 2);
}

.intake-suggestion__state {
	color: var(--color-text-maxcontrast);
	margin: 0;
}
</style>
