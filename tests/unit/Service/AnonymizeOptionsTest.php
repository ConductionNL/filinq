<?php
/**
 * Unit tests for AnonymizeOptions.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizeOptions;
use PHPUnit\Framework\TestCase;

/**
 * The defaults, each `with` copy, and that no copy changes the instance it came from.
 *
 * @covers \OCA\Filinq\Service\AnonymizeOptions
 * @uses   \OCA\Filinq\Service\AnonymizeRun
 *
 * @spec openspec/specs/anonymization/spec.md
 */
class AnonymizeOptionsTest extends TestCase {

	private const DEFAULTS = [
		'appendBasisSummary' => false,
		'outputFormat' => 'pdf-only',
		'unredactedEntities' => [],
		'scope' => 'document',
		'dossierKey' => null,
		'reversible' => false,
		'userId' => '',
	];

	/**
	 * A fresh instance runs with the defaults and no overrides.
	 *
	 * @return void
	 */
	public function testDefaults(): void {
		$run = (new AnonymizeOptions())->runFor(fileId: 5, entities: [['text' => 'Jan']]);

		$this->assertSame(5, $run->fileId);
		$this->assertSame([['text' => 'Jan']], $run->entities);
		$this->assertSame([], $run->overrides);
		$this->assertSame(self::DEFAULTS, $run->options);

	}//end testDefaults()

	/**
	 * Every `with` method sets its own fields and leaves the original as it was.
	 *
	 * @return void
	 */
	public function testEachCopyChangesOnlyItself(): void {
		$base = new AnonymizeOptions();
		$changed = $base
			->withOutputFormat(outputFormat: 'preserve')
			->withConsents(unredactedEntities: [['text' => 'Gemeente']], overrides: [['ruleId' => 'r1', 'entityId' => 'e1']])
			->withUserId(userId: 'noor')
			->withScope(scope: 'dossier', dossierKey: 'folder-3')
			->reversible();

		$run = $changed->runFor(fileId: 8, entities: []);
		$this->assertSame([['ruleId' => 'r1', 'entityId' => 'e1']], $run->overrides);
		$this->assertSame(
			[
				'appendBasisSummary' => false,
				'outputFormat' => 'preserve',
				'unredactedEntities' => [['text' => 'Gemeente']],
				'scope' => 'dossier',
				'dossierKey' => 'folder-3',
				'reversible' => true,
				'userId' => 'noor',
			],
			$run->options
		);
		$this->assertSame(self::DEFAULTS, $base->runFor(fileId: 8, entities: [])->options);
		$this->assertSame([], $base->runFor(fileId: 8, entities: [])->overrides);

	}//end testEachCopyChangesOnlyItself()

	/**
	 * Each `with` method, called on its own, returns a copy and leaves its source at the defaults.
	 *
	 * @return void
	 */
	public function testNoCopyChangesItsSource(): void {
		$base = new AnonymizeOptions();
		$copies = [
			$base->withOutputFormat(outputFormat: 'pdf'),
			$base->withConsents(unredactedEntities: [['text' => 'x']], overrides: [['ruleId' => 'r']]),
			$base->withUserId(userId: 'noor'),
			$base->withScope(scope: 'dossier', dossierKey: 'k'),
			$base->reversible(),
		];

		foreach ($copies as $copy) {
			$this->assertNotSame($base, $copy);
		}

		$this->assertSame(self::DEFAULTS, $base->runFor(fileId: 1, entities: [])->options);
		$this->assertSame([], $base->runFor(fileId: 1, entities: [])->overrides);

	}//end testNoCopyChangesItsSource()

	/**
	 * The summary run appends the summary, and the options it came from still do not.
	 *
	 * @return void
	 */
	public function testTheSummaryRunLeavesThePlainRunAlone(): void {
		$options = (new AnonymizeOptions())->withUserId(userId: 'anna');

		$summary = $options->summaryRunFor(fileId: 9, entities: [['text' => 'Jan']]);

		$this->assertSame(9, $summary->fileId);
		$this->assertSame([['text' => 'Jan']], $summary->entities);
		$this->assertTrue($summary->options['appendBasisSummary']);
		$this->assertSame('anna', $summary->options['userId']);
		$this->assertFalse($options->runFor(fileId: 9, entities: [])->options['appendBasisSummary']);

	}//end testTheSummaryRunLeavesThePlainRunAlone()
}//end class
