<?php

/**
 * The API answers a refused run with the reason, and a run that went ahead
 * with the backend that looked.
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
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizeRequestService;
use OCA\Filinq\Service\DocumentAnonymizeRunner;
use OCA\OpenRegister\Service\Anonymisation\BackendState;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * AnonymizeRequestService::executeAnonymize() over the detector gate.
 */
class AnonymizeRequestServiceDetectorTest extends TestCase {

	use BuildsAnonymizationService;

	private const REQUEST = [
		'entities' => [],
		'unredactedEntities' => [],
		'overrides' => [],
		'appendBasisSummary' => false,
		'hasStrayBases' => false,
	];

	/**
	 * Detection off: 503, the reason by name, and the runner never ran.
	 *
	 * @return void
	 */
	public function testARefusedRunAnswersWithTheReason(): void {
		$runner = $this->getMockBuilder(DocumentAnonymizeRunner::class)
			->disableOriginalConstructor()
			->onlyMethods(['run'])
			->getMock();
		$runner->expects($this->never())->method('run');

		$response = $this->requestServiceOver(
			state: DetectionStates::orState(enabled: false, active: 'regex', effective: 'regex'),
			runner: $runner
		)->executeAnonymize(fileId: 42, userId: 'noor', params: [], request: self::REQUEST, outputFormat: 'preserve');

		$this->assertSame(503, $response['status']);
		$this->assertSame('detection_disabled', $response['body']['detectionUnavailable']);
		$this->assertStringContainsString('detection is disabled', $response['body']['error']);

	}//end testARefusedRunAnswersWithTheReason()

	/**
	 * A clean document on a live backend: 200, and the backend that found nothing.
	 *
	 * @return void
	 */
	public function testACleanRunAnswersWithTheBackend(): void {
		$runner = $this->getMockBuilder(DocumentAnonymizeRunner::class)
			->disableOriginalConstructor()
			->onlyMethods(['run'])
			->getMock();
		$runner->method('run')->willReturn(['anonymizedFileId' => 99]);

		$response = $this->requestServiceOver(
			state: DetectionStates::orState(enabled: true, active: 'regex', effective: 'regex'),
			runner: $runner
		)->executeAnonymize(fileId: 42, userId: 'noor', params: [], request: self::REQUEST, outputFormat: 'preserve');

		$this->assertSame(200, $response['status']);
		$this->assertSame('nothing_found', $response['body']['detection']['outcome']);
		$this->assertSame('regex', $response['body']['detection']['backend']);

	}//end testACleanRunAnswersWithTheBackend()

	/**
	 * Build the request service over a real AnonymizationService.
	 *
	 * @param BackendState $state The OpenRegister state.
	 * @param DocumentAnonymizeRunner $runner The runner.
	 *
	 * @return AnonymizeRequestService The service.
	 */
	private function requestServiceOver(BackendState $state, DocumentAnonymizeRunner $runner): AnonymizeRequestService {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array $params = []): string => vsprintf($text, $params)
		);

		return new AnonymizeRequestService(
			logger: new NullLogger(),
			anonymizationService: $this->makeAnonymizationServiceFrom(
				deps: [
					'container' => $this->containerWithoutAPolicyMatcher(),
					'anonymizeRunner' => $runner,
					'backendState' => DetectionStates::clientOver($state),
				]
			),
			l10n: $l10n,
			rootFolder: $this->createMock(IRootFolder::class),
			userSession: $this->createMock(IUserSession::class)
		);

	}//end requestServiceOver()

	/**
	 * A container with no PolicyMatchService, so the prohibition backstop stands aside.
	 *
	 * @return ContainerInterface The container.
	 */
	private function containerWithoutAPolicyMatcher(): ContainerInterface {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willThrowException(new RuntimeException('not registered'));

		return $container;

	}//end containerWithoutAPolicyMatcher()
}//end class
