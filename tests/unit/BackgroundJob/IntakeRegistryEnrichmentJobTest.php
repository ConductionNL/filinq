<?php

/**
 * The registry enrichment job stores what the lookups answered on the
 * document, leaves a document alone when there is nothing to store, and
 * never fails the arrival when the lookups do.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\IntakeRegistryEnrichmentJob;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Service\RegistryEnrichmentService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\BackgroundJob\IntakeRegistryEnrichmentJob
 */
class IntakeRegistryEnrichmentJobTest extends TestCase {

	/**
	 * The documents saved, with their uuid.
	 *
	 * @var array<int, array{0: array<string, mixed>, 1: string|null}>
	 */
	private array $saved = [];

	/**
	 * Run the job for `intake-1` over the given document and enrichment.
	 *
	 * @param array<string, mixed>|null $document   The stored document, or null when it is gone.
	 * @param RegistryEnrichmentService $enrichment The lookups.
	 * @param LoggerInterface|null      $logger     The logger, or a silent one.
	 *
	 * @return void
	 */
	private function runJob(?array $document, RegistryEnrichmentService $enrichment, ?LoggerInterface $logger = null): void {
		$repository = $this->createMock(IntakeRepositoryInterface::class);
		$repository->method('findByUuid')->with('intake-1')->willReturn($document);
		$repository->method('save')->willReturnCallback(
			function (array $saved, ?string $uuid = null): array {
				$this->saved[] = [$saved, $uuid];

				return $saved;
			}
		);

		$job = new IntakeRegistryEnrichmentJob(
			$this->createMock(ITimeFactory::class),
			$repository,
			$enrichment,
			($logger ?? $this->createMock(LoggerInterface::class))
		);

		(new \ReflectionMethod($job, 'run'))->invoke($job, ['uuid' => 'intake-1']);

	}//end runJob()

	/**
	 * An enrichment answering the given outcome.
	 *
	 * @param array<string, mixed>|null $outcome The outcome, or null to throw.
	 *
	 * @return RegistryEnrichmentService The double.
	 */
	private function enrichment(?array $outcome): RegistryEnrichmentService {
		$enrichment = $this->createMock(RegistryEnrichmentService::class);
		if ($outcome === null) {
			$enrichment->method('enrich')->willThrowException(new RuntimeException('lookup broke on 111222333'));
		} else {
			$enrichment->method('enrich')->willReturn($outcome);
		}

		return $enrichment;

	}//end enrichment()

	/**
	 * The suggestions and the note are stored on the document, the rest of it untouched.
	 *
	 * @return void
	 */
	public function testTheOutcomeIsStoredOnTheDocument(): void {
		$suggestion = ['source' => 'kvk', 'identifier' => '12345678', 'fields' => ['name' => 'Voorbeeld B.V.'], 'written' => false];
		$this->runJob(
			document: ['uuid' => 'intake-1', 'status' => 'received', 'partySuggestion' => [['party' => ['name' => 'X']]]],
			enrichment: $this->enrichment(outcome: ['registrySuggestion' => [$suggestion], 'registryLookupNote' => ''])
		);

		$this->assertSame(
			[
				[
					[
						'uuid' => 'intake-1',
						'status' => 'received',
						'partySuggestion' => [['party' => ['name' => 'X']]],
						'registrySuggestion' => [$suggestion],
						'registryLookupNote' => '',
					],
					'intake-1',
				],
			],
			$this->saved
		);

	}//end testTheOutcomeIsStoredOnTheDocument()

	/**
	 * A note without suggestions is stored too.
	 *
	 * @return void
	 */
	public function testANoteAloneIsStored(): void {
		$this->runJob(
			document: ['uuid' => 'intake-1'],
			enrichment: $this->enrichment(outcome: ['registrySuggestion' => [], 'registryLookupNote' => RegistryEnrichmentService::NOTE_KVK_NOT_CONFIGURED])
		);

		$this->assertSame(RegistryEnrichmentService::NOTE_KVK_NOT_CONFIGURED, $this->saved[0][0]['registryLookupNote']);

	}//end testANoteAloneIsStored()

	/**
	 * Nothing to store, a document that is gone, or a lookup that throws: nothing is saved.
	 *
	 * @return void
	 */
	public function testNothingIsSavedWhenThereIsNothingToStore(): void {
		$this->runJob(document: ['uuid' => 'intake-1'], enrichment: $this->enrichment(outcome: ['registrySuggestion' => [], 'registryLookupNote' => '']));
		$this->runJob(document: null, enrichment: $this->enrichment(outcome: ['registrySuggestion' => [], 'registryLookupNote' => 'x']));

		$logged = [];
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')->willReturnCallback(
			static function (string $message, array $context = []) use (&$logged): void {
				$logged[] = $message . json_encode($context);
			}
		);
		$this->runJob(document: ['uuid' => 'intake-1'], enrichment: $this->enrichment(outcome: null), logger: $logger);

		$this->assertSame([], $this->saved);
		$this->assertCount(1, $logged);
		$this->assertStringNotContainsString('111222333', $logged[0]);

	}//end testNothingIsSavedWhenThereIsNothingToStore()
}//end class
