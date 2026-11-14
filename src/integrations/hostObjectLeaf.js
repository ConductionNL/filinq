// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.
//
// Mixin for a leaf that reads or writes on behalf of one host object: the
// object's props, the object as query parameters, and requests that carry
// the page's request token. The token is read from the DOM, because a leaf
// renders inside another app's page and must not assume the host bundled
// `@nextcloud/auth`.

import { generateUrl } from '@nextcloud/router'

export const hostObjectLeaf = {
	props: {
		/** The host object's register slug. */
		register: { type: String, default: '' },
		/** The host object's schema slug. */
		schema: { type: String, default: '' },
		/** The host object's id. */
		objectId: { type: String, default: '' },
	},

	methods: {
		/**
		 * The host object as query parameters.
		 *
		 * @return {URLSearchParams} `register`, `schema` and `id`.
		 *
		 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
		 */
		hostObjectQuery() {
			return new URLSearchParams({
				register: this.register,
				schema: this.schema,
				id: this.objectId,
			})
		},

		/**
		 * Send a JSON request to a Filinq route about the host object.
		 *
		 * @param {string} path The route, e.g. `/apps/filinq/api/case-documents/files`.
		 * @param {string} [method] The HTTP method; GET when left out.
		 * @return {Promise<Response>} The response.
		 *
		 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
		 */
		fetchForHostObject(path, method) {
			const headers = {
				requesttoken: this.requestToken(),
				Accept: 'application/json',
			}
			return fetch(
				generateUrl(path) + '?' + this.hostObjectQuery().toString(),
				method ? { method, headers } : { headers },
			)
		},

		/**
		 * Read a Filinq route about the host object into the leaf. Sets
		 * `error` on a refusal or an unreadable answer and clears `loading`
		 * once done. Without an object nothing is asked: the route would
		 * answer for the caller's whole set, not for one object.
		 *
		 * @param {string} path The route, e.g. `/apps/filinq/api/case-documents/files`.
		 * @param {function(object): void} apply Receives the parsed JSON body.
		 * @return {Promise<void>} Resolves once the answer is applied.
		 *
		 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
		 */
		async loadForHostObject(path, apply) {
			if (this.objectId === '') {
				this.loading = false
				return
			}
			try {
				const response = await this.fetchForHostObject(path)
				if (response.ok === false) {
					this.error = true
					return
				}
				apply(await response.json())
			} catch {
				this.error = true
			} finally {
				this.loading = false
			}
		},

		/**
		 * Read a route that answers `{ results: [...] }` into `rows`.
		 *
		 * @param {string} path The route.
		 * @return {Promise<void>} Resolves once `rows` reflects the answer.
		 *
		 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
		 */
		loadHostRows(path) {
			return this.loadForHostObject(path, (body) => {
				this.rows = Array.isArray(body.results) ? body.results : []
			})
		},

		/**
		 * The current page's request token.
		 *
		 * @return {string} The token, or an empty string when there is none.
		 *
		 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
		 */
		requestToken() {
			const head = document.getElementsByTagName('head')[0]
			return (head && head.getAttribute('data-requesttoken')) || ''
		},
	},
}
