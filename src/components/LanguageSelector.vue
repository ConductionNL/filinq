<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Language selector dropdown for template detail pages.

Allows users to switch the displayed content language of translatable template
metadata (name, description) without a page reload (REQ-I18N-022).
The selected language persists across navigation via the template store (REQ-I18N-021).

@spec openspec/changes/register-i18n/tasks.md#task-1
-->
<template>
	<div class="language-selector">
		<NcSelect
			v-if="hasMultipleLanguages"
			:modelValue="currentOption"
			:options="languageOptions"
			:clearable="false"
			:inputLabel="t('docudesk', 'Content language')"
			label="label"
			trackBy="value"
			class="language-selector__select"
			@update:modelValue="onLanguageChange" />
		<span v-if="isFallbackLanguage" class="language-selector__fallback-badge">
			{{ t('docudesk', 'Showing fallback language') }}
		</span>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcSelect } from '@nextcloud/vue'

/**
 * Language code to display label mapping.
 */
const LANGUAGE_LABELS = {
	nl: 'Nederlands',
	en: 'English',
}

export default {
	name: 'LanguageSelector',
	components: { NcSelect },
	props: {
		/**
		 * List of available BCP 47 language codes for the current template.
		 */
		availableLanguages: {
			type: Array,
			default: () => [],
		},

		/**
		 * Currently selected language code.
		 */
		selectedLanguage: {
			type: String,
			default: null,
		},

		/**
		 * Whether the content is being shown in a fallback language (REQ-I18N-012).
		 */
		isFallbackLanguage: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['language-change'],
	computed: {
		/** True when the template has translations in more than one language. */
		hasMultipleLanguages() {
			return this.availableLanguages.length >= 2
		},

		languageOptions() {
			return this.availableLanguages.map((lang) => ({
				value: lang,
				label: LANGUAGE_LABELS[lang] || lang.toUpperCase(),
			}))
		},

		currentOption() {
			if (!this.selectedLanguage) {
				return this.languageOptions[0] || null
			}
			return (
				this.languageOptions.find((o) => o.value === this.selectedLanguage)
				|| this.languageOptions[0]
				|| null
			)
		},
	},

	methods: {
		t,
		onLanguageChange(option) {
			if (option && option.value) {
				this.$emit('language-change', option.value)
			}
		},
	},
}
</script>

<style scoped>
.language-selector {
	display: flex;
	align-items: center;
	gap: 8px;
}

.language-selector__select {
	min-width: 140px;
}

.language-selector__fallback-badge {
	font-size: 12px;
	color: var(--color-warning-text);
	background: var(--color-warning);
	padding: 2px 8px;
	border-radius: 10px;
	white-space: nowrap;
}
</style>
