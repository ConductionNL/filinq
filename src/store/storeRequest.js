/**
 * The loading and error bookkeeping every store action shares.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

/* eslint-disable no-console */

/**
 * The message a failed request leaves in the store: the server's own error
 * when the request asks for it, else the error's message.
 *
 * @param {Error} err the failure
 * @param {boolean} serverError whether the server's error text comes first
 * @return {string} the message
 * @spec openspec/specs/filinq-store-migration/spec.md
 */
function failureMessage(err, serverError) {
	return (serverError && err.response?.data?.error) || err.message
}

/**
 * Run one request of a store without a loading flag: its error is cleared
 * first and set when the request fails; a failure is logged, then rethrown
 * or answered with the fallback.
 *
 * @param {object} store the Pinia store
 * @param {object} request what to run
 * @param {() => Promise<unknown>} request.run the request; its result is returned
 * @param {string} request.failure the text logged with a failure
 * @param {unknown} [request.fallback] what a failure returns
 * @param {boolean} [request.rethrow] throw the failure on instead
 * @param {boolean} [request.serverError] prefer the server's error text
 * @return {Promise<unknown>} the result of run, or the fallback
 * @spec openspec/specs/filinq-store-migration/spec.md
 */
export async function quietRequest(store, request) {
	store.error = null
	try {
		return await request.run()
	} catch (err) {
		console.error(request.failure, err)
		store.error = failureMessage(err, request.serverError)
		if (request.rethrow) {
			throw err
		}
		return request.fallback
	}
}

/**
 * Run one request of a store like quietRequest, with the store's loading
 * flag up while it runs.
 *
 * @param {object} store the Pinia store
 * @param {object} request what to run, as for quietRequest
 * @param {string} [request.flag] the loading flag, `loading` by default
 * @return {Promise<unknown>} the result of run, or the fallback
 * @spec openspec/specs/filinq-store-migration/spec.md
 */
export async function storeRequest(store, request) {
	const flag = request.flag || 'loading'
	store[flag] = true
	try {
		return await quietRequest(store, request)
	} finally {
		store[flag] = false
	}
}
