<?php

/**
 * Unit tests for the step that keeps or removes the key of an anonymise run
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
use PHPUnit\Framework\TestCase;

/**
 * Reversible keeps a key and points the link at it; irreversible keeps nothing
 * and removes a key an earlier run left.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymMapRecorderTest extends TestCase {
	use PseudonymDoubles;

	/**
	 * A run as the runner hands it over.
	 *
	 * @param bool $reversible The mode.
	 *
	 * @return array<string, mixed> The run.
	 */
	private function aRun(bool $reversible): array {
		return [
			'fileId' => 812200,
			'entities' => [
				['text' => 'Jan Jansen', 'entityType' => 'PERSON', 'key' => 'k1'],
				['text' => 'Marieke de Vries', 'entityType' => 'PERSON', 'key' => 'k2'],
			],
			'placeholderMap' => ['41' => '[PERSOON: 1]', '42' => '[PERSOON: 2]'],
			'reversible' => $reversible,
			'scope' => 'document',
			'userId' => 'alice',
		];

	}//end aRun()

	/**
	 * Seed the link recordAnonymizationLink() wrote and the entity rows OpenRegister has.
	 *
	 * @return array<string, mixed> The result as the runner has it after the link write.
	 */
	private function afterLinkWrite(): array {
		$this->rows['anonymizationLink']['link-1'] = ['sourceFileId' => 812200, 'anonymizedFileId' => 812201, 'runCount' => 1];
		$this->entityRows = [
			'Jan Jansen' => ['id' => 41, 'type' => 'PERSON'],
			'Marieke de Vries' => ['id' => 42, 'type' => 'PERSON'],
		];

		return ['anonymizedFileId' => 812201, 'anonymizationLinkId' => 'link-1'];

	}//end afterLinkWrite()

	/**
	 * A reversible run stores the key and the link points at it; runCount does not move.
	 *
	 * @return void
	 */
	public function testAReversibleRunKeepsItsKey(): void {
		$result = $this->recorder()->record(resultInfo: $this->afterLinkWrite(), run: $this->aRun(reversible: true));

		$this->assertSame(['reversible' => true, 'keyKept' => true, 'entryCount' => 2, 'mappingRef' => 'pseudonymMap-1'], $result['pseudonymisation']);
		$this->assertSame('link-1', $this->rows['pseudonymMap']['pseudonymMap-1']['anonymizationLink']);
		$this->assertSame('pseudonymMap-1', $this->rows['anonymizationLink']['link-1']['mappingRef']);
		$this->assertSame(1, $this->rows['anonymizationLink']['link-1']['runCount']);

	}//end testAReversibleRunKeepsItsKey()

	/**
	 * The default run stores nothing and adds nothing to the response.
	 *
	 * @return void
	 */
	public function testAnIrreversibleRunKeepsNothing(): void {
		$resultInfo = $this->afterLinkWrite();
		$result = $this->recorder()->record(resultInfo: $resultInfo, run: $this->aRun(reversible: false));

		$this->assertSame($resultInfo, $result);
		$this->assertSame([], ($this->rows['pseudonymMap'] ?? []));

	}//end testAnIrreversibleRunKeepsNothing()

	/**
	 * An irreversible run after a reversible one removes the old key and clears the pointer.
	 *
	 * @return void
	 */
	public function testAnIrreversibleRerunRemovesTheEarlierKey(): void {
		$this->recorder()->record(resultInfo: $this->afterLinkWrite(), run: $this->aRun(reversible: true));
		$this->assertCount(1, $this->rows['pseudonymMap']);

		$result = $this->recorder()->record(resultInfo: ['anonymizedFileId' => 812299, 'anonymizationLinkId' => 'link-1'], run: $this->aRun(reversible: false));

		$this->assertSame([], $this->rows['pseudonymMap']);
		$this->assertSame('', $this->rows['anonymizationLink']['link-1']['mappingRef']);
		$this->assertSame(['reversible' => false, 'previousKeyRemoved' => true], $result['pseudonymisation']);

	}//end testAnIrreversibleRerunRemovesTheEarlierKey()

	/**
	 * When OpenRegister gave no stable placeholder, the operator is told no key was kept.
	 *
	 * @return void
	 */
	public function testAReversibleRunWithoutPlaceholdersSaysSo(): void {
		$resultInfo = $this->afterLinkWrite();
		$this->entityRows = [];

		$result = $this->recorder()->record(resultInfo: $resultInfo, run: $this->aRun(reversible: true));

		$this->assertSame(['reversible' => true, 'keyKept' => false, 'reason' => 'no_placeholders'], $result['pseudonymisation']);
		$this->assertSame([], ($this->rows['pseudonymMap'] ?? []));

	}//end testAReversibleRunWithoutPlaceholdersSaysSo()
}//end class
