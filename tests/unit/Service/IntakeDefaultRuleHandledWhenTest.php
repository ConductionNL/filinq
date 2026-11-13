<?php

/**
 * Unit tests for IntakeDefaultRuleService::handledWhenByRule().
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
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
use RuntimeException;

/**
 * Each rule says when its documents count as handled; `assigned` unless it says `answered`.
 *
 * @covers \OCA\Filinq\Service\IntakeDefaultRuleService
 */
class IntakeDefaultRuleHandledWhenTest extends TestCase {

	/**
	 * The service over stored rule rows.
	 *
	 * @param array<int, mixed>|null $rows The rows; null makes the register unreadable.
	 *
	 * @return IntakeDefaultRuleService
	 */
	private function service(?array $rows): IntakeDefaultRuleService {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		if ($rows === null) {
			$resolver->method('resolve')->willThrowException(new RuntimeException('no register'));
		} else {
			$objectService = $this->createMock(ObjectService::class);
			$objectService->method('searchObjectsBySlug')->with('filinq', 'intakeDefaultRule', [])->willReturn($rows);
			$resolver->method('resolve')->willReturn($objectService);
		}

		return new IntakeDefaultRuleService($resolver, $this->createMock(LoggerInterface::class));
	}//end service()

	/**
	 * Every rule with a uuid is named, `answered` only when it says so.
	 *
	 * @return void
	 */
	public function testEachRuleSaysWhenItsDocumentsAreHandled(): void {
		$handledWhen = $this->service(
			rows: [
				['uuid' => 'mail', 'handledWhen' => 'answered'],
				['uuid' => 'scan', 'handledWhen' => 'assigned'],
				['@self' => ['id' => 'legacy']],
				['uuid' => 'odd', 'handledWhen' => 'sometime'],
				['name' => 'no uuid', 'handledWhen' => 'answered'],
			]
		)->handledWhenByRule();

		self::assertSame(['mail' => 'answered', 'scan' => 'assigned', 'legacy' => 'assigned', 'odd' => 'assigned'], $handledWhen);
	}//end testEachRuleSaysWhenItsDocumentsAreHandled()

	/**
	 * An unreadable rule set names no rules.
	 *
	 * @return void
	 */
	public function testAnUnreadableRuleSetNamesNoRules(): void {
		self::assertSame([], $this->service(rows: null)->handledWhenByRule());
	}//end testAnUnreadableRuleSetNamesNoRules()
}//end class
