<?php

/**
 * Register test for the portal signature assurance field
 *
 * The level SigningService writes on a portal signature must validate
 * against the `signerRecord` schema the register ships.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Settings
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/portal-signing-surface/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use OCA\Filinq\Service\Signing\PortalSignatureAssurance;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Asserts the register declares what the portal signature writes.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SignerRecordAssuranceSchemaTest extends TestCase {

	/**
	 * The parsed register descriptor, as objects for the validator.
	 *
	 * @return object The descriptor.
	 */
	private function descriptor(): object {
		$raw = file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json');
		$this->assertIsString($raw);

		$parsed = json_decode($raw);
		$this->assertIsObject($parsed);

		return $parsed;

	}//end descriptor()

	/**
	 * Every level the cap can return validates against the declared property,
	 * and QES does not.
	 *
	 * @return void
	 */
	public function testEveryWrittenLevelValidatesAndQesDoesNot(): void {
		$signerRecord = $this->descriptor()->components->schemas->signerRecord;
		$this->assertObjectHasProperty('signatureAssurance', $signerRecord->properties);

		$property = $signerRecord->properties->signatureAssurance;
		$validator = new Validator();
		$cap = new PortalSignatureAssurance();

		foreach (['SES', 'AES', 'QES', 'XYZ'] as $requested) {
			foreach (['low', 'substantial', 'high', ''] as $trust) {
				$written = $cap->levelFor(requestedLevel: $requested, trust: $trust);
				$result = $validator->validate($written, json_encode($property));
				$this->assertTrue($result->isValid(), 'The written level ' . $written . ' must validate.');
			}
		}

		$this->assertFalse($validator->validate('QES', json_encode($property))->isValid());
		$this->assertSame(PortalSignatureAssurance::LEVELS, $property->enum);

	}//end testEveryWrittenLevelValidatesAndQesDoesNot()

	/**
	 * The versions move, so the field reaches installs that already imported.
	 *
	 * @return void
	 */
	public function testTheVersionsMoveSoTheFieldReachesExistingInstalls(): void {
		$descriptor = $this->descriptor();

		$this->assertTrue(version_compare((string)$descriptor->info->version, '8.20.0', '>='));
		$this->assertTrue(
			version_compare((string)$descriptor->components->schemas->signerRecord->version, '1.5.0', '>=')
		);

	}//end testTheVersionsMoveSoTheFieldReachesExistingInstalls()
}//end class
