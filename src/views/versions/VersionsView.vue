<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/specs/document-versions/spec.md
-->

<template>
	<div class="versions-view">
		<div class="versions-view__header">
			<h2>{{ t('filinq', 'Versions') }}</h2>
			<p class="versions-view__subtitle">
				{{
					t(
						'filinq',
						'File versions of this document, read from Nextcloud. Open, download, restore, or compare a version.',
					)
				}}
			</p>
		</div>

		<NcNoteCard
			v-if="finalState.final"
			type="warning"
			data-testid="document-final">
			{{ finalSentence }}
		</NcNoteCard>

		<NcNoteCard
			v-if="unfrozenSentence"
			type="error"
			data-testid="document-unfrozen">
			{{ unfrozenSentence }}
		</NcNoteCard>

		<NcNoteCard
			v-if="checksumWarning"
			type="error"
			data-testid="document-checksum-mismatch">
			{{ checksumWarning }}
		</NcNoteCard>

		<div class="versions-view__final-actions">
			<NcButton
				v-if="fileId > 0 && !finalState.final"
				variant="secondary"
				data-testid="document-finalise"
				@click="prompt = 'finalise'">
				{{ t('filinq', 'Make final') }}
			</NcButton>
			<NcButton
				v-if="finalState.final"
				variant="primary"
				data-testid="document-correct"
				@click="prompt = 'correct'">
				{{ t('filinq', 'Issue a correction') }}
			</NcButton>
			<NcButton
				v-if="finalState.final && finalState.mayUnfreeze"
				variant="tertiary"
				data-testid="document-unfreeze"
				@click="prompt = 'unfreeze'">
				{{ t('filinq', 'Unfreeze') }}
			</NcButton>
		</div>

		<NcNoteCard
			v-if="unavailable"
			type="info"
			data-testid="versions-unavailable">
			{{ t('filinq', 'File versions are not available on this instance') }}
		</NcNoteCard>

		<NcNoteCard v-else-if="error" type="error">
			{{ error }}
		</NcNoteCard>

		<NcLoadingIcon v-if="loading" :size="32" />

		<CnDataTable
			v-else-if="!unavailable"
			:columns="columns"
			:rows="rows"
			:tableLabel="t('filinq', 'Versions')"
			data-testid="versions-table">
			<template #actions="{ row }">
				<NcButton variant="tertiary" @click="download(row)">
					{{ t('filinq', 'Download') }}
				</NcButton>
				<NcButton
					v-if="!row.isCurrent && !finalState.final"
					variant="tertiary"
					data-testid="version-restore"
					@click="promptRestore(row)">
					{{ t('filinq', 'Restore') }}
				</NcButton>
				<NcButton
					v-if="canCompare(row)"
					variant="tertiary"
					data-testid="version-compare"
					@click="compare(row)">
					{{ t('filinq', 'Compare with current') }}
				</NcButton>
			</template>
		</CnDataTable>

		<CnConfirmDialog
			v-if="restoreTarget"
			:dialogTitle="t('filinq', 'Restore version')"
			:message="restoreMessage"
			:confirmLabel="t('filinq', 'Restore')"
			@confirm="confirmRestore"
			@close="restoreTarget = null" />

		<FinalDocumentReasonDialog
			v-if="prompt"
			:name="promptCopy.name"
			:explanation="promptCopy.explanation"
			:placeholder="promptCopy.placeholder"
			:confirmLabel="promptCopy.confirmLabel"
			@confirm="submitPrompt"
			@cancel="prompt = ''" />
	</div>
</template>

<script>
import { CnConfirmDialog, CnDataTable } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import FinalDocumentReasonDialog from '../../dialogs/FinalDocumentReasonDialog.vue'
import {
	correctDocument,
	finaliseDocument,
	readFinalState,
	unfreezeDocument,
} from '../../services/finalDocumentService.js'
import {
	listVersions,
	restoreVersion,
	versionDownloadUrl,
} from '../../services/versionService.js'

/**
 * The reason dialog's copy when it issues a correction.
 *
 * @return {object} The dialog's title, explanation, placeholder and button label.
 */
