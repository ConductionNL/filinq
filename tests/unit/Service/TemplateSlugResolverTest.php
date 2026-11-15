<?php

/**
 * Unit tests for TemplateSlugResolver
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\TemplateSlugResolver;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Unit tests for TemplateSlugResolver::resolve() and ::assertAvailable(),
 * added for openspec/changes/filinq-configurable-report-templates.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#requirement-template-slug-resolution-req-tmpl-13
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class TemplateSlugResolverTest extends TestCase {

	/**
	 * Mocked OpenRegisterResolver for register/schema resolution.
	 *
	 * @var OpenRegisterResolver|MockObject
	 */
	private OpenRegisterResolver|MockObject $mockRegisterResolver;

	/**
	 * Build a TemplateSlugResolver wired to a mocked ObjectService.
	 *
	 * @param ObjectService|MockObject $mockObjectService The OpenRegister object-service mock.
	 *
	 * @return TemplateSlugResolver
	 */
	private function buildResolver(ObjectService|MockObject $mockObjectService): TemplateSlugResolver {
		$mockAppManager = $this->createMock(IAppManager::class);
		$mockAppManager->method('getInstalledApps')->willReturn(['openregister']);

		$mockContainer = $this->createMock(ContainerInterface::class);
		$mockContainer->method('get')
			->with('OCA\OpenRegister\Service\ObjectService')
			->willReturn($mockObjectService);

		$this->mockRegisterResolver = $this->createMock(OpenRegisterResolver::class);
		$this->mockRegisterResolver->method('getRegisterAndSchema')
			->willReturn(['register' => 'reg-1', 'schema' => 'schema-1']);

		return new TemplateSlugResolver(
			$mockContainer,
			$mockAppManager,
			$this->mockRegisterResolver,
		);
	}//end buildResolver()

	/**
	 * A tenant-specific match is returned without a fallback lookup.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#scenario-resolve-a-tenant-specific-template
	 */
	public function testResolveReturnsTenantSpecificMatch(): void {
		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->method('buildSearchQuery')->willReturn([]);
		$mockObjectService->expects($this->once())
			->method('searchObjectsPaginated')
			->willReturn([
				'results' => [
					['id' => 'tenant-tpl', 'namespace' => 'learniq', 'slug' => 'report-card', 'tenantId' => 'school-a'],
				],
				'total' => 1,
			]);

		$resolver = $this->buildResolver($mockObjectService);

		$result = $resolver->resolve('learniq', 'report-card', 'school-a');

		$this->assertEquals('tenant-tpl', $result['id']);

	}//end testResolveReturnsTenantSpecificMatch()

	/**
	 * No tenant-specific template exists: falls back to the namespace-wide
	 * default (the same slug, no tenantId).
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#scenario-fall-back-to-the-namespace-wide-default
	 */
	public function testResolveFallsBackToNamespaceDefault(): void {
		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->method('buildSearchQuery')->willReturn([]);
		$mockObjectService->method('searchObjectsPaginated')
			->willReturnOnConsecutiveCalls(
				['results' => [], 'total' => 0],
				[
					'results' => [
						['id' => 'default-tpl', 'namespace' => 'learniq', 'slug' => 'report-card', 'tenantId' => null],
					],
					'total' => 1,
				]
			);

		$resolver = $this->buildResolver($mockObjectService);

		$result = $resolver->resolve('learniq', 'report-card', 'school-b');

		$this->assertEquals('default-tpl', $result['id']);

	}//end testResolveFallsBackToNamespaceDefault()

	/**
	 * No match in either form raises a 404.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#scenario-no-match-at-all
	 */
	public function testResolveThrows404WhenNothingMatches(): void {
		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->method('buildSearchQuery')->willReturn([]);
		$mockObjectService->method('searchObjectsPaginated')
			->willReturn(['results' => [], 'total' => 0]);

		$resolver = $this->buildResolver($mockObjectService);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(404);

		$resolver->resolve('learniq', 'does-not-exist', null);

	}//end testResolveThrows404WhenNothingMatches()

	/**
	 * Resolution without a tenantId goes straight to the namespace lookup.
	 *
	 * @return void
	 */
	public function testResolveWithoutTenantIdSkipsTenantLookup(): void {
		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->method('buildSearchQuery')->willReturn([]);
		$mockObjectService->expects($this->once())
			->method('searchObjectsPaginated')
			->willReturn([
				'results' => [
					['id' => 'default-tpl', 'namespace' => 'learniq', 'slug' => 'report-card', 'tenantId' => null],
				],
				'total' => 1,
			]);

		$resolver = $this->buildResolver($mockObjectService);

		$result = $resolver->resolve('learniq', 'report-card');

		$this->assertEquals('default-tpl', $result['id']);

	}//end testResolveWithoutTenantIdSkipsTenantLookup()

	/**
	 * assertAvailable() rejects a slug already claimed by the same
	 * (namespace, tenantId) pair.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#scenario-duplicate-slug-within-the-same-namespace-and-tenant-is-rejected
	 */
	public function testAssertAvailableRejectsDuplicateSlugForSameTenant(): void {
		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->method('buildSearchQuery')->willReturn([]);
		$mockObjectService->method('searchObjectsPaginated')
			->willReturn([
				'results' => [
					['id' => 'existing-tpl', 'namespace' => 'learniq', 'slug' => 'report-card', 'tenantId' => 'school-a'],
				],
				'total' => 1,
			]);

		$resolver = $this->buildResolver($mockObjectService);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);
		$this->expectExceptionMessage('report-card');

		$resolver->assertAvailable('learniq', 'report-card', 'school-a');

	}//end testAssertAvailableRejectsDuplicateSlugForSameTenant()

	/**
	 * A different tenant may reuse the same slug within the same namespace.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#scenario-slug-is-optional-and-namespace-scoped
	 */
	public function testAssertAvailableAllowsSameSlugForDifferentTenant(): void {
		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->method('buildSearchQuery')->willReturn([]);
		$mockObjectService->method('searchObjectsPaginated')
			->willReturn([
				'results' => [
					['id' => 'existing-tpl', 'namespace' => 'learniq', 'slug' => 'report-card', 'tenantId' => 'school-a'],
				],
				'total' => 1,
			]);

		$resolver = $this->buildResolver($mockObjectService);

		// No exception expected.
		$resolver->assertAvailable('learniq', 'report-card', 'school-b');
		$this->addToAssertionCount(1);

	}//end testAssertAvailableAllowsSameSlugForDifferentTenant()
}//end class
