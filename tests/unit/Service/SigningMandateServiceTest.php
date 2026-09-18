<?php

/**
 * Unit tests for SigningMandateService
 *
 * The rule a consuming app declares about its own record types, and the
 * refusal that quotes it back (signing-folder-across-cases REQ-SFC-04).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\SigningMandateService;
use OCP\IAppConfig;
use OCP\IGroupManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the per-type mandate declarations.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningMandateServiceTest extends TestCase {

	/**
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $config;

	/**
	 * @var IGroupManager|MockObject
	 */
	private IGroupManager|MockObject $groupManager;

	/**
	 * @var SigningMandateService
	 */
	private SigningMandateService $service;

	/**
	 * The stored declarations, as the app config would hold them.
	 *
	 * @var string
	 */
	private string $stored = '';

	/**
	 * Set up the service over an in-memory app config.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IAppConfig::class);
		$this->config->method('getValueString')->willReturnCallback(
			function (string $app, string $key, string $default = ''): string {
				return ($this->stored === '' ? $default : $this->stored);
			}
		);
		$this->config->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->stored = $value;
				return true;
			}
		);

		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->service = new SigningMandateService(
			config: $this->config,
			groupManager: $this->groupManager
		);

	}//end setUp()

	/**
	 * A request carries its type as the consuming app plus the record schema.
	 *
	 * @return void
	 */
	public function testTheTypeReferenceIsTheAppAndTheRecordSchema(): void {
		$reference = $this->service->typeReference(['sourceApp' => 'dossiq', 'subjectSchema' => 'besluit']);

		$this->assertSame('dossiq/besluit', $reference);

	}//end testTheTypeReferenceIsTheAppAndTheRecordSchema()

	/**
	 * A request that names no record type has no type reference to bind a rule to.
	 *
	 * @return void
	 */
	public function testARequestWithoutAnAppOrSchemaHasNoTypeReference(): void {
		$this->assertSame('', $this->service->typeReference(['sourceApp' => 'dossiq']));
		$this->assertSame('', $this->service->typeReference([]));

	}//end testARequestWithoutAnAppOrSchemaHasNoTypeReference()

	/**
	 * With no declaration, filinq invents no restriction of its own.
	 *
	 * @return void
	 */
	public function testNoDeclarationMeansNoInventedRestriction(): void {
		$this->groupManager->expects($this->never())->method('isInGroup');

		$may = $this->service->maySign(
			['sourceApp' => 'dossiq', 'subjectSchema' => 'besluit'],
			'beleidsmedewerker'
		);

		$this->assertTrue($may);

	}//end testNoDeclarationMeansNoInventedRestriction()

	/**
	 * A declared mandate admits the group that holds it and refuses everyone else.
	 *
	 * @return void
	 */
	public function testTheDeclaredGroupHoldsTheMandateAndNobodyElseDoes(): void {
		$this->service->declareMandate(
			typeReference: 'dossiq/besluit',
			groups: ['portefeuillehouders'],
			rule: 'Only the portefeuillehouder signs a besluit'
		);

		$this->groupManager->method('isInGroup')->willReturnCallback(
			static function (string $userId, string $group): bool {
				return ($userId === 'wethouder' && $group === 'portefeuillehouders');
			}
		);

		$request = ['sourceApp' => 'dossiq', 'subjectSchema' => 'besluit'];

		$this->assertTrue($this->service->maySign($request, 'wethouder'));
		$this->assertFalse($this->service->maySign($request, 'beleidsmedewerker'));

	}//end testTheDeclaredGroupHoldsTheMandateAndNobodyElseDoes()

	/**
	 * A mandate on one type says nothing about another type.
	 *
	 * @return void
	 */
	public function testAMandateBindsToItsOwnTypeOnly(): void {
		$this->service->declareMandate(
			typeReference: 'dossiq/besluit',
			groups: ['portefeuillehouders'],
			rule: 'Only the portefeuillehouder signs a besluit'
		);

		$this->groupManager->method('isInGroup')->willReturn(false);

		$this->assertTrue(
			$this->service->maySign(['sourceApp' => 'dossiq', 'subjectSchema' => 'brief'], 'beleidsmedewerker')
		);

	}//end testAMandateBindsToItsOwnTypeOnly()

	/**
	 * The refusal names the type, the rule in the app's own words, and who holds it.
	 *
	 * @return void
	 */
	public function testTheRefusalQuotesTheRuleThatRefused(): void {
		$this->service->declareMandate(
			typeReference: 'dossiq/besluit',
			groups: ['portefeuillehouders'],
			rule: 'Only the portefeuillehouder signs a besluit'
		);

		$this->groupManager->method('isInGroup')->willReturn(false);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Only the portefeuillehouder signs a besluit');

		$this->service->assertMaySign(
			['sourceApp' => 'dossiq', 'subjectSchema' => 'besluit'],
			'beleidsmedewerker'
		);

	}//end testTheRefusalQuotesTheRuleThatRefused()

	/**
	 * A mandate naming no group would empty a folder without saying so.
	 *
	 * @return void
	 */
	public function testAMandateWithoutAGroupIsRefused(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('at least one group');

		$this->service->declareMandate(typeReference: 'dossiq/besluit', groups: ['', '  '], rule: 'Nobody');

	}//end testAMandateWithoutAGroupIsRefused()

	/**
	 * A mandate has to bind to an app and a schema, not to free text.
	 *
	 * @return void
	 */
	public function testAMandateBindsToATypeReference(): void {
		$this->expectException(InvalidArgumentException::class);

		$this->service->declareMandate(typeReference: 'besluiten', groups: ['a'], rule: 'Some rule');

	}//end testAMandateBindsToATypeReference()

	/**
	 * A mandate says in words which rule it is, because a refusal quotes it.
	 *
	 * @return void
	 */
	public function testAMandateSaysWhichRuleItIs(): void {
		$this->expectException(InvalidArgumentException::class);

		$this->service->declareMandate(typeReference: 'dossiq/besluit', groups: ['a'], rule: '   ');

	}//end testAMandateSaysWhichRuleItIs()

	/**
	 * A withdrawn mandate stops restricting, and withdrawing twice says so.
	 *
	 * @return void
	 */
	public function testAWithdrawnMandateStopsRestricting(): void {
		$this->service->declareMandate(
			typeReference: 'dossiq/besluit',
			groups: ['portefeuillehouders'],
			rule: 'Only the portefeuillehouder signs a besluit'
		);
		$this->groupManager->method('isInGroup')->willReturn(false);

		$request = ['sourceApp' => 'dossiq', 'subjectSchema' => 'besluit'];
		$this->assertFalse($this->service->maySign($request, 'beleidsmedewerker'));

		$this->assertTrue($this->service->withdrawMandate('dossiq/besluit'));
		$this->assertTrue($this->service->maySign($request, 'beleidsmedewerker'));
		$this->assertFalse($this->service->withdrawMandate('dossiq/besluit'));

	}//end testAWithdrawnMandateStopsRestricting()

	/**
	 * A declaration store nobody can read is reported, not read as "no rules".
	 *
	 * @return void
	 */
	public function testAnUnreadableStoreIsReportedRatherThanReadAsNoRules(): void {
		$this->stored = '{ this is not json';

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('could not be read');

		$this->service->declarations();

	}//end testAnUnreadableStoreIsReportedRatherThanReadAsNoRules()
}//end class