function correctionCopy() {
	return {
		name: t('filinq', 'Issue a correction'),
		explanation: t(
			'filinq',
			'The final version stays readable and stays final. The correction is a new document that says which version it supersedes.',
		),

		placeholder: t('filinq', 'What does this correction change?'),
		confirmLabel: t('filinq', 'Issue a correction'),
	}
}

/**
 * The reason dialog's copy when it unfreezes the document.
 *
 * @return {object} The dialog's title, explanation, placeholder and button label.
 */
function unfreezeCopy() {
	return {
		name: t('filinq', 'Unfreeze this document'),
		explanation: t(
			'filinq',
			'The document becomes editable again, and it carries a permanent mark that it was unfrozen. Your name, the moment and this reason stay on the record.',
		),

		placeholder: t(
			'filinq',
			'Why are you unfreezing this document?',
		),

		confirmLabel: t('filinq', 'Unfreeze'),
	}
}

/**
 * The reason dialog's copy when it makes the document final, the default.
 *
 * @return {object} The dialog's title, explanation, placeholder and button label.
 */
function finaliseCopy() {
	return {
		name: t('filinq', 'Make this document final'),
		explanation: t(
			'filinq',
			'A final document refuses every edit, and there is no way back. Correct it later by issuing a correction.',
		),

		placeholder: t('filinq', 'What makes this document final?'),
		confirmLabel: t('filinq', 'Make final'),
	}
}

/**
 * The reason dialog's copy per dialog; any other dialog shows the finalise copy.
 */
const PROMPT_COPY = new Map([
	['correct', correctionCopy],
	['unfreeze', unfreezeCopy],
])

/**
 * Make the panel's document final.
 *
 * @param {object} vm The versions panel.
 * @param {string} reason Why, as the user wrote it.
 * @return {Promise<void>}
 */
async function finaliseFrom(vm, reason) {
	await finaliseDocument(vm.fileId, reason)
}

/**
 * Issue a correction of the panel's document and announce it.
 *
 * @param {object} vm The versions panel.
 * @param {string} reason What the correction changes, as the user wrote it.
 * @return {Promise<void>}
 */
async function correctFrom(vm, reason) {
	const correction = await correctDocument(vm.fileId, reason)
	vm.$emit('corrected', correction)
}

/**
 * Unfreeze the panel's document.
 *
 * @param {object} vm The versions panel.
 * @param {string} reason Why, as the user wrote it.
 * @return {Promise<void>}
 */
async function unfreezeFrom(vm, reason) {
	await unfreezeDocument(vm.fileId, reason)
}

/**
 * What submitting each reason dialog carries out on the panel's document.
 */
const PROMPT_ACTIONS = new Map([
	['finalise', finaliseFrom],
	['correct', correctFrom],
	['unfreeze', unfreezeFrom],
])

