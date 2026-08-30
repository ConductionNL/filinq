<?php

/**
 * Unit tests for translatable-value flattening in PolicyRuleNormaliser.
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

use OCA\Filinq\Service\PolicyRuleNormaliser;
use PHPUnit\Framework\TestCase;

/**
 * `publicationProhibition.primaryName` is declared `translatable: true`, so
 * OpenRegister stores it as a language-keyed map, not a bare string. The
 * matcher reads rows through `searchObjectsBySlug()` — bypassing the
 * TranslationHandler that resolves the map on the HTTP read path — so the raw
 * map reached a `(string)` cast and every consumer got the literal "Array":
 * the prohibition rejection's `ruleName`, and the `ruleName` that
 * `anonymisation-prohibition-gate` requires on the anonymise gate's 422 body.
 *
 * Newman caught it as `expected 'Array' to equal 'Newman Detect Prohibition
 * Subject'` once the rejection started returning the rule identity at all.
 *
 * Verified to FAIL ('Array') without flattenTranslatable().
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PolicyRuleNormaliserTranslatableTest extends TestCase {

	/**
	 * The normaliser under test.
	 *
	 * @var PolicyRuleNormaliser
	 */
	private PolicyRuleNormaliser $normaliser;

	/**
	 * Build the normaliser.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->normaliser = new PolicyRuleNormaliser();

	}//end setUp()

	/**
	 * Build a minimal admissible prohibition row around one primaryName value.
	 *
	 * @param mixed $primaryName The stored primaryName (string or language map).
	 *
	 * @return array<string, mixed>
	 */
	private function row(mixed $primaryName): array {
		return [
			'id' => 'R-PROHIBIT-1',
			'active' => true,
			'entityType' => 'PERSON',
			'primaryName' => $primaryName,
			'matchRules' => [['type' => 'exact', 'value' => 'Jansen']],
		];
	}//end row()

	/**
	 * A language-keyed primaryName is flattened to a display string.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/entity-publication-policies/spec.md
	 */
	public function testLanguageKeyedPrimaryNameIsFlattened(): void {
		$rule = $this->normaliser->normaliseRule(
			kind: 'prohibition',
			object: $this->row(['en' => 'Newman Detect Prohibition Subject'])
		);

		$this->assertIsArray($rule);
		$this->assertSame(
			expected: 'Newman Detect Prohibition Subject',
			actual: $rule['primaryName'],
			message: 'A translatable primaryName must not be cast to the string "Array".'
		);

	}//end testLanguageKeyedPrimaryNameIsFlattened()

	/**
	 * Dutch wins over English when both are stored.
	 *
	 * @return void
	 */
	public function testDutchIsPreferredOverEnglish(): void {
		$rule = $this->normaliser->normaliseRule(
			kind: 'prohibition',
			object: $this->row(['en' => 'Protected witness', 'nl' => 'Beschermde getuige'])
		);

		$this->assertIsArray($rule);
		$this->assertSame('Beschermde getuige', $rule['primaryName']);

	}//end testDutchIsPreferredOverEnglish()

	/**
	 * An unexpected locale key still yields a usable string.
	 *
	 * @return void
	 */
	public function testAnUnknownLocaleFallsBackToTheFirstAvailableValue(): void {
		$rule = $this->normaliser->normaliseRule(
			kind: 'prohibition',
			object: $this->row(['de' => 'Geschützter Zeuge'])
		);

		$this->assertIsArray($rule);
		$this->assertSame('Geschützter Zeuge', $rule['primaryName']);

	}//end testAnUnknownLocaleFallsBackToTheFirstAvailableValue()

	/**
	 * A plain string is still passed through unchanged.
	 *
	 * Positive control: without it, a flattener that always returned '' or a
	 * fixed value would pass the tests above.
	 *
	 * @return void
	 */
	public function testAPlainStringPrimaryNameIsUnchanged(): void {
		$rule = $this->normaliser->normaliseRule(
			kind: 'prohibition',
			object: $this->row('Politiemedewerker undercover')
		);

		$this->assertIsArray($rule);
		$this->assertSame('Politiemedewerker undercover', $rule['primaryName']);

	}//end testAPlainStringPrimaryNameIsUnchanged()
}//end class
