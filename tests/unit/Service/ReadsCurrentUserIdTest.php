<?php

/**
 * Unit tests for the session user id read shared by several services.
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

use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * Pins what each class reads as the current user id.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\ReadsCurrentUserId
 * @covers \OCA\Filinq\Controller\SigningFolderController
 * @covers \OCA\Filinq\Service\CaseArchiveService
 * @covers \OCA\Filinq\Service\DocumentDomainService
 * @covers \OCA\Filinq\Service\DocumentMergeService
 * @covers \OCA\Filinq\Service\IntakeDetachmentService
 * @covers \OCA\Filinq\Service\IntakeService
 * @covers \OCA\Filinq\Service\PartySuggestionService
 */
class ReadsCurrentUserIdTest extends TestCase {

	/**
	 * @return array<string, array{0: class-string}>
	 */
	public static function classes(): array {
		$cases = [];
		foreach ([
			\OCA\Filinq\Controller\SigningFolderController::class,
			\OCA\Filinq\Service\CaseArchiveService::class,
			\OCA\Filinq\Service\DocumentDomainService::class,
			\OCA\Filinq\Service\DocumentMergeService::class,
			\OCA\Filinq\Service\IntakeDetachmentService::class,
			\OCA\Filinq\Service\IntakeService::class,
			\OCA\Filinq\Service\PartySuggestionService::class,
		] as $class) {
			$cases[substr(strrchr($class, '\\'), 1)] = [$class];
		}
		return $cases;
	}

	/**
	 * Call the class's currentUserId() with the given session user.
	 *
	 * @param class-string $class The class.
	 * @param IUser|null $user The session user.
	 *
	 * @return mixed
	 */
	private function read(string $class, ?IUser $user): mixed {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$object = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
		(function () use ($session): void {
			$this->userSession = $session;
		})->call($object);
		return (new \ReflectionMethod($class, 'currentUserId'))->invoke($object);
	}

	/**
	 * @dataProvider classes
	 *
	 * @param class-string $class The class.
	 */
	public function testReadsTheSessionUserOrNothing(string $class): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('ann');

		$this->assertSame('ann', $this->read(class: $class, user: $user));
		$this->assertSame('', $this->read(class: $class, user: null));
	}
}
