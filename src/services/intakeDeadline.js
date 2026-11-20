/**
 * The handling deadline of an intake document as a registrar reads it.
 *
 * The countdown is in working days, the same days the deadline was counted
 * in at arrival: Saturdays and Sundays do not count.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-every-waiting-document-has-a-handling-deadline-req-iws-001
 */

import { translatePlural as n, translate as t } from '@nextcloud/l10n'

const DAY = 86400000

/**
 * A Y-m-d date (or the date part of an ISO moment) as UTC midnight, or null.
 *
 * @param {string} value The date.
 * @return {number|null} Milliseconds since the epoch, or null when it is not a date.
 */
function utcDay(value) {
	const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value || ''))
	if (!match) {
		return null
	}
	return Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
}

/**
 * Today as a Y-m-d date in the browser's own calendar.
 *
 * @param {Date} now The moment.
 * @return {string} The date.
 */
export function localToday(now = new Date()) {
	const month = String(now.getMonth() + 1).padStart(2, '0')
	const day = String(now.getDate()).padStart(2, '0')
	return `${now.getFullYear()}-${month}-${day}`
}

/**
 * Working days from today to the deadline: positive ahead, negative past it, 0 on the day.
 *
 * @param {string} deadline The deadline (Y-m-d).
 * @param {string} today Today (Y-m-d).
 * @return {number|null} The working days, or null when there is no deadline.
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-every-waiting-document-has-a-handling-deadline-req-iws-001
 */
export function workingDaysLeft(deadline, today) {
	const end = utcDay(deadline)
	const start = utcDay(today)
	if (end === null || start === null) {
		return null
	}
	const sign = end >= start ? 1 : -1
	let count = 0
	for (let day = Math.min(start, end) + DAY; day <= Math.max(start, end); day += DAY) {
		const weekday = new Date(day).getUTCDay()
		if (weekday !== 0 && weekday !== 6) {
			count++
		}
	}
	return sign * count
}

/**
 * The countdown a row reads: "3 days left", "due today" or "5 days overdue".
 *
 * @param {string} deadline The deadline (Y-m-d).
 * @param {string} today Today (Y-m-d).
 * @return {string} The countdown, or '' without a deadline.
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-the-worklist-shows-what-is-overdue-req-iws-002
 */
export function countdownLabel(deadline, today) {
	const days = workingDaysLeft(deadline, today)
	if (days === null) {
		return ''
	}
	if (utcDay(deadline) === utcDay(today)) {
		return t('filinq', 'Due today')
	}
	if (utcDay(deadline) < utcDay(today)) {
		return n('filinq', '%n day overdue', '%n days overdue', Math.max(1, -days))
	}
	return n('filinq', '%n day left', '%n days left', days)
}

/**
 * How the countdown badge is coloured: past the deadline, on the day, or ahead.
 *
 * @param {string} deadline The deadline (Y-m-d).
 * @param {string} today Today (Y-m-d).
 * @return {string} 'error', 'warning' or 'default'.
 */
export function countdownTone(deadline, today) {
	const end = utcDay(deadline)
	const start = utcDay(today)
	if (end === null || start === null) {
		return 'default'
	}
	if (end < start) {
		return 'error'
	}
	return end === start ? 'warning' : 'default'
}
