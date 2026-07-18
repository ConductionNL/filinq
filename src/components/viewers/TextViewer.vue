<template>
	<div class="text-viewer" :class="{ 'dd-marking-cursor': isAddMode }" @mouseup="captureSelection">
		<div v-if="loading" class="text-viewer__loading">
			<NcLoadingIcon :size="48" />
			<span>{{ t('docudesk', 'Loading document…') }}</span>
		</div>
		<div v-else-if="error" class="text-viewer__error">
			{{ error }}
		</div>
		<pre v-else class="text-viewer__content"><span
		v-for="(seg, idx) in segments"
		:key="idx"
		:class="segClass(seg)"
		:style="segStyle(seg)">{{ seg.text }}</span></pre>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { fetchFileAsText } from '../../services/fileViewerService.js'
import { fileViewerStore } from '../../store/store.js'
import { buildHighlightSegments, PENDING_TYPE } from '../../services/highlightText.js'
import { entityTypeColor } from '../../services/entityTypes.js'

export default {
	name: 'TextViewer',
	components: {
		NcLoadingIcon,
	},
	props: {
		path: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			loading: true,
			error: null,
			content: '',
		}
	},
	computed: {
		/**
		 * The document text split into highlight segments. Combines the
		 * entities the sidebar asked to mark with the pending selection
		 * (add mode only), so the user sees both detected values and the
		 * text they are about to add.
		 *
		 * @return {Array<{text: string, type: (string|null)}>}
		 */
		segments() {
			const entities = fileViewerStore.highlightEntities || []
			const pending = fileViewerStore.addMode && fileViewerStore.selection
				? [{ value: fileViewerStore.selection, type: PENDING_TYPE }]
				: []
			return buildHighlightSegments(this.content, [...pending, ...entities])
		},
		/**
		 * Whether the viewer is in add mode — drives the marking (highlighter)
		 * cursor so it is obvious the user can select text to add an entity.
		 *
		 * @return {boolean}
		 */
		isAddMode() {
			return fileViewerStore.addMode
		},
	},
	watch: {
		path: {
			immediate: true,
			handler() {
				this.load()
			},
		},
	},
	methods: {
		/**
		 * Class for a highlight segment: plain text gets none, detected
		 * entities get `dd-hl`, the pending selection gets the pending variant.
		 *
		 * @param {{text: string, type: (string|null)}} seg Segment.
		 * @return {(string|null)}
		 */
		segClass(seg) {
			if (!seg.type) {
				return null
			}
			return seg.type === PENDING_TYPE ? 'dd-hl dd-hl--pending' : 'dd-hl'
		},
		/**
		 * Inline style for a highlight segment — the per-type background colour
		 * for detected entities; nothing for plain text or the pending span
		 * (which is styled by its class).
		 *
		 * @param {{text: string, type: (string|null)}} seg Segment.
		 * @return {(object|null)}
		 */
		segStyle(seg) {
			if (!seg.type || seg.type === PENDING_TYPE) {
				return null
			}
			return { backgroundColor: entityTypeColor(seg.type) }
		},
		/**
		 * Fetch the file as plain text.
		 *
		 * @return {Promise<void>}
		 */
		async load() {
			this.loading = true
			this.error = null
			try {
				this.content = await fetchFileAsText(this.path)
			} catch (err) {
				console.error('[TextViewer] failed to load text:', err)
				this.error = err.message || t('docudesk', 'Failed to load document')
			} finally {
				this.loading = false
			}
		},
		/**
		 * Push the current text selection into the viewer store so future
		 * features (e.g. "send selection to anonymisation") can pick it up.
		 */
		captureSelection() {
			const text = window.getSelection()?.toString() || ''
			fileViewerStore.setSelection(text)
		},
	},
}
</script>

<style scoped>
.text-viewer {
	display: flex;
	justify-content: center;
	padding: 16px;
	background: var(--color-background-dark);
	min-height: 100%;
}

.text-viewer__loading,
.text-viewer__error {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
	padding: 32px;
	color: var(--color-text-maxcontrast);
}

.text-viewer__content {
	background: white;
	color: black;
	max-width: 1000px;
	width: 100%;
	padding: 32px;
	box-shadow: var(--dd-shadow-doc);
	border-radius: var(--border-radius);
	font-family: var(--font-face-monospace, monospace);
	font-size: 13px;
	line-height: 1.5;
	white-space: pre-wrap;
	word-break: break-word;
	margin: 0;
}
</style>
