<script setup>
import { translate as t } from '@nextcloud/l10n'
import { CnStatusBadge } from '@conduction/nextcloud-vue'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import dossierIcon from '../assets/dossier.png'
import singleFileIcon from '../assets/single-file.png'
</script>

<template>
	<article
		class="dd-document-card"
		:class="{ 'dd-document-card--selected': selected }"
		tabindex="0"
		role="button"
		:aria-label="ariaLabel"
		@click="$emit('click', item)"
		@keyup.enter="$emit('click', item)"
		@keyup.space.prevent="$emit('click', item)">
		<NcCheckboxRadioSwitch
			v-if="selectable"
			class="dd-document-card__select"
			:checked="selected"
			:aria-label="t('docudesk', 'Select')"
			@update:checked="$emit('toggle-select', item)"
			@click.native.stop />
		<figure class="dd-document-card__icon">
			<img
				:src="iconSrc"
				:alt="''"
				class="dd-document-card__icon-img">
		</figure>
		<div class="dd-document-card__title" :title="displayName">
			{{ displayName }}
		</div>
		<div class="dd-document-card__date">
			{{ formattedDate }}
		</div>
		<CnStatusBadge
			:label="pillLabel"
			:color-map="pillColorMap" />
	</article>
</template>

<script>
/**
 * Single document tile for DocuDesk index/dashboard surfaces.
 *
 * Layout (top → bottom): asset icon (dossier.png / single-file.png),
 * filename without extension, date, kind pill (CnStatusBadge).
 * Whole card is focusable + activatable (Enter / Space) for a11y and
 * emits `click` with the original item.
 */
export default {
	name: 'DdDocumentCard',
	props: {
		/**
		 * Item descriptor. Compatible with both the My Documents store
		 * shape and the recent-anonymized payload.
		 *   { fileName, isFolder, isAnonymized, modified, mimeType, … }
		 */
		item: {
			type: Object,
			required: true,
		},
		/** Show the bulk-selection checkbox in the top-left corner. */
		selectable: {
			type: Boolean,
			default: false,
		},
		/** Whether this card's item is currently selected. */
		selected: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['click', 'toggle-select'],
	data() {
		return {
			dossierIconSrc: dossierIcon,
			singleFileIconSrc: singleFileIcon,
			// Color tokens used by CnStatusBadge — keyed on the localised
			// label so swapping languages still picks the right colour.
			pillColorMap: {
				[t('docudesk', 'Dossier')]: 'info',
				[t('docudesk', 'Concept')]: 'warning',
				[t('docudesk', 'Anonymized')]: 'success',
			},
		}
	},
	computed: {
		/**
		 * Asset icon source — dossier.png for folders, single-file.png
		 * for everything else. Matches the design language of the
		 * upload widget hero icon.
		 *
		 * @return {string}
		 */
		iconSrc() {
			return this.item.isFolder ? this.dossierIconSrc : this.singleFileIconSrc
		},
		/**
		 * Filename with the trailing extension stripped, so the title
		 * stays scannable. Folders keep their full name.
		 *
		 * @return {string}
		 */
		displayName() {
			const name = this.item.fileName || ''
			return this.item.isFolder ? name : name.replace(/\.[^./]+$/, '')
		},
		/**
		 * Pill label for the kind of document. Mirrors the labels used
		 * by `MyDocumentsIndex` so the color map stays consistent.
		 *
		 * @return {string}
		 */
		pillLabel() {
			if (this.item.isFolder) return t('docudesk', 'Dossier')
			if (this.item.isAnonymized) return t('docudesk', 'Anonymized')
			return t('docudesk', 'Concept')
		},
		/**
		 * DD-MM-YYYY for the date row. Accepts unix seconds or ISO strings;
		 * returns '-' for missing values.
		 *
		 * @return {string}
		 */
		formattedDate() {
			const ts = this.item.modified
			if (!ts) return '-'
			const d = typeof ts === 'number' ? new Date(ts * 1000) : new Date(ts)
			if (Number.isNaN(d.getTime())) return '-'
			const dd = String(d.getDate()).padStart(2, '0')
			const mm = String(d.getMonth() + 1).padStart(2, '0')
			const yyyy = d.getFullYear()
			return `${dd}-${mm}-${yyyy}`
		},
		/**
		 * Accessible label combining kind + name for screen readers.
		 *
		 * @return {string}
		 */
		ariaLabel() {
			return `${this.pillLabel}: ${this.displayName}`
		},
	},
}
</script>

<style scoped>
/*
 * Token layer — local-only fallbacks for Nextcloud / Conduction CSS
 * variables. Caller can override these on a wrapping element to retheme
 * a single grid without touching the global stylesheet (NL Design
 * System token-cascade pattern).
 */
.dd-document-card {
	--dd-card-padding-block-start: 32px;
	--dd-card-padding-block-end: 16px;
	--dd-card-padding-inline: 16px;
	--dd-card-radius: var(--dd-radius-panel);
	--dd-card-gap: 16px;
	--dd-card-border: 1px solid var(--dd-border, #d9d9d9);
	--dd-card-bg: var(--dd-surface, #fff);
	--dd-card-shadow: var(--dd-shadow-panel);
	--dd-card-shadow-hover: 0 6px 26px -3px rgba(0, 0, 0, 0.12);
	--dd-card-focus-ring: 0 0 0 2px var(--color-primary-element, #0a5eaf);
	position: relative;

	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: var(--dd-card-gap);
	padding: var(--dd-card-padding-block-start) var(--dd-card-padding-inline) var(--dd-card-padding-block-end);
	border: var(--dd-card-border);
	border-radius: var(--dd-card-radius);
	background: var(--dd-card-bg);
	box-shadow: var(--dd-card-shadow);
	cursor: pointer;
	text-align: start;
	inline-size: 100%;
	block-size: 100%;
	transition: box-shadow 0.15s ease, transform 0.15s ease;

	> * {
		cursor: pointer;
	}
}

.dd-document-card:hover {
	box-shadow: var(--dd-card-shadow-hover);
}

.dd-document-card:focus {
	outline: none;
}

.dd-document-card:focus-visible {
	box-shadow: var(--dd-card-focus-ring);
}

.dd-document-card--selected {
	box-shadow: var(--dd-card-focus-ring);
}

.dd-document-card__select {
	position: absolute;
	inset-block-start: 8px;
	inset-inline-start: 8px;
	z-index: 1;
}

.dd-document-card__icon {
	display: block;
	inline-size: 162px;
	max-inline-size: 100%;
	aspect-ratio: 162 / 144;
	margin-inline: auto;
}

.dd-document-card__icon-img {
	display: block;
	inline-size: 100%;
	block-size: 100%;
	object-fit: cover;
	pointer-events: none;
}

.dd-document-card__title {
	font-size: 0.95rem;
	font-weight: 600;
	color: var(--color-main-text, #1b1b1b);
	line-height: 1.3;
	min-block-size: calc(1.3em * 2);
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
	word-break: break-word;
}

.dd-document-card__date {
	font-size: 0.8rem;
	color: var(--color-text-maxcontrast, #6b7280);
}
</style>
