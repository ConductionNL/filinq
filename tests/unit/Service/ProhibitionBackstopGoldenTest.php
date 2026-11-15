<?php

/**
 * Golden tests for the prohibition backstop before redaction
 *
 * Pins which un-redacted occurrences the backstop reports, what it logs and
 * in which order it asks OpenRegister and the matcher, so the method can be
 * split into named steps without changing what it catches.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\ProhibitionGateService;
use OCA\Filinq\Service\ProhibitionPolicyService;
use OCA\Filinq\Service\RelationSkipDecisionService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * What ProhibitionPolicyService::absoluteProhibitionViolations() reports.
 *
 * @covers \OCA\Filinq\Service\ProhibitionPolicyService
 */
final class ProhibitionBackstopGoldenTest extends TestCase {

	/**
	 * Everything the doubles saw, in order.
	 *
	 * @var list<string>
	 */
	private array $calls = [];

	/**
	 * Redacted rows, empty values and matches below the threshold are left alone; the rest are reported and logged.
	 *
	 * @return void
	 */
	public function testOnlyConfidentUnredactedMatchesAreReported(): void {
		$rows = [
			['relation_id' => 1, 'entity_id' => 10, 'entity_value' => 'Jan', 'entity_type' => 'PERSON', 'confidence' => 0.99],
			['relation_id' => 2, 'entity_id' => 20, 'entity_value' => '', 'confidence' => 0.99],
			['relation_id' => 3, 'entity_id' => 30, 'entity_value' => 'Piet', 'confidence' => 0.5],
			['relation_id' => 4, 'entity_id' => 40, 'entity_value' => 'Kees', 'entity_type' => 'PERSON', 'confidence' => 0.9],
			['relation_id' => 5, 'entity_id' => 50, 'entity_value' => 'Klaas', 'confidence' => '0.95'],
			['entity_value' => 'Anon'],
		];
		$service = $this->service(rows: $rows, matches: ['Piet' => 'r-p', 'Kees' => 'r-k', 'Klaas' => 'r-l', 'Anon' => 'r-a']);

		$violations = $service->absoluteProhibitionViolations(7);

		$this->assertSame(
			[
				['entityId' => 40, 'entityName' => 'Kees', 'ruleId' => 'r-k', 'ruleName' => 'Name r-k', 'confidence' => 0.9, 'absolute' => true],
				['entityId' => 50, 'entityName' => 'Klaas', 'ruleId' => 'r-l', 'ruleName' => 'Name r-l', 'confidence' => 0.95, 'absolute' => true],
			],
			$violations
		);
		$this->assertSame(
			[
				'container:OCA\Filinq\Service\PolicyMatchService',
				'locator:OCA\OpenRegister\Db\EntityRelationMapper',
				'threshold',
				'anonymization:7',
				'entities:7',
				'match:Piet:OTHER',
				'match:Kees:PERSON',
				'warning:Prohibition backstop caught an un-redacted absolute match:{"ruleId":"r-k","entityId":40,"fileId":7}',
				'match:Klaas:OTHER',
				'warning:Prohibition backstop caught an un-redacted absolute match:{"ruleId":"r-l","entityId":50,"fileId":7}',
				'match:Anon:OTHER',
			],
			$this->calls
		);

	}//end testOnlyConfidentUnredactedMatchesAreReported()

	/**
	 * Without a matcher the backstop reports nothing and warns once.
	 *
	 * @return void
	 */
	public function testNoMatcherReportsNothing(): void {
		$service = $this->service(rows: null, matches: []);

		$this->assertSame([], $service->absoluteProhibitionViolations(7));
		$expected = [
			'container:OCA\Filinq\Service\PolicyMatchService',
			'warning:Policy matcher unavailable; prohibition backstop is a no-op:{"exception":"gone"}',
		];
		$this->assertSame($expected, $this->calls);

	}//end testNoMatcherReportsNothing()

	/**
	 * The service over recording doubles; null rows means the matcher cannot be resolved.
	 *
	 * @param list<array<string, mixed>>|null $rows    The rows detected for the file; null for no matcher.
	 * @param array<string, string>           $matches Rule uuid per matching entity value.
	 *
	 * @return ProhibitionPolicyService
	 */
	private function service(?array $rows, array $matches): ProhibitionPolicyService {
		$this->calls = [];
		$calls = &$this->calls;
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')->willReturnCallback(static function (string $message, array $context = []) use (&$calls): void {
			$calls[] = 'warning:' . $message . ':' . json_encode($context);
		});
		$matcher = new class($matches, $calls) {
			/**
			 * Constructor.
			 *
			 * @param array<string, string> $matches The rule per value.
			 * @param list<string>          $calls   The call log.
			 */
			public function __construct(private array $matches, private array &$calls) {
			}

			/**
			 * The threshold.
			 *
			 * @return float
			 */
			public function highConfidenceThreshold(): float {
				$this->calls[] = 'threshold';
				return 0.9;
			}

			/**
			 * The matching rule.
			 *
			 * @param string $entityText The value.
			 * @param string $entityType The type.
			 *
			 * @return array<string, string>|null
			 */
			public function matchProhibition(string $entityText, string $entityType): ?array {
				$this->calls[] = 'match:' . $entityText . ':' . $entityType;
				if (isset($this->matches[$entityText]) === false) {
					return null;
				}

				return ['uuid' => $this->matches[$entityText], 'primaryName' => 'Name ' . $this->matches[$entityText]];
			}
		};
		$mapper = new class($rows ?? [], $calls) {
			/**
			 * Constructor.
			 *
			 * @param list<array<string, mixed>> $rows  The detected rows.
			 * @param list<string>               $calls The call log.
			 */
			public function __construct(private array $rows, private array &$calls) {
			}

			/**
			 * The rows going to be redacted: relation 1 only.
			 *
			 * @param int $fileId The file.
			 *
			 * @return list<array<string, int>>
			 */
			public function findEntitiesForAnonymization(int $fileId): array {
				$this->calls[] = 'anonymization:' . $fileId;
				return [['relation_id' => 1]];
			}

			/**
			 * Every row detected for the file.
			 *
			 * @param int $fileId The file.
			 *
			 * @return list<array<string, mixed>>
			 */
			public function findEntitiesForFile(int $fileId): array {
				$this->calls[] = 'entities:' . $fileId;
				return $this->rows;
			}
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(static function (string $id) use (&$calls, $matcher, $rows): object {
			$calls[] = 'container:' . $id;
			if ($rows === null) {
				throw new Exception('gone');
			}

			return $matcher;
		});
		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		$locator->method('get')->willReturnCallback(static function (string $className) use (&$calls, $mapper): object {
			$calls[] = 'locator:' . $className;
			return $mapper;
		});

		return new ProhibitionPolicyService(
			$logger,
			$container,
			$locator,
			$this->createMock(ProhibitionGateService::class),
			$this->createMock(RelationSkipDecisionService::class)
		);

	}//end service()
}//end class
