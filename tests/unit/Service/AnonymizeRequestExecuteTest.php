<?php

/**
 * What one executeAnonymize call hands to the anonymisation service and answers.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\DetectionUnavailableException;
use OCA\Filinq\Exception\ProhibitionGateException;
use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCA\Filinq\Service\AnonymizeRequestService;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Throwable;

/**
 * Every branch of executeAnonymize, call by call.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizeRequestExecuteTest extends TestCase {

	/**
	 * What the anonymisation service and the logger were asked, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * The validated request body most cases start from.
	 *
	 * @var array<string, mixed>
	 */
	private const REQUEST = [
		'entities' => [
			['type' => 'PERSON', 'value' => 'Jan', 'confidence' => 0.9],
			['type' => 'EMAIL', 'value' => 'jan@example.org', 'confidence' => 0.95],
			['entityType' => 'PERSON', 'value' => 'Piet', 'confidence' => 0.4],
		],
		'unredactedEntities' => [['value' => 'Gemeente']],
		'overrides' => [['ruleId' => 'r-1', 'entityId' => 'e-1']],
		'appendBasisSummary' => false,
		'hasStrayBases' => false,
	];

	/**
	 * A request service over a recording anonymisation service.
	 *
	 * @param array<int, mixed> $violations What absoluteProhibitionViolations answers.
	 * @param Throwable|null    $throws     What the anonymise call throws, if anything.
	 *
	 * @return AnonymizeRequestService The service.
	 */
	private function service(array $violations = [], ?Throwable $throws = null): AnonymizeRequestService {
		$anonymization = $this->createMock(AnonymizationServiceInterface::class);
		$anonymization->method('absoluteProhibitionViolations')->willReturnCallback(
			function (int $fileId) use ($violations): array {
				$this->calls[] = ['violations', $fileId];
				return $violations;
			}
		);
		foreach (['anonymizeDocument', 'anonymizeDocumentWithBasisSummary'] as $method) {
			$anonymization->method($method)->willReturnCallback(
				function (...$arguments) use ($method, $throws): array {
					$this->calls[] = [$method, array_combine(['fileId', 'entities', 'outputFormat', 'unredactedEntities', 'overrides', 'userId', 'scope', 'dossierKey', 'reversible'], $arguments)];
					if ($throws !== null) {
						throw $throws;
					}

					return ['anonymizedFileId' => 99];
				}
			);
		}

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $params = []): string => vsprintf($text, $params));

		$calls = &$this->calls;
		$logger = new class($calls) extends AbstractLogger {
			/**
			 * Constructor.
			 *
			 * @param array<int, array<int, mixed>> $calls Where to record.
			 */
			public function __construct(private array &$calls) {
			}

			/**
			 * Record one log line.
			 *
			 * @param mixed                $level   The level.
			 * @param string|\Stringable   $message The message.
			 * @param array<string, mixed> $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|\Stringable $message, array $context = []): void {
				$this->calls[] = ['log ' . $level, (string) $message, $context];
			}
		};

		return new AnonymizeRequestService(
			logger: $logger,
			anonymizationService: $anonymization,
			l10n: $l10n,
			rootFolder: $this->createMock(IRootFolder::class),
			userSession: $this->createMock(IUserSession::class)
		);

	}//end service()

	/**
	 * The filtered entities and every request field reach the plain entry
	 * point by name, and its result is the 200 body.
	 *
	 * @return void
	 */
	public function testARunGetsTheFilteredEntitiesAndEveryField(): void {
		$response = $this->service()->executeAnonymize(
			fileId: 42,
			userId: 'noor',
			params: ['excludeTypes' => ['EMAIL'], 'minConfidence' => '0.5', 'scope' => 'dossier', 'dossierKey' => 'z-7'],
			request: self::REQUEST + ['reversible' => true],
			outputFormat: 'preserve'
		);

		$this->assertSame(['status' => 200, 'body' => ['anonymizedFileId' => 99]], $response);
		$this->assertSame(
			[
				['violations', 42],
				[
					'anonymizeDocument',
					[
						'fileId' => 42,
						'entities' => [['type' => 'PERSON', 'value' => 'Jan', 'confidence' => 0.9]],
						'outputFormat' => 'preserve',
						'unredactedEntities' => [['value' => 'Gemeente']],
						'overrides' => [['ruleId' => 'r-1', 'entityId' => 'e-1']],
						'userId' => 'noor',
						'scope' => 'dossier',
						'dossierKey' => 'z-7',
						'reversible' => true,
					],
				],
			],
			$this->calls
		);

	}//end testARunGetsTheFilteredEntitiesAndEveryField()

	/**
	 * The basis summary entry point gets the same fields; stray bases are named
	 * in the answer.
	 *
	 * @return void
	 */
	public function testTheBasisSummaryRunAndStrayBases(): void {
		$response = $this->service()->executeAnonymize(
			fileId: 7,
			userId: 'anna',
			params: [],
			request: array_merge(self::REQUEST, ['appendBasisSummary' => true, 'hasStrayBases' => true]),
			outputFormat: 'pdf-only'
		);

		$this->assertSame(['status' => 200, 'body' => ['anonymizedFileId' => 99, 'ignoredFields' => ['bases']]], $response);
		$this->assertSame('anonymizeDocumentWithBasisSummary', $this->calls[1][0]);
		$this->assertSame(
			[
				'fileId' => 7,
				'entities' => self::REQUEST['entities'],
				'outputFormat' => 'pdf-only',
				'unredactedEntities' => [['value' => 'Gemeente']],
				'overrides' => [['ruleId' => 'r-1', 'entityId' => 'e-1']],
				'userId' => 'anna',
				'scope' => 'document',
				'dossierKey' => null,
				'reversible' => false,
			],
			$this->calls[1][1]
		);

	}//end testTheBasisSummaryRunAndStrayBases()

	/**
	 * An absolute prohibition left un-redacted refuses the run before it starts.
	 *
	 * @return void
	 */
	public function testAnAbsoluteProhibitionRefusesBeforeTheRun(): void {
		$response = $this->service(violations: [['entity' => 'Jan']])->executeAnonymize(
			fileId: 42,
			userId: 'noor',
			params: [],
			request: self::REQUEST,
			outputFormat: 'preserve'
		);

		$this->assertSame(
			[
				'status' => 422,
				'body' => [
					'error' => 'Anonymisation blocked: prohibition-listed entities would be left un-redacted.',
					'missingProhibitionMatches' => [['entity' => 'Jan']],
				],
			],
			$response
		);
		$this->assertSame([['violations', 42]], $this->calls);

	}//end testAnAbsoluteProhibitionRefusesBeforeTheRun()

	/**
	 * The prohibition gate's refusal and its fail-closed answer, logged with the file.
	 *
	 * @return void
	 */
	public function testTheProhibitionGateAnswers(): void {
		$blocked = $this->service(throws: new ProhibitionGateException(missingMatches: [['m' => 1]], rejectedOverrides: [['o' => 2]]))
			->executeAnonymize(fileId: 5, userId: 'u', params: [], request: self::REQUEST, outputFormat: 'preserve');
		$this->assertSame(422, $blocked['status']);
		$this->assertSame([['m' => 1]], $blocked['body']['missingProhibitionMatches']);
		$this->assertSame([['o' => 2]], $blocked['body']['rejectedOverrides']);

		$this->calls = [];
		$closed = $this->service(throws: new ProhibitionGateException(backendUnavailable: 'or down'))
			->executeAnonymize(fileId: 6, userId: 'u', params: [], request: self::REQUEST, outputFormat: 'preserve');
		$this->assertSame(503, $closed['status']);
		$this->assertSame('or down', $closed['body']['backendUnavailable']);
		$this->assertSame(['log warning', 'ProhibitionGate failed closed: or down', ['fileId' => 6]], $this->calls[2]);

	}//end testTheProhibitionGateAnswers()

	/**
	 * A run without a live detector answers 503 and logs the file and reason.
	 *
	 * @return void
	 */
	public function testNoLiveDetectorAnswers503(): void {
		$refusal = new DetectionUnavailableException(reason: DetectionUnavailableException::REASON_UNAVAILABLE, message: 'presidio gone', backend: 'presidio');
		$response = $this->service(throws: $refusal)
			->executeAnonymize(fileId: 8, userId: 'u', params: [], request: self::REQUEST, outputFormat: 'preserve');

		$this->assertSame(503, $response['status']);
		$this->assertSame('presidio', $response['body']['detectionBackend']);
		$this->assertSame(['log warning', 'presidio gone', ['fileId' => 8, 'reason' => DetectionUnavailableException::REASON_UNAVAILABLE]], $this->calls[2]);

	}//end testNoLiveDetectorAnswers503()
}//end class
