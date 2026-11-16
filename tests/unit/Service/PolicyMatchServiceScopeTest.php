<?php

/**
 * Unit tests for which organisation's rules PolicyMatchService applies
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

use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\PolicyRuleScope;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * The rules loaded through the seam are kept to the user's organisation.
 *
 * @covers \OCA\Filinq\Service\PolicyMatchService
 * @uses   \OCA\Filinq\Service\SystemContextSeam
 * @uses   \OCA\Filinq\Service\ObjectResultExtractor
 * @uses   \OCA\Filinq\Service\TextNormaliser
 * @uses   \OCA\Filinq\Service\PolicyRuleNormaliser
 * @uses   \OCA\Filinq\Service\PolicyMatchRule
 * @uses   \OCA\Filinq\Service\PolicyEntity
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md#requirement-shared-nothing-isolation-across-all-filinq-object-families-req-ddmth-003
 */
class PolicyMatchServiceScopeTest extends TestCase {

	/**
	 * Whether the object service was asked for anything.
	 *
	 * @var bool
	 */
	private bool $searched = false;

	/**
	 * A prohibition on "Pieter de Vries" in org-a and one on "Jan Jansen" in org-b,
	 * and a standing consent for "Pieter de Vries" in org-b.
	 *
	 * @param string|null $organisation What the scope answers; false-y scope object when null is wanted.
	 * @param bool        $withScope    Whether a scope is given at all.
	 *
	 * @return PolicyMatchService
	 */
	private function matcher(?string $organisation, bool $withScope = true): PolicyMatchService {
		$objectService = $this->createMock(ObjectServiceInterface::class);
		$objectService->method('runAsSystem')->willReturnCallback(static fn (callable $operation): mixed => $operation());
		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug): array {
				$this->searched = true;
				if ($schemaSlug === 'publicationProhibition') {
					return [
						self::prohibition(id: 'p-a', name: 'Pieter de Vries', organisation: 'org-a'),
						self::prohibition(id: 'p-b', name: 'Jan Jansen', organisation: 'org-b'),
						self::prohibition(id: 'p-none', name: 'Karel Klaassen', organisation: null),
					];
				}

				return [
					[
						'@self' => ['id' => 'c-b', 'organisation' => 'org-b'],
						'scope' => 'entity',
						'active' => true,
						'primaryName' => 'Pieter de Vries',
						'entityType' => 'PERSON',
						'matchRules' => [['type' => 'exact', 'value' => 'Pieter de Vries']],
					],
				];
			}
		);

		$scope = null;
		if ($withScope === true) {
			$scope = $this->createMock(PolicyRuleScope::class);
			$scope->method('organisation')->willReturn($organisation);
		}

		return new PolicyMatchService(
			logger: new NullLogger(),
			config: $this->createMock(IAppConfig::class),
			objectService: $objectService,
			scope: $scope
		);
	}//end matcher()

	/**
	 * A prohibition row.
	 *
	 * @param string      $id           The id.
	 * @param string      $name         The protected name.
	 * @param string|null $organisation The organisation, or none.
	 *
	 * @return array<string, mixed>
	 */
	private static function prohibition(string $id, string $name, ?string $organisation): array {
		$self = ['id' => $id];
		if ($organisation !== null) {
			$self['organisation'] = $organisation;
		}

		return [
			'@self' => $self,
			'primaryName' => $name,
			'entityType' => 'PERSON',
			'matchRules' => [['type' => 'exact', 'value' => $name]],
			'active' => true,
		];
	}//end prohibition()

	/**
	 * A user of org-a gets org-a's prohibition and not org-b's rules.
	 *
	 * @return void
	 */
	public function testAUserGetsOnlyTheirOrganisationsRules(): void {
		$matcher = $this->matcher(organisation: 'org-a');

		self::assertSame(['ruleId' => 'p-a', 'ruleName' => 'Pieter de Vries'], $matcher->matchProhibitionHint(entityType: 'PERSON', entityValue: 'Pieter de Vries'));
		self::assertNull($matcher->match(entityText: 'Jan Jansen', entityType: 'PERSON'));
		self::assertNull($matcher->match(entityText: 'Karel Klaassen', entityType: 'PERSON'), 'A rule without an organisation belongs to none.');
	}//end testAUserGetsOnlyTheirOrganisationsRules()

	/**
	 * A user of org-b gets org-b's prohibition and standing consent, not org-a's prohibition.
	 *
	 * @return void
	 */
	public function testAnotherOrganisationsUserGetsTheirOwnRules(): void {
		$matcher = $this->matcher(organisation: 'org-b');

		self::assertSame('p-b', $matcher->matchProhibitionHint(entityType: 'PERSON', entityValue: 'Jan Jansen')['ruleId'] ?? null);
		self::assertSame(PolicyMatchService::KIND_STANDING_CONSENT, $matcher->match(entityText: 'Pieter de Vries', entityType: 'PERSON')['kind'] ?? null);
	}//end testAnotherOrganisationsUserGetsTheirOwnRules()

	/**
	 * A user whose organisation cannot be resolved gets no rules, and nothing is loaded.
	 *
	 * @return void
	 */
	public function testAUserWithoutAnOrganisationGetsNoRules(): void {
		$matcher = $this->matcher(organisation: '');

		self::assertNull($matcher->match(entityText: 'Pieter de Vries', entityType: 'PERSON'));
		self::assertFalse($this->searched, 'Nothing is loaded through the seam for a user without an organisation.');
	}//end testAUserWithoutAnOrganisationGetsNoRules()

	/**
	 * Without a user session (a background job) every organisation's rules apply.
	 *
	 * @return void
	 */
	public function testABackgroundRunAppliesEveryOrganisationsRules(): void {
		$matcher = $this->matcher(organisation: null);

		self::assertSame('p-b', $matcher->matchProhibitionHint(entityType: 'PERSON', entityValue: 'Jan Jansen')['ruleId'] ?? null);
		self::assertSame('p-none', $matcher->matchProhibitionHint(entityType: 'PERSON', entityValue: 'Karel Klaassen')['ruleId'] ?? null);
	}//end testABackgroundRunAppliesEveryOrganisationsRules()
}//end class
