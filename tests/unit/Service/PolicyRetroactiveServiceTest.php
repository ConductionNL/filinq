<?php

/**
 * Unit tests for PolicyRetroactiveService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\PolicyMatchService;
use OCA\OpenRegister\Service\ObjectService;
use OCA\DocuDesk\Service\PolicyRetroactiveService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Cover the contract of PolicyRetroactiveService.
 *
 * Spec §5 of entity-publication-policies. We exercise the public API directly
 * with stubs — the OpenRegister ObjectService is mocked.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PolicyRetroactiveServiceTest extends TestCase {

	/**
	 * Mock logger.
	 *
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * Mock DI container.
	 *
	 * @var ObjectService|MockObject
	 */
	private ObjectService|MockObject $mockObjectService;

	/**
	 * Mock policy matcher.
	 *
	 * @var PolicyMatchService|MockObject
	 */
	private PolicyMatchService|MockObject $mockPolicyMatcher;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockLogger = $this->createMock(originalClassName: LoggerInterface::class);
		$this->mockObjectService = $this->createMock(originalClassName: ObjectService::class);
		$this->mockPolicyMatcher = $this->createMock(originalClassName: PolicyMatchService::class);

	}//end setUp()

	/**
	 * Build a fresh service instance with the configured mocks.
	 *
	 * @return PolicyRetroactiveService
	 */
	private function makeService(): PolicyRetroactiveService {
		return new PolicyRetroactiveService(
			logger: $this->mockLogger,
			policyMatcher: $this->mockPolicyMatcher,
			objectService: $this->mockObjectService
		);

	}//end makeService()

	/**
	 * Inactive prohibitions MUST short-circuit and resolve nothing.
	 *
	 * @return void
	 */
	public function testInactiveProhibitionResolvesNothing(): void {
		$this->mockObjectService->expects($this->never())->method('searchObjectsBySlug');

		$service = $this->makeService();
		$result = $service->applyProhibitionMutation(
			prohibition: [
				'active' => false,
				'matchRules' => [['type' => 'exact', 'value' => 'Jan Janssen']],
				'@self' => ['id' => 'p-1'],
				'entityType' => 'PERSON',
			]
		);

		$this->assertSame(expected: 0, actual: $result);

	}//end testInactiveProhibitionResolvesNothing()

	/**
	 * Time-bound prohibitions outside their validity window MUST not sweep.
	 *
	 * @return void
	 */
	public function testFutureProhibitionResolvesNothing(): void {
		$this->mockObjectService->expects($this->never())->method('searchObjectsBySlug');

		$service = $this->makeService();
		$result = $service->applyProhibitionMutation(
			prohibition: [
				'active' => true,
				'validFrom' => '2099-01-01T00:00:00Z',
				'matchRules' => [['type' => 'exact', 'value' => 'Jan Janssen']],
				'@self' => ['id' => 'p-1'],
				'entityType' => 'PERSON',
			]
		);

		$this->assertSame(expected: 0, actual: $result);

	}//end testFutureProhibitionResolvesNothing()

	/**
	 * Prohibitions without a UUID anchor MUST be skipped with a warning.
	 *
	 * @return void
	 */
	public function testProhibitionWithoutUuidLogsAndReturnsZero(): void {
		$this->mockLogger->expects($this->once())->method('warning');

		$service = $this->makeService();
		$result = $service->applyProhibitionMutation(
			prohibition: [
				'active' => true,
				'matchRules' => [['type' => 'exact', 'value' => 'Jan Janssen']],
				'entityType' => 'PERSON',
			]
		);

		$this->assertSame(expected: 0, actual: $result);

	}//end testProhibitionWithoutUuidLogsAndReturnsZero()

	/**
	 * Standing-consent mutation MUST invalidate the matcher cache and do nothing else.
	 *
	 * @return void
	 */
	public function testStandingConsentMutationOnlyInvalidatesCache(): void {
		$this->mockPolicyMatcher->expects($this->once())->method('invalidateCache');
		$this->mockObjectService->expects($this->never())->method('searchObjectsBySlug');

		$this->makeService()->applyStandingConsentMutation();

	}//end testStandingConsentMutationOnlyInvalidatesCache()

	/**
	 * Rule removal MUST invalidate the matcher cache and do nothing else.
	 *
	 * @return void
	 */
	public function testRuleRemovalOnlyInvalidatesCache(): void {
		$this->mockPolicyMatcher->expects($this->once())->method('invalidateCache');
		$this->mockObjectService->expects($this->never())->method('searchObjectsBySlug');

		$this->makeService()->applyRuleRemoval();

	}//end testRuleRemovalOnlyInvalidatesCache()

}//end class
