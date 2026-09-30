/**
 * The renewal pipeline: which active contracts need attention, and when.
 * Pure date arithmetic over the contracts the caller can read; no endpoint
 * aggregates anything.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
 */

/** The buckets, most urgent first. */
export const BUCKETS = Object.freeze([
	'expired',
	'noticeDue',
	'expiring',
	'later',
])

const NOTICE_WINDOW_DAYS = 30
const EXPIRY_WINDOW_DAYS = 90

/**
 * Today as YYYY-MM-DD in the viewer's time zone.
 *
 * @return {string} The date.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
 */
export function todayIso() {
	const now = new Date()
	const pad = (n) => String(n).padStart(2, '0')
	return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`
}

/**
 * Days from one YYYY-MM-DD date to another, or null when either is missing.
 *
 * @param {string} from The first date.
 * @param {string} to The second date.
 * @return {number|null} to minus from, in days.
 */
function daysBetween(from, to) {
	const parse = (value) => {
		const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value ?? ''))
		return match ? Date.UTC(+match[1], +match[2] - 1, +match[3]) : null
	}
	const a = parse(from)
	const b = parse(to)
	if (a === null || b === null) {
		return null
	}
	return Math.round((b - a) / 86400000)
}

/**
 * The status a contract shows: an active contract whose end date passed is
 * expired, whether or not the stored transition has fired.
 *
 * @param {object} contract The contract.
 * @param {string} today YYYY-MM-DD.
 * @return {string} The status.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
 */
export function displayStatus(contract, today = todayIso()) {
	const status = contract?.status || 'draft'
	if (status === 'active') {
		const left = daysBetween(today, contract.endDate)
		if (left !== null && left < 0) {
			return 'expired'
		}
	}
	return status
}

/**
 * The bucket an active contract falls in, or null for any other contract.
 *
 * @param {object} contract The contract.
 * @param {string} today YYYY-MM-DD.
 * @return {string|null} One of BUCKETS, or null.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
 */
export function bucketOf(contract, today = todayIso()) {
	if (contract?.status !== 'active') {
		return null
	}
	if (displayStatus(contract, today) === 'expired') {
		return 'expired'
	}
	const notice = daysBetween(today, contract.noticeDeadline)
	if (notice !== null && notice <= NOTICE_WINDOW_DAYS) {
		return 'noticeDue'
	}
	const end = daysBetween(today, contract.endDate)
	if (end !== null && end <= EXPIRY_WINDOW_DAYS) {
		return 'expiring'
	}
	return 'later'
}

/**
 * The date a bucket is sorted on.
 *
 * @param {string} bucket The bucket.
 * @param {object} contract The contract.
 * @return {string} YYYY-MM-DD, or a high value when absent.
 */
function sortDate(bucket, contract) {
	const date = bucket === 'noticeDue' ? contract.noticeDeadline : contract.endDate
	return date || '9999-12-31'
}

/**
 * The active contracts per bucket, most urgent first within each.
 *
 * @param {Array<object>} contracts The contracts.
 * @param {string} today YYYY-MM-DD.
 * @return {object} { expired, noticeDue, expiring, later }.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
 */
export function bucketContracts(contracts, today = todayIso()) {
	const buckets = Object.fromEntries(BUCKETS.map((bucket) => [bucket, []]))
	for (const contract of contracts || []) {
		const bucket = bucketOf(contract, today)
		if (bucket !== null) {
			buckets[bucket].push(contract)
		}
	}
	for (const bucket of BUCKETS) {
		buckets[bucket].sort((a, b) =>
			sortDate(bucket, a).localeCompare(sortDate(bucket, b)),
		)
	}
	return buckets
}
