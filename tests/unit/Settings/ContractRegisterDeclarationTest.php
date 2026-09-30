<?php

/**
 * Unit tests for the contract schema declaration
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#1-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * What the register declares about a contract: its lifecycle in the dialect
 * OpenRegister reads, its two reminders, its own authorization, and the
 * signing properties the contract detail reads.
 */
class ContractRegisterDeclarationTest extends TestCase {

	/**
	 * The parsed register.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function register(): array {
		$parsed = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
		$this->assertIsArray($parsed);

		return $parsed;

	}//end register()

	/**
	 * The contract schema is in the filinq register, at a new register version.
	 *
	 * @return array<string, mixed> The contract schema.
	 */
	private function contract(): array {
		$register = $this->register();
		$this->assertContains('contract', $register['components']['registers']['filinq']['schemas']);
		$this->assertTrue(version_compare($register['info']['version'], '8.39.0', '>='));

		return $register['components']['schemas']['contract'];

	}//end contract()

	/**
	 * The lifecycle uses `initial` (OpenRegister ignores `initialState`) and
	 * declares exactly the four moves, from active only after activation.
	 *
	 * @return void
	 */
	public function testTheLifecycleIsDeclaredInTheDialectOpenRegisterReads(): void {
		$lifecycle = $this->contract()['x-openregister-lifecycle'];

		$this->assertSame('status', $lifecycle['field']);
		$this->assertSame('draft', $lifecycle['initial']);
		$this->assertArrayNotHasKey('initialState', $lifecycle);
		$pairs = [];
		foreach ($lifecycle['transitions'] as $name => $transition) {
			$pairs[$name] = $transition['from'] . '->' . $transition['to'];
		}

		$this->assertSame(
			['activate' => 'draft->active', 'renew' => 'active->renewed', 'terminate' => 'active->terminated', 'expire' => 'active->expired'],
			$pairs
		);
		foreach (['renewed', 'terminated', 'expired'] as $terminal) {
			$this->assertTrue($lifecycle['states'][$terminal]['terminal']);
		}

		$this->assertSame(array_keys($lifecycle['states']), $this->contract()['properties']['status']['enum']);

	}//end testTheLifecycleIsDeclaredInTheDialectOpenRegisterReads()

	/**
	 * Both reminders use the scheduled dialect, for active contracts, to the
	 * contract managers and the object's managers, in Dutch and English.
	 *
	 * @return void
	 */
	public function testTheTwoRemindersAreDeclared(): void {
		$notifications = $this->contract()['x-openregister-notifications'];

		$this->assertSame(['noticeDeadline', 'endDate'], array_keys($notifications));
		foreach ($notifications as $entry) {
			$this->assertSame('scheduled', $entry['trigger']['type']);
			$this->assertSame(['status' => 'active'], $entry['trigger']['filter']);
			$this->assertSame(['nc-notification'], $entry['channels']);
			$this->assertSame(['kind' => 'groups', 'groups' => ['filinq-contract-managers']], $entry['recipients'][0]);
			$this->assertSame(['kind' => 'object-acl', 'permission' => 'manage'], $entry['recipients'][1]);
			$this->assertNotSame('', $entry['subject']['nl']);
			$this->assertNotSame('', $entry['subject']['en']);
		}

	}//end testTheTwoRemindersAreDeclared()

	/**
	 * The schema names who may read, create, update and delete.
	 *
	 * @return void
	 */
	public function testTheSchemaDeclaresItsOwnAuthorization(): void {
		$authorization = $this->contract()['authorization'];

		$this->assertSame(['filinq-contract-managers'], $authorization['read']);
		$this->assertSame(['filinq-contract-managers'], $authorization['create']);
		$this->assertSame(['filinq-contract-managers'], $authorization['update']);
		$this->assertSame(['admin'], $authorization['delete']);
		$this->assertArrayNotHasKey('organisation', $this->contract()['properties']);

	}//end testTheSchemaDeclaresItsOwnAuthorization()

	/**
	 * The signing request properties the contract detail reads exist.
	 *
	 * @return void
	 */
	public function testTheReferencedSigningPropertiesExist(): void {
		$signing = $this->register()['components']['schemas']['signingRequest']['properties'];

		foreach (['status', 'deadline', 'signatureLevel'] as $property) {
			$this->assertArrayHasKey($property, $signing);
		}

	}//end testTheReferencedSigningPropertiesExist()
}//end class
