<?php

/**
 * The two curated tools of mcp-generation-tools: getDocumentStatus on
 * FileListingService and anonymizeDocument on AnonymizationService. Pins the
 * hints, the fileId-only signature, the access contract, the gate refusal,
 * the mcp attribution and the counts-only response shapes.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/mcp-generation-tools/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Mcp;

use OCA\Filinq\Kernel\Contracts\RedactionOutputGuardInterface;
use OCA\Filinq\Mcp\FilinqScannableServices;
use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\FileListingService;
use OCA\Filinq\Service\FileUploadService;
use OCA\Filinq\Service\Ocr\OcrResultRepository;
use OCA\Filinq\Service\Ocr\OcrRunService;
use OCA\Filinq\Service\ReadableFileResolver;
use OCA\OpenRegister\Mcp\Attribute\McpTool;
use OCP\Files\File;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

class McpGenerationToolsTest extends TestCase {

	private ReadableFileResolver&MockObject $files;

	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		$this->files  = $this->createMock(ReadableFileResolver::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}

	/**
	 * The tool attribute on a method, as OpenRegister's scanner reads it.
	 */
	private static function tool(string $class, string $method): McpTool {
		$attributes = (new ReflectionMethod($class, $method))->getAttributes(McpTool::class);
		self::assertCount(1, $attributes, $class . '::' . $method . ' carries exactly one tool');

		return $attributes[0]->newInstance();
	}

	private function statusService(FileEntityStatsService $stats, RedactionOutputGuardInterface $guard): FileListingService {
		return new FileListingService(
			$this->logger,
			$this->createMock(FileUploadService::class),
			$stats,
			$this->createMock(OcrResultRepository::class),
			$this->createMock(OcrRunService::class),
			$this->files,
			$guard
		);
	}

	/**
	 * An AnonymizationService whose extraction and prohibition lookup are
	 * stubbed, built over mocks of its real constructor parameters.
	 */
	private function anonymizer(?string $uid, array $entities, array|\Throwable $violations, ?\Throwable $extractionFails = null): AnonymizationService&MockObject {
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnMap([[IUserSession::class, $session], [ReadableFileResolver::class, $this->files]]);

		$args = [];
		foreach ((new ReflectionMethod(AnonymizationService::class, '__construct'))->getParameters() as $parameter) {
			$type = $parameter->getType();
			self::assertInstanceOf(ReflectionNamedType::class, $type);
			$args[] = match ($type->getName()) {
				LoggerInterface::class => $this->logger,
				ContainerInterface::class => $container,
				default => $this->createMock($type->getName()),
			};
		}

		$service = $this->getMockBuilder(AnonymizationService::class)
			->setConstructorArgs($args)
			->onlyMethods(['extractAndDetectEntities', 'absoluteProhibitionViolations', 'anonymizeDocument', 'anonymizeDocumentWithBasisSummary'])
			->getMock();
		$service->expects($this->never())->method('anonymizeDocument');
		$service->expects($this->never())->method('anonymizeDocumentWithBasisSummary');

		if ($extractionFails !== null) {
			$service->method('extractAndDetectEntities')->willThrowException($extractionFails);
		} else {
			$service->method('extractAndDetectEntities')->willReturn(['entities' => $entities, 'entityCount' => count($entities)]);
		}

		if ($violations instanceof \Throwable) {
			$service->method('absoluteProhibitionViolations')->willThrowException($violations);
		} else {
			$service->method('absoluteProhibitionViolations')->willReturn($violations);
		}

		return $service;
	}

	private static function detected(): array {
		return [
			['type' => 'person', 'value' => 'J. de Vries', 'confidence' => 0.98],
			['type' => 'BSN', 'value' => '123456782', 'confidence' => 0.99],
			['type' => 'PERSON', 'value' => 'P. Jansen', 'confidence' => 0.9],
		];
	}

	public function testBothToolsDeclareCompleteHonestHints(): void {
		$status = self::tool(FileListingService::class, 'getFileStatus');
		$this->assertSame(['getDocumentStatus', 'read', true, false, true], [$status->name, $status->scope, $status->readOnlyHint, $status->destructiveHint, $status->idempotentHint]);

		$anonymize = self::tool(AnonymizationService::class, 'anonymizeViaAgent');
		$this->assertSame(['anonymizeDocument', 'create', false, false, false], [$anonymize->name, $anonymize->scope, $anonymize->readOnlyHint, $anonymize->destructiveHint, $anonymize->idempotentHint]);

		$scannable = (new FilinqScannableServices())->getScannableServiceClasses();
		$this->assertContains(FileListingService::class, $scannable);
		$this->assertContains(AnonymizationService::class, $scannable);
	}

	public function testTheAnonymiseToolTakesTheFileIdAndNothingElse(): void {
		$parameters = (new ReflectionMethod(AnonymizationService::class, 'anonymizeViaAgent'))->getParameters();

		$this->assertCount(1, $parameters);
		$this->assertSame('fileId', $parameters[0]->getName());
		$this->assertSame('int', (string)$parameters[0]->getType());
	}

	public function testAnUnreadableFileIsNotFoundLikeAMissingOne(): void {
		$this->files->method('node')->willReturn(null);
		$stats = $this->createMock(FileEntityStatsService::class);
		$stats->expects($this->never())->method('getEntityStats');
		$service = $this->statusService($stats, $this->createMock(RedactionOutputGuardInterface::class));

		$this->assertSame(['fileId' => 41, 'status' => 'not_found'], $service->getFileStatus(41));
	}

	public function testTheStatusIsCountsAndStatusesOnly(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(7);
		$file->method('getName')->willReturn('besluit-j-de-vries.pdf');
		$file->method('getPath')->willReturn('/alice/files/Zaken/besluit-j-de-vries.pdf');
		$file->method('getMimeType')->willReturn('application/vnd.oasis.opendocument.text');
		$this->files->method('node')->with(7)->willReturn($file);

		$stats = $this->createMock(FileEntityStatsService::class);
		$stats->method('getEntityStats')->willReturn(['entityCount' => 3, 'anonymizedCount' => 0, 'status' => 'extracted']);
		$stats->method('getFileRiskLevel')->willReturn('high');
		$stats->method('entityCountsByType')->willReturn(['BSN' => 1, 'PERSON' => 2]);
		$guard = $this->createMock(RedactionOutputGuardInterface::class);
		$guard->method('marksFor')->with([7])->willReturn([]);

		$status = $this->statusService($stats, $guard)->getFileStatus(7);

		$this->assertSame(
			[
				'fileId' => 7,
				'status' => 'extracted',
				'entityCount' => 3,
				'anonymizedCount' => 0,
				'entityCounts' => ['BSN' => 1, 'PERSON' => 2],
				'riskLevel' => 'high',
				'reviewChecked' => false,
				'reviewCheckedAt' => null,
				'ocrAvailable' => false,
				'ocrProcessed' => false,
				'ocrConfidence' => null,
			],
			$status
		);
		$this->assertStringNotContainsString('de-vries', json_encode($status));
	}

	public function testACheckedDocumentReportsItsMarkAndAnUnreadableMarkReadsUnchecked(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(8);
		$file->method('getMimeType')->willReturn('application/vnd.oasis.opendocument.text');
		$this->files->method('node')->willReturn($file);
		$stats = $this->createMock(FileEntityStatsService::class);
		$stats->method('getEntityStats')->willReturn(['entityCount' => 1, 'anonymizedCount' => 1, 'status' => 'anonymized']);

		$guard = $this->createMock(RedactionOutputGuardInterface::class);
		$guard->method('marksFor')->willReturn([8 => ['checkedBy' => 'bob', 'checkedAt' => '2026-11-01T10:00:00+00:00', 'entityCount' => 1]]);
		$checked = $this->statusService($stats, $guard)->getFileStatus(8);
		$this->assertSame([true, '2026-11-01T10:00:00+00:00'], [$checked['reviewChecked'], $checked['reviewCheckedAt']]);
		$this->assertArrayNotHasKey('checkedBy', $checked);

		$broken = $this->createMock(RedactionOutputGuardInterface::class);
		$broken->method('marksFor')->willThrowException(new RuntimeException('store down'));
		$this->logger->expects($this->once())->method('warning')->with('The review mark could not be read', ['fileId' => 8, 'exception' => 'store down']);
		$this->assertFalse($this->statusService($stats, $broken)->getFileStatus(8)['reviewChecked']);
	}

	public function testIntakeAnswersCountsPerTypeAndLeavesTheDecisionToAPerson(): void {
		$this->files->method('node')->with(12)->willReturn($this->createMock(File::class));
		$this->logger->expects($this->once())->method('info')
			->with('Filinq: anonymisation intake started', ['fileId' => 12, 'triggeredBy' => 'mcp', 'uid' => 'alice']);

		$result = $this->anonymizer('alice', self::detected(), [])->anonymizeViaAgent(12);

		$this->assertSame(['status' => 'review_required', 'entityCounts' => ['BSN' => 1, 'PERSON' => 2], 'reviewRequired' => true], $result);
		$encoded = json_encode($result);
		$this->assertStringNotContainsString('de Vries', $encoded);
		$this->assertStringNotContainsString('123456782', $encoded);
	}

	public function testAProhibitionIsARefusalWithItsReason(): void {
		$this->files->method('node')->willReturn($this->createMock(File::class));

		$result = $this->anonymizer('alice', self::detected(), [['entity' => 'J. de Vries', 'tier' => 'absolute']])->anonymizeViaAgent(12);

		$this->assertSame('refused', $result['status']);
		$this->assertSame(AnonymizationService::PROHIBITION_REASON, $result['refusedReason']);
		$this->assertSame(['BSN' => 1, 'PERSON' => 2], $result['entityCounts']);
		$this->assertStringNotContainsString('de Vries', json_encode($result));
	}

	public function testAGateThatCannotBeAskedRefuses(): void {
		$this->files->method('node')->willReturn($this->createMock(File::class));
		$this->logger->expects($this->once())->method('warning')
			->with('Filinq: the prohibition gate could not be asked; the agent intake is refused', ['fileId' => 12, 'exception' => 'policy store down']);

		$result = $this->anonymizer('alice', [], new RuntimeException('policy store down'))->anonymizeViaAgent(12);

		$this->assertSame(['status' => 'refused', 'entityCounts' => [], 'reviewRequired' => true, 'refusedReason' => AnonymizationService::PROHIBITION_REASON], $result);
	}

	public function testWithoutAUserOrAReadableFileNothingRuns(): void {
		$this->files->method('node')->willReturn(null);

		$unreadable = $this->anonymizer('alice', self::detected(), []);
		$unreadable->expects($this->never())->method('extractAndDetectEntities');
		$this->assertSame(['status' => 'not_found', 'entityCounts' => [], 'reviewRequired' => false], $unreadable->anonymizeViaAgent(99));

		$anonymous = $this->anonymizer(null, self::detected(), []);
		$anonymous->expects($this->never())->method('extractAndDetectEntities');
		$this->assertSame(['status' => 'not_found', 'entityCounts' => [], 'reviewRequired' => false], $anonymous->anonymizeViaAgent(12));
	}

	public function testAFailedDetectionSaysFailedWithoutDetail(): void {
		$this->files->method('node')->willReturn($this->createMock(File::class));
		$this->logger->expects($this->once())->method('warning')
			->with('Filinq: anonymisation intake failed', ['fileId' => 12, 'triggeredBy' => 'mcp', 'uid' => 'alice', 'exception' => 'Failed to extract: J. de Vries']);

		$result = $this->anonymizer('alice', [], [], new RuntimeException('Failed to extract: J. de Vries'))->anonymizeViaAgent(12);

		$this->assertSame(['status' => 'failed', 'entityCounts' => [], 'reviewRequired' => false], $result);
	}

	public function testEntityCountsReadTheTypeTheWayDetectionNormalisesIt(): void {
		$this->assertSame(
			['EMAIL' => 1, 'PERSON' => 2, 'UNKNOWN' => 1],
			FileEntityStatsService::countByType([['entity_type' => 'person'], ['entityType' => 'PERSON'], ['type' => 'email'], ['entity_value' => 'x']])
		);
	}

	public function testEntityCountsOfAFileComeFromItsRelations(): void {
		$container = $this->createMock(ContainerInterface::class);
		$stats = new FileEntityStatsService($this->logger, $container, $this->createMock(\OCP\App\IAppManager::class));
		$mapper = $this->createMock(\OCA\OpenRegister\Db\EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->willReturnMap([[5, [['entity_type' => 'IBAN'], ['entity_type' => 'iban']]]]);

		$this->assertSame(['IBAN' => 2], $stats->entityCountsByType(5, $mapper));
		$this->assertSame([], $stats->entityCountsByType(5, null));

		$broken = $this->createMock(\OCA\OpenRegister\Db\EntityRelationMapper::class);
		$broken->method('findEntitiesForFile')->willThrowException(new \Exception('db down'));
		$this->assertSame([], $stats->entityCountsByType(5, $broken));
	}
}
