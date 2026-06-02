<template>
	<span
		class="dd-icon"
		:style="sizeStyle"
		v-bind="$attrs"
		v-on="$listeners">
		<span v-if="svg" class="dd-icon__svg" v-html="svg" /><!-- eslint-disable-line vue/no-v-html -->
		<slot v-else />
	</span>
</template>

<script>
/**
 * Generic icon component for DocuDesk.
 *
 * Resolves icons by name from the project's custom SVG set in
 * `src/assets/icons/` and inlines them so they inherit color via
 * `currentColor`. When the requested name is not in the registry,
 * the default slot is rendered as a fallback (use this slot for
 * MDI icons that have not been migrated yet).
 *
 * Webpack rule (see webpack.config.js) loads those SVGs as raw
 * source strings (`asset/source`); all other SVGs continue to load
 * as data URIs.
 *
 * Usage:
 *   <DdIcon name="search" :size="20" />
 *   <DdIcon name="not-yet-imported"><Magnify :size="20" /></DdIcon>
 */
const iconContext = require.context('../assets/icons', false, /\.svg$/)
const ICONS = iconContext.keys().reduce((acc, key) => {
	const name = key
		.replace(/^\.\//, '')
		.replace(/\.svg$/i, '')
		.toLowerCase()
	const mod = iconContext(key)
	const raw = typeof mod === 'string' ? mod : mod.default
	acc[name] = prepareSvg(raw)
	return acc
}, {})

/**
 * Strip width/height from the root <svg> tag so the wrapper controls the
 * box, and replace the designer's hardcoded dark fill with `currentColor`
 * so consumers can recolor with standard CSS `color`.
 *
 * @param {string} raw The raw SVG source.
 * @return {string} The cleaned-up SVG source.
 */
function prepareSvg(raw) {
	if (!raw) return ''
	return raw
		.replace(/<svg([^>]*)>/i, (match, attrs) => {
			const stripped = attrs
				.replace(/\s(width|height)="[^"]*"/gi, '')
			return `<svg${stripped} width="100%" height="100%">`
		})
		.replace(/fill="#02162E"/gi, 'fill="currentColor"')
}

export default {
	name: 'DdIcon',
	inheritAttrs: false,
	props: {
		/**
		 * Icon name — matches the SVG filename (without extension)
		 * in `src/assets/icons/`, lowercased. Unknown names fall
		 * back to the default slot.
		 */
		name: {
			type: String,
			default: '',
		},
		/**
		 * Square size in pixels. Defaults to 24 (24x24).
		 */
		size: {
			type: [Number, String],
			default: 24,
		},
	},
	computed: {
		svg() {
			return ICONS[(this.name || '').toLowerCase()] || ''
		},
		sizeStyle() {
			const px = typeof this.size === 'number' ? `${this.size}px` : this.size
			return { width: px, height: px }
		},
	},
}
</script>

<style scoped>
.dd-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	color: currentColor;
	line-height: 0;
}

.dd-icon__svg {
	display: inline-flex;
	width: 100%;
	height: 100%;
}

.dd-icon__svg :deep(svg) {
	width: 100%;
	height: 100%;
	display: block;
}
</style>
