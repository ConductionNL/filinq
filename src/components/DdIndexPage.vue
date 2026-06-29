<template>
	<div class="dd-index-page">
		<!-- Toolbar: header-actions slot (e.g. search) + view toggle -->
		<div class="dd-index-page__toolbar">
			<div class="dd-index-page__tools">
				<slot name="header-actions" />
			</div>

			<DdViewToggle
				v-if="showViewToggle"
				:value="toggleValue"
				:tiles-label="cardsLabel"
				:list-label="tableLabel"
				:aria-label="viewToggleLabel"
				@input="onToggle" />
		</div>

		<!-- Body: table or cards -->
		<DdDataTable
			v-if="currentViewMode === 'table'"
			:columns="columns"
			:rows="objects"
			:loading="loading"
			:row-key="rowKey"
			:empty-text="emptyText"
			:selectable="selectable"
			:selected-keys="selectedKeys"
			:select-all-label="selectAllLabel"
			@row-click="$emit('row-click', $event)"
			@toggle-select="$emit('toggle-select', $event)"
			@toggle-select-all="$emit('toggle-select-all')">
			<template
				v-for="col in slotColumns"
				#[`column-${col}`]="{ row, value }">
				<slot :name="'column-' + col" :row="row" :value="value" />
			</template>
			<template v-if="$scopedSlots['row-actions']" #row-actions="{ row }">
				<slot name="row-actions" :row="row" />
			</template>
			<template v-if="$scopedSlots['actions-header']" #actions-header>
				<slot name="actions-header" />
			</template>
		</DdDataTable>

		<DdCardGrid
			v-else
			:objects="objects"
			:loading="loading"
			:row-key="rowKey"
			:empty-text="emptyText"
			@row-click="$emit('row-click', $event)">
			<template v-if="$scopedSlots.card" #card="{ object }">
				<slot name="card" :object="object" />
			</template>
		</DdCardGrid>

		<!-- Pagination -->
		<DdPagination
			v-if="pagination"
			:current-page="pagination.page || 1"
			:total-pages="pagination.pages || 1"
			:total-items="pagination.total || 0"
			:current-page-size="pagination.limit || 20"
			:items-per-page-label="itemsPerPageLabel"
			:page-info-format="pageInfoFormat"
			:first-label="firstLabel"
			:previous-label="previousLabel"
			:next-label="nextLabel"
			:last-label="lastLabel"
			@page-changed="$emit('page-changed', $event)"
			@page-size-changed="$emit('page-size-changed', $event)" />
	</div>
</template>

<script>
import DdViewToggle from './DdViewToggle.vue'
import DdDataTable from './DdDataTable.vue'
import DdCardGrid from './DdCardGrid.vue'
import DdPagination from './DdPagination.vue'

/**
 * Top-level index-page component for DocuDesk list views.
 *
 * Layout is fixed: a tools bar (consumer-controlled `header-actions`
 * slot on the left, list/cards toggle on the right), then the list or
 * card grid, then pagination. The page title is rendered separately by
 * `DdPageHeader` outside this component.
 *
 * Slots:
 * - `header-actions` — left side of the toolbar (e.g. a search bar).
 * - `column-{key}` — per-column cell renderer for the table view.
 * - `row-actions` — trailing column with a row's action menu.
 * - `card` — card content for the tile view.
 */
export default {
	name: 'DdIndexPage',
	components: {
		DdViewToggle,
		DdDataTable,
		DdCardGrid,
		DdPagination,
	},
	props: {
		/** Column definitions for the table view: `{ key, label, sortable?, width? }`. */
		columns: {
			type: Array,
			default: () => [],
		},
		/** Page of items being displayed (already paginated by the consumer). */
		objects: {
			type: Array,
			default: () => [],
		},
		/** Pagination state: `{ page, pages, total, limit }`. Pass `null` to hide. */
		pagination: {
			type: Object,
			default: null,
		},
		/** Show a loading state in the body. */
		loading: {
			type: Boolean,
			default: false,
		},
		/** Initial / external view mode (`table` or `cards`). */
		viewMode: {
			type: String,
			default: 'table',
			validator: (v) => ['table', 'cards'].includes(v),
		},
		/** Property name used as unique row identifier. */
		rowKey: {
			type: String,
			default: 'id',
		},
		/** Text shown when objects is empty. */
		emptyText: {
			type: String,
			default: 'No items found',
		},
		/** Show the table/cards toggle in the toolbar. */
		showViewToggle: {
			type: Boolean,
			default: true,
		},
		/** Enable bulk-selection checkboxes (leading column in the table view). */
		selectable: {
			type: Boolean,
			default: false,
		},
		/** Row keys (`row[rowKey]`) currently selected. */
		selectedKeys: {
			type: Array,
			default: () => [],
		},
		/** Accessible label for the table's select-all checkbox. */
		selectAllLabel: {
			type: String,
			default: 'Select all',
		},
		tableLabel: { type: String, default: 'List' },
		cardsLabel: { type: String, default: 'Tiles' },
		viewToggleLabel: { type: String, default: 'View mode' },
		itemsPerPageLabel: { type: String, default: 'Items per page:' },
		pageInfoFormat: { type: String, default: 'Page {current} of {total}' },
		firstLabel: { type: String, default: 'First' },
		previousLabel: { type: String, default: 'Previous' },
		nextLabel: { type: String, default: 'Next' },
		lastLabel: { type: String, default: 'Last' },
	},
	data() {
		return {
			currentViewMode: this.viewMode,
		}
	},
	computed: {
		/** Map the internal `table`/`cards` mode to DdViewToggle's `list`/`tiles`. */
		toggleValue() {
			return this.currentViewMode === 'cards' ? 'tiles' : 'list'
		},
		/** Names of `column-*` slots provided by the parent, for pass-through. */
		slotColumns() {
			return Object.keys(this.$scopedSlots)
				.filter((name) => name.startsWith('column-'))
				.map((name) => name.replace('column-', ''))
		},
	},
	watch: {
		viewMode(val) {
			this.currentViewMode = val
		},
	},
	methods: {
		/**
		 * Translate a DdViewToggle selection back to the internal mode.
		 *
		 * @param {string} mode `'tiles'` or `'list'`.
		 */
		onToggle(mode) {
			this.setViewMode(mode === 'tiles' ? 'cards' : 'table')
		},
		/**
		 * Switch the active view mode and notify the parent so it can persist
		 * the choice (e.g. via `:view-mode.sync` or `@update:view-mode`).
		 *
		 * @param {string} mode `'table'` or `'cards'`.
		 */
		setViewMode(mode) {
			if (mode === this.currentViewMode) return
			this.currentViewMode = mode
			this.$emit('update:view-mode', mode)
			this.$emit('view-mode-change', mode)
		},
	},
}
</script>

<style scoped>
.dd-index-page {
	display: flex;
	flex-direction: column;
	gap: 32px;
	padding-inline: calc(5 * var(--default-grid-baseline));
}

.dd-index-page__toolbar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
}

.dd-index-page__tools {
	display: flex;
	align-items: center;
	gap: 8px;
	flex: 1;
	max-width: 360px;
}

</style>
