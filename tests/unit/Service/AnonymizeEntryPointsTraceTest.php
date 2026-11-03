<?php
/**
 * Trace tests for the two anonymise entry points, as their callers reach them.
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

use OCA\Filinq\Kernel\AnonymizeCall;
use OCA\Filinq\Service\AnonymiserBackendStateClient;
use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\AnonymizeRequestService;
use OCA\Filinq\Service\BatchAnonymizeRequest;
use OCA\Filinq\Service\BatchAnonymizeService;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\ConfidentialityLabelService;
use OCA\Filinq\Service\Conversion\OutputLayoutMover;
use OCA\Filinq\Service\CustomDictionaryDetectionRunner;
use OCA\Filinq\Service\DocumentAnonymizeRunner;
use OCA\Filinq\Service\EntityDetectionService;
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\Ocr\OcrExtractionFallback;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\ProhibitionPolicyService;
use OCA\Filinq\Service\Redaction\RedactionOutputGuard;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * What the prohibition gate, the review guard and the runner receive when the
 * request service and the batch service anonymise one file, compared with JSON
 * recorded from the implementation that took nine separate parameters.
 *
 * Each case acts as its own user, so the trace shows which user the
 * prohibition gate ran for.
 *
 * @covers \OCA\Filinq\Service\AnonymizationService
 * @covers \OCA\Filinq\Service\AnonymizeRequestService
 * @covers \OCA\Filinq\Service\BatchAnonymizeService
 * @uses   \OCA\Filinq\Kernel\AnonymizeCall
 * @uses   \OCA\Filinq\Service\AnonymizeRequestValidator
 * @uses   \OCA\Filinq\Service\AnonymizeRun
 * @uses   \OCA\Filinq\Service\BatchAnonymizeRequest
 *
 * @spec openspec/specs/anonymization/spec.md
 */
class AnonymizeEntryPointsTraceTest extends TestCase {

	/**
	 * The calls the collaborators saw, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $calls = [];

	/**
	 * Request-service cases: the request body, the query parameters, the output format and the acting user.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>, 2: string, 3: string}>
	 */
	public static function requestCases(): array {
		$entities = [
			['text' => 'Jan', 'type' => 'PERSON', 'confidence' => 0.9],
			['text' => 'Utrecht', 'type' => 'LOCATION', 'confidence' => 0.4],
		];
		$plain = ['entities' => $entities, 'appendBasisSummary' => false, 'unredactedEntities' => [], 'overrides' => []];

		return [
			'plain, defaults' => [$plain, [], 'pdf-only', 'alice'],
			'plain, every option' => [
				$plain + ['reversible' => true],
				['scope' => 'dossier', 'dossierKey' => 'folder-7', 'excludeTypes' => ['LOCATION']],
				'preserve',
				'bob',
			],
			'plain, consent and overrides' => [
				['entities' => $entities, 'appendBasisSummary' => false, 'unredactedEntities' => [['text' => 'Utrecht']], 'overrides' => [['ruleId' => 'r1', 'entityId' => 'e1']]],
				['scope' => 'other', 'dossierKey' => '', 'minConfidence' => '0.5'],
				'pdf',
				'carol',
			],
			'plain, reversible not exactly true' => [$plain + ['reversible' => 1], ['dossierKey' => 12], 'pdf-only', 'dave'],
			'summary, defaults' => [['appendBasisSummary' => true] + $plain, [], 'pdf-only', 'erin'],
			'summary, every option' => [
				['entities' => $entities, 'appendBasisSummary' => true, 'unredactedEntities' => [['text' => 'Jan']], 'overrides' => [['ruleId' => 'r2', 'entityId' => 'e2', 'reason' => 'ok']], 'reversible' => true],
				['scope' => 'dossier', 'dossierKey' => 'folder-9'],
				'preserve',
				'frank',
			],
		];

	}//end requestCases()

