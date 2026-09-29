<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/specs/document-validation-checks/spec.md
-->

<template>
	<div class="validation-findings">
		<div class="validation-findings__header">
			<CnStatusBadge :label="verdictLabel" :colorMap="colorMap" />
		</div>

		<section
			v-for="group in groups"
			:key="group.category"
			class="validation-findings__group"
			:data-testid="'findings-' + group.category">
			<h3 v-if="groups.length > 1" class="validation-findings__group-title">
				{{ group.title }}
			</h3>
			<ul class="validation-findings__list">
				<li
					v-for="(finding, index) in group.findings"
					:key="index"
					class="validation-findings__item">
					<span class="validation-findings__check">{{
						checkLabel(finding)
					}}</span>
					<span class="validation-findings__message">{{
						findingMessage(finding)
					}}</span>
					<span
						v-if="adviceFor(finding)"
						class="validation-findings__advice"
						>{{ adviceFor(finding) }}</span
					>
					<a
						v-if="finding.suggestedAction === 'ocr'"
						class="validation-findings__ocr"
						href="#/anonymization"
						@click="$emit('ocr', finding)">
						{{ t('filinq', 'Run OCR') }}
					</a>
				</li>
			</ul>
		</section>
		<p v-if="findings.length === 0" class="validation-findings__empty">
			{{ t('filinq', 'No validation findings.') }}
		</p>
	</div>
</template>

<script>
import { CnStatusBadge } from '@conduction/nextcloud-vue'
import { guidanceText } from '../services/conformance.js'
import { groupFindings, verdictColor } from '../services/validationService.js'

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
					return t('filinq', 'Validation passed')
				case 'warnings':
					return t('filinq', 'Validation warnings')
				case 'failed':
					return t('filinq', 'Validation failed')
				default:
					return t('filinq', 'Not yet validated')
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

		/**
		 * Findings grouped by category: document checks first, then
		 * accessibility, then the archival (PDF/A) checks veraPDF answers.
		 *
		 * @return {Array<{category: string, title: string, findings: Array}>} The groups.
		 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
		 */
		groups() {
			return groupFindings(this.findings)
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
				'format-not-allowed': t('filinq', 'Format not allowed'),
				'extension-mime-mismatch': t('filinq', 'Extension/type mismatch'),
				'file-unreadable': t('filinq', 'File unreadable'),
				'pdf-encrypted': t('filinq', 'Encrypted PDF'),
				'text-layer-missing': t('filinq', 'Missing text layer'),
				'metadata-incomplete': t('filinq', 'Incomplete metadata'),
				'pdfa-conformance-failed': t('filinq', 'Not PDF/A'),
				'pdfa-font-not-embedded': t('filinq', 'Fonts not embedded'),
				'archival-validator-unavailable': t(
					'filinq',
					'Not checked against PDF/A',
				),
				'pdf-not-tagged': t('filinq', 'No tags'),
				'pdf-language-missing': t('filinq', 'No language'),
				'pdf-title-missing': t('filinq', 'No title'),
				'pdfua-identifier-missing': t('filinq', 'Not marked as PDF/UA'),
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
			return t('filinq', finding.message || '', finding.params || {})
		},

		/**
		 * The advice an archival finding carries.
		 *
		 * @param {object} finding A validation finding.
		 * @return {string} The advice, '' for none.
		 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
		 */
		adviceFor(finding) {
			return guidanceText(finding.guidance || '')
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

.validation-findings__group-title {
	font-size: 1em;
	font-weight: bold;
	margin: 4px 0;
}

.validation-findings__advice {
	flex-basis: 100%;
	color: var(--color-text-maxcontrast);
}

.validation-findings__ocr {
	color: var(--color-primary-element);
	text-decoration: underline;
}
</style>
