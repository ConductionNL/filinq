/**
 * The answer shape the service modules give their callers: `{ ok: true, data }`
 * for a request the server answered, `{ ok: false, status, error }` for one
 * it refused or that never reached it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

/**
 * The refusal shape of a failed request.
 *
 * @param {Error} error the failure
 * @return {{ok: false, status: number, error: string}} the refusal
 * @spec openspec/specs/filinq-store-migration/spec.md
 */
export function refusal(error) {
	return {
		ok: false,
		status: error?.response?.status ?? 0,
		error: error?.response?.data?.error ?? '',
	}
}

/**
 * Wait for a request and give its answer.
 *
 * @param {Promise} call the request in flight
 * @return {Promise<object>} the answer, or the refusal when it failed
 * @spec openspec/specs/filinq-store-migration/spec.md
 */
export async function answer(call) {
	try {
		const { data } = await call
		return { ok: true, data }
	} catch (error) {
		return refusal(error)
	}
}
