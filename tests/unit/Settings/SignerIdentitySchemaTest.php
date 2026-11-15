<?php

/**
 * Register contract for the signer identity rails
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
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * REQ-DDSIR-004: `requiredAssurance` and `identityEvidence` are declared
 * additively, with a version bump, and a demo request shows them.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SignerIdentitySchemaTest extends TestCase {

	/**
	 * The demo request's id (design.md Seed Data).
	 *
	 * @var string
	 */
	private const DEMO_REQUEST = '00000000-0000-0000-0000-00000000e001';

	/**
	 * The demo signer's id.
	 *
	 * @var string
	 */
	private const DEMO_SIGNER = '00000000-0000-0000-0000-00000000e002';

	/**
	 * The decoded register.
	 *
	 * @var array<string, mixed>
	 */
	private array $register = [];

	/**
	 * Load the register.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->register = json_decode((string)file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);

	}//end setUp()

	/**
	 * A schema's properties.
	 *
	 * @param string $schema The schema slug.
	 *
	 * @return array<string, mixed>
	 */
	private function properties(string $schema): array {
		return $this->register['components']['schemas'][$schema]['properties'];

	}//end properties()

	/**
	 * A seed object by id.
	 *
	 * @param string $id The id.
	 *
	 * @return array<string, mixed>|null
	 */
	private function seed(string $id): ?array {
		foreach ($this->register['components']['objects'] as $object) {
			if (($object['@self']['id'] ?? '') === $id) {
				return $object;
			}
		}

		return null;

	}//end seed()

	/**
	 * The request declares its required assurance, and the guardian's, on the eIDAS scale.
	 *
	 * @return void
	 */
	public function testTheRequestDeclaresRequiredAssurance(): void {
		$properties = $this->properties(schema: 'signingRequest');

		foreach (['requiredAssurance', 'guardianRequiredAssurance', 'resolvedAssurance'] as $name) {
			$this->assertArrayHasKey($name, $properties);
			$this->assertSame(['low', 'substantial', 'high'], $properties[$name]['enum']);
		}

		$this->assertSame('array', $properties['signerEvidence']['type']);

	}//end testTheRequestDeclaresRequiredAssurance()

	/**
	 * The signer record declares the evidence tuple, hidden from list views.
	 *
	 * @return void
	 */
	public function testTheSignerRecordDeclaresTheEvidenceTuple(): void {
		$evidence = $this->properties(schema: 'signerRecord')['identityEvidence'];

		$this->assertSame('object', $evidence['type']);
		$this->assertFalse($evidence['visible']);
		$this->assertSame(
			['provider', 'means', 'assurance', 'subjectPseudonym', 'authenticatedAt', 'evidenceHash'],
			array_keys($evidence['properties'])
		);
		$this->assertSame(['low', 'substantial', 'high'], $evidence['properties']['assurance']['enum']);

	}//end testTheSignerRecordDeclaresTheEvidenceTuple()

	/**
	 * Nothing that was there before is dropped (union-additive edit).
	 *
	 * @return void
	 */
	public function testTheEditIsAdditive(): void {
		$request = array_keys($this->properties(schema: 'signingRequest'));
		$signer = array_keys($this->properties(schema: 'signerRecord'));

		foreach (['documentFileId', 'signatureLevel', 'status', 'signerIds', 'guardianConsentAge', 'consentBasis'] as $name) {
			$this->assertContains($name, $request);
		}

		foreach (['userId', 'email', 'status', 'signedAt', 'role', 'birthDate', 'actingIdentity'] as $name) {
			$this->assertContains($name, $signer);
		}

	}//end testTheEditIsAdditive()

	/**
	 * The register and both schemas are bumped so the boot import picks the fields up.
	 *
	 * @return void
	 */
	public function testTheVersionsAreBumped(): void {
		$this->assertTrue(version_compare($this->register['info']['version'], '8.18.0', '>'));
		$this->assertTrue(version_compare($this->register['components']['schemas']['signingRequest']['version'], '1.5.0', '>'));
		$this->assertTrue(version_compare($this->register['components']['schemas']['signerRecord']['version'], '1.3.0', '>'));

	}//end testTheVersionsAreBumped()

	/**
	 * A demo request asks for substantial, and its signer carries fixture evidence with no BSN.
	 *
	 * @return void
	 */
	public function testTheDemoRequestShowsTheRails(): void {
		$request = $this->seed(id: self::DEMO_REQUEST);
		$signer = $this->seed(id: self::DEMO_SIGNER);

		$this->assertNotNull($request);
		$this->assertNotNull($signer);
		$this->assertSame('signingRequest', $request['@self']['schema']);
		$this->assertSame('substantial', $request['requiredAssurance']);
		$this->assertSame('SES', $request['signatureLevel']);
		$this->assertSame([self::DEMO_SIGNER], $request['signerIds']);
		$this->assertSame('signerRecord', $signer['@self']['schema']);
		$this->assertSame(self::DEMO_REQUEST, $signer['signingRequestId']);

		$evidence = $signer['identityEvidence'];
		$this->assertSame('oidc-broker', $evidence['provider']);
		$this->assertSame('digid', $evidence['means']);
		$this->assertSame('substantial', $evidence['assurance']);
		$this->assertSame('demo-pseudonym-not-a-bsn-0001', $evidence['subjectPseudonym']);
		$this->assertSame(hash('sha256', 'fixture'), $evidence['evidenceHash']);
		$this->assertDoesNotMatchRegularExpression('/(?<!\d)\d{9}(?!\d)/', (string)json_encode($signer));

	}//end testTheDemoRequestShowsTheRails()
}//end class