export default {
	name: 'VersionsView',
	components: {
		CnDataTable,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		CnConfirmDialog,
		FinalDocumentReasonDialog,
	},

	props: {
		// Optional preselected document (else read from ?fileId=).
		initialFileId: { type: [String, Number], default: '' },
		// Whether the document's versions are text-extractable (enables compare).
		textExtractable: { type: Boolean, default: true },
	},

	emits: ['corrected'],

	data() {
		return {
			fileId: Number(
				this.initialFileId || (this.$route && this.$route.query.fileId) || 0,
			),

			versions: [],
			loading: false,
			error: '',
			unavailable: false,
			restoreTarget: null,

			finalState: {
				final: false,
				version: null,
				checksum: null,
				mayUnfreeze: false,
			},

			prompt: '',
		}
	},

	computed: {
		/**
		 * The restore question; a document version is named by its timestamp.
		 *
		 * @return {string}
		 * @spec openspec/specs/document-versions/spec.md
		 */
		restoreMessage() {
			return t(
				'filinq',
				'Restore to version {n}? The current state will be saved as a new version first.',
				{ n: this.restoreTarget?.timestamp },
			)
		},

		/**
		 * Table columns for the version list.
		 *
		 * @return {Array} The column definitions.
		 * @spec openspec/specs/document-versions/spec.md
		 */
		columns() {
			return [
				{ key: 'when', label: t('filinq', 'When') },
				{ key: 'author', label: t('filinq', 'Author') },
				{ key: 'size', label: t('filinq', 'Size') },
				{ key: 'current', label: t('filinq', 'Current') },
			]
		},

		/**
		 * Rows for the CnDataTable, newest first, current marked.
		 *
		 * @return {Array} The rows.
		 * @spec openspec/specs/document-versions/spec.md
		 */
		rows() {
			return this.versions.map((v) => ({
				...v,
				when: this.formatTimestamp(v.timestamp),
				current: v.isCurrent ? t('filinq', 'Current version') : '',
				size: this.formatBytes(v.size),
			}))
		},

		/**
		 * The sentence shown on a final document, naming who made it final and when.
		 *
		 * @return {string} The sentence, or an empty string when the document is not final.
		 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
		 */
		finalSentence() {
			const version = this.finalState.version
			if (!this.finalState.final || !version) {
				return ''
			}

			const who =
				version.finalisedByName
				|| version.finalisedBy
				|| t('filinq', 'someone whose name was not recorded')
			const when = this.formatMoment(version.finalisedAt)
			const opening = t(
				'filinq',
				'This document is final. {who} made it final on {when}.',
				{ who, when },
			)

			if (version.finalReason) {
				return `${opening} ${t('filinq', 'Reason: {reason}', { reason: version.finalReason })}`
			}

			return opening
		},

		/**
		 * The mark left by an unfreeze, which stays on the document for good.
		 *
		 * @return {string} The sentence, or an empty string when it was never unfrozen.
		 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
		 */
		unfrozenSentence() {
			const version = this.finalState.version
			if (!version || !version.unfrozen) {
				return ''
			}

			return t(
				'filinq',
				'This document was unfrozen by {who} on {when}. Reason: {reason}',
				{
					who: version.unfrozenBy || t('filinq', 'an administrator'),
					when: this.formatMoment(version.unfrozenAt),
					reason: version.unfrozenReason || t('filinq', 'none recorded'),
				},
			)
		},

		/**
		 * The warning shown when a final document's file changed on the storage.
		 *
		 * @return {string} The warning, or an empty string when the bytes still match.
		 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
		 */
		checksumWarning() {
			const checksum = this.finalState.checksum
			if (!checksum || checksum.matches !== false) {
				return ''
			}

			return (
				checksum.message
				|| t(
					'filinq',
					'The file behind this final version has changed on the storage.',
				)
			)
		},

		/**
		 * The copy for whichever reason dialog is open.
		 *
		 * @return {object} The dialog's title, explanation, placeholder and button label.
		 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
		 */
		promptCopy() {
			return (PROMPT_COPY.get(this.prompt) ?? finaliseCopy)()
		},
	},

	mounted() {
		if (this.fileId > 0) {
			this.load()
		}
	},

	methods: {
		t,
		/**
		 * Load the version list for the current document.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/document-versions/spec.md
		 */
		async load() {
			this.loading = true
			this.error = ''
			this.unavailable = false
			try {
				const data = await listVersions(this.fileId)
				this.versions = data.versions || []
				await this.loadFinalState()
			} catch (e) {
				this.showLoadFailure(e)
			} finally {
				this.loading = false
			}
		},

		/**
		 * Show why the version list could not be loaded: unavailable on this
		 * instance, the server's message, or a generic one.
		 *
		 * @param {Error|null} e What listing the versions threw.
		 * @spec openspec/specs/document-versions/spec.md
		 */
		showLoadFailure(e) {
			const data = e?.response?.data
			if (data?.reason === 'versions-unavailable') {
				this.unavailable = true
				return
			}
			this.error = data?.error || t('filinq', 'Could not list versions')
		},

		/**
		 * Whether the compare action is offered for a row (text-extractable only).
		 *
		 * @param {object} row The version row.
		 * @return {boolean} True when compare is available.
		 * @spec openspec/specs/document-versions/spec.md
		 */
		canCompare(row) {
			return this.textExtractable && !row.isCurrent
		},

		/**
		 * Open/download a version's bytes.
		 *
		 * @param {object} row The version row.
		 * @return {void}
		 * @spec openspec/specs/document-versions/spec.md
		 */
		download(row) {
			const ts = row.isCurrent ? 0 : row.timestamp
			window.open(versionDownloadUrl(this.fileId, ts), '_blank')
		},

		/**
		 * Prompt to restore a prior version.
		 *
		 * @param {object} row The version row.
		 * @return {void}
		 * @spec openspec/specs/document-versions/spec.md
		 */
		promptRestore(row) {
			this.restoreTarget = row
		},

		/**
		 * Confirm and perform the restore, then reload.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/document-versions/spec.md
		 */
		async confirmRestore() {
			const target = this.restoreTarget
			this.restoreTarget = null
			if (!target) return
			try {
				await restoreVersion(this.fileId, target.timestamp)
				await this.load()
			} catch (e) {
				this.error =
					(e && e.response && e.response.data && e.response.data.error)
					|| t('filinq', 'Could not restore version')
			}
		},

		/**
		 * Hand the version to the existing comparison flow (fileId + timestamp).
		 *
		 * @param {object} row The version row.
		 * @return {void}
		 * @spec openspec/specs/document-versions/spec.md
		 */
		compare(row) {
			this.$router.push({
				name: 'Comparison',
				query: {
					left: String(this.fileId),
					leftVersion: String(row.timestamp),
					right: String(this.fileId),
				},
			})
		},

		/**
		 * Read the final state of the current document.
		 *
		 * A document that was never finalised has no record, which is not an
		 * error: the panel simply offers to make it final.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
		 */
		async loadFinalState() {
			try {
				this.finalState = await readFinalState(this.fileId)
			} catch {
				// A document nobody ever finalised has no record, and that is
				// not an error: the panel simply offers to make it final.
				this.finalState = {
					final: false,
					version: null,
					checksum: null,
					mayUnfreeze: false,
				}
			}
		},

		/**
		 * Carry out whichever reason dialog was confirmed.
		 *
		 * @param {string} reason The reason the user typed.
		 * @return {Promise<void>}
		 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
		 */
		async submitPrompt(reason) {
			const action = this.prompt
			this.prompt = ''
			this.error = ''

			try {
				await PROMPT_ACTIONS.get(action)?.(this, reason)
				await this.load()
			} catch (e) {
				this.error = e?.response?.data?.error || t('filinq', 'Could not carry that out')
			}
		},

		/**
		 * Format a stored ISO 8601 moment for display.
		 *
		 * @param {string} value The stored moment.
		 * @return {string} The formatted moment, or an empty string.
		 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
		 */
		formatMoment(value) {
			if (!value) {
				return t('filinq', 'a moment that was not recorded')
			}

			const parsed = new Date(value)
			if (Number.isNaN(parsed.getTime())) {
				return value
			}

			return parsed.toLocaleString()
		},

		/**
		 * Format a UNIX timestamp for display.
		 *
		 * @param {number} ts The timestamp (seconds).
		 * @return {string} The formatted date.
		 */
		formatTimestamp(ts) {
			if (!ts) return ''
			return new Date(Number(ts) * 1000).toLocaleString()
		},

		/**
		 * Human-readable byte size.
		 *
		 * @param {number} bytes The size in bytes.
		 * @return {string} The formatted size.
		 */
		formatBytes(bytes) {
			const n = Number(bytes || 0)
			if (n < 1024) return `${n} B`
			if (n < 1048576) return `${(n / 1024).toFixed(1)} KB`
			return `${(n / 1048576).toFixed(1)} MB`
		},
	},
}
</script>

<style scoped>
.versions-view {
	padding: var(--default-grid-baseline, 16px);
}

.versions-view__subtitle {
	color: var(--color-text-maxcontrast);
}

.versions-view__final-actions {
	display: flex;
	flex-wrap: wrap;
	gap: var(--default-grid-baseline, 8px);
	margin-block: var(--default-grid-baseline, 8px);
}
</style>
