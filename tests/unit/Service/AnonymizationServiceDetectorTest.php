<?php

/**
 * An anonymisation run with no live detector is refused, and a run that goes
 * ahead names the backend that looked.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-2
 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\DetectionUnavailableException;
use OCA\Filinq\Service\AnonymiserBackendStateClient;
use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\DocumentAnonymizeRunner;
use OCA\OpenRegister\Service\Anonymisation\BackendState;
use PHPUnit\Framework\TestCase;

/**
 * The detector gate on AnonymizationService::runAnonymize().
 */
class AnonymizationServiceDetectorTest extends TestCase {

	use BuildsAnonymizationService;

	private const FOUR = [
		['entityType' => 'PERSON', 'text' => 'Fatima El-Amrani', 'start' => 120, 'end' => 136],
		['entityType' => 'BSN', 'text' => '111222333', 'start' => 200, 'end' => 209],
		['entityType' => 'EMAIL', 'text' => 'fatima@example.nl', 'start' => 240, 'end' => 257],
		['entityType' => 'PHONE', 'text' => '0612345678', 'start' => 300, 'end' => 310],
	];

	/**
	 * Detection off means no file: the runner, which writes it, never runs.
	 *
	 * @return void
	 */
	public function testDetectionOffMeansNoFile(): void {
		$service = $this->serviceOver(
			state: DetectionStates::orState(enabled: false, active: 'regex', effective: 'regex'),
			runner: $this->runnerThatMustNotRun()
		);

		try {
			$service->anonymizeDocument(fileId: 42, entities: self::FOUR);
			$this->fail('A run with recognition switched off was not refused.');
		} catch (DetectionUnavailableException $e) {
			$this->assertSame(AnonymiserBackendStateClient::REFUSE_DISABLED, $e->getReason());
			$this->assertStringContainsString('disabled', $e->getMessage());
		}

	}//end testDetectionOffMeansNoFile()

	/**
	 * An unknown backend state is refused, not assumed.
	 *
	 * @return void
	 */
	public function testAnUnknownStateIsRefused(): void {
		$service = $this->serviceOver(state: null, runner: $this->runnerThatMustNotRun());

		$this->expectException(DetectionUnavailableException::class);

		$service->anonymizeDocumentWithBasisSummary(fileId: 42, entities: self::FOUR);

	}//end testAnUnknownStateIsRefused()

	/**
	 * An effective backend that is down is refused.
	 *
	 * @return void
	 */
	public function testAnUnavailableBackendIsRefused(): void {
		$service = $this->serviceOver(
			state: DetectionStates::orState(
				enabled: true,
				active: 'hybrid',
				effective: 'hybrid',
				available: ['regex' => true, 'hybrid' => false]
			),
			runner: $this->runnerThatMustNotRun()
		);

		try {
			$service->anonymizeDocument(fileId: 42, entities: self::FOUR);
			$this->fail('A run on an unavailable backend was not refused.');
		} catch (DetectionUnavailableException $e) {
			$this->assertSame(AnonymiserBackendStateClient::REFUSE_UNAVAILABLE, $e->getReason());
			$this->assertSame('hybrid', $e->getBackend());
		}

	}//end testAnUnavailableBackendIsRefused()

	/**
	 * The mutation check: the same document with the detector back on runs.
	 * A live backend that found nothing still writes the file and says so.
	 *
	 * @return void
	 */
	public function testALiveBackendThatFoundNothingStillWritesAndSaysSo(): void {
		$service = $this->serviceOver(
			state: DetectionStates::orState(
				enabled: true,
				active: 'presidio',
				effective: 'presidio',
				available: ['presidio' => true]
			),
			runner: $this->runnerThatWrites()
		);

		$result = $service->anonymizeDocument(fileId: 42, entities: []);

		$this->assertSame(99, $result['anonymizedFileId']);
		$this->assertSame(
			[
				'ran' => true,
				'backend' => 'presidio',
				'entitiesRedacted' => 0,
				'outcome' => 'nothing_found',
			],
			$result['detection']
		);

	}//end testALiveBackendThatFoundNothingStillWritesAndSaysSo()

	/**
	 * A redacting run names its backend too.
	 *
	 * @return void
	 */
	public function testARedactingRunNamesItsBackend(): void {
		$service = $this->serviceOver(
			state: DetectionStates::orState(enabled: true, active: 'presidio', effective: 'regex'),
			runner: $this->runnerThatWrites()
		);

		$result = $service->anonymizeDocument(fileId: 42, entities: self::FOUR);

		$this->assertSame('regex', $result['detection']['backend']);
		$this->assertSame(4, $result['detection']['entitiesRedacted']);
		$this->assertSame('redacted', $result['detection']['outcome']);

	}//end testARedactingRunNamesItsBackend()

	/**
	 * Build the service over a given OpenRegister state.
	 *
	 * @param BackendState|null $state The state, null when OpenRegister cannot answer.
	 * @param DocumentAnonymizeRunner $runner The runner.
	 *
	 * @return AnonymizationService The service.
	 */
	private function serviceOver(?BackendState $state, DocumentAnonymizeRunner $runner): AnonymizationService {
		return $this->makeAnonymizationServiceFrom(
			deps: [
				'anonymizeRunner' => $runner,
				'backendState' => DetectionStates::clientOver($state),
			]
		);

	}//end serviceOver()

	/**
	 * A runner that must never be reached.
	 *
	 * @return DocumentAnonymizeRunner The runner double.
	 */
	private function runnerThatMustNotRun(): DocumentAnonymizeRunner {
		$runner = $this->getMockBuilder(DocumentAnonymizeRunner::class)
			->disableOriginalConstructor()
			->onlyMethods(['run'])
			->getMock();
		$runner->expects($this->never())->method('run');

		return $runner;

	}//end runnerThatMustNotRun()

	/**
	 * A runner that writes file 99.
	 *
	 * @return DocumentAnonymizeRunner The runner double.
	 */
	private function runnerThatWrites(): DocumentAnonymizeRunner {
		$runner = $this->getMockBuilder(DocumentAnonymizeRunner::class)
			->disableOriginalConstructor()
			->onlyMethods(['run'])
			->getMock();
		$runner->expects($this->once())->method('run')->willReturn(['anonymizedFileId' => 99, 'replacementCount' => 4]);

		return $runner;

	}//end runnerThatWrites()
}//end class
