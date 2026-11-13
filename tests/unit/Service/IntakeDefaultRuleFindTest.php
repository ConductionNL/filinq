<?php

/**
 * A default rule is found by its uuid, so the registry lookup reads the
 * purpose of the rule a document was stamped with.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\Filinq\Service\IntakeDefaultRuleService
 */
class IntakeDefaultRuleFindTest extends TestCase {

	/**
	 * The rule with the uuid comes back with its purpose; an unknown or empty uuid finds none.
	 *
	 * @return void
	 */
	public function testARuleIsFoundByUuid(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturn(
			[
				['uuid' => 'rule-1', 'channel' => 'scan', 'brpPurpose' => ''],
				['@self' => ['id' => 'rule-2'], 'channel' => 'mail', 'brpPurpose' => 'Behandeling bezwaar'],
			]
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$service = new IntakeDefaultRuleService($resolver, $this->createMock(LoggerInterface::class));

		$this->assertSame('Behandeling bezwaar', ($service->find(uuid: ' rule-2 ')['brpPurpose'] ?? null));
		$this->assertSame('scan', ($service->find(uuid: 'rule-1')['channel'] ?? null));
		$this->assertNull($service->find(uuid: 'rule-9'));
		$this->assertNull($service->find(uuid: ''));

	}//end testARuleIsFoundByUuid()
}//end class
