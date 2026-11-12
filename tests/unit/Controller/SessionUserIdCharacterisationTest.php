<?php

/**
 * Unit tests for how controllers read the session user's id
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 */

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\CorrespondenceController;
use OCA\Filinq\Controller\EntitySearchController;
use OCA\Filinq\Controller\LegalHoldCaseController;
use OCA\Filinq\Controller\PseudonymisationController;
use OCA\Filinq\Controller\SubjectErasureController;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Pins the session user id each controller hands to its services: the
 * signed-in user's id, and '' when nobody is signed in.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SessionUserIdCharacterisationTest extends TestCase {

	/**
	 * The controllers that read the session user's id.
	 *
	 * @return array<string,array{0:class-string}>
	 */
	public static function controllers(): array {
		return [
			'entity search'    => [EntitySearchController::class],
			'legal hold'       => [LegalHoldCaseController::class],
			'subject erasure'  => [SubjectErasureController::class],
			'pseudonymisation' => [PseudonymisationController::class],
			'correspondence'   => [CorrespondenceController::class],
		];

	}//end controllers()

	/**
	 * A signed-in user's id is returned as is.
	 *
	 * @param class-string $class The controller under test.
	 *
	 * @return void
	 */
	#[DataProvider('controllers')]
	public function testSignedInUserId(string $class): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->assertSame('alice', $this->userIdOf($this->controllerWith($class, $user)));

	}//end testSignedInUserId()

	/**
	 * Without a session the id is the empty string.
	 *
	 * @param class-string $class The controller under test.
	 *
	 * @return void
	 */
	#[DataProvider('controllers')]
	public function testNobodySignedInIsEmpty(string $class): void {
		$this->assertSame('', $this->userIdOf($this->controllerWith($class, null)));

	}//end testNobodySignedInIsEmpty()

	/**
	 * Build the controller with only its user session wired in.
	 *
	 * @param class-string $class The controller under test.
	 * @param IUser|null   $user  The signed-in user, or null.
	 *
	 * @return object The controller.
	 */
	private function controllerWith(string $class, ?IUser $user): object {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		(function (IUserSession $session): void {
			$this->userSession = $session;
		})->call($controller, $session);

		return $controller;

	}//end controllerWith()

	/**
	 * Read the controller's session user id through its own private reader.
	 *
	 * @param object $controller The controller under test.
	 *
	 * @return string The user id.
	 */
	private function userIdOf(object $controller): string {
		$class = new ReflectionClass($controller);
		foreach (['currentUserId', 'userId', 'getCurrentUserId'] as $reader) {
			if ($class->hasMethod($reader) === true) {
				return $class->getMethod($reader)->invoke($controller);
			}
		}

		$this->fail($class->getName() . ' has no session user id reader');

	}//end userIdOf()

}//end class
