<?php

/**
 * Behaviour tests for OpenRegisterResolver
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Exception\RegisterNotConfiguredException;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\SettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The resolver turns stored register/schema settings into a binding and fails
 * closed when either half is missing.
 *
 * @covers \OCA\Filinq\Service\OpenRegisterResolver
 * @uses   \OCA\Filinq\Exception\RegisterNotConfiguredException
 */
class OpenRegisterResolverBehaviourTest extends TestCase {

	/**
	 * The settings the resolver reads.
	 *
	 * @var SettingsService&MockObject
	 */
	private SettingsService&MockObject $settings;

	/**
	 * The resolver under test.
	 *
	 * @var OpenRegisterResolver
	 */
	private OpenRegisterResolver $resolver;

	/**
	 * Set up the resolver over a mocked settings service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->settings = $this->createMock(SettingsService::class);
		$this->resolver = new OpenRegisterResolver($this->settings);

	}//end setUp()

	/**
	 * The template binding comes from template_register / template_schema.
	 *
	 * @return void
	 */
	public function testTemplateBindingIsReadFromSettings(): void {
		$this->settings->method('getAllSettings')->willReturn([
			'configuration' => [
				'template_register' => 'reg-1',
				'template_schema' => 'schema-1',
				'templateVersion_register' => 'other',
				'templateVersion_schema' => 'other',
			],
		]);

		$this->assertSame(['register' => 'reg-1', 'schema' => 'schema-1'], $this->resolver->getRegisterAndSchema());

	}//end testTemplateBindingIsReadFromSettings()

	/**
	 * A missing template schema fails closed.
	 *
	 * @return void
	 */
	public function testTemplateBindingWithoutSchemaIsNotConfigured(): void {
		$this->settings->method('getAllSettings')->willReturn([
			'configuration' => ['template_register' => 'reg-1', 'template_schema' => ''],
		]);

		$this->expectException(RegisterNotConfiguredException::class);
		$this->expectExceptionMessage('Template register/schema not configured');

		$this->resolver->getRegisterAndSchema();

	}//end testTemplateBindingWithoutSchemaIsNotConfigured()

	/**
	 * The template version binding comes from its own keys.
	 *
	 * @return void
	 */
	public function testTemplateVersionBindingIsReadFromSettings(): void {
		$this->settings->method('getAllSettings')->willReturn([
			'configuration' => [
				'template_register' => 'other',
				'template_schema' => 'other',
				'templateVersion_register' => 'reg-2',
				'templateVersion_schema' => 'schema-2',
			],
		]);

		$this->assertSame(['register' => 'reg-2', 'schema' => 'schema-2'], $this->resolver->getVersionRegisterAndSchema());

	}//end testTemplateVersionBindingIsReadFromSettings()

	/**
	 * A missing template version register fails closed.
	 *
	 * @return void
	 */
	public function testTemplateVersionBindingWithoutRegisterIsNotConfigured(): void {
		$this->settings->method('getAllSettings')->willReturn(['configuration' => ['templateVersion_schema' => 's']]);

		$this->expectException(RegisterNotConfiguredException::class);
		$this->expectExceptionMessage('Template version register/schema not configured');

		$this->resolver->getVersionRegisterAndSchema();

	}//end testTemplateVersionBindingWithoutRegisterIsNotConfigured()

	/**
	 * Lowercase alphanumeric namespaces are valid.
	 *
	 * @return void
	 */
	public function testValidateNamespaceAcceptsLowercaseAlphanumeric(): void {
		$this->assertTrue($this->resolver->validateNamespace('filinq2'));

	}//end testValidateNamespaceAcceptsLowercaseAlphanumeric()

	/**
	 * Anything else is refused with a 400 code.
	 *
	 * @return void
	 */
	public function testValidateNamespaceRefusesOtherCharacters(): void {
		try {
			$this->resolver->validateNamespace('Filinq-app');
			$this->fail('An uppercase/hyphenated namespace was accepted.');
		} catch (Exception $e) {
			$this->assertSame('Invalid namespace: must be lowercase alphanumeric only', $e->getMessage());
			$this->assertSame(400, $e->getCode());
		}

	}//end testValidateNamespaceRefusesOtherCharacters()

	/**
	 * Each typed binding passes the settings service's binding through.
	 *
	 * @return void
	 */
	public function testTypedBindingsPassTheResolvedBindingThrough(): void {
		$this->settings->method('resolveFinancialExtractionBinding')->willReturn(['register' => 'r1', 'schema' => 's1']);
		$this->settings->method('resolveGlAccountBookingBinding')->willReturn(['register' => 'r2', 'schema' => 's2']);
		$this->settings->method('resolveGlAccountMappingRuleBinding')->willReturn(['register' => 'r3', 'schema' => 's3']);
		$this->settings->method('resolveSignerRecordBinding')->willReturn(['register' => 'r4', 'schema' => 's4']);

		$this->assertSame(['register' => 'r1', 'schema' => 's1'], $this->resolver->getFinancialExtractionRegisterAndSchema());
		$this->assertSame(['register' => 'r2', 'schema' => 's2'], $this->resolver->getGlAccountBookingRegisterAndSchema());
		$this->assertSame(['register' => 'r3', 'schema' => 's3'], $this->resolver->getGlAccountMappingRuleRegisterAndSchema());
		$this->assertSame(['register' => 'r4', 'schema' => 's4'], $this->resolver->getSignerRecordRegisterAndSchema());

	}//end testTypedBindingsPassTheResolvedBindingThrough()

	/**
	 * Each typed binding fails closed with its own message when unset.
	 *
	 * @return void
	 */
	public function testTypedBindingsFailClosedWhenUnset(): void {
		$calls = [
			'getFinancialExtractionRegisterAndSchema' => 'Financial extraction register/schema not configured',
			'getGlAccountBookingRegisterAndSchema' => 'GL account booking register/schema not configured',
			'getGlAccountMappingRuleRegisterAndSchema' => 'GL account mapping rule register/schema not configured',
			'getSignerRecordRegisterAndSchema' => 'Signer record register/schema not configured',
		];

		foreach ($calls as $method => $message) {
			try {
				$this->resolver->$method();
				$this->fail($method . ' did not fail closed.');
			} catch (RegisterNotConfiguredException $e) {
				$this->assertSame($message, $e->getMessage());
			}
		}

	}//end testTypedBindingsFailClosedWhenUnset()
}//end class
