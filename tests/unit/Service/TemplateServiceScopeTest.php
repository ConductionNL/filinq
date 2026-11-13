<?php

/**
 * Unit tests for TemplateService::listForObject()
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

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateVersionService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Templates offered on a host object follow their scope.
 *
 * @covers \OCA\Filinq\Service\TemplateService
 * @covers \OCA\Filinq\Service\TemplateScope
 * @uses   \OCA\Filinq\Service\Support\OpenRegisterAccess
 *
 * @spec openspec/changes/template-library-leaf/specs/template-management/spec.md#requirement-template-scope-per-register-and-schema-req-tmpl-13
 */
class TemplateServiceScopeTest extends TestCase {

	/**
	 * The service over a store holding these templates.
	 *
	 * @param list<mixed> $templates The stored templates, as OpenRegister returns them.
	 * @param int         $limit     The limit the listing must be asked for.
	 *
	 * @return TemplateService The service.
	 */
	private function service(array $templates, int $limit=100): TemplateService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->expects($this->once())->method('buildSearchQuery')
			->with(['_limit' => $limit, '_offset' => 0], 'reg-templates', 'schema-template')
			->willReturn(['q' => 1]);
		$objectService->expects($this->once())->method('searchObjectsPaginated')
			->with(['q' => 1])
			->willReturn(['results' => $templates, 'total' => count($templates)]);

		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with('OCA\OpenRegister\Service\ObjectService')->willReturn($objectService);
		$resolver = $this->createMock(OpenRegisterResolver::class);
		$resolver->method('getRegisterAndSchema')->willReturn(['register' => 'reg-templates', 'schema' => 'schema-template']);

		return new TemplateService(
			new OpenRegisterAccess(apps: $apps, container: $container),
			$resolver,
			$this->createMock(TemplateVersionService::class),
			$this->createMock(IUserSession::class),
			$this->createMock(IAppConfig::class)
		);
	}//end service()

	/**
	 * The spec scenario: a template scoped to dossiq/case does not appear on dossiq/contact; an unscoped one does.
	 *
	 * @return void
	 */
	public function testAScopedTemplateOnlyAppearsOnItsSchema(): void {
		$templates = [
			['id' => 'case-letter', 'scope' => [['register' => 'dossiq', 'schema' => 'case']]],
			['id' => 'any', 'scope' => []],
			['id' => 'no-scope-key'],
			['id' => 'null-scope', 'scope' => null],
		];

		self::assertSame(['any', 'no-scope-key', 'null-scope'], array_column($this->service($templates)->listForObject('dossiq', 'contact'), 'id'));
	}//end testAScopedTemplateOnlyAppearsOnItsSchema()

	/**
	 * A template listing the pair among others appears; slugs are trimmed but matched exactly.
	 *
	 * @return void
	 */
	public function testAPairAmongSeveralMatchesExactly(): void {
		$templates = [
			['id' => 'both', 'scope' => [['register' => 'pipelinq', 'schema' => 'lead'], ['register' => ' dossiq ', 'schema' => 'case ']]],
			['id' => 'other-register', 'scope' => [['register' => 'decidiq', 'schema' => 'case']]],
			['id' => 'case-insensitive-miss', 'scope' => [['register' => 'Dossiq', 'schema' => 'Case']]],
			['id' => 'half-pair', 'scope' => [['register' => 'dossiq']]],
		];

		self::assertSame(['both'], array_column($this->service($templates)->listForObject(' dossiq', 'case '), 'id'));
	}//end testAPairAmongSeveralMatchesExactly()

	/**
	 * A malformed scope is offered nowhere, malformed scope entries never match, and non-template rows are skipped.
	 *
	 * @return void
	 */
	public function testMalformedScopesAndRowsAreNotOffered(): void {
		$templates = [
			['id' => 'string-scope', 'scope' => 'dossiq/case'],
			['id' => 'scalar-entries', 'scope' => ['dossiq', 'case']],
			'not-a-template',
			42,
		];

		self::assertSame([], $this->service($templates)->listForObject('dossiq', 'case'));
	}//end testMalformedScopesAndRowsAreNotOffered()

	/**
	 * Entities are read through jsonSerialize, and the limit is passed through.
	 *
	 * @return void
	 */
	public function testEntitiesAreSerialisedAndTheLimitIsPassed(): void {
		$entity = new class implements \JsonSerializable {
			/**
			 * The template as an array.
			 *
			 * @return array<string, mixed> The template.
			 */
			public function jsonSerialize(): array {
				return ['id' => 'entity', 'name' => 'Brief', 'scope' => [['register' => 'dossiq', 'schema' => 'case']]];
			}//end jsonSerialize()
		};

		self::assertSame(
			[['id' => 'entity', 'name' => 'Brief', 'scope' => [['register' => 'dossiq', 'schema' => 'case']]]],
			$this->service([$entity], 25)->listForObject('dossiq', 'case', 25)
		);
	}//end testEntitiesAreSerialisedAndTheLimitIsPassed()
}//end class
