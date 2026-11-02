<?php

/**
 * Golden results of aggregating a dossier's per-file entities into the legal-bases summary rows.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\GrondslagenPdfWriter;
use OCA\Filinq\Service\LegalBasesSummaryService;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * Each dossier's rows and totals, compared with JSON recorded from the
 * implementation before aggregateForDossier() was split.
 *
 * @covers \OCA\Filinq\Service\LegalBasesSummaryService
 * @uses   \OCA\Filinq\Service\DossierSummaryDataService
 * @uses   \OCA\Filinq\Service\DossierEntityTally
 * @uses   \OCA\Filinq\Service\BaseLabelResolver
 * @uses   \OCA\Filinq\Service\DossierEntityCollector
 * @uses   \OCA\Filinq\Service\DossierObjectRepository
 * @uses   \OCA\Filinq\Service\DossierPlaceholderRanker
 */
class DossierBasesAggregateGoldenTest extends TestCase {

	/**
	 * The dossiers.
	 *
	 * @return array<string, array{0: array<int, mixed>}>
	 */
	public static function dossiers(): array {
		$jan = ['placeholder' => '[PERSOON 2]', 'entityType' => 'PERSON', 'entityId' => 2, 'count' => 3, 'bases' => ['5.1.2e'], 'baseLabels' => ['Privacy']];
		return [
			'no files' => [[]],
			'a file without entities' => [[['fileId' => 1, 'filename' => 'leeg.pdf']]],
			'one person in two files' => [[
				['fileId' => 1, 'filename' => 'b.pdf', 'entities' => [$jan]],
				['fileId' => 2, 'filename' => 'a.pdf', 'entities' => [['count' => 1, 'bases' => ['5.1.2e', '5.1.1c'], 'baseLabels' => ['Privacy', 'Bedrijfsgegevens']] + $jan]],
			]],
			'ids that sort numerically' => [[
				['fileId' => 1, 'filename' => 'x.pdf', 'entities' => [
					['placeholder' => '[PERSOON 10]', 'entityType' => 'PERSON', 'entityId' => 10, 'count' => 1],
					['placeholder' => '[DATUM 1]', 'entityType' => 'DATE', 'entityId' => 1, 'count' => 2, 'baseLabels' => 'Privacy'],
					['placeholder' => '[PERSOON 2]', 'entityType' => 'PERSON', 'entityId' => 2, 'count' => '4'],
				]],
			]],
			'a file without a name' => [[
				['fileId' => 1, 'entities' => [$jan]],
				['fileId' => 2, 'filename' => '', 'entities' => [$jan]],
			]],
			'an entity without type or id' => [[
				['fileId' => 1, 'filename' => 'c.pdf', 'entities' => [['count' => 1], ['placeholder' => '[X]', 'bases' => [7]]]],
			]],
		];
	}//end dossiers()

	/**
	 * The aggregate matches the recorded one.
	 *
	 * @param array<int, mixed> $perFile The per-file rows.
	 *
	 * @dataProvider dossiers
	 *
	 * @return void
	 */
	public function testAggregateMatchesTheRecordedOne(array $perFile): void {
		$service = new LegalBasesSummaryService(
			logger: new NullLogger(),
			pdfWriter: $this->createMock(GrondslagenPdfWriter::class),
			rootFolder: $this->createMock(IRootFolder::class),
			userSession: $this->createMock(IUserSession::class),
			appManager: $this->createMock(IAppManager::class),
			container: $this->createMock(ContainerInterface::class)
		);
		$result = (new ReflectionMethod(LegalBasesSummaryService::class, 'aggregateForDossier'))->invoke($service, $perFile, []);

		$path = __DIR__ . '/golden/dossier-bases-aggregate.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $result;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $result);
	}//end testAggregateMatchesTheRecordedOne()
}//end class
