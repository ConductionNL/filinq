<?php

/**
 * Unit tests for PseudonymPairs
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Pseudonymisation;

use OCA\Filinq\Service\Pseudonymisation\PseudonymPairs;
use PHPUnit\Framework\TestCase;

/**
 * Placeholders joined to original values, and put back.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymPairsTest extends TestCase {

	/**
	 * The join goes value -> entity id -> placeholder, keeps the trimmed value, and
	 * leaves out an entity OpenRegister gave no stable placeholder.
	 *
	 * @return void
	 */
	public function testThePlaceholderIsTheKeyOpenRegisterEmitted(): void {
		$pairs = (new PseudonymPairs())->build(
			entities: [
				['text' => 'Jan Jansen ', 'entityType' => 'PERSON', 'key' => 'k1'],
				['text' => 'Dorpsstraat 1', 'entityType' => 'ADDRESS', 'key' => 'k2'],
				['text' => 'no row for me', 'entityType' => 'PERSON', 'key' => 'k3'],
			],
			idsByValue: [
				'Jan Jansen ' => ['id' => 41, 'type' => 'PERSON'],
				'Dorpsstraat 1' => ['id' => 77, 'type' => 'ADDRESS'],
			],
			placeholderMap: ['41' => '[PERSOON: 1]', '77' => '[ADRES: 1]']
		);

		$this->assertSame(
			[
				['placeholder' => '[PERSOON: 1]', 'originalValue' => 'Jan Jansen', 'entityType' => 'PERSON'],
				['placeholder' => '[ADRES: 1]', 'originalValue' => 'Dorpsstraat 1', 'entityType' => 'ADDRESS'],
			],
			$pairs
		);

	}//end testThePlaceholderIsTheKeyOpenRegisterEmitted()

	/**
	 * `[PERSOON: 1]` must not eat the start of `[PERSOON: 10]`, and a value that
	 * contains a placeholder is not replaced twice.
	 *
	 * @return void
	 */
	public function testReversalIsLongestFirstAndSinglePass(): void {
		$result = (new PseudonymPairs())->reverse(
			text: '[PERSOON: 10] schreef aan [PERSOON: 1]. [PERSOON: 1] antwoordde.',
			pairs: [
				['placeholder' => '[PERSOON: 1]', 'originalValue' => 'Jan [PERSOON: 10]'],
				['placeholder' => '[PERSOON: 10]', 'originalValue' => 'Marieke de Vries'],
			]
		);

		$this->assertSame('Marieke de Vries schreef aan Jan [PERSOON: 10]. Jan [PERSOON: 10] antwoordde.', $result['text']);
		$this->assertSame(3, $result['restored']);

	}//end testReversalIsLongestFirstAndSinglePass()
}//end class
