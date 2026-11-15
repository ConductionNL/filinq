<?php

/**
 * Unit tests for how the two EML paths fail when OpenRegister cannot anonymise.
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

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\EmlBackend;
use OCA\Filinq\Service\EmlAnonymizationService;
use OCA\Filinq\Service\EmlAnonymizeRequest;
use OCA\Filinq\Service\EmlPdfAssemblyService;
use OCA\Filinq\Service\EntityDetectionService;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Pins message, code, attempts, previous and log line of every EML failure.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\Conversion\EmlBackend
 * @covers \OCA\Filinq\Service\EmlAnonymizationService
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 * @uses   \OCA\Filinq\Service\EmlAnonymizeRequest
 */
class EmlStructureFailuresTest extends TestCase {

	/**
	 * An OpenRegister file service that records its call and answers as told.
	 *
	 * @param mixed $answer A value to return, or a Throwable to throw.
	 *
	 * @return object
	 */
	private function fileService(mixed $answer): object {
		return new class($answer) {
			public array $calls = [];

			public function __construct(
				private mixed $answer,
			) {
			}

			public function anonymizeEmlStructured(mixed $node, array $entities, string $scope = 'document', mixed $dossierKey = null): mixed {
				$this->calls[] = [$node, $entities, $scope, $dossierKey];
				if ($this->answer instanceof \Throwable) {
					throw $this->answer;
				}
				return $this->answer;
			}
		};
	}

	/**
	 * Run the call and describe the failure it raises.
	 *
	 * @param callable $call The call.
	 *
	 * @return array{0: string, 1: int, 2: array<int, array<string, mixed>>, 3: ?\Throwable}
	 */
	private function failure(callable $call): array {
		try {
			$call();
		} catch (ConversionFailedException $e) {
			return [$e->getMessage(), $e->getCode(), $e->getAttempts(), $e->getPrevious()];
		}
		$this->fail('Expected ConversionFailedException');
	}

	/**
	 * The backend over a container answering with the given file service.
	 *
	 * @param mixed $fileService A file service, or a Throwable for the container to throw.
	 * @param LoggerInterface|null $logger Optional logger.
	 *
	 * @return EmlBackend
	 */
	private function backend(mixed $fileService, ?LoggerInterface $logger = null): EmlBackend {
		$container = $this->createMock(ContainerInterface::class);
		if ($fileService instanceof \Throwable) {
			$container->method('get')->willThrowException($fileService);
		} else {
			$container->method('get')->willReturn($fileService);
		}
		$assembly = $this->createMock(EmlPdfAssemblyService::class);
		$assembly->expects($this->never())->method('assemble');
		return new EmlBackend(
			appConfig: $this->createMock(IAppConfig::class),
			appManager: $this->createMock(IAppManager::class),
			container: $container,
			assembly: $assembly,
			logger: ($logger ?? $this->createMock(LoggerInterface::class)),
		);
	}

	/**
	 * The service with an assembler that must not run.
	 *
	 * @param LoggerInterface|null $logger Optional logger.
	 *
	 * @return EmlAnonymizationService
	 */
	private function service(?LoggerInterface $logger = null): EmlAnonymizationService {
		$assembly = $this->createMock(EmlPdfAssemblyService::class);
		$assembly->expects($this->never())->method('assemble');
		return new EmlAnonymizationService(
			logger: ($logger ?? $this->createMock(LoggerInterface::class)),
			entityDetection: $this->createMock(EntityDetectionService::class),
			emlAssembly: $assembly,
		);
	}

	/**
	 * A source file named message.eml.
	 *
	 * @return File
	 */
	private function source(): File {
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn('message.eml');
		$source->method('getPath')->willReturn('/u/admin/message.eml');
		return $source;
	}

	public function testBackendWithoutFileService(): void {
		$boom = new RuntimeException('no container entry');
		$this->assertSame(
			[
				'EML backend could not resolve OpenRegister FileService: no container entry',
				422,
				[['name' => 'eml', 'available' => false, 'supports' => true, 'reason' => 'OpenRegister FileService unavailable']],
				$boom,
			],
			$this->failure(fn () => $this->backend(fileService: $boom)->convert($this->source()))
		);
	}

