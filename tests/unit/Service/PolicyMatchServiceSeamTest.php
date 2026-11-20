<?php

/**
 * Unit tests for how PolicyMatchService loads its rules
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

use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\SystemContextSeam;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The rule load runs as the system through the seam and passes no bypass flag.
 *
 * @covers \OCA\Filinq\Service\PolicyMatchService
 * @uses   \OCA\Filinq\Service\SystemContextSeam
 * @uses   \OCA\Filinq\Service\ObjectResultExtractor
 * @uses   \OCA\Filinq\Service\TextNormaliser
 * @uses   \OCA\Filinq\Service\PolicyRuleNormaliser
 * @uses   \OCA\Filinq\Service\PolicyMatchRule
 * @uses   \OCA\Filinq\Service\PolicyEntity
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md#requirement-openregister-enforcement-is-on-by-default-bypasses-are-removed-req-ddmth-002
 */
class PolicyMatchServiceSeamTest extends TestCase {

	/**
	 * The object service.
	 *
	 * @var ObjectServiceInterface&MockObject
	 */
	private ObjectServiceInterface&MockObject $objectService;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Every searchObjectsBySlug call: schema slug, rbac flag, multitenancy flag, inside the system run.
	 *
	 * @var array<int, array{0: string, 1: bool, 2: bool, 3: bool}>
	 */
	private array $calls = [];

	/**
	 * Whether a runAsSystem operation is executing.
	 *
	 * @var bool
	 */
	private bool $asSystem = false;

	/**
	 * Fresh doubles: one prohibition on "Pieter de Vries", no standing consents.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->calls = [];
		$this->asSystem = false;
		$this->objectService = $this->createMock(ObjectServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->objectService->method('runAsSystem')->willReturnCallback(
			function (callable $operation): mixed {
				$this->asSystem = true;
				try {
					return $operation();
				} finally {
					$this->asSystem = false;
				}
			}
		);
		$this->objectService->method('searchObjectsBySlug')->willReturnCallback(
			function (
				string $registerSlug,
				string $schemaSlug,
				array $filters = [],
				bool $_rbac = true,
				bool $_multitenancy = true,
			): array {
				$this->calls[] = [$schemaSlug, $_rbac, $_multitenancy, $this->asSystem];
				if ($schemaSlug === 'publicationProhibition') {
					return [
						[
							'@self' => ['id' => 'p-1'],
							'primaryName' => 'Pieter de Vries',
							'entityType' => 'PERSON',
							'matchRules' => [['type' => 'exact', 'value' => 'Pieter de Vries']],
							'active' => true,
						],
					];
				}

				return [];
			}
		);
	}//end setUp()

	/**
	 * Build the matcher.
	 *
	 * @return PolicyMatchService
	 */
	private function matcher(): PolicyMatchService {
		return new PolicyMatchService(
			logger: $this->logger,
			config: $this->createMock(IAppConfig::class),
			objectService: $this->objectService,
			seam: new SystemContextSeam(logger: $this->logger)
		);
	}//end matcher()

	/**
	 * Both rule sources load inside one system run, with OpenRegister's enforcing defaults on every call.
	 *
	 * @return void
	 */
	public function testRulesLoadAsTheSystemWithoutBypassFlags(): void {
		$this->objectService->expects($this->once())->method('runAsSystem');

		$this->matcher()->match(entityText: 'Pieter de Vries', entityType: 'PERSON');

		self::assertSame(
			[
				['publicationProhibition', true, true, true],
				['publicationConsent', true, true, true],
			],
			$this->calls
		);
	}//end testRulesLoadAsTheSystemWithoutBypassFlags()

	/**
	 * A rule loaded through the seam still matches, so a user who cannot read the rules is still protected by them.
	 *
	 * @return void
	 */
	public function testARuleLoadedAsTheSystemStillMatches(): void {
		$hint = $this->matcher()->matchProhibitionHint(entityType: 'PERSON', entityValue: 'Pieter de Vries');

		self::assertSame(['ruleId' => 'p-1', 'ruleName' => 'Pieter de Vries'], $hint);
	}//end testARuleLoadedAsTheSystemStillMatches()

	/**
	 * Every load is logged with the literal reason and this class as the caller.
	 *
	 * @return void
	 */
	public function testTheSeamUseIsLoggedWithItsReason(): void {
		$this->logger->expects($this->once())->method('info')->with(
			$this->anything(),
			$this->callback(
				static fn (array $context): bool => $context['reason'] === 'anonymisation applies every prohibition and standing consent, whatever the user may read'
					&& str_starts_with((string)$context['caller'], PolicyMatchService::class . '::') === true
			)
		);

		$matcher = $this->matcher();
		$matcher->match(entityText: 'Pieter de Vries', entityType: 'PERSON');
		// The cache holds the rules for the rest of the request: no second system run.
		$matcher->match(entityText: 'Jan Jansen', entityType: 'PERSON');
	}//end testTheSeamUseIsLoggedWithItsReason()

	/**
	 * When the system run hands back nothing usable, the matcher matches nothing rather than failing.
	 *
	 * @return void
	 */
	public function testANonArraySystemResultMatchesNothing(): void {
		$objectService = $this->createMock(ObjectServiceInterface::class);
		$objectService->method('runAsSystem')->willReturn(null);
		$matcher = new PolicyMatchService(
			logger: $this->logger,
			config: $this->createMock(IAppConfig::class),
			objectService: $objectService
		);

		self::assertNull($matcher->match(entityText: 'Pieter de Vries', entityType: 'PERSON'));
	}//end testANonArraySystemResultMatchesNothing()
}//end class
