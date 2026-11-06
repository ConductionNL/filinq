<template>
	<fieldset class="field-placement">
		<legend>{{ t('filinq', 'Fields on the document') }}</legend>
		<p class="field-placement__hint">
			{{
				t(
					'filinq',
					'Choose a signer and a field, then click the page where it goes. Drag a field to move it, or select it and use the arrow keys. Shift and an arrow key changes its size. The fields are drawn into the document when it is signed.',
				)
			}}
		</p>
		<div class="field-placement__tools">
			<NcSelect
				v-model="signerIndex"
				:inputLabel="t('filinq', 'Signer')"
				:options="signerOptions"
				:clearable="false"
				label="label"
				:reduce="(option) => option.id" />
			<NcSelect
				v-model="fieldType"
				:inputLabel="t('filinq', 'Field')"
				:options="typeOptions"
				:clearable="false"
				label="label"
				:reduce="(option) => option.id" />
			<div class="field-placement__pager">
				<NcButton
					variant="tertiary"
					:disabled="page <= 1"
					:aria-label="t('filinq', 'Previous page')"
					@click="showPage(page - 1)">
					&lsaquo;
				</NcButton>
				<span aria-live="polite">{{
					t('filinq', 'Page {page} of {pages}', { page, pages: pageCount })
				}}</span>
				<NcButton
					variant="tertiary"
					:disabled="page >= pageCount"
					:aria-label="t('filinq', 'Next page')"
					@click="showPage(page + 1)">
					&rsaquo;
				</NcButton>
			</div>
		</div>
		<NcNoteCard v-if="error" type="warning">
			{{ error }}
		</NcNoteCard>
		<div
			v-else
			ref="sheet"
			class="field-placement__sheet"
			data-testid="field-placement-sheet"
			role="application"
			tabindex="0"
			:aria-label="
				t(
					'filinq',
					'Page {page}: press Enter to place a field in the middle',
					{ page },
				)
			"
			@click="place"
			@keydown.enter.self.prevent="placeInMiddle"
			@keydown.space.self.prevent="placeInMiddle">
			<canvas ref="canvas" class="field-placement__canvas" />
			<button
				v-for="box in pageBoxes"
				:key="box.index"
				type="button"
				class="field-placement__box"
				:class="{ 'field-placement__box--selected': box.index === selected }"
				:style="boxStyle(box)"
				:aria-label="boxLabel(box)"
				@click.stop="selected = box.index"
				@pointerdown.stop="startDrag($event, box.index)"
				@keydown="nudge($event, box.index)">
				{{ typeLabel(box.type) }}
			</button>
		</div>
		<ul v-if="modelValue.length" class="field-placement__list">
			<li v-for="(box, index) in modelValue" :key="index">
				{{ boxLabel({ ...box, index }) }}
				<NcButton
					variant="tertiary"
					:aria-label="
						t('filinq', 'Remove field {number}', { number: index + 1 })
					"
					@click="remove(index)">
					{{ t('filinq', 'Remove') }}
				</NcButton>
			</li>
		</ul>
	</fieldset>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcNoteCard, NcSelect } from '@nextcloud/vue'
import { fetchUrlAsArrayBuffer } from '../../services/fileViewerService.js'
import { loadPdfjs } from '../../services/pdfjsLoader.js'
import {
	addPlacement,
	FIELD_TYPES,
	movePlacement,
	resizePlacement,
} from './fieldPlacement.js'

