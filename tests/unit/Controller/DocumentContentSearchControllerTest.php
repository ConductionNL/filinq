<?php

/**
 * Unit tests for DocumentContentSearchController
 *
 * `POST api/documents/content-search` asks OpenRegister's scoped content
 * query about the caller's readable files only, and refuses without a
 * session, without words or with too many files (work-content-search 2.1).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\DocumentContentSearchController;
use OCA\Filinq\Kernel\Contracts\ReadableFileResolverInterface;
use OCA\Filinq\Service\ContentSearchClient;
use OCP\Files\Node;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Controller\DocumentContentSearchController
 */
#[CoversClass(DocumentContentSearchController::class)]
class DocumentContentSearchControllerTest extends TestCase {

	/**
	 * Only the readable files are asked about, each once, and the answer is passed on.
	 *
	 * @return void
	 */
	public function testOnlyTheReadableFilesAreAskedAbout(): void {
		$answer  = ['available' => true, 'hits' => [['fileId' => 11, 'rank' => 0.7, 'snippet' => 'de vergunning']], 'unread' => [12]];
		$content = $this->createMock(originalClassName: ContentSearchClient::class);
		$content->expects($this->once())->method(constraint: 'search')
			->with('vergunning', [11, 12])
			->willReturn($answer);

		$response = $this->controller(content: $content, readable: [11, 12], uid: 'bea')
			->search(words: '  vergunning ', fileIds: [11, '12', 13, 11, 'x', 0, -4]);

		$this->assertSame(expected: 200, actual: $response->getStatus());
		$this->assertSame(expected: $answer, actual: $response->getData());

	}//end testOnlyTheReadableFilesAreAskedAbout()

	/**
	 * Without a session nothing is asked and the answer is 401.
	 *
	 * @return void
	 */
	public function testNobodySignedInIsRefused(): void {
		$content = $this->createMock(originalClassName: ContentSearchClient::class);
		$content->expects($this->never())->method(constraint: 'search');

		$response = $this->controller(content: $content, readable: [11], uid: null)->search(words: 'a', fileIds: [11]);

		$this->assertSame(expected: 401, actual: $response->getStatus());

	}//end testNobodySignedInIsRefused()

	/**
	 * Without words, or with too many files, nothing is asked and the answer is 400.
	 *
	 * @return void
	 */
	public function testASearchWithoutWordsOrWithTooManyFilesIsRefused(): void {
		$content = $this->createMock(originalClassName: ContentSearchClient::class);
		$content->expects($this->never())->method(constraint: 'search');
		$controller = $this->controller(content: $content, readable: [11], uid: 'bea');

		$this->assertSame(expected: 400, actual: $controller->search(words: '   ', fileIds: [11])->getStatus());
		$tooMany = range(start: 1, end: DocumentContentSearchController::MAX_FILES + 1);
		$this->assertSame(expected: 400, actual: $controller->search(words: 'a', fileIds: $tooMany)->getStatus());

	}//end testASearchWithoutWordsOrWithTooManyFilesIsRefused()

	/**
	 * The controller for this caller, with these files readable.
	 *
	 * @param ContentSearchClient $content  The content query double.
	 * @param array<int, int>     $readable The file ids the caller may read.
	 * @param string|null         $uid      The caller, or null.
	 *
	 * @return DocumentContentSearchController
	 */
	private function controller(ContentSearchClient $content, array $readable, ?string $uid): DocumentContentSearchController {
		$session = $this->createMock(originalClassName: IUserSession::class);
		if ($uid !== null) {
			$user = $this->createMock(originalClassName: IUser::class);
			$user->method('getUID')->willReturn($uid);
			$session->method('getUser')->willReturn($user);
		}

		$resolver = $this->createMock(originalClassName: ReadableFileResolverInterface::class);
		$resolver->method('node')->willReturnCallback(
			fn (int $fileId): ?Node => in_array(needle: $fileId, haystack: $readable, strict: true) === true ? $this->createMock(originalClassName: Node::class) : null
		);

		return new DocumentContentSearchController(
			appName: 'filinq',
			request: $this->createMock(originalClassName: IRequest::class),
			content: $content,
			readable: $resolver,
			userSession: $session
		);

	}//end controller()
}//end class
