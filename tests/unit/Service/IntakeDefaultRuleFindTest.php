<?php

/**
 * Unit tests for IntakeDefaultRuleService::findRule.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The rule a document names is found by its uuid among the rule set.
 *
 * @covers \OCA\Filinq\Service\IntakeDefaultRuleService
 */
class IntakeDefaultRuleFindTest extends TestCase {

	/**
	 * The service over a rule set.
	 *
	 * @param array<int, mixed> $rules The rows searchObjectsBySlug answers.
	 *
	 * @return IntakeDefaultRuleService
	 */
	private function service(array $rules): IntakeDefaultRuleService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturn($rules);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new IntakeDefaultRuleService($resolver, $this->createMock(LoggerInterface::class));
	}//end service()

	/**
	 * A rule is found by uuid, whether the row carries it flat or under @self.
	 *
	 * @return void
	 */
	public function testARuleIsFoundByItsUuid(): void {
		$service = $this->service(
			[
				['uuid' => 'rule-scan', 'channel' => 'scan'],
				['object' => ['channel' => 'mail', 'acknowledgeWith' => 'tpl'], '@self' => ['id' => 'rule-mail']],
			]
		);

		$this->assertSame(['channel' => 'mail', 'acknowledgeWith' => 'tpl', 'uuid' => 'rule-mail'], $service->findRule('rule-mail'));
		$this->assertSame('scan', $service->findRule('rule-scan')['channel']);
	}//end testARuleIsFoundByItsUuid()

	/**
	 * An empty or unknown uuid finds nothing.
	 *
	 * @return void
	 */
	public function testAnEmptyOrUnknownUuidFindsNothing(): void {
		$service = $this->service([['uuid' => 'rule-scan']]);

		$this->assertNull($service->findRule(''));
		$this->assertNull($service->findRule('rule-gone'));
	}//end testAnEmptyOrUnknownUuidFindsNothing()
}//end class
