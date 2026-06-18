<template>
	<div class="file-viewer-header">
		<div v-if="$slots.icon" class="file-viewer-header__icon">
			<slot name="icon" />
		</div>
		<div class="file-viewer-header__text">
			<h1 class="file-viewer-header__title">
				{{ title }}
			</h1>
			<p v-if="description" class="file-viewer-header__description">
				{{ description }}
			</p>
		</div>
		<div v-if="$slots.actions" class="file-viewer-header__actions">
			<slot name="actions" />
		</div>
	</div>
</template>

<script>
/**
 * Header dedicated to the in-app file viewer.
 *
 * Visually distinct from the generic `DdPageHeader`: it sits on a solid
 * white background so the viewer chrome reads as a document toolbar rather
 * than a page-level heading. Exposes the same `icon` and `actions` slots so
 * the viewer can place a file-type icon and its Back / toggle controls.
 *
 * Usage:
 *   <FileViewerHeader :title="fileName">
 *     <template #icon><FilePdfBox :size="28" /></template>
 *     <template #actions><NcButton ... /></template>
 *   </FileViewerHeader>
 */
export default {
	name: 'FileViewerHeader',
	props: {
		/** File name / header title text. */
		title: {
			type: String,
			required: true,
		},
		/** Optional sub-line shown below the title. */
		description: {
			type: String,
			default: '',
		},
	},
}
</script>

<style scoped>
.file-viewer-header {
	display: flex;
	align-items: center;
	gap: var(--dd-file-viewer-header-gap, 16px);
	padding-block: var(--dd-file-viewer-header-padding-block, 16px);
	padding-inline: var(--dd-file-viewer-header-padding-inline, 20px);
	background: var(--dd-file-viewer-header-background, #fff);
	position: sticky;
	top: 0;
	z-index: 1;
	border-bottom: 1px solid var(--dd-file-viewer-header-border-color, var(--color-border));
}

.file-viewer-header__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	color: var(--color-main-text);
	flex-shrink: 0;
}

.file-viewer-header__text {
	display: flex;
	flex-direction: column;
	gap: 2px;
	min-width: 0;
	flex: 1;
}

.file-viewer-header__title {
	font-size: 1.25rem;
	font-weight: 600;
	line-height: 1.2;
	margin: 0;
	color: var(--color-main-text);
	word-break: break-word;
}

.file-viewer-header__description {
	margin: 0;
	color: var(--color-text-maxcontrast);
	font-size: 0.9rem;
}

.file-viewer-header__actions {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-left: auto;
}
</style>
