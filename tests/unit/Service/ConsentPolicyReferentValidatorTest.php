<?php

/**
 * Unit tests for ConsentPolicyReferentValidator.
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
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\ConsentPolicyReferentValidator;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Pins the scope contract and the policyMatch referent lookup.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\ConsentPolicyReferentValidator
 * @uses   \OCA\Filinq\Service\ObjectResultExtractor
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsentPolicyReferentValidatorTest extends TestCase {

	/**
	 * Lookups the fake ObjectService received, as [schema, uuid, limit, rbac].
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * Build a validator whose ObjectService answers per schema.
	 *
	 * @param array<string, array<int, mixed>|\Throwable> $bySchema Result (or throwable) per schema.
	 * @param LoggerInterface|null $logger Optional logger.
	 * @param array<int, string> $apps Installed apps.
	 *
	 * @return ConsentPolicyReferentValidator
	 */
	private function validator(array $bySchema, ?LoggerInterface $logger = null, array $apps = ['openregister']): ConsentPolicyReferentValidator {
		$objectService = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
		$objectService->method('findAll')->willReturnCallback(
			function (array $config = [], bool $_rbac = true) use ($bySchema): array {
				$filters = $config['filters'];
				$this->calls[] = [$filters['register'], $filters['schema'], $filters['uuid'], $config['limit'], $_rbac];
				$answer = ($bySchema[$filters['schema']] ?? []);
				if ($answer instanceof \Throwable) {
					throw $answer;
				}
				return $answer;
			}
		);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Service\ObjectService')->willReturn($objectService);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn($apps);

		return new ConsentPolicyReferentValidator(
			logger: ($logger ?? $this->createMock(LoggerInterface::class)),
			container: $container,
			appManager: $appManager,
		);
	}

	/**
	 * Run a document-scope validation with the given policyMatch.
	 *
	 * @param ConsentPolicyReferentValidator $validator The validator.
	 * @param mixed $policyMatch The policyMatch value.
	 *
	 * @return void
	 */
	private function validateDocument(ConsentPolicyReferentValidator $validator, mixed $policyMatch): void {
		$validator->validatePublicationConsentData(data: ['scope' => 'document', 'documentId' => 'd1', 'policyMatch' => $policyMatch]);
	}

	/**
	 * Assert the call raises InvalidArgumentException with exactly this message.
	 *
	 * @param string $message Expected message.
	 * @param callable $call The call.
	 *
	 * @return InvalidArgumentException
	 */
	private function assertRejects(string $message, callable $call): InvalidArgumentException {
		try {
			$call();
		} catch (InvalidArgumentException $e) {
			$this->assertSame($message, $e->getMessage());
			return $e;
		}
		$this->fail('Expected InvalidArgumentException: ' . $message);
	}

	public function testProhibitionReferentPassesWithOneLookup(): void {
		$this->validateDocument(validator: $this->validator(bySchema: ['publicationProhibition' => [['uuid' => 'p1']]]), policyMatch: 'p1');
		$this->assertSame([['filinq', 'publicationProhibition', 'p1', 1, false]], $this->calls);
	}

	public function testEntityConsentReferentPassesAfterTwoLookups(): void {
		$this->validateDocument(validator: $this->validator(bySchema: ['publicationConsent' => [['scope' => 'entity']]]), policyMatch: 'c1');
		$this->assertSame(
			[['filinq', 'publicationProhibition', 'c1', 1, false], ['filinq', 'publicationConsent', 'c1', 1, false]],
			$this->calls
		);
	}

	public function testDocumentConsentReferentIsRejected(): void {
		$validator = $this->validator(bySchema: ['publicationConsent' => [['scope' => 'document']]]);
		$this->assertRejects(
			message: 'policyMatch points at a publicationConsent with scope=document; only entity-scope records are permitted.',
			call: fn () => $this->validateDocument(validator: $validator, policyMatch: 'c1'),
		);
	}

	public function testConsentReferentWithoutScopeCountsAsDocument(): void {
		$validator = $this->validator(bySchema: ['publicationConsent' => [['uuid' => 'c1']]]);
		$this->assertRejects(
			message: 'policyMatch points at a publicationConsent with scope=document; only entity-scope records are permitted.',
			call: fn () => $this->validateDocument(validator: $validator, policyMatch: 'c1'),
		);
	}

	public function testUnknownReferentIsRejectedWithoutLogging(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('error');
		$validator = $this->validator(bySchema: [], logger: $logger);
		$e = $this->assertRejects(
			message: 'policyMatch UUID "x9" does not resolve to a known prohibition or entity-scope publicationConsent record.',
			call: fn () => $this->validateDocument(validator: $validator, policyMatch: 'x9'),
		);
		$this->assertNull($e->getPrevious());
	}

	public function testLookupFailureIsLoggedAndRejected(): void {
		$boom = new RuntimeException('db down');
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')->with(
			'ConsentService: policyMatch referent lookup failed — rejecting write',
			['policyMatch' => 'u1', 'error' => 'db down']
		);
		$validator = $this->validator(bySchema: ['publicationConsent' => $boom], logger: $logger);
		$e = $this->assertRejects(
			message: 'policyMatch UUID "u1" could not be validated against the policy registry: db down',
			call: fn () => $this->validateDocument(validator: $validator, policyMatch: 'u1'),
		);
		$this->assertSame($boom, $e->getPrevious());
	}

	public function testMissingOpenRegisterIsReportedAsLookupFailure(): void {
		$validator = $this->validator(bySchema: [], apps: ['files']);
		$this->assertRejects(
			message: 'policyMatch UUID "u2" could not be validated against the policy registry: OpenRegister service is not available.',
			call: fn () => $this->validateDocument(validator: $validator, policyMatch: 'u2'),
		);
	}

	public function testEmptyOrNonStringPolicyMatchSkipsTheLookup(): void {
		$validator = $this->validator(bySchema: []);
		$this->validateDocument(validator: $validator, policyMatch: '');
		$this->validateDocument(validator: $validator, policyMatch: 42);
		$this->validateDocument(validator: $validator, policyMatch: null);
		$this->assertSame([], $this->calls);
	}

	public function testDocumentScopeNeedsDocumentId(): void {
		$validator = $this->validator(bySchema: []);
		$this->assertRejects(
			message: 'scope=document requires a non-empty documentId',
			call: fn () => $validator->validatePublicationConsentData(data: ['scope' => 'document']),
		);
	}

	/**
	 * @return array<string, array{0: array<string, mixed>, 1: string}>
	 */
	public static function entityScopeViolations(): array {
		$ok = ['scope' => 'entity', 'matchRules' => ['r'], 'consentMethod' => 'm'];
		return [
			'documentId' => [$ok + ['documentId' => 'd'], 'scope=entity must not include documentId'],
			'matchRules' => [['scope' => 'entity', 'consentMethod' => 'm'], 'scope=entity requires a non-empty matchRules array'],
			'consentMethod' => [['scope' => 'entity', 'matchRules' => ['r']], 'scope=entity requires a non-empty consentMethod'],
			'policyMatch' => [$ok + ['policyMatch' => 'p'], 'scope=entity must not include policyMatch'],
		];
	}

	/**
	 * @dataProvider entityScopeViolations
	 *
	 * @param array<string, mixed> $data Consent data.
	 * @param string $message Expected message.
	 */
	public function testEntityScopeContract(array $data, string $message): void {
		$validator = $this->validator(bySchema: []);
		$this->assertRejects(message: $message, call: fn () => $validator->validatePublicationConsentData(data: $data));
	}

	public function testValidEntityAndUnknownScopePassWithoutLookups(): void {
		$validator = $this->validator(bySchema: []);
		$validator->validatePublicationConsentData(data: ['scope' => 'entity', 'matchRules' => ['r'], 'consentMethod' => 'm']);
		$validator->validatePublicationConsentData(data: ['scope' => 'other']);
		$this->assertSame([], $this->calls);
	}
}
