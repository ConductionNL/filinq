<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/specs/document-validation-checks/spec.md
-->

<template>
	<div class="validation-findings">
		<div class="validation-findings__header">
			<CnStatusBadge :label="verdictLabel" :color-map="colorMap" />
		</div>

		<ul v-if="findings.length > 0" class="validation-findings__list">
			<li v-for="(finding, index) in findings" :key="index" class="validation-findings__item">
				<span class="validation-findings__check">{{ checkLabel(finding) }}</span>
				<span class="validation-findings__message">{{ findingMessage(finding) }}</span>
				<a v-if="finding.suggestedAction === 'ocr'"
					class="validation-findings__ocr"
					href="#/anonymization"
					@click="$emit('ocr', finding)">
					{{ t('docudesk', 'Run OCR') }}
				</a>
			</li>
		</ul>
		<p v-else class="validation-findings__empty">
			{{ t('docudesk', 'No validation findings.') }}
		</p>
	</div>
</template>

<script>
import { CnStatusBadge } from '@conduction/nextcloud-vue'
import { verdictColor } from '../services/validationService.js'

export default {
	name: 'ValidationFindingsPanel',
	components: { CnStatusBadge },
	props: {
		status: { type: String, default: '' },
		findings: { type: Array, default: () => [] },
	},
	computed: {
		/**
		 * Localised verdict label for the status chip.
		 *
		 * @return {string} The label.
		 * @spec openspec/specs/document-validation-checks/spec.md
		 */
		verdictLabel() {
			switch (this.status) {
			case 'passed':
				return t('docudesk', 'Validation passed')
			case 'warnings':
				return t('docudesk', 'Validation warnings')
			case 'failed':
				return t('docudesk', 'Validation failed')
			default:
				return t('docudesk', 'Not yet validated')
			}
		},
		/**
		 * Colour-map for the status chip keyed by the verdict label.
		 *
		 * @return {object} The colour map.
		 * @spec openspec/specs/document-validation-checks/spec.md
		 */
		colorMap() {
			return { [this.verdictLabel]: verdictColor(this.status) }
		},
	},
	methods: {
		/**
		 * Human-readable label for a finding's check id.
		 *
		 * @param {object} finding A validation finding.
		 * @return {string} The localised check label.
		 * @spec openspec/specs/document-validation-checks/spec.md
		 */
		checkLabel(finding) {
			const map = {
				'format-not-allowed': t('docudesk', 'Format not allowed'),
				'extension-mime-mismatch': t('docudesk', 'Extension/type mismatch'),
				'file-unreadable': t('docudesk', 'File unreadable'),
				'pdf-encrypted': t('docudesk', 'Encrypted PDF'),
				'text-layer-missing': t('docudesk', 'Missing text layer'),
				'metadata-incomplete': t('docudesk', 'Incomplete metadata'),
			}
			return map[finding.checkId] || finding.checkId
		},
		/**
		 * Translate a finding's English source message + interpolate its
		 * (non-content) placeholder params.
		 *
		 * @param {object} finding A validation finding.
		 * @return {string} The localised message.
		 * @spec openspec/specs/document-validation-checks/spec.md
		 */
		findingMessage(finding) {
			return t('docudesk', finding.message || '', finding.params || {})
		},
	},
}
</script>

<style scoped>
.validation-findings {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.validation-findings__list {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.validation-findings__item {
	display: flex;
	gap: 8px;
	align-items: baseline;
	flex-wrap: wrap;
}

.validation-findings__check {
	font-weight: bold;
}

.validation-findings__ocr {
	color: var(--color-primary-element);
	text-decoration: underline;
}
</style>
