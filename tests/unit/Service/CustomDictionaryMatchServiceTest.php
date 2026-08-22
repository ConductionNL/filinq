<?php

/**
 * Unit tests for CustomDictionaryMatchService — custom-dictionary-recognition
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
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\CustomDictionaryMatchService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the pure exact / caseInsensitive / wordBoundary matcher.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
class CustomDictionaryMatchServiceTest extends TestCase {

	/**
	 * The service under test.
	 *
	 * @var CustomDictionaryMatchService
	 */
	private CustomDictionaryMatchService $service;

	/**
	 * Set up a fresh matcher before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->service = new CustomDictionaryMatchService();

	}//end setUp()

	/**
	 * `exact` mode is case-sensitive: a differently-cased occurrence is not matched.
	 *
	 * @return void
	 */
	public function testExactModeIsCaseSensitive(): void {
		$result = $this->service->match(
			text: 'Operatie Zilverreiger is geheim. operatie zilverreiger niet.',
			terms: [['value' => 'Operatie Zilverreiger']],
			mode: CustomDictionaryMatchService::MODE_EXACT
		);

		$this->assertCount(1, $result);
		$this->assertSame('Operatie Zilverreiger', $result[0]['value']);

	}//end testExactModeIsCaseSensitive()

	/**
	 * `caseInsensitive` mode returns both differently-cased occurrences with
	 * correct positions (REQ-DDCDR-002 scenario).
	 *
	 * @return void
	 */
	public function testCaseInsensitiveModeReturnsAllOccurrences(): void {
		$text = 'Dossier Zilverreiger en dossier zilverreiger zijn hetzelfde project.';

		$result = $this->service->match(
			text: $text,
			terms: [['value' => 'Zilverreiger']],
			mode: CustomDictionaryMatchService::MODE_CASE_INSENSITIVE
		);

		$this->assertCount(2, $result);
		$this->assertSame('Zilverreiger', $result[0]['value']);
		$this->assertSame('zilverreiger', $result[1]['value']);

		foreach ($result as $occurrence) {
			$found = substr($text, $occurrence['positionStart'], ($occurrence['positionEnd'] - $occurrence['positionStart']));
			$this->assertSame($occurrence['value'], $found, 'positions must point at the literal match');
		}

	}//end testCaseInsensitiveModeReturnsAllOccurrences()

	/**
	 * `wordBoundary` mode does not match a term inside a longer word
	 * (REQ-DDCDR-002 scenario: "Berg" does not match inside "Bergen").
	 *
	 * @return void
	 */
	public function testWordBoundaryDoesNotMatchInsideWord(): void {
		$result = $this->service->match(
			text: 'De inwoners van Bergen protesteerden',
			terms: [['value' => 'Berg']],
			mode: CustomDictionaryMatchService::MODE_WORD_BOUNDARY
		);

		$this->assertSame([], $result);

	}//end testWordBoundaryDoesNotMatchInsideWord()

	/**
	 * `wordBoundary` mode still matches the term when it IS a whole word.
	 *
	 * @return void
	 */
	public function testWordBoundaryMatchesWholeWord(): void {
		$result = $this->service->match(
			text: 'De Berg was zichtbaar vanaf het dorp.',
			terms: [['value' => 'Berg']],
			mode: CustomDictionaryMatchService::MODE_WORD_BOUNDARY
		);

		$this->assertCount(1, $result);
		$this->assertSame('Berg', $result[0]['value']);

	}//end testWordBoundaryMatchesWholeWord()

	/**
	 * Blank / whitespace-only terms are skipped entirely.
	 *
	 * @return void
	 */
	public function testBlankTermsAreSkipped(): void {
		$result = $this->service->match(
			text: 'Some text with Operatie Zilverreiger in it.',
			terms: [
				['value' => ''],
				['value' => '   '],
				['value' => 'Operatie Zilverreiger'],
			],
			mode: CustomDictionaryMatchService::MODE_CASE_INSENSITIVE
		);

		$this->assertCount(1, $result);
		$this->assertSame('Operatie Zilverreiger', $result[0]['value']);

	}//end testBlankTermsAreSkipped()

	/**
	 * Overlapping terms are resolved longest-term-first: a shorter term
	 * cannot pre-empt a longer one at the same position.
	 *
	 * @return void
	 */
	public function testLongestTermWinsOverlap(): void {
		$result = $this->service->match(
			text: 'Dossier Karekiet is een geheim dossier.',
			terms: [
				['value' => 'Karekiet', 'label' => 'short'],
				['value' => 'Dossier Karekiet', 'label' => 'long'],
			],
			mode: CustomDictionaryMatchService::MODE_CASE_INSENSITIVE
		);

		$this->assertCount(1, $result);
		$this->assertSame('Dossier Karekiet', $result[0]['value']);
		$this->assertSame('long', $result[0]['label']);

	}//end testLongestTermWinsOverlap()

	/**
	 * A term's label defaults to its own value when no label is supplied.
	 *
	 * @return void
	 */
	public function testLabelDefaultsToTermValue(): void {
		$result = $this->service->match(
			text: 'Contains Zonnebloem here.',
			terms: [['value' => 'Zonnebloem']],
			mode: CustomDictionaryMatchService::MODE_CASE_INSENSITIVE
		);

		$this->assertCount(1, $result);
		$this->assertSame('Zonnebloem', $result[0]['label']);

	}//end testLabelDefaultsToTermValue()

	/**
	 * An empty text or empty term list yields no occurrences without error.
	 *
	 * @return void
	 */
	public function testEmptyInputsYieldNoOccurrences(): void {
		$this->assertSame([], $this->service->match(text: '', terms: [['value' => 'x']], mode: 'caseInsensitive'));
		$this->assertSame([], $this->service->match(text: 'some text', terms: [], mode: 'caseInsensitive'));

	}//end testEmptyInputsYieldNoOccurrences()

	/**
	 * An unrecognised mode falls back to caseInsensitive rather than erroring.
	 *
	 * @return void
	 */
	public function testUnknownModeFallsBackToCaseInsensitive(): void {
		$result = $this->service->match(
			text: 'ZILVERREIGER staat hier.',
			terms: [['value' => 'zilverreiger']],
			mode: 'not-a-real-mode'
		);

		$this->assertCount(1, $result);

	}//end testUnknownModeFallsBackToCaseInsensitive()

	/**
	 * Occurrences are returned in document order (positionStart ascending),
	 * even when the underlying terms are matched out of order internally
	 * (longest-first pass).
	 *
	 * @return void
	 */
	public function testOccurrencesAreReturnedInDocumentOrder(): void {
		$result = $this->service->match(
			text: 'Barendrecht ligt naast Ridderkerk, niet naast Barendrecht zelf.',
			terms: [
				['value' => 'Ridderkerk'],
				['value' => 'Barendrecht'],
			],
			mode: CustomDictionaryMatchService::MODE_CASE_INSENSITIVE
		);

		$this->assertCount(3, $result);
		$positions = array_column($result, 'positionStart');
		$sorted = $positions;
		sort($sorted);
		$this->assertSame($sorted, $positions);

	}//end testOccurrencesAreReturnedInDocumentOrder()

	/**
	 * The matcher is a pure, deterministic function of its inputs: calling
	 * `match()` twice with identical arguments returns identical results.
	 * This is the property AnonymizationService's clear-before-rewrite
	 * catalogue pass relies on for re-run idempotency (REQ-DDCDR-003
	 * scenario: re-running detection does not duplicate relations) — the
	 * full DB-level idempotency (clear prior relations, then re-insert) is
	 * covered end-to-end by
	 * `AnonymizationServiceCustomDictionaryTest::testDictionaryHitWritesCatalogueAndClearsPriorRelationsOnly`.
	 *
	 * @return void
	 */
	public function testReRunDoesNotDuplicate(): void {
		$text = 'Het dossier over Operatie Zilverreiger is geheim.';
		$terms = [['value' => 'Operatie Zilverreiger']];

		$first = $this->service->match(text: $text, terms: $terms, mode: CustomDictionaryMatchService::MODE_CASE_INSENSITIVE);
		$second = $this->service->match(text: $text, terms: $terms, mode: CustomDictionaryMatchService::MODE_CASE_INSENSITIVE);

		$this->assertSame($first, $second);
		$this->assertCount(1, $first);

	}//end testReRunDoesNotDuplicate()
}//end class