export default {
	name: 'FieldPlacementEditor',
	components: { NcButton, NcNoteCard, NcSelect },
	props: {
		fileId: {
			type: [String, Number],
			required: true,
		},

		signers: {
			type: Array,
			required: true,
		},

		modelValue: {
			type: Array,
			default: () => [],
		},
	},

	emits: ['update:modelValue'],
	data() {
		return {
			signerIndex: 0,
			fieldType: 'signature',
			page: 1,
			pageCount: 1,
			selected: -1,
			error: '',
			pdfDoc: null,
			drag: null,
		}
	},

	computed: {
		/**
		 * @return {Array<object>} One option per signer row.
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		signerOptions() {
			return this.signers.map((name, id) => ({
				id,
				label: name || t('filinq', 'Signer {number}', { number: id + 1 }),
			}))
		},

		/**
		 * @return {Array<object>} The five field types.
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		typeOptions() {
			return FIELD_TYPES.map((id) => ({ id, label: this.typeLabel(id) }))
		},

		/**
		 * @return {Array<object>} The boxes on the page shown, with their list index.
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		pageBoxes() {
			return this.modelValue
				.map((box, index) => ({ ...box, index }))
				.filter((box) => box.page === this.page)
		},
	},

	watch: {
		fileId: {
			immediate: true,
			/**
			 * Read the new document.
			 *
			 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
			 */
			handler() {
				this.load()
			},
		},
	},

	/**
	 * Stop listening for drags and free the document.
	 *
	 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
	 */
	beforeUnmount() {
		window.removeEventListener('pointermove', this.onDrag)
		window.removeEventListener('pointerup', this.endDrag)
		if (this.pdfDoc) {
			this.pdfDoc.destroy()
		}
	},

	methods: {
		t,
		/**
		 * The name of a field type.
		 *
		 * @param {string} type The type.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		typeLabel(type) {
			return {
				signature: t('filinq', 'Signature'),
				initials: t('filinq', 'Initials'),
				date: t('filinq', 'Date'),
				text: t('filinq', 'Name'),
				checkbox: t('filinq', 'Checkbox'),
			}[type]
		},

		/**
		 * What a screen reader hears for a box.
		 *
		 * @param {object} box The box.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		boxLabel(box) {
			return t('filinq', '{field} for {signer} on page {page}', {
				field: this.typeLabel(box.type),
				signer: this.signerOptions[box.signerIndex]?.label ?? '',
				page: box.page,
			})
		},

		/**
		 * Where a box sits on the sheet, in percent.
		 *
		 * @param {object} box The box.
		 * @return {object}
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		boxStyle(box) {
			return {
				left: `${box.x * 100}%`,
				top: `${box.y * 100}%`,
				width: `${box.width * 100}%`,
				height: `${box.height * 100}%`,
			}
		},

		/**
		 * Read the document and show its first page.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		async load() {
			this.error = ''
			try {
				const [pdfjsLib, data] = await Promise.all([
					loadPdfjs(),
					fetchUrlAsArrayBuffer(
						generateUrl(
							'/apps/filinq/api/documents/{fileId}/versions/0/download',
							{ fileId: this.fileId },
						),
					),
				])
				this.pdfDoc = await pdfjsLib.getDocument({ data }).promise
				this.pageCount = this.pdfDoc.numPages
				await this.showPage(1)
			} catch {
				this.error = t(
					'filinq',
					'This document cannot be shown as a PDF, so no fields can be placed on it.',
				)
			}
		},

		/**
		 * Render one page onto the sheet.
		 *
		 * @param {number} number The page.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		async showPage(number) {
			this.page = number
			this.selected = -1
			await this.$nextTick()
			const canvas = this.$refs.canvas
			if (!this.pdfDoc || !canvas) {
				return
			}
			const pdfPage = await this.pdfDoc.getPage(number)
			const width = this.$refs.sheet.clientWidth || 600
			const viewport = pdfPage.getViewport({
				scale: width / pdfPage.getViewport({ scale: 1 }).width,
			})
			canvas.width = viewport.width
			canvas.height = viewport.height
			await pdfPage.render({
				canvasContext: canvas.getContext('2d'),
				viewport,
			}).promise
		},

		/**
		 * Put a field where the page was clicked.
		 *
		 * @param {MouseEvent} event The click.
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		place(event) {
			const rect = this.$refs.sheet.getBoundingClientRect()
			this.placeAt(
				(event.clientX - rect.left) / rect.width,
				(event.clientY - rect.top) / rect.height,
			)
		},

		/**
		 * Put a field in the middle of the page, for the keyboard; the arrow
		 * keys then move it.
		 *
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		placeInMiddle() {
			this.placeAt(0.5, 0.5)
		},

		/**
		 * Put a field at a point of the page.
		 *
		 * @param {number} x Share of the page width.
		 * @param {number} y Share of the page height.
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		placeAt(x, y) {
			const next = addPlacement(this.modelValue, {
				signerIndex: this.signerIndex,
				page: this.page,
				type: this.fieldType,
				x,
				y,
			})
			this.selected = next.length - 1
			this.$emit('update:modelValue', next)
		},

		/**
		 * Move or resize the selected box with the keyboard.
		 *
		 * @param {KeyboardEvent} event The key.
		 * @param {number} index The box.
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		nudge(event, index) {
			const step = 0.01
			const moves = {
				ArrowLeft: [-step, 0],
				ArrowRight: [step, 0],
				ArrowUp: [0, -step],
				ArrowDown: [0, step],
			}
			if (event.key === 'Delete' || event.key === 'Backspace') {
				event.preventDefault()
				this.remove(index)
				return
			}
			if (!moves[event.key]) {
				return
			}
			event.preventDefault()
			const [dx, dy] = moves[event.key]
			const next = event.shiftKey
				? resizePlacement(this.modelValue, index, dx, dy)
				: movePlacement(this.modelValue, index, dx, dy)
			this.$emit('update:modelValue', next)
		},

		/**
		 * Start dragging a box.
		 *
		 * @param {PointerEvent} event The pointer.
		 * @param {number} index The box.
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		startDrag(event, index) {
			this.selected = index
			this.drag = { index, x: event.clientX, y: event.clientY }
			window.addEventListener('pointermove', this.onDrag)
			window.addEventListener('pointerup', this.endDrag)
		},

		/**
		 * Follow the pointer.
		 *
		 * @param {PointerEvent} event The pointer.
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		onDrag(event) {
			if (!this.drag) {
				return
			}
			const rect = this.$refs.sheet.getBoundingClientRect()
			const next = movePlacement(
				this.modelValue,
				this.drag.index,
				(event.clientX - this.drag.x) / rect.width,
				(event.clientY - this.drag.y) / rect.height,
			)
			this.drag = { ...this.drag, x: event.clientX, y: event.clientY }
			this.$emit('update:modelValue', next)
		},

		/**
		 * Stop dragging.
		 *
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		endDrag() {
			this.drag = null
			window.removeEventListener('pointermove', this.onDrag)
			window.removeEventListener('pointerup', this.endDrag)
		},

		/**
		 * Remove one field.
		 *
		 * @param {number} index The box.
		 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.3
		 */
		remove(index) {
			this.selected = -1
			this.$emit(
				'update:modelValue',
				this.modelValue.filter((box, i) => i !== index),
			)
		},
	},
}
</script>

<style scoped>
.field-placement__tools {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 2);
	align-items: flex-end;
}

.field-placement__pager {
	display: flex;
	align-items: center;
	gap: var(--default-grid-baseline);
}

.field-placement__sheet {
	position: relative;
	max-width: 640px;
	margin-block: calc(var(--default-grid-baseline) * 2);
	border: 1px solid var(--color-border);
	cursor: crosshair;
}

.field-placement__canvas {
	display: block;
	width: 100%;
}

.field-placement__box {
	position: absolute;
	min-height: 0;
	margin: 0;
	padding: 0;
	overflow: hidden;
	border: 2px solid var(--color-primary-element);
	background: color-mix(in srgb, var(--color-primary-element) 15%, transparent);
	color: var(--color-main-text);
	font-size: 0.75em;
	cursor: move;
	touch-action: none;
}

.field-placement__box--selected,
.field-placement__box:focus-visible {
	outline: 2px solid var(--color-main-text);
	outline-offset: 1px;
}

.field-placement__hint {
	color: var(--color-text-maxcontrast);
}
</style>
