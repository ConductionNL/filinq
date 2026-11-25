<?php

/**
 * Unit tests for SlugSearch.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
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
 * @link https://conduction.nl
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\SlugSearch;
use OCA\OpenRegister\Contract\RegisterSchemaSlugs;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * An object service in OpenRegister main's form: one target value.
 */
final class TargetFormObjectService {

	/**
	 * Every call received.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $calls = [];

	/**
	 * Record the call.
	 *
	 * @param RegisterSchemaSlugs  $target        The register and schema.
	 * @param array<string, mixed> $filters       The filters.
	 * @param bool                 $_rbac         The RBAC flag.
	 * @param bool                 $_multitenancy The multitenancy flag.
	 *
	 * @return array|int
	 */
	public function searchObjectsBySlug(
		RegisterSchemaSlugs $target,
		array $filters=[],
		bool $_rbac=true,
		bool $_multitenancy=true
	): array|int {
		$this->calls[] = [
			'register'     => $target->register,
			'schema'       => $target->schema,
			'filters'      => $filters,
			'rbac'         => $_rbac,
			'multitenancy' => $_multitenancy,
		];
		return [['id' => 'from-target-form']];
	}//end searchObjectsBySlug()
}//end class

/**
 * An object service in the form of earlier OpenRegister releases: two slug strings.
 */
final class SlugFormObjectService {

	/**
	 * Every call received.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $calls = [];

	/**
	 * Record the call.
	 *
	 * @param string               $registerSlug  The register slug.
	 * @param string               $schemaSlug    The schema slug.
	 * @param array<string, mixed> $filters       The filters.
	 * @param bool                 $_rbac         The RBAC flag.
	 * @param bool                 $_multitenancy The multitenancy flag.
	 *
	 * @return array|int
	 */
	public function searchObjectsBySlug(
		string $registerSlug,
		string $schemaSlug,
		array $filters=[],
		bool $_rbac=true,
		bool $_multitenancy=true
	): array|int {
		$this->calls[] = [
			'register'     => $registerSlug,
			'schema'       => $schemaSlug,
			'filters'      => $filters,
			'rbac'         => $_rbac,
			'multitenancy' => $_multitenancy,
		];
		return 3;
	}//end searchObjectsBySlug()
}//end class

/**
 * A test double that declares only the arguments it reads, as many filinq tests do.
 */
final class NarrowObjectService {

	/**
	 * Answer with the slugs.
	 *
	 * @param string $registerSlug The register slug.
	 * @param string $schemaSlug   The schema slug.
	 *
	 * @return string|null
	 */
	public function searchObjectsBySlug(string $registerSlug, string $schemaSlug): ?string {
		return $registerSlug.'/'.$schemaSlug;
	}//end searchObjectsBySlug()
}//end class

/**
 * SlugSearch calls the form the object service declares.
 *
 * @spec openspec/specs/openregister-bridge/spec.md
 */
#[CoversClass(SlugSearch::class)]
#[CoversClass(DocumentObjectServiceResolver::class)]
final class SlugSearchTest extends TestCase {

	/**
	 * OpenRegister main receives one RegisterSchemaSlugs target and the given flags.
	 *
	 * @return void
	 */
	public function testOpenRegisterMainGetsTheTargetValue(): void {
		$objects = new TargetFormObjectService();
		$search  = new SlugSearch(objects: $objects, _rbac: false, _multitenancy: false);

		$result = $search->search(register: 'filinq', schema: 'base', filters: ['a' => 1]);

		$this->assertSame(expected: [['id' => 'from-target-form']], actual: $result);
		$this->assertSame(
			expected: [['register' => 'filinq', 'schema' => 'base', 'filters' => ['a' => 1], 'rbac' => false, 'multitenancy' => false]],
			actual: $objects->calls
		);
	}//end testOpenRegisterMainGetsTheTargetValue()

	/**
	 * An earlier release receives the two slug strings; unset arguments keep OpenRegister's defaults.
	 *
	 * @return void
	 */
	public function testAnEarlierReleaseGetsTheTwoSlugStrings(): void {
		$objects = new SlugFormObjectService();

		$result = (new SlugSearch(objects: $objects))->search(register: 'filinq', schema: 'dossier');

		$this->assertSame(expected: 3, actual: $result);
		$this->assertSame(
			expected: [['register' => 'filinq', 'schema' => 'dossier', 'filters' => [], 'rbac' => true, 'multitenancy' => true]],
			actual: $objects->calls
		);
	}//end testAnEarlierReleaseGetsTheTwoSlugStrings()

	/**
	 * Only the given arguments are passed on, and the result comes back unchanged.
	 *
	 * @return void
	 */
	public function testANarrowDoubleGetsOnlyTheSlugsAndItsResultIsPassedThrough(): void {
		$result = (new SlugSearch(objects: new NarrowObjectService()))->search(register: 'filinq', schema: 'policy');

		$this->assertSame(expected: 'filinq/policy', actual: $result);
	}//end testANarrowDoubleGetsOnlyTheSlugsAndItsResultIsPassedThrough()

	/**
	 * The form is read per class, so both forms work in one process.
	 *
	 * @return void
	 */
	public function testTheFormIsReadPerClassSoBothFormsWorkInOneProcess(): void {
		$target = new TargetFormObjectService();
		$slugs  = new SlugFormObjectService();

		(new SlugSearch(objects: $target))->search(register: 'r', schema: 's');
		(new SlugSearch(objects: $slugs))->search(register: 'r', schema: 's');
		(new SlugSearch(objects: $target))->search(register: 'r2', schema: 's2');

		$this->assertCount(expectedCount: 2, haystack: $target->calls);
		$this->assertSame(expected: 'r2', actual: $target->calls[1]['register']);
		$this->assertCount(expectedCount: 1, haystack: $slugs->calls);
	}//end testTheFormIsReadPerClassSoBothFormsWorkInOneProcess()

	/**
	 * The resolver hands out a slug search over the ObjectService it resolves.
	 *
	 * @return void
	 */
	public function testTheResolverHandsOutASlugSearchOverItsObjectService(): void {
		$objects = $this->createMock(originalClassName: ObjectService::class);
		$objects->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', 'x')
			->willReturn([['id' => 'row']]);
		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		$resolver = new DocumentObjectServiceResolver(container: $container, appManager: $apps);

		$this->assertSame(expected: [['id' => 'row']], actual: $resolver->slugSearch()->search(register: 'filinq', schema: 'x'));
	}//end testTheResolverHandsOutASlugSearchOverItsObjectService()
}//end class