	/**
	 * Batch-service cases: the per-batch call options.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function batchCases(): array {
		return [
			'batch plain' => [['appendBasisSummary' => false, 'unredactedEntities' => [], 'outputFormat' => 'pdf-only', 'scope' => 'dossier']],
			'batch plain, consent' => [['appendBasisSummary' => false, 'unredactedEntities' => [['text' => 'Jan']], 'outputFormat' => 'preserve', 'scope' => 'document']],
			'batch summary' => [['appendBasisSummary' => true, 'unredactedEntities' => [], 'outputFormat' => 'pdf', 'scope' => 'dossier']],
			'batch summary, consent' => [['appendBasisSummary' => true, 'unredactedEntities' => [['text' => 'Utrecht']], 'outputFormat' => 'pdf-only', 'scope' => 'document']],
		];

	}//end batchCases()

	/**
	 * The request service's call into the anonymiser.
	 *
	 * @param array<string, mixed> $request      The validated request body.
	 * @param array<string, mixed> $params       The query parameters.
	 * @param string               $outputFormat The output format.
	 * @param string               $userId       The acting user.
	 *
	 * @dataProvider requestCases
	 *
	 * @return void
	 */
	public function testRequestServiceTrace(array $request, array $params, string $outputFormat, string $userId): void {
		$service = new AnonymizeRequestService(
			new NullLogger(),
			$this->anonymizer(),
			$this->createMock(IL10N::class),
			$this->createMock(IRootFolder::class),
			$this->createMock(IUserSession::class)
		);
		$call = new AnonymizeCall(fileId: 31, params: $params, request: $request, outputFormat: $outputFormat);

		$result = (new ReflectionMethod($service, 'callAnonymizeDocument'))->invoke($service, $call, $userId);

		$this->assertTrace(trace: ['result' => $result, 'calls' => $this->calls]);

	}//end testRequestServiceTrace()

	/**
	 * The batch service's call into the anonymiser for one file.
	 *
	 * @param array<string, mixed> $callOptions The per-batch options.
	 *
	 * @dataProvider batchCases
	 *
	 * @return void
	 */
	public function testBatchServiceTrace(array $callOptions): void {
		$service = new BatchAnonymizeService(
			$this->anonymizer(),
			$this->createMock(BatchStateService::class),
			$this->createMock(OutputLayoutMover::class)
		);
		$request = new BatchAnonymizeRequest(entities: [['text' => 'Jan', 'type' => 'PERSON']], callOptions: $callOptions);

		$result = (new ReflectionMethod($service, 'anonymizeOneFile'))->invoke($service, 57, $request);

		$this->assertTrace(trace: ['result' => $result, 'calls' => $this->calls]);

	}//end testBatchServiceTrace()

	/**
	 * A call with only the file and the entities, as the folder extraction job makes it.
	 *
	 * @param bool $summary Whether the summary-producing entry point is called.
	 *
	 * @testWith [false]
	 *           [true]
	 *
	 * @return void
	 */
	public function testDefaultsTrace(bool $summary): void {
		$entities = [['text' => 'Jan', 'type' => 'PERSON']];
		$anonymizer = $this->anonymizer();

		$result = $anonymizer->anonymizeDocument(44, $entities);
		if ($summary === true) {
			$result = $anonymizer->anonymizeDocumentWithBasisSummary(44, $entities);
		}

		$this->assertTrace(trace: ['result' => $result, 'calls' => $this->calls]);

	}//end testDefaultsTrace()

	/**
	 * Compare the trace with the recorded one; GOLDEN_RECORD=1 rewrites it.
	 *
	 * @param array<string, mixed> $trace The result and the calls.
	 *
	 * @return void
	 */
	private function assertTrace(array $trace): void {
		$path = __DIR__ . '/golden/anonymize-entry-points-trace.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			ksort($recorded);
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);

	}//end assertTrace()

	/**
	 * The real anonymiser over recording gate, guard, detector state and runner.
	 *
	 * @return AnonymizationService
	 */
	private function anonymizer(): AnonymizationService {
		$gate = $this->createMock(ProhibitionPolicyService::class);
		$gate->method('runGate')->willReturnCallback(
			function (int $fileId, array $requestEntities, array $overrides, string $userId): void {
				$this->calls[] = ['gate' => compact('fileId', 'requestEntities', 'overrides', 'userId')];
			}
		);

		$guard = $this->createMock(RedactionOutputGuard::class);
		$guard->method('assertMayWrite')->willReturnCallback(
			function (int $fileId, array $entities): void {
				$this->calls[] = ['guard' => compact('fileId', 'entities')];
			}
		);

		$state = $this->createMock(AnonymiserBackendStateClient::class);
		$state->method('getState')->willReturn(['effectiveMethod' => 'presidio']);
		$state->method('refusalReason')->willReturn(null);

		$runner = $this->createMock(DocumentAnonymizeRunner::class);
		$runner->method('run')->willReturnCallback(
			function (int $fileId, array $entities, array $options): array {
				$this->calls[] = ['runner' => compact('fileId', 'entities', 'options')];
				return ['anonymizedFileId' => 900 + $fileId];
			}
		);

		return new AnonymizationService(
			new NullLogger(),
			$this->createMock(ContainerInterface::class),
			$this->createMock(OpenRegisterServiceLocator::class),
			$this->createMock(EntityDetectionService::class),
			$this->createMock(CustomDictionaryDetectionRunner::class),
			$this->createMock(FileEntityStatsService::class),
			$this->createMock(ConfidentialityLabelService::class),
			$gate,
			$runner,
			$guard,
			$state,
			$this->createMock(OcrExtractionFallback::class)
		);

	}//end anonymizer()
}//end class
