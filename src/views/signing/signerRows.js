/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Signer rows on the new signing request form (issue #1209, REQ-SAO-001
 * task 4.1). A signer is reachable through a Nextcloud user id or an e-mail
 * address; a name alone reaches nobody. The server refuses a request whose
 * signers are empty or unreachable with a 400, so the form checks the same
 * rule before it lets the user submit.
 */

/**
 * A new, empty signer row.
 *
 * @return {{displayName: string, email: string, userId: string}}
 */
export function emptySignerRow() {
	return { displayName: '', email: '', userId: '' }
}

/**
 * Whether a row names a user id or an e-mail address.
 *
 * @param {{email?: string, userId?: string}} row The signer row.
 * @return {boolean}
 */
export function isReachable(row) {
	return (
		String(row?.email ?? '').trim() !== ''
		|| String(row?.userId ?? '').trim() !== ''
	)
}

/**
 * Whether the rows make a request somebody can sign: at least one row, and
 * every row reachable.
 *
 * @param {Array<object>} rows The signer rows.
 * @return {boolean}
 */
export function signersAreComplete(rows) {
	return Array.isArray(rows) && rows.length > 0 && rows.every(isReachable)
}

/**
 * The `signers` payload for `POST api/signing/requests`: trimmed values, the
 * row position as the signing order, and empty optional fields left out.
 *
 * @param {Array<object>} rows The signer rows.
 * @return {Array<object>}
 */
export function toSigners(rows) {
	return rows.map((row, index) => {
		const signer = { order: index }
		for (const key of ['displayName', 'email', 'userId']) {
			const value = String(row?.[key] ?? '').trim()
			if (value !== '') {
				signer[key] = value
			}
		}
		return signer
	})
}
