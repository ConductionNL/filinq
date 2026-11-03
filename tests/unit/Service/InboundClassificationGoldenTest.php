<?php

/**
 * Golden cases of what inbound classification asks and saves.
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

use OCA\Filinq\Service\Classification\ClassificationResultRepository;
use OCA\Filinq\Service\Classification\ClassificationSources;
use OCA\Filinq\Service\Classification\CorrespondentRanker;
use OCA\Filinq\Service\Classification\DossierMatcher;
use OCA\Filinq\Service\DocumentTypeClassifier;
use OCA\Filinq\Service\InboundClassificationService;
use OCA\Filinq\Service\Support\ClassificationMatchers;
use OCA\Filinq\Tests\Unit\Support\RecordsCalls;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The calls classify() makes and the record it saves, compared with JSON
 * recorded from the service whose suggestion builder took five parameters.
 *
 * @covers \OCA\Filinq\Service\InboundClassificationService
 * @uses   \OCA\Filinq\Service\Support\ClassificationMatchers
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
 */
class InboundClassificationGoldenTest extends TestCase {
	use RecordsCalls;

	private const GOLDEN = __DIR__.'/golden/inbound-classification.json';

	/**
	 * The cases: the object fields, the active record, the entities and the ranked correspondent.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: ?array<string, mixed>, 2: ?array<int, array<string, mixed>>, 3: ?array<string, mixed>}>
	 */
	public static function cases(): array {
		$intake   = ['@self' => ['schema' => 'intakeDocument'], 'channel' => 'email', 'fileId' => '17', 'fileName' => 'brief.pdf', 'subject' => 'Zaak Z-12', 'contentText' => '  Geachte heer  '];
		$entities = [['type' => 'PERSON', 'text' => 'Jan Jansen']];
		$jan      = ['name' => 'Jan Jansen', 'confidence' => 0.8];
		$pending  = ['uuid' => 'a-1', 'status' => 'suggested', 'correspondentPending' => true];
		return [
			'a new inbound document with entities'  => [$intake, null, $entities, $jan],
			'entities not known yet'                => [$intake, null, null, null],
			'no correspondent ranked'               => [$intake, null, $entities, null],
			'a pending suggestion is superseded'    => [$intake, $pending, $entities, $jan],
			'an active suggestion stays'            => [$intake, ['uuid' => 'a-2', 'status' => 'suggested', 'correspondentPending' => false], $entities, $jan],
			'a file id under file, name fallback'   => [['file' => 9, 'name' => 'scan.png', 'text' => 'Factuur'] + $intake, null, $entities, $jan],
			'no file'                               => [['fileId' => 'x'] + $intake, null, $entities, $jan],
			'no text'                               => [['contentText' => ' ', 'subject' => ''] + $intake, null, $entities, $jan],
			'the subject is the only text'          => [['contentText' => ''] + $intake, null, null, null],
			'not inbound'                           => [['channel' => ''] + $intake, null, $entities, $jan],
		];

	}//end cases()

	/**
	 * The calls and the outcome match the recorded ones.
	 *
	 * @param array<string, mixed>                  $data          The object fields.
	 * @param array<string, mixed>|null             $active        The active record.
	 * @param array<int, array<string, mixed>>|null $entities      The detected entities.
	 * @param array<string, mixed>|null             $correspondent The ranked correspondent.
	 *
	 * @dataProvider cases
	 *
	 * @return void
	 */
	public function testClassify(array $data, ?array $active, ?array $entities, ?array $correspondent): void {
		$service = new InboundClassificationService(
			classifier: $this->recorder(DocumentTypeClassifier::class, ['classify' => ['type' => 'brief', 'confidence' => 0.6]]),
			matchers: new ClassificationMatchers(
				ranker: $this->recorder(CorrespondentRanker::class, ['rank' => $correspondent]),
				matcher: $this->recorder(DossierMatcher::class, ['match' => 'd-1'])
			),
			results: $this->recorder(
				ClassificationResultRepository::class,
				['activeFor' => $active, 'save' => static fn (array $record): array => $record + ['uuid' => 'new']]
			),
			sources: $this->recorder(ClassificationSources::class, ['entitiesFor' => $entities, 'dossiers' => [['id' => 'd-1']]]),
			appConfig: $this->recorder(IAppConfig::class, ['getValueString' => '1']),
			logger: $this->recorder(LoggerInterface::class, ['info' => null])
		);
		$this->traceCall(fn () => $service->classify(objectData: $data, objectRef: ['id' => 'o-1', 'register' => 'filinq', 'schema' => 'intakeDocument']));

		$this->assertTrace(file: self::GOLDEN, name: (string) $this->dataName());

	}//end testClassify()
}//end class
