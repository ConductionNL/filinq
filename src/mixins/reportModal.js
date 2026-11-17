// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.
//
// What the conformance report modals share: the `show` and `fileId` props,
// loading each time the modal opens, the busy and error state, and a date for
// people. A modal using it defines `load()`.

import { translate as t } from '@nextcloud/l10n'

export const reportModal = {
	props: {
		show: { type: Boolean, default: false },
		fileId: { type: Number, default: 0 },
	},

	data() {
		return {
			loading: false,
			checking: false,
			error: '',
		}
	},

	watch: {
		show: {
			immediate: true,
			/**
			 * Load the report each time the modal opens.
			 *
			 * @param {boolean} open Whether it is open.
			 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
			 */
			handler(open) {
				if (open && this.fileId) {
					this.load()
				}
			},
		},
	},

	methods: {
		t,

		/**
		 * A date for people.
		 *
		 * @param {string} value An ISO date-time.
		 * @return {string} The local date and time.
		 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
		 */
		formatDate(value) {
			const date = new Date(value)
			return Number.isNaN(date.getTime()) ? '' : date.toLocaleString()
		},
	},
}
