<?php

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\SlugSearch;
use OCA\OpenRegister\Contract\RegisterSchemaSlugs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * An object service in OpenRegister main's form: one target value.
 */
final class TargetFormObjectService {

	/** @var array<int, array<string, mixed>> Every call received. */
	public array $calls = [];

	public function searchObjectsBySlug(
		RegisterSchemaSlugs $target,
		array $filters=[],
		bool $_rbac=true,
		bool $_multitenancy=true
	): array|int {
		$this->calls[] = ['register' => $target->register, 'schema' => $target->schema, 'filters' => $filters, 'rbac' => $_rbac, 'multitenancy' => $_multitenancy];
		return [['id' => 'from-target-form']];
	}
}

/**
 * An object service in the form of earlier OpenRegister releases: two slug strings.
 */
final class SlugFormObjectService {

	/** @var array<int, array<string, mixed>> Every call received. */
	public array $calls = [];

	public function searchObjectsBySlug(
		string $registerSlug,
		string $schemaSlug,
		array $filters=[],
		bool $_rbac=true,
		bool $_multitenancy=true
	): array|int {
		$this->calls[] = ['register' => $registerSlug, 'schema' => $schemaSlug, 'filters' => $filters, 'rbac' => $_rbac, 'multitenancy' => $_multitenancy];
		return 3;
	}
}

/**
 * @spec openspec/specs/openregister-bridge/spec.md
 */
#[CoversClass(SlugSearch::class)]
final class SlugSearchTest extends TestCase {

	public function testOpenRegisterMainGetsTheTargetValue(): void {
		$objects = new TargetFormObjectService();

		$result = (new SlugSearch(objects: $objects))->search(register: 'filinq', schema: 'base', filters: ['a' => 1], _rbac: false, _multitenancy: false);

		$this->assertSame([['id' => 'from-target-form']], $result);
		$this->assertSame([['register' => 'filinq', 'schema' => 'base', 'filters' => ['a' => 1], 'rbac' => false, 'multitenancy' => false]], $objects->calls);
	}

	public function testAnEarlierReleaseGetsTheTwoSlugStrings(): void {
		$objects = new SlugFormObjectService();

		$result = (new SlugSearch(objects: $objects))->search(register: 'filinq', schema: 'dossier');

		$this->assertSame(3, $result);
		$this->assertSame([['register' => 'filinq', 'schema' => 'dossier', 'filters' => [], 'rbac' => true, 'multitenancy' => true]], $objects->calls);
	}

	public function testTheFormIsReadPerClassSoBothFormsWorkInOneProcess(): void {
		$target = new TargetFormObjectService();
		$slugs  = new SlugFormObjectService();

		(new SlugSearch(objects: $target))->search(register: 'r', schema: 's');
		(new SlugSearch(objects: $slugs))->search(register: 'r', schema: 's');
		(new SlugSearch(objects: $target))->search(register: 'r2', schema: 's2');

		$this->assertCount(2, $target->calls);
		$this->assertSame('r2', $target->calls[1]['register']);
		$this->assertCount(1, $slugs->calls);
	}
}
