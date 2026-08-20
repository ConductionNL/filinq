<template>
	<div
		class="dd-skeleton"
		:class="['dd-skeleton--' + variant]"
		:style="rootStyle"
		role="presentation"
		aria-hidden="true">
		<span
			v-for="i in barCount"
			:key="i"
			class="dd-skeleton__bar"
			:style="barStyle(i)" />
	</div>
</template>

<script>
/**
 * Animated skeleton loader for placeholder UI while data fetches.
 *
 * Three variants:
 *  - 'text'   — one or more text-line bars (default). Use `rows` for paragraphs.
 *  - 'row'    — single block, e.g. for list-item placeholders. Set `height` to
 *               match the real item.
 *  - 'circle' — circular placeholder, e.g. avatars or icons. `width` becomes
 *               the diameter.
 *
 * Shimmer uses NC colour tokens so it adapts to light/dark themes.
 */
export default {
	name: 'DdSkeleton',
	props: {
		/**
		 * Shape variant.
		 *
		 * @type {string} One of 'text', 'row', 'circle'.
		 */
		variant: {
			type: String,
			default: 'text',
			validator: (v) => ['text', 'row', 'circle'].includes(v),
		},

		/**
		 * Width — number (px) or any CSS length string. Defaults to 100%.
		 */
		width: {
			type: [String, Number],
			default: '100%',
		},

		/**
		 * Height — number (px) or any CSS length string. Required for `row`,
		 * ignored for `circle` (uses width), optional for `text` (defaults to
		 * 1em per line).
		 */
		height: {
			type: [String, Number],
			default: null,
		},

		/**
		 * Number of lines for the `text` variant. Ignored for other variants.
		 * The last line is rendered narrower so the block reads as text.
		 */
		rows: {
			type: Number,
			default: 1,
		},
	},

	computed: {
		/**
		 * How many bars to render. `text` uses `rows`, others one block.
		 *
		 * @return {number}
		 */
		barCount() {
			return this.variant === 'text' ? Math.max(1, this.rows) : 1
		},

		/**
		 * Root-level inline style. Width comes from the prop; circle gets
		 * an explicit aspect-ratio via the CSS class.
		 *
		 * @return {object}
		 */
		rootStyle() {
			return { width: this.toCssLength(this.width) }
		},
	},

	methods: {
		/**
		 * Convert a number to a px string or pass through a CSS length.
		 *
		 * @param {string|number|null} value Length value.
		 * @return {string|null}
		 */
		toCssLength(value) {
			if (value === null || value === undefined) {
				return null
			}
			return typeof value === 'number' ? value + 'px' : value
		},

		/**
		 * Per-bar inline style. For multi-line text, the last bar is
		 * shortened to ~70% so the block reads as a paragraph.
		 *
		 * @param {number} i 1-based bar index.
		 * @return {object|null}
		 */
		barStyle(i) {
			const style = {}
			const h = this.toCssLength(this.height)
			if (h !== null) {
				style.height = h
			}
			if (this.variant === 'text' && this.rows > 1 && i === this.rows) {
				style.width = '70%'
			}
			return style
		},
	},
}
</script>

<style lang="scss" scoped>
.dd-skeleton {
	display: block;
	line-height: 0;
}

.dd-skeleton--text {
	display: block;
}

.dd-skeleton--row {
	display: block;
}

.dd-skeleton--circle {
	display: inline-block;
	/* aspect-ratio: square, width drives the size */
	aspect-ratio: 1 / 1;
}

.dd-skeleton__bar {
	display: block;
	width: 100%;
	height: 1em;
	margin: 4px 0;
	border-radius: var(--border-radius);
	/* Shimmer using NC tokens. Fall back to neutral greys in case the
	 * variables are missing in some themes. */
	background: linear-gradient(
		90deg,
		var(--color-background-dark, #ededed) 0%,
		var(--color-background-darker, #d7d7d7) 50%,
		var(--color-background-dark, #ededed) 100%
	);
	background-size: 200% 100%;
	animation: dd-skeleton-shimmer 1.4s ease-in-out infinite;
}

.dd-skeleton--row .dd-skeleton__bar {
	height: 100%;
	min-height: 24px;
	margin: 0;
}

.dd-skeleton--circle .dd-skeleton__bar {
	height: 100%;
	margin: 0;
	border-radius: 50%;
}

/* Respect users who disabled motion. */
@media (prefers-reduced-motion: reduce) {
	.dd-skeleton__bar {
		animation: none;
	}
}

@keyframes dd-skeleton-shimmer {
	0% {
		background-position: 200% 0;
	}
	100% {
		background-position: -200% 0;
	}
}
</style>
