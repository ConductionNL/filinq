<?php

/**
 * The signerRecord schema stores a signer invited by e-mail address only
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * A portal signer has an e-mail address and no Nextcloud account, so `userId` cannot be required.
 *
 * Seen live (NC 34, OpenRegister main): a request naming a signer by e-mail address
 * only answered 500, because a required `userId` makes OpenRegister's table column
 * NOT NULL and the empty user id is stored as NULL.
 *
 * @spec openspec/specs/portal-signing-actions/spec.md
 */
class SignerRecordSchemaTest extends TestCase {

	/**
	 * The signerRecord schema as shipped.
	 *
	 * @return array<string, mixed>
	 */
	private static function schema(): array {
		$register = json_decode((string) file_get_contents(__DIR__.'/../../../lib/Settings/filinq_register.json'), true);

		return $register['components']['schemas']['signerRecord'];

	}//end schema()

	/**
	 * Neither the schema nor the property requires a user id; who and what is signed still are.
	 *
	 * @return void
	 */
	public function testASignerWithoutAUserIdCanBeStored(): void {
		$schema = self::schema();

		$this->assertNotContains(needle: 'userId', haystack: $schema['required']);
		$this->assertArrayNotHasKey(key: 'required', array: $schema['properties']['userId']);
		$this->assertSame(expected: ['signingRequestId', 'displayName', 'status'], actual: $schema['required']);

	}//end testASignerWithoutAUserIdCanBeStored()

	/**
	 * A signer still reads only the record that names their own user id.
	 *
	 * @return void
	 */
	public function testASignerStillReadsOnlyTheirOwnRecord(): void {
		$read = self::schema()['x-openregister-authorization']['read'] ?? (self::schema()['authorization']['read'] ?? []);

		$this->assertContains(needle: ['group' => 'authenticated', 'match' => ['userId' => '$userId']], haystack: $read);

	}//end testASignerStillReadsOnlyTheirOwnRecord()
}//end class
