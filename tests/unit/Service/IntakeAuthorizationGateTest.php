<?php

/**
 * Unit tests for IntakeAuthorizationGate
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
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\OpenRegister\Db\Schema;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts who the intake inbox lets write, and that it fails CLOSED.
 *
 * The rights come from the schema's own `authorization.update` declaration, so
 * these tests hand the gate a declaration rather than a flag: a schema whose
 * rights change in the register must change what the inbox allows with no code
 * change here, and that is only true if nothing here keeps its own list.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class IntakeAuthorizationGateTest extends TestCase {

	/**
	 * Build the gate over a schema declaring the given update rights.
	 *
	 * @param array<string, mixed>|null $declaration The schema as OpenRegister serialises it, or null for no schema.
	 * @param array<int, string> $memberOf The groups the user belongs to.
	 * @param bool $isAdmin Whether the user is an instance admin.
	 * @param bool $loggedIn Whether anybody is logged in.
	 * @param bool $registerReadable Whether OpenRegister answers at all.
	 *
	 * @return IntakeAuthorizationGate The gate under test.
	 */
	private function gate(
		?array $declaration,
		array $memberOf = [],
		bool $isAdmin = false,
		bool $loggedIn = true,
		bool $registerReadable = true,
	): IntakeAuthorizationGate {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		if ($registerReadable === false) {
			$resolver->method('resolve')->willThrowException(
				new RuntimeException('OpenRegister service is not available.')
			);
		} else {
			$schema = null;
			if ($declaration !== null) {
				$schema = $this->createMock(Schema::class);
				$schema->method('jsonSerialize')->willReturn($declaration);
			}

			$objectService = $this->createMock(ObjectService::class);
			$objectService->method('setRegister')->willReturnSelf();
			$objectService->method('setSchema')->willReturnSelf();
			$objectService->method('getCurrentSchemaEntity')->willReturn($schema);
			$resolver->method('resolve')->willReturn($objectService);
		}

		$session = $this->createMock(IUserSession::class);
		if ($loggedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('anna');
			$session->method('getUser')->willReturn($user);
		} else {
			$session->method('getUser')->willReturn(null);
		}

		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isInGroup')->willReturnCallback(
			static fn (string $userId, string $group): bool => in_array($group, $memberOf, true)
		);
		$groups->method('isAdmin')->willReturn($isAdmin);

		return new IntakeAuthorizationGate(
			$resolver,
			$session,
			$groups,
			$this->createMock(LoggerInterface::class)
		);

	}//end gate()

	/**
	 * A schema that names no groups is open to anybody logged in.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testASchemaWithNoDeclaredGroupsIsOpenToAnyLoggedInUser(): void {
		$gate = $this->gate(declaration: ['authorization' => ['update' => []]]);

		$this->assertTrue($gate->mayWrite(register: 'zaken', schema: 'zaak'));

	}//end testASchemaWithNoDeclaredGroupsIsOpenToAnyLoggedInUser()

	/**
	 * A schema open to `authenticated` is open to anybody logged in.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testASchemaOpenToAuthenticatedUsersLetsTheClerkWrite(): void {
		$gate = $this->gate(declaration: ['authorization' => ['update' => ['authenticated']]]);

		$this->assertTrue($gate->mayWrite(register: 'filinq', schema: 'intakeDocument'));

	}//end testASchemaOpenToAuthenticatedUsersLetsTheClerkWrite()

	/**
	 * A restricted schema lets a member of the named group write.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAMemberOfTheNamedGroupMayWriteARestrictedSchema(): void {
		$gate = $this->gate(
			declaration: ['authorization' => ['update' => ['zaakbehandelaars']]],
			memberOf: ['zaakbehandelaars']
		);

		$this->assertTrue($gate->mayWrite(register: 'zaken', schema: 'zaak'));

	}//end testAMemberOfTheNamedGroupMayWriteARestrictedSchema()

	/**
	 * Somebody outside every named group may NOT write, admins excepted.
	 *
	 * This is the case the whole gate exists for: the clerk can read the case
	 * and is refused the write, and the refusal comes from the register's own
	 * declaration rather than from a list kept in this app.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testSomebodyOutsideEveryNamedGroupMayNotWrite(): void {
		$gate = $this->gate(
			declaration: ['authorization' => ['update' => ['zaakbehandelaars']]],
			memberOf: ['baliemedewerkers']
		);

		$this->assertFalse($gate->mayWrite(register: 'zaken', schema: 'zaak'));

	}//end testSomebodyOutsideEveryNamedGroupMayNotWrite()

	/**
	 * An instance admin writes what the groups do not cover.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAnInstanceAdminMayWriteARestrictedSchema(): void {
		$gate = $this->gate(
			declaration: ['authorization' => ['update' => ['zaakbehandelaars']]],
			isAdmin: true
		);

		$this->assertTrue($gate->mayWrite(register: 'zaken', schema: 'zaak'));

	}//end testAnInstanceAdminMayWriteARestrictedSchema()

	/**
	 * Nobody logged in writes nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAnAnonymousCallerMayNotWrite(): void {
		$gate = $this->gate(
			declaration: ['authorization' => ['update' => ['authenticated']]],
			loggedIn: false
		);

		$this->assertFalse($gate->mayWrite(register: 'filinq', schema: 'intakeDocument'));

	}//end testAnAnonymousCallerMayNotWrite()

	/**
	 * An unreachable register refuses the write rather than allowing it.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAnUnreachableRegisterFailsClosed(): void {
		$gate = $this->gate(declaration: null, registerReadable: false);

		$this->assertFalse($gate->mayWrite(register: 'zaken', schema: 'zaak'));

	}//end testAnUnreachableRegisterFailsClosed()

	/**
	 * A schema OpenRegister does not know refuses the write.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAnUnknownSchemaFailsClosed(): void {
		$gate = $this->gate(declaration: null);

		$this->assertFalse($gate->mayWrite(register: 'zaken', schema: 'niet-bestaand'));

	}//end testAnUnknownSchemaFailsClosed()
}//end class
