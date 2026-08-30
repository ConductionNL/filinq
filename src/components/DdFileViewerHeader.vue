<template>
	<div class="dd-file-viewer-header">
		<div v-if="$slots.icon" class="dd-file-viewer-header__icon">
			<slot name="icon" />
		</div>
		<div class="dd-file-viewer-header__text">
			<h1 class="dd-file-viewer-header__title">
				{{ title }}
			</h1>
			<p v-if="description" class="dd-file-viewer-header__description">
				{{ description }}
			</p>
		</div>
		<div v-if="$slots.actions" class="dd-file-viewer-header__actions">
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
 *   <DdFileViewerHeader :title="fileName">
 *     <template #icon><FilePdfBox :size="28" /></template>
 *     <template #actions><NcButton ... /></template>
 *   </DdFileViewerHeader>
 */
export default {
	name: 'DdFileViewerHeader',
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
.dd-file-viewer-header {
	display: flex;
	align-items: center;
	gap: var(--dd-dd-file-viewer-header-gap, 16px);
	padding-block: var(--dd-dd-file-viewer-header-padding-block, 16px);
	padding-inline: var(--dd-dd-file-viewer-header-padding-inline, 20px);
	background: var(--dd-dd-file-viewer-header-background, var(--dd-surface, #fff));
	position: sticky;
	top: 0;
	z-index: 1;
	border-bottom: 1px solid
		var(--dd-dd-file-viewer-header-border-color, var(--color-border));
}

.dd-file-viewer-header__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	color: var(--color-main-text);
	flex-shrink: 0;
}

.dd-file-viewer-header__text {
	display: flex;
	flex-direction: column;
	gap: 2px;
	min-width: 0;
	flex: 1;
}

.dd-file-viewer-header__title {
	font-size: 1.25rem;
	font-weight: 600;
	line-height: 1.2;
	margin: 0;
	color: var(--color-main-text);
	overflow-wrap: break-word;
}

.dd-file-viewer-header__description {
	margin: 0;
	color: var(--color-text-maxcontrast);
	font-size: 0.9rem;
}

.dd-file-viewer-header__actions {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-inline-start: auto;
}
</style>
