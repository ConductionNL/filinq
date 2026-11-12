<?php

/**
 * Unit tests for how the financial extraction and the OCR service find a file
 * in the session user's own folder
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\FinancialExtractionService;
use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Service\SessionUserFile;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Pins the allowed and the refused outcomes of the session user's file
 * lookup: only a file in the signed-in user's own folder is returned.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SessionUserFileCharacterisationTest extends TestCase {

	/**
	 * Both services and the name of their lookup.
	 *
	 * @return array<string,array{0:class-string,1:string}>
	 */
	public static function services(): array {
		return [
			'financial extraction' => [FinancialExtractionService::class, 'resolveFile'],
			'ocr'                  => [OcrService::class, 'getFileById'],
		];

	}//end services()

	/**
	 * Allowed: the first node in the signed-in user's own folder, when it is a file.
	 *
	 * @param class-string $class  The service under test.
	 * @param string       $lookup The lookup method.
	 *
	 * @return void
	 */
	#[DataProvider('services')]
	public function testOwnFileIsFound(string $class, string $lookup): void {
		$file = $this->createMock(File::class);
		$this->assertSame($file, $this->find([$class, $lookup], $this->root('alice', [$file, $this->createMock(File::class)])));

	}//end testOwnFileIsFound()

	/**
	 * Refused: nobody signed in, so no folder is even opened.
	 *
	 * @param class-string $class  The service under test.
	 * @param string       $lookup The lookup method.
	 *
	 * @return void
	 */
	#[DataProvider('services')]
	public function testNoSessionFindsNothing(string $class, string $lookup): void {
		$root = $this->createMock(IRootFolder::class);
		$root->expects($this->never())->method('getUserFolder');
		$this->assertNull($this->find([$class, $lookup], ['root' => $root, 'user' => null]));

	}//end testNoSessionFindsNothing()

	/**
	 * Refused: the id is not in the user's own folder, or it is no file.
	 *
	 * @param class-string $class  The service under test.
	 * @param string       $lookup The lookup method.
	 *
	 * @return void
	 */
	#[DataProvider('services')]
	public function testMissingOrFolderFindsNothing(string $class, string $lookup): void {
		$this->assertNull($this->find([$class, $lookup], $this->root('alice', [])));
		$this->assertNull($this->find([$class, $lookup], $this->root('alice', [$this->createMock(Folder::class)])));

	}//end testMissingOrFolderFindsNothing()

	/**
	 * A root folder whose `uid` user folder answers id 42 with these nodes.
	 *
	 * @param string             $uid   The signed-in user.
	 * @param array<int, object> $nodes The nodes for id 42.
	 *
	 * @return array{root:IRootFolder,user:IUser}
	 */
	private function root(string $uid, array $nodes): array {
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->with(42)->willReturn($nodes);
		$root = $this->createMock(IRootFolder::class);
		$root->expects($this->once())->method('getUserFolder')->with($uid)->willReturn($folder);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);

		return ['root' => $root, 'user' => $user];

	}//end root()

	/**
	 * Call the service's private lookup for file 42 with this root and session user.
	 *
	 * @param array{0:class-string,1:string}         $lookup The service and its lookup method.
	 * @param array{root:IRootFolder,user:IUser|null} $world  The root folder and the session user.
	 *
	 * @return mixed What the lookup returns.
	 */
	private function find(array $lookup, array $world): mixed {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($world['user']);
		$reflection = new ReflectionClass($lookup[0]);
		$service = $reflection->newInstanceWithoutConstructor();
		if ($reflection->hasProperty('userFiles') === true) {
			(function (SessionUserFile $files): void {
				$this->userFiles = $files;
			})->call($service, new SessionUserFile(rootFolder: $world['root'], userSession: $session));
		} else {
			(function (IRootFolder $root, IUserSession $session): void {
				$this->rootFolder  = $root;
				$this->userSession = $session;
			})->call($service, $world['root'], $session);
		}

		return $reflection->getMethod($lookup[1])->invoke($service, 42);

	}//end find()

}//end class
