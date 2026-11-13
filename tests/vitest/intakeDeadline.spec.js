/**
 * The handling deadline as a registrar reads it: working days left, due
 * today, or days overdue.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
 */

import { describe, expect, it, vi } from 'vitest'
import {
	countdownLabel,
	countdownTone,
	localToday,
	workingDaysLeft,
} from '../../src/services/intakeDeadline.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
	translatePlural: (app, singular, plural, count) => (count === 1 ? singular : plural).replace('%n', String(count)),
}))

describe('workingDaysLeft', () => {
	it('counts ten working days from Monday 5 to Monday 19 October', () => {
		expect(workingDaysLeft('2026-10-19', '2026-10-05')).toBe(10)
	})

	it('skips the weekend in both directions', () => {
		expect(workingDaysLeft('2026-10-12', '2026-10-09')).toBe(1)
		expect(workingDaysLeft('2026-10-09', '2026-10-12')).toBe(-1)
		expect(workingDaysLeft('2026-10-10', '2026-10-09')).toBe(0)
	})

	it('reads the date part of a moment, and knows no deadline', () => {
		expect(workingDaysLeft('2026-10-08T23:59:00+02:00', '2026-10-05')).toBe(3)
		expect(workingDaysLeft('', '2026-10-05')).toBeNull()
		expect(workingDaysLeft('soon', '2026-10-05')).toBeNull()
	})
})

describe('countdownLabel', () => {
	it('reads days left, due today and days overdue', () => {
		expect(countdownLabel('2026-10-19', '2026-10-05')).toBe('10 days left')
		expect(countdownLabel('2026-10-06', '2026-10-05')).toBe('1 day left')
		expect(countdownLabel('2026-10-05', '2026-10-05')).toBe('Due today')
		expect(countdownLabel('2026-10-08', '2026-10-15')).toBe('5 days overdue')
		expect(countdownLabel('2026-10-14', '2026-10-15')).toBe('1 day overdue')
	})

	it('calls a deadline on Saturday overdue on Sunday, not zero days', () => {
		expect(countdownLabel('2026-10-10', '2026-10-11')).toBe('1 day overdue')
	})

	it('says nothing without a deadline', () => {
		expect(countdownLabel(undefined, '2026-10-05')).toBe('')
	})
})

describe('countdownTone', () => {
	it('is an error past the deadline, a warning on the day and plain ahead of it', () => {
		expect(countdownTone('2026-10-08', '2026-10-15')).toBe('error')
		expect(countdownTone('2026-10-15', '2026-10-15')).toBe('warning')
		expect(countdownTone('2026-10-16', '2026-10-15')).toBe('default')
		expect(countdownTone('', '2026-10-15')).toBe('default')
	})
})

describe('localToday', () => {
	it('writes the browser calendar day as Y-m-d', () => {
		expect(localToday(new Date(2026, 0, 7, 23, 30))).toBe('2026-01-07')
	})
})