	public function testBackendWhenTheApiThrows(): void {
		$boom = new RuntimeException('OR exploded');
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			'[EmlBackend] OR anonymizeEmlStructured failed; no raw-parse fallback',
			['source' => '/u/admin/message.eml', 'exception' => RuntimeException::class, 'message' => 'OR exploded']
		);
		$fileService = $this->fileService(answer: $boom);
		$source = $this->source();
		$this->assertSame(
			[
				'OpenRegister anonymise-EML API failed: OR exploded',
				422,
				[['name' => 'eml', 'available' => true, 'supports' => true, 'reason' => 'anonymizeEmlStructured threw: OR exploded']],
				$boom,
			],
			$this->failure(fn () => $this->backend(fileService: $fileService, logger: $logger)->convert($source))
		);
		$this->assertSame([[$source, [], 'document', null]], $fileService->calls);
	}

	public function testBackendWrapsAConversionFailureFromTheApi(): void {
		$inner = new ConversionFailedException(message: 'inner', code: 500);
		[$message, $code, , $previous] = $this->failure(fn () => $this->backend(fileService: $this->fileService(answer: $inner))->convert($this->source()));
		$this->assertSame(['OpenRegister anonymise-EML API failed: inner', 422, $inner], [$message, $code, $previous]);
	}

	public function testBackendWhenTheApiReturnsNoStructure(): void {
		$this->assertSame(
			[
				'OpenRegister anonymise-EML API returned no structure.',
				422,
				[['name' => 'eml', 'available' => true, 'supports' => true, 'reason' => 'anonymizeEmlStructured returned non-object']],
				null,
			],
			$this->failure(fn () => $this->backend(fileService: $this->fileService(answer: ['not' => 'an object']))->convert($this->source()))
		);
	}

	public function testServiceWithoutTheApi(): void {
		$this->assertSame(
			[
				'OpenRegister does not expose the anonymise-EML API; cannot anonymise EML input.',
				422,
				[['name' => 'eml', 'available' => false, 'supports' => true, 'reason' => 'anonymizeEmlStructured not present on OpenRegister FileService']],
				null,
			],
			$this->failure(fn () => $this->service()->anonymize(request: new EmlAnonymizeRequest(fileId: 5, node: $this->source(), mappedEntities: [], options: ['scope' => 'document', 'dossierKey' => null]), fileService: new \stdClass()))
		);
	}

	public function testServiceWhenTheApiThrows(): void {
		$boom = new RuntimeException('OR exploded');
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			'EML anonymise-API failed; no raw-parse fallback.',
			['fileId' => 5, 'exception' => RuntimeException::class, 'message' => 'OR exploded']
		);
		$fileService = $this->fileService(answer: $boom);
		$node = $this->source();
		$this->assertSame(
			[
				'OpenRegister anonymise-EML API failed: OR exploded',
				422,
				[['name' => 'eml', 'available' => true, 'supports' => true, 'reason' => 'anonymizeEmlStructured threw: OR exploded']],
				$boom,
			],
			$this->failure(fn () => $this->service(logger: $logger)->anonymize(request: new EmlAnonymizeRequest(fileId: 5, node: $node, mappedEntities: [['t' => 'x']], options: ['scope' => 'dossier', 'dossierKey' => 'd-1']), fileService: $fileService))
		);
		$this->assertSame([[$node, [['t' => 'x']], 'dossier', 'd-1']], $fileService->calls);
	}

	public function testServicePassesAConversionFailureFromTheApiThroughUntouched(): void {
		$inner = new ConversionFailedException(message: 'inner', code: 500);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('warning');
		try {
			$this->service(logger: $logger)->anonymize(request: new EmlAnonymizeRequest(fileId: 5, node: $this->source(), mappedEntities: [], options: ['scope' => 'document', 'dossierKey' => null]), fileService: $this->fileService(answer: $inner));
			$this->fail('Expected ConversionFailedException');
		} catch (ConversionFailedException $e) {
			$this->assertSame($inner, $e);
		}
	}

	public function testServiceWhenTheApiReturnsNoStructure(): void {
		$this->assertSame(
			[
				'OpenRegister anonymise-EML API returned no structure.',
				422,
				[['name' => 'eml', 'available' => true, 'supports' => true, 'reason' => 'anonymizeEmlStructured returned non-object']],
				null,
			],
			$this->failure(fn () => $this->service()->anonymize(request: new EmlAnonymizeRequest(fileId: 5, node: $this->source(), mappedEntities: [], options: ['scope' => 'document', 'dossierKey' => null]), fileService: $this->fileService(answer: 'flat')))
		);
	}
}
