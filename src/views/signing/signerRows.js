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
 * A new, empty signer row. A new recipient signs; `accept` makes it an
 * accept-only recipient (REQ-SAO-001).
 *
 * @return {{displayName: string, email: string, userId: string, role: string}}
 * @spec openspec/changes/signing-accept-only-recipient/specs/signing-accept-only/spec.md
 */
export function emptySignerRow() {
	return { displayName: '', email: '', userId: '', role: 'sign' }
}

/**
 * Whether a row only accepts the document instead of signing it.
 *
 * @param {{role?: string}} row The signer row.
 * @return {boolean}
 * @spec openspec/changes/signing-accept-only-recipient/specs/signing-accept-only/spec.md
 */
export function acceptsOnly(row) {
	return String(row?.role ?? '').trim().toLowerCase() === 'accept'
}

/**
 * Whether at least one row signs: a request of accept-only recipients
 * has nothing to sign, and the server refuses it (REQ-SAO-001).
 *
 * @param {Array<object>} rows The signer rows.
 * @return {boolean}
 * @spec openspec/changes/signing-accept-only-recipient/specs/signing-accept-only/spec.md
 */
export function someoneSigns(rows) {
	return Array.isArray(rows) && rows.some((row) => !acceptsOnly(row))
}

/**
 * Whether a row names a user id or an e-mail address.
 *
 * @param {{email?: string, userId?: string}} row The signer row.
 * @return {boolean}
 * @spec openspec/changes/signing-accept-only-recipient/specs/signing-accept-only/spec.md
 */
export function isReachable(row) {
	return (
		String(row?.email ?? '').trim() !== ''
		|| String(row?.userId ?? '').trim() !== ''
	)
}

/**
 * Whether the rows make a request somebody can sign: at least one row,
 * every row reachable, and at least one row that signs.
 *
 * @param {Array<object>} rows The signer rows.
 * @return {boolean}
 * @spec openspec/changes/signing-accept-only-recipient/specs/signing-accept-only/spec.md
 */
export function signersAreComplete(rows) {
	return (
		Array.isArray(rows)
		&& rows.length > 0
		&& rows.every(isReachable)
		&& someoneSigns(rows)
	)
}

/**
 * The `signers` payload for `POST api/signing/requests`: trimmed values, the
 * row position as the signing order, empty optional fields left out, and
 * `role: 'accept'` on an accept-only recipient (a recipient without a role signs).
 *
 * @param {Array<object>} rows The signer rows.
 * @return {Array<object>}
 * @spec openspec/changes/signing-accept-only-recipient/specs/signing-accept-only/spec.md
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
		if (acceptsOnly(row)) {
			signer.role = 'accept'
		}
		return signer
	})
}
