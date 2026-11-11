// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.
//
// Submitting a "new …" dialog: show it busy, send the create call, keep a
// refusal on the dialog or hand the created record back. A dialog using it
// has `busy` and `error` data properties and emits `created`.

export const createDialogSubmit = {
	methods: {
		/**
		 * Run one create call with the dialog busy, then keep its refusal or
		 * emit what it created.
		 *
		 * @param {() => Promise<object>} create Sends the create call; answers `{ok, data}` or `{ok: false, error}`.
		 * @return {Promise<void>}
		 */
		async submitCreate(create) {
			this.busy = true
			this.error = ''
			const answer = await create()
			this.busy = false
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.$emit('created', answer.data)
		},
	},
}
