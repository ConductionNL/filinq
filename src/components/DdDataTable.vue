<template>
	<div class="dd-data-table">
		<div v-if="loading" class="dd-data-table__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<table v-else class="dd-data-table__table">
			<thead>
				<tr>
					<th
						v-for="col in columns"
						:key="col.key"
						class="dd-data-table__th"
						:style="col.width ? { width: col.width } : null">
						{{ col.label }}
					</th>
					<th v-if="$scopedSlots['row-actions']" class="dd-data-table__th dd-data-table__th--actions">
						<slot name="actions-header" />
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-if="rows.length === 0" class="dd-data-table__empty">
					<td :colspan="totalColumns">
						{{ emptyText }}
					</td>
				</tr>
				<tr
					v-for="row in rows"
					v-else
					:key="row[rowKey]"
					class="dd-data-table__row"
					@click="$emit('row-click', row)">
					<td
						v-for="col in columns"
						:key="col.key"
						class="dd-data-table__td">
						<slot
							:name="'column-' + col.key"
							:row="row"
							:value="getCellValue(row, col.key)">
							{{ getCellValue(row, col.key) }}
						</slot>
					</td>
					<td v-if="$scopedSlots['row-actions']" class="dd-data-table__td dd-data-table__td--actions" @click.stop>
						<slot name="row-actions" :row="row" />
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'

/**
 * Data table for DocuDesk index views.
 *
 * Manual columns only (no schema mode). Per-column rendering via
 * `column-{key}` scoped slots; whole-row click via `row-click`;
 * row action menu via the `row-actions` slot. Headers are purely
 * informational — sorting is not supported.
 */
export default {
	name: 'DdDataTable',
	components: { NcLoadingIcon },
	props: {
		/** Column definitions: `{ key, label, width? }`. */
		columns: {
			type: Array,
			default: () => [],
		},
		/** Row data array. Rows must have a unique `rowKey` field. */
		rows: {
			type: Array,
			default: () => [],
		},
		/** Show a loading spinner instead of the table body. */
		loading: {
			type: Boolean,
			default: false,
		},
		/** Property name used as unique row identifier. */
		rowKey: {
			type: String,
			default: 'id',
		},
		/** Text shown when rows is empty and not loading. */
		emptyText: {
			type: String,
			default: 'No items found',
		},
	},
	computed: {
		totalColumns() {
			return this.columns.length + (this.$scopedSlots['row-actions'] ? 1 : 0)
		},
	},
	methods: {
		/**
		 * Resolve a cell value, supporting dot-notation keys.
		 *
		 * @param {object} row Row object.
		 * @param {string} key Column key (e.g. `address.city`).
		 * @return {*} Cell value.
		 */
		getCellValue(row, key) {
			if (key.includes('.')) {
				return key.split('.').reduce((obj, k) => obj?.[k], row)
			}
			return row[key]
		},
	},
}
</script>

<style scoped>
.dd-data-table {
	border-radius: var(--dd-data-table-border-radius, var(--dd-radius-panel));
	border: 1px solid var(--dd-data-table-border-color, #E9E9E9);
	overflow-x: auto;
	box-shadow: var(--dd-shadow-panel);
}

.dd-data-table__loading {
	display: flex;
	justify-content: center;
	padding: 40px;
}

.dd-data-table__table {
	width: 100%;
	border-collapse: collapse;
	background: var(--dd-data-table-background, var(--color-main-background));
	font-size: var(--dd-data-table-font-size, 12px);
}

.dd-data-table__th,
.dd-data-table__td {
	background: var(--dd-data-table-cell-background, var(--color-main-background));
	padding: var(--dd-data-table-cell-padding, 8px);
	text-align: left;
	border-bottom: 1px solid var(--dd-data-table-cell-border-color, var(--color-border));
	vertical-align: middle;
}

.dd-data-table__th:first-child,
.dd-data-table__td:first-child {
	padding-inline-start: var(--dd-data-table-cell-padding-start, 20px);
}

.dd-data-table__th:last-child,
.dd-data-table__td:last-child {
	padding-inline-end: var(--dd-data-table-cell-padding-end, 20px);
}

.dd-data-table__th {
	font-weight: 500;
	color: var(--dd-data-table-header-color, var(--color-text-maxcontrast));
	min-height: var(--dd-data-table-header-height, 48px);
	padding-block: var(--dd-data-table-header-padding-block, 12px);
	padding-inline: var(--dd-data-table-header-padding-inline, 12px);
	text-transform: uppercase;
}

.dd-data-table__td {
	padding-block: var(--dd-data-table-cell-padding-block, 8px);
	padding-inline: var(--dd-data-table-cell-padding-inline, 8px);
}

.dd-data-table__th--actions,
.dd-data-table__td--actions {
	width: 64px;
	text-align: center;
}

.dd-data-table__row {
	cursor: pointer;
	transition: background-color 0.15s ease;
}

.dd-data-table__row:hover {
	background: var(--dd-data-table-row-hover-background, var(--color-background-hover));
}

.dd-data-table__empty td {
	text-align: center;
	padding: calc(12 * var(--default-grid-baseline)) calc(6 * var(--default-grid-baseline));
	color: var(--dd-data-table-empty-color, var(--color-text-maxcontrast));
}
</style>
