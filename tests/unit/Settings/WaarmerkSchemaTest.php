<?php

/**
 * Unit tests for the waarmerk schema (document-waarmerk-certification task 1.1, 1.2).
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/document-waarmerk-certification/tasks.md#1-register--data-model
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * The waarmerk schema holds a seal's evidence write-once, never key material,
 * and only the server writes it.
 */
class WaarmerkSchemaTest extends TestCase {

	/**
	 * A register file, decoded.
	 *
	 * @param string $file The file under lib/Settings.
	 *
	 * @return array<string, mixed>
	 */
	private function descriptor(string $file): array {
		return json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/' . $file), true);
	}//end descriptor()

	/**
	 * The waarmerk schema.
	 *
	 * @return array<string, mixed>
	 */
	private function schema(): array {
		return $this->descriptor(file: 'filinq_register.json')['components']['schemas']['waarmerk'];
	}//end schema()

	/**
	 * The schema is in the filinq register with the fields the design names.
	 *
	 * @return void
	 */
	public function testTheSchemaIsInTheRegisterWithItsFields(): void {
		$register = $this->descriptor(file: 'filinq_register.json')['components']['registers']['filinq'];
		$this->assertContains('waarmerk', $register['schemas']);

		$this->assertSame(
			[
				'documentFileId', 'documentName', 'documentHash', 'sealType', 'cmsSignature', 'certificateFingerprint',
				'certificateSubject', 'verificationCode', 'sealedAt', 'sealedBy', 'status', 'revokedAt', 'revokedBy',
				'revocationReason', 'anonymizationLinkId',
			],
			array_keys($this->schema()['properties'])
		);
		$this->assertSame(['waarmerk', 'processing-certificate', 'signing-evidence'], $this->schema()['properties']['sealType']['enum']);
		$this->assertSame(['active', 'revoked'], $this->schema()['properties']['status']['enum']);
		$this->assertSame('^[0-9a-f]{64}$', $this->schema()['properties']['documentHash']['pattern']);
	}//end testTheSchemaIsInTheRegisterWithItsFields()

	/**
	 * No field holds key material (ADR-064): no private key, PEM or credential.
	 *
	 * @return void
	 */
	public function testNoFieldHoldsKeyMaterial(): void {
		foreach (array_keys($this->schema()['properties']) as $name) {
			$this->assertDoesNotMatchRegularExpression('/key|pem|secret|password|credential/i', $name);
		}

		$this->assertStringNotContainsString('BEGIN', (string) json_encode($this->schema()));
	}//end testNoFieldHoldsKeyMaterial()

	/**
	 * The evidence is write-once; only the status and the revocation change.
	 *
	 * @return void
	 */
	public function testTheEvidenceIsWriteOnce(): void {
		$mutable = [];
		foreach ($this->schema()['properties'] as $name => $property) {
			if (($property['immutable'] ?? false) !== true) {
				$mutable[] = $name;
			}
		}

		$this->assertSame(['status', 'revokedAt', 'revokedBy', 'revocationReason'], $mutable);
	}//end testTheEvidenceIsWriteOnce()

	/**
	 * Only the server writes a seal: create, update and delete are granted to nobody.
	 *
	 * @return void
	 */
	public function testOnlyTheServerWritesASeal(): void {
		$this->assertSame(
			['read' => ['authenticated'], 'create' => [], 'update' => [], 'delete' => []],
			$this->schema()['authorization']
		);
	}//end testOnlyTheServerWritesASeal()

	/**
	 * The demo seals cover each kind of state and carry placeholders only.
	 *
	 * @return void
	 */
	public function testTheDemoSealsArePlaceholders(): void {
		$seals = array_values(
			array_filter(
				$this->descriptor(file: 'filinq_mock_register.json')['components']['objects'],
				static fn (array $row): bool => ($row['@self']['schema'] ?? '') === 'waarmerk'
			)
		);

		$this->assertCount(3, $seals);
		$this->assertSame(['active', 'active', 'revoked'], array_column($seals, 'status'));
		$this->assertSame('PLACEHOLDER_BASE64_CMS', $seals[0]['cmsSignature']);
		$this->assertNotSame('', $seals[2]['revocationReason']);
		$shipped = $this->descriptor(file: 'filinq_register.json')['components']['objects'];
		$this->assertSame([], array_filter($shipped, static fn (array $row): bool => ($row['@self']['schema'] ?? '') === 'waarmerk'), 'no placeholder seal ships');
	}//end testTheDemoSealsArePlaceholders()
}//end class
