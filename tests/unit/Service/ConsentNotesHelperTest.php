<?php

/**
 * Unit tests for ConsentNotesHelper
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-7
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ConsentNotesHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConsentNotesHelper sentinel-tagged notes serialisation.
 *
 * Covers all scenarios from tasks.md#task-7:
 * - Multi-element publicationBases[] produces the bracketed region.
 * - Operator-authored content outside brackets is preserved across re-submits.
 * - Re-submit with the same multi-element array is a no-op on notes (idempotent).
 * - Shrink-to-one-element removes the bracketed region cleanly.
 * - Shrink-to-zero removes the bracketed region cleanly.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-7
 */
class ConsentNotesHelperTest extends TestCase {

	/**
	 * Helper under test.
	 *
	 * @var ConsentNotesHelper
	 */
	private ConsentNotesHelper $helper;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->helper = new ConsentNotesHelper();

	}//end setUp()

	// ------------------------------------------------------------------
	// updateSentinelRegion — Task 7
	// ------------------------------------------------------------------

	/**
	 * Multi-element publicationBases[] produces the bracketed sentinel region.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-7
	 */
	public function testMultiElementBasesProducesSentinelRegion(): void {
		$result = $this->helper->updateSentinelRegion(
			currentNotes: '',
			additionalBases: ['Article 6(1)(e)', 'Article 6(1)(f)']
		);

		$this->assertStringContainsString(
			needle: ConsentNotesHelper::SENTINEL_BEGIN,
			haystack: $result
		);
		$this->assertStringContainsString(
			needle: ConsentNotesHelper::SENTINEL_END,
			haystack: $result
		);
		$this->assertStringContainsString(
			needle: '- Article 6(1)(e)',
			haystack: $result
		);
		$this->assertStringContainsString(
			needle: '- Article 6(1)(f)',
			haystack: $result
		);
		$this->assertStringContainsString(
			needle: '**Aanvullende publicatiegrondslagen:**',
			haystack: $result
		);

	}//end testMultiElementBasesProducesSentinelRegion()

	/**
	 * Operator-authored content outside the brackets is preserved across re-submits.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-7
	 */
	public function testOperatorContentOutsideBracketsIsPreserved(): void {
		$operatorContent = 'This is my operator note about the entity.';
		$existingWithSentinel = $this->helper->updateSentinelRegion(
			currentNotes: $operatorContent,
			additionalBases: ['Basis A', 'Basis B']
		);

		// Re-submit: update with new bases; operator content must survive.
		$resubmitted = $this->helper->updateSentinelRegion(
			currentNotes: $existingWithSentinel,
			additionalBases: ['Basis X', 'Basis Y']
		);

		$this->assertStringContainsString(
			needle: $operatorContent,
			haystack: $resubmitted,
			message: 'Operator-authored content must survive re-submit.'
		);
		$this->assertStringContainsString(needle: '- Basis X', haystack: $resubmitted);
		$this->assertStringNotContainsString(needle: '- Basis A', haystack: $resubmitted);

	}//end testOperatorContentOutsideBracketsIsPreserved()

	/**
	 * Re-submit with the same multi-element array is a no-op on notes (idempotent rendering).
	 *
	 * @return void
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-7
	 */
	public function testResubmitWithSameBasesIsIdempotent(): void {
		$operatorContent = 'Operator-authored note.';
		$bases = ['Article 6(1)(e)', 'Article 6(1)(f)'];

		$first = $this->helper->updateSentinelRegion(currentNotes: $operatorContent, additionalBases: $bases);
		$second = $this->helper->updateSentinelRegion(currentNotes: $first, additionalBases: $bases);

		$this->assertSame(
			expected: $first,
			actual: $second,
			message: 'Re-rendering with the same bases must produce identical output.'
		);

	}//end testResubmitWithSameBasesIsIdempotent()

	/**
	 * Shrink-to-one-element removes the bracketed region cleanly (including leading blank line).
	 *
	 * @return void
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-7
	 */
	public function testShrinkToOneElementRemovesSentinelRegion(): void {
		$operatorContent = 'Operator note stays.';
		$withSentinel = $this->helper->updateSentinelRegion(
			currentNotes: $operatorContent,
			additionalBases: ['Basis A', 'Basis B']
		);

		// Shrink: now only one basis total (basis 0 = legalBasis, no additional bases).
		$shrunk = $this->helper->updateSentinelRegion(
			currentNotes: $withSentinel,
			additionalBases: []
		);

		$this->assertStringNotContainsString(
			needle: ConsentNotesHelper::SENTINEL_BEGIN,
			haystack: $shrunk,
			message: 'Sentinel begin must be removed on shrink-to-one.'
		);
		$this->assertStringNotContainsString(
			needle: ConsentNotesHelper::SENTINEL_END,
			haystack: $shrunk,
			message: 'Sentinel end must be removed on shrink-to-one.'
		);
		$this->assertStringContainsString(
			needle: $operatorContent,
			haystack: $shrunk,
			message: 'Operator content must survive shrink.'
		);
		// No trailing blank lines introduced.
		$this->assertStringNotContainsString(
			needle: "\n\n\n",
			haystack: $shrunk,
			message: 'No triple newline after sentinel removal.'
		);

	}//end testShrinkToOneElementRemovesSentinelRegion()

	/**
	 * Shrink-to-zero (all bases removed) removes the bracketed region cleanly.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-7
	 */
	public function testShrinkToZeroRemovesSentinelRegion(): void {
		$withSentinel = $this->helper->updateSentinelRegion(
			currentNotes: '',
			additionalBases: ['Basis A', 'Basis B', 'Basis C']
		);

		$shrunk = $this->helper->updateSentinelRegion(
			currentNotes: $withSentinel,
			additionalBases: []
		);

		$this->assertSame(expected: '', actual: $shrunk, message: 'Empty input with zero bases should result in empty string.');

	}//end testShrinkToZeroRemovesSentinelRegion()

	/**
	 * Notes with no sentinel are returned unchanged by stripSentinelRegion.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-7
	 */
	public function testStripNoopWhenNoSentinelPresent(): void {
		$notes = 'Just operator notes, no sentinel here.';
		$result = $this->helper->stripSentinelRegion(notes: $notes);
		$this->assertSame(expected: $notes, actual: $result);

	}//end testStripNoopWhenNoSentinelPresent()

	/**
	 * Multi-line sentinel block is fully stripped by stripSentinelRegion.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-7
	 */
	public function testStripRemovesMultiLineSentinelBlock(): void {
		$notes = "Operator text.\n\n"
			. ConsentNotesHelper::SENTINEL_BEGIN . "\n"
			. "**Aanvullende publicatiegrondslagen:**\n"
			. "- Basis A\n"
			. "- Basis B\n"
			. ConsentNotesHelper::SENTINEL_END;

		$result = $this->helper->stripSentinelRegion(notes: $notes);

		$this->assertStringNotContainsString(needle: ConsentNotesHelper::SENTINEL_BEGIN, haystack: $result);
		$this->assertStringNotContainsString(needle: ConsentNotesHelper::SENTINEL_END, haystack: $result);
		$this->assertStringContainsString(needle: 'Operator text.', haystack: $result);

	}//end testStripRemovesMultiLineSentinelBlock()

	// ------------------------------------------------------------------
	// truncateAtWordBoundary — Task 4
	// ------------------------------------------------------------------

	/**
	 * String shorter than maxLength is returned unchanged.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-4
	 */
	public function testTruncateShortStringUnchanged(): void {
		$value = 'Short string';
		$result = $this->helper->truncateAtWordBoundary(value: $value);
		$this->assertSame(expected: $value, actual: $result);

	}//end testTruncateShortStringUnchanged()

	/**
	 * String exactly at maxLength is returned unchanged.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-4
	 */
	public function testTruncateExactlyAtMaxLengthUnchanged(): void {
		$value = str_repeat(string: 'A', times: ConsentNotesHelper::LEGAL_BASIS_MAX_LENGTH);
		$result = $this->helper->truncateAtWordBoundary(value: $value);
		$this->assertSame(expected: $value, actual: $result);

	}//end testTruncateExactlyAtMaxLengthUnchanged()

	/**
	 * Long string is truncated at word boundary, not mid-word.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-4
	 */
	public function testTruncateLongStringAtWordBoundary(): void {
		// Build a string slightly over 500 chars with spaces to allow word-boundary truncation.
		$value = str_repeat(string: 'word ', times: 110);
		// 110 * 5 = 550 chars
		$result = $this->helper->truncateAtWordBoundary(value: $value);

		$this->assertLessThanOrEqual(
			expected: ConsentNotesHelper::LEGAL_BASIS_MAX_LENGTH,
			actual: mb_strlen(string: $result),
			message: 'Truncated string must not exceed LEGAL_BASIS_MAX_LENGTH.'
		);
		// The result must end at a word boundary: it ends with a complete 'word' token.
		// str_repeat('word ', 110) produces full 'word' tokens separated by spaces,
		// so the trimmed result must end with 'word' (not ' word' or 'wor').
		$trimmed = rtrim($result);
		$this->assertMatchesRegularExpression(
			pattern: '/word$/',
			string: $trimmed,
			message: 'Truncation must end at a complete word boundary.'
		);

	}//end testTruncateLongStringAtWordBoundary()
}//end class
