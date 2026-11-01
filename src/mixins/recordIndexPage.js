/**
 * Mixins for the admin index pages that list register records in a
 * CnIndexPage: client-side paging and refresh, the create/edit form dialog,
 * and the confirmed delete.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * A page using them supplies the store calls as methods:
 *  - `reloadRecords()` for `indexPaging`;
 *  - `createRecord(formData)`, `updateRecord(id, formData)` and
 *    `toEditingRecord(row)` for `recordFormDialog`;
 *  - `deleteRecord(id)` for `recordDeletion`, with the console prefix for a
 *    failed delete in the component option `deleteFailedLog`.
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * The object id of a register row: its `@self` id, else `id`, else `uuid`.
 *
 * @param {object} row Register row.
 * @return {string|undefined} The id.
 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
 */
export function recordId(row) {
	return row['@self']?.id || row.id || row.uuid
}

/**
 * The message shown when a save fails: the server's error, else the
 * exception message, else a generic text.
 *
 * @param {object} err The rejection.
 * @return {string} The message.
 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-rbac-must-govern-writes-to-both-policy-surfaces
 */
export function saveErrorMessage(err) {
	return err.response?.data?.error || err.message || t('filinq', 'Save failed')
}

export const indexPaging = {
	data() {
		return {
			isRefreshing: false,
			currentPage: 1,
			pageSize: 20,
		}
	},

	methods: {
		/**
		 * Reload the records, with the refresh spinner on while it runs.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		async handleRefresh() {
			this.isRefreshing = true
			try {
				await this.reloadRecords()
			} finally {
				this.isRefreshing = false
			}
		},

		/**
		 * Show another page of the table.
		 *
		 * @param {number} page The page number.
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-listing-and-querying-req-cons-03
		 */
		onPageChanged(page) {
			this.currentPage = page
		},

		/**
		 * Change the page size and go back to the first page.
		 *
		 * @param {number} size Rows per page.
		 * @spec openspec/specs/consent-management/spec.md#requirement-consent-listing-and-querying-req-cons-03
		 */
		onPageSizeChanged(size) {
			this.pageSize = size
			this.currentPage = 1
		},
	},
}

export const recordFormDialog = {
	data() {
		return {
			dialogOpen: false,
			saving: false,
			editing: null, // id of the record being edited, or null for create
			editingRecord: null, // record handed to the form dialog to fill its form
			formError: '',
		}
	},

	methods: {
		/**
		 * Open the form dialog to create a record.
		 *
		 * @spec openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		openCreateDialog() {
			this.editing = null
			this.editingRecord = null
			this.formError = ''
			this.dialogOpen = true
		},

		/**
		 * Open the form dialog to edit a row.
		 *
		 * @param {object} row The record to edit.
		 * @spec openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		openEditDialog(row) {
			this.editing = recordId(row)
			this.editingRecord = this.toEditingRecord(row)
			this.formError = ''
			this.dialogOpen = true
		},

		/**
		 * Create or update the record from the dialog's form and close the
		 * dialog; on failure keep it open with the error.
		 *
		 * @param {object} formData The submitted form.
		 * @return {Promise<void>}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-rbac-must-govern-writes-to-both-policy-surfaces
		 */
		async onModalSubmit(formData) {
			this.saving = true
			this.formError = ''
			try {
				if (this.editing) {
					await this.updateRecord(this.editing, formData)
				} else {
					await this.createRecord(formData)
				}
				this.dialogOpen = false
			} catch (err) {
				this.formError = saveErrorMessage(err)
			} finally {
				this.saving = false
			}
		},

		/**
		 * A row's match rules as `type:value` pairs, or `-` when it has none.
		 *
		 * @param {Array} rules The match rules.
		 * @return {string} The display text.
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		formatMatchRules(rules) {
			if (!Array.isArray(rules) || rules.length === 0) {
				return '-'
			}
			return rules.map((r) => `${r.type}:${r.value}`).join(', ')
		},
	},
}

export const recordDeletion = {
	data() {
		return {
			deleteTarget: null, // row awaiting delete confirmation, or null
			deleting: false,
		}
	},

	methods: {
		/**
		 * Ask for confirmation before deleting a row; nothing is removed here.
		 *
		 * @param {object} row The row to delete.
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		confirmDelete(row) {
			this.deleteTarget = row
		},

		/**
		 * Dismiss the delete confirmation without deleting anything.
		 *
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		cancelDelete() {
			this.deleteTarget = null
		},

		/**
		 * Delete the confirmed row. Reachable only from the confirmation
		 * dialog's @confirm, so nothing is removed without an explicit
		 * confirmation.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		async executeDelete() {
			const row = this.deleteTarget
			if (!row) {
				return
			}
			this.deleting = true
			try {
				await this.deleteRecord(recordId(row))
				this.deleteTarget = null
			} catch (err) {
				console.error(this.$options.deleteFailedLog, err)
			} finally {
				this.deleting = false
			}
		},
	},
}
