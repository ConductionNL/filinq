<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/document-comparison/specs/document-comparison/spec.md
-->

<template>
	<div class="comparison-view">
		<div class="comparison-view__header">
			<h2>{{ t('docudesk', 'Document comparison') }}</h2>
			<p class="comparison-view__subtitle">
				{{ t('docudesk', 'Compare two versions of a file or two distinct files side by side.') }}
			</p>
		</div>

		<NcNoteCard v-if="error" type="error">
			{{ error }}
		</NcNoteCard>

		<div class="comparison-view__pickers">
			<div class="comparison-view__field">
				<NcTextField :value.sync="leftFileId"
					:label="t('docudesk', 'Left file ID')"
					type="number" />
				<NcTextField :value.sync="leftVersion"
					:label="t('docudesk', 'Left version timestamp (optional)')"
					type="number" />
			</div>
			<div class="comparison-view__field">
				<NcTextField :value.sync="rightFileId"
					:label="t('docudesk', 'Right file ID')"
					type="number" />
				<NcTextField :value.sync="rightVersion"
					:label="t('docudesk', 'Right version timestamp (optional)')"
					type="number" />
			</div>
			<NcButton type="primary" :disabled="loading || !canCompare" @click="runComparison">
				{{ t('docudesk', 'Compare') }}
			</NcButton>
		</div>

		<NcLoadingIcon v-if="loading" :size="32" />

		<NcNoteCard v-if="result && result.crossFormat" type="warning">
			{{ t('docudesk', 'The two subjects have different formats; layout-derived differences may appear as noise.') }}
		</NcNoteCard>

		<NcNoteCard v-if="result && result.redactionAnnotation === 'unavailable'" type="info">
			{{ t('docudesk', 'Redaction annotation is unavailable (OpenRegister not reachable).') }}
		</NcNoteCard>

		<div v-if="unredactedEntities.length > 0" class="comparison-view__advisory">
			<h3>{{ t('docudesk', 'Verify manually — entities with no detected change') }}</h3>
			<ul>
				<li v-for="entity in unredactedEntities" :key="entity.entityId">
					{{ entity.entityName }}
				</li>
			</ul>
		</div>

		<div v-if="result" ref="diffPanes" class="comparison-view__diff">
			<div class="comparison-view__pane" @scroll="syncScroll('left', $event)">
				<template v-for="(hunk, index) in result.hunks">
					<span v-if="hunk.leftText !== undefined"
						:key="'l' + index"
						:class="hunkClass(hunk, 'left')">
						{{ hunk.leftText }}<span v-if="hunk.redaction" class="comparison-view__badge">{{ hunk.redaction.entityType }}</span>
					</span>
				</template>
			</div>
			<div class="comparison-view__pane" @scroll="syncScroll('right', $event)">
				<template v-for="(hunk, index) in result.hunks">
					<span v-if="hunk.rightText !== undefined"
						:key="'r' + index"
						:class="hunkClass(hunk, 'right')">
						{{ hunk.rightText }}<span v-if="hunk.redaction" class="comparison-view__badge">{{ hunk.redaction.entityType }}</span>
					</span>
				</template>
			</div>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import { compareDocuments } from '../../services/comparisonService.js'

export default {
	name: 'ComparisonView',
	components: {
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
	},
	props: {
		// Optional preselected subjects (e.g. original-vs-anonymised shortcut).
		initialLeftFileId: { type: [String, Number], default: '' },
		initialRightFileId: { type: [String, Number], default: '' },
	},
	data() {
		return {
			leftFileId: String(this.initialLeftFileId || ''),
			rightFileId: String(this.initialRightFileId || ''),
			leftVersion: '',
			rightVersion: '',
			loading: false,
			error: '',
			result: null,
			syncing: false,
		}
	},
	computed: {
		canCompare() {
			return this.leftFileId !== '' && this.rightFileId !== ''
		},
		unredactedEntities() {
			return (this.result && this.result.unredactedEntities) || []
		},
	},
	mounted() {
		if (this.canCompare) {
			this.runComparison()
		}
	},
	methods: {
		buildSubject(fileId, version) {
			const subject = { fileId: Number(fileId) }
			if (version !== '' && version !== null && version !== undefined) {
				subject.versionTimestamp = Number(version)
			}
			return subject
		},
		async runComparison() {
			this.loading = true
			this.error = ''
			this.result = null
			try {
				this.result = await compareDocuments(
					this.buildSubject(this.leftFileId, this.leftVersion),
					this.buildSubject(this.rightFileId, this.rightVersion),
				)
			} catch (e) {
				const reason = e.response && e.response.data && e.response.data.error
				this.error = reason || t('docudesk', 'Comparison failed')
			} finally {
				this.loading = false
			}
		},
		hunkClass(hunk, side) {
			const base = 'comparison-view__hunk'
			if (hunk.type === 'unchanged') {
				return base
			}
			if (hunk.type === 'changed') {
				return `${base} ${base}--changed`
			}
			if (hunk.type === 'added' && side === 'right') {
				return `${base} ${base}--added`
			}
			if (hunk.type === 'removed' && side === 'left') {
				return `${base} ${base}--removed`
			}
			return base
		},
		syncScroll(source, event) {
			if (this.syncing) {
				return
			}
			this.syncing = true
			const panes = this.$refs.diffPanes
			if (panes) {
				const other = source === 'left' ? panes.children[1] : panes.children[0]
				if (other) {
					other.scrollTop = event.target.scrollTop
				}
			}
			this.$nextTick(() => { this.syncing = false })
		},
	},
}
</script>

<style scoped>
.comparison-view {
	padding: 16px;
}
.comparison-view__pickers {
	display: flex;
	gap: 16px;
	align-items: flex-end;
	flex-wrap: wrap;
	margin-bottom: 16px;
}
.comparison-view__field {
	display: flex;
	flex-direction: column;
	gap: 8px;
}
.comparison-view__diff {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 8px;
}
.comparison-view__pane {
	max-height: 60vh;
	overflow-y: auto;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: 8px;
	white-space: pre-wrap;
	word-break: break-word;
}
.comparison-view__hunk--added {
	background-color: var(--color-success-hover, #e6ffed);
}
.comparison-view__hunk--removed {
	background-color: var(--color-error-hover, #ffeef0);
}
.comparison-view__hunk--changed {
	background-color: var(--color-warning-hover, #fff5b1);
}
.comparison-view__badge {
	display: inline-block;
	font-size: 0.7em;
	background: var(--color-primary-element);
	color: var(--color-primary-element-text);
	border-radius: var(--border-radius);
	padding: 0 4px;
	margin-left: 2px;
}
.comparison-view__advisory {
	border: 1px solid var(--color-warning, #c8a500);
	border-radius: var(--border-radius);
	padding: 8px;
	margin-bottom: 16px;
}
</style>
