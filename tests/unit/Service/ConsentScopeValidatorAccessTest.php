<?php

/**
 * Unit tests for ConsentScopeValidator's group check and scope transitions
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

use InvalidArgumentException;
use OCA\Filinq\Service\ConsentScopeValidator;
use OCP\IGroupManager;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Standing-consent writes need admin or the standing-consent group; a
 * consent's scope is fixed after creation and the merged record must stay valid.
 *
 * @covers \OCA\Filinq\Service\ConsentScopeValidator
 */
class ConsentScopeValidatorAccessTest extends TestCase {

	/**
	 * The group manager the validator asks.
	 *
	 * @var IGroupManager&MockObject
	 */
	private IGroupManager&MockObject $groupManager;

	/**
	 * The validator under test.
	 *
	 * @var ConsentScopeValidator
	 */
	private ConsentScopeValidator $validator;

	/**
	 * Set up the validator.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->validator    = new ConsentScopeValidator(groupManager: $this->groupManager);

	}//end setUp()

	/**
	 * A user with the given uid.
	 *
	 * @param string $uid The user id.
	 *
	 * @return IUser
	 */
	private function user(string $uid): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);

		return $user;

	}//end user()

	/**
	 * An admin passes without the group being consulted.
	 *
	 * @return void
	 */
	public function testAdminPassesWithoutGroupLookup(): void {
		$this->groupManager->expects($this->once())->method('isAdmin')->with('alice')->willReturn(true);
		$this->groupManager->expects($this->never())->method('isInGroup');

		$this->validator->requireStandingConsentAdminGroup($this->user('alice'));

	}//end testAdminPassesWithoutGroupLookup()

	/**
	 * A member of the standing-consent group passes.
	 *
	 * @return void
	 */
	public function testGroupMemberPasses(): void {
		$this->groupManager->method('isAdmin')->willReturn(false);
		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with('bob', 'filinq-standing-consent-admins')
			->willReturn(true);

		$this->validator->requireStandingConsentAdminGroup($this->user('bob'));

	}//end testGroupMemberPasses()

	/**
	 * Anyone else is refused, naming the group.
	 *
	 * @return void
	 */
	public function testOtherUsersAreRefused(): void {
		$this->groupManager->method('isAdmin')->willReturn(false);
		$this->groupManager->method('isInGroup')->willReturn(false);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Standing-consent write requires membership in the "filinq-standing-consent-admins" group.');

		$this->validator->requireStandingConsentAdminGroup($this->user('carol'));

	}//end testOtherUsersAreRefused()

	/**
	 * Changing scope after creation is refused, naming both scopes.
	 *
	 * @return void
	 */
	public function testTransitionRefusesAScopeChange(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('publicationConsent.scope cannot change from "document" to "entity" after creation.');

		$this->validator->validateTransition(['documentId' => 'doc-1'], ['scope' => 'entity']);

	}//end testTransitionRefusesAScopeChange()

	/**
	 * An update without scope keeps the existing scope and validates the merged record.
	 *
	 * @return void
	 */
	public function testTransitionValidatesTheMergedRecord(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('scope=document publicationConsent records require a non-empty documentId');

		$this->validator->validateTransition(['scope' => 'document', 'documentId' => 'doc-1'], ['documentId' => '']);

	}//end testTransitionValidatesTheMergedRecord()

	/**
	 * A valid merged entity record passes.
	 *
	 * @return void
	 */
	public function testTransitionAcceptsAValidEntityUpdate(): void {
		$existing = [
			'scope' => 'entity',
			'matchRules' => [['type' => 'exact', 'value' => 'A']],
			'consentMethod' => 'paper',
		];

		$this->validator->validateTransition($existing, ['matchRules' => [['type' => 'kvk', 'value' => '12345678']]]);
		$this->addToAssertionCount(1);

	}//end testTransitionAcceptsAValidEntityUpdate()

	/**
	 * A matchRule that is not a {type, value} object is refused with its index.
	 *
	 * @return void
	 */
	public function testMalformedMatchRuleIsRefusedWithItsIndex(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('scope=entity publicationConsent matchRules[1] must be a {type, value} object');

		$this->validator->assertValid([
			'scope' => 'entity',
			'matchRules' => [['type' => 'exact', 'value' => 'A'], ['type' => 'exact']],
			'consentMethod' => 'paper',
		]);

	}//end testMalformedMatchRuleIsRefusedWithItsIndex()
}//end class
