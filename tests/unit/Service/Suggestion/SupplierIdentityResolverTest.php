<?php

/**
 * Unit tests for SupplierIdentityResolver
 *
 * Covers REQ-GLS-01: KvK > IBAN > normalised-name resolution order and the
 * no-resolvable-identity case.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Suggestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Suggestion;

use OCA\Filinq\Service\Suggestion\SupplierIdentityResolver;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SupplierIdentityResolver.
 */
class SupplierIdentityResolverTest extends TestCase {

	private SupplierIdentityResolver $resolver;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->resolver = new SupplierIdentityResolver();

	}//end setUp()

	/**
	 * @return void
	 */
	public function testKvkPreferredOverIbanAndName(): void {
		$result = $this->resolver->resolve([
			'supplierKvk' => '12345678',
			'supplierIban' => 'NL91ABNA0417164300',
			'supplierName' => 'Hostbaar B.V.',
		]);

		$this->assertSame('12345678', $result['identity']);
		$this->assertSame('kvk', $result['identityType']);

	}//end testKvkPreferredOverIbanAndName()

	/**
	 * @return void
	 */
	public function testIbanUsedWhenKvkAbsent(): void {
		$result = $this->resolver->resolve([
			'supplierKvk' => null,
			'supplierIban' => 'NL91ABNA0417164300',
			'supplierName' => 'Hostbaar B.V.',
		]);

		$this->assertSame('NL91ABNA0417164300', $result['identity']);
		$this->assertSame('iban', $result['identityType']);

	}//end testIbanUsedWhenKvkAbsent()

	/**
	 * @return void
	 */
	public function testNormalisedNameAsLastResort(): void {
		$result = $this->resolver->resolve([
			'supplierKvk' => null,
			'supplierIban' => null,
			'supplierName' => '  Lunchroom   De Hoek  ',
		]);

		$this->assertSame('lunchroom de hoek', $result['identity']);
		$this->assertSame('name', $result['identityType']);

	}//end testNormalisedNameAsLastResort()

	/**
	 * @return void
	 */
	public function testNoResolvableIdentityReturnsNull(): void {
		$result = $this->resolver->resolve([
			'supplierKvk' => null,
			'supplierIban' => null,
			'supplierName' => null,
		]);

		$this->assertNull($result);

	}//end testNoResolvableIdentityReturnsNull()

	/**
	 * @return void
	 */
	public function testBlankStringsTreatedAsAbsent(): void {
		$result = $this->resolver->resolve([
			'supplierKvk' => '   ',
			'supplierIban' => '',
			'supplierName' => '',
		]);

		$this->assertNull($result);

	}//end testBlankStringsTreatedAsAbsent()
}//end class
