<?php

/**
 * Unit tests for ContentSearchClient
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
 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ContentSearchClient;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCP\App\IAppManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

/**
 * Asserts that the client asks OpenRegister's scoped query with the words, the
 * list's file ids and the caller, keeps only hits inside the scope, and says
 * "not available" on an OpenRegister without the query.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ContentSearchClientTest extends TestCase {

	/**
	 * Build the client over a query double, or over none.
	 *
	 * @param object|null $query    The query service, null for an OpenRegister without it.
	 * @param bool        $enabled  Whether OpenRegister is enabled.
	 * @param bool        $loggedIn Whether there is a caller.
	 *
	 * @return ContentSearchClient
	 */
	private function client(?object $query, bool $enabled=true, bool $loggedIn=true): ContentSearchClient {
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('isEnabledForUser')->willReturn(value: $enabled);

		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			callback: static function (string $name) use ($query): object {
				if ($query === null || $name !== ContentSearchClient::QUERY_SERVICE) {
					throw new RuntimeException(message: 'Could not resolve ' . $name);
				}

				return $query;
			}
		);

		$user = $this->createMock(originalClassName: IUser::class);
		$user->method('getUID')->willReturn(value: 'coordinator');
		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->method('getUser')->willReturn(value: $user);
		if ($loggedIn === false) {
			$session = $this->createMock(originalClassName: IUserSession::class);
		}

		return new ContentSearchClient(
			openRegister: new OpenRegisterAccess(apps: $apps, container: $container),
			session: $session,
			logger: $this->createMock(originalClassName: LoggerInterface::class)
		);

	}//end client()

	/**
	 * A query double that answers with a fixed answer and records its call.
	 *
	 * @param mixed $answer The answer to give, or an exception to throw.
	 *
	 * @return object
	 */
	private function query(mixed $answer): object {
		return new class($answer) {
			/**
			 * @var array<int, mixed> The arguments of the last call.
			 */
			public array $called = [];

			/**
			 * @param mixed $answer The answer to give.
			 */
			public function __construct(private mixed $answer) {
			}

			/**
			 * @param string          $words   The words.
			 * @param array<int, int> $fileIds The scope.
			 * @param string          $userId  The caller.
			 *
			 * @return mixed
			 */
			public function searchFiles(string $words, array $fileIds, string $userId): mixed {
				$this->called = [$words, $fileIds, $userId];
				if ($this->answer instanceof RuntimeException) {
					throw $this->answer;
				}

				return $this->answer;
			}
		};

	}//end query()

	/**
	 * The query is asked with the words, the scope and the caller, and its hits come back by rank.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function testTheScopedQueryAnswersWithHitsByRank(): void {
		$query = $this->query(
			answer: [
				'results' => [
					['fileId' => 11, 'rank' => 0.2, 'snippet' => 'aan de <mark>Lindenlaan</mark> 4'],
					['fileId' => 12, 'rank' => 0.9, 'snippet' => '<mark>Lindenlaan</mark> en omgeving'],
				],
				'unextracted' => [13, 13, 77],
			]
		);

		$answer = $this->client(query: $query)->search(words: 'Lindenlaan', fileIds: [11, 12, 13]);

		$this->assertSame(expected: ['Lindenlaan', [11, 12, 13], 'coordinator'], actual: $query->called);
		$this->assertTrue(condition: $answer['available']);
		$this->assertSame(expected: [12, 11], actual: array_column($answer['hits'], 'fileId'));
		$this->assertSame(expected: '<mark>Lindenlaan</mark> en omgeving', actual: $answer['hits'][0]['snippet']);
		$this->assertSame(expected: [13], actual: $answer['unread']);

	}//end testTheScopedQueryAnswersWithHitsByRank()

	/**
	 * A hit on a file outside the list's scope never comes back, and a file keeps its best hit.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function testAHitOutsideTheScopeIsDropped(): void {
		$query = $this->query(
			answer: [
				['file_id' => 11, 'rank' => 0.5, 'snippet' => 'one'],
				['fileId' => 99, 'rank' => 1.0, 'snippet' => 'a private document'],
				['fileId' => 11, 'rank' => 0.1, 'snippet' => 'a weaker chunk of the same file'],
				'not a row',
			]
		);

		$answer = $this->client(query: $query)->search(words: 'Lindenlaan', fileIds: [11, 12]);

		$this->assertSame(expected: [['fileId' => 11, 'rank' => 0.5, 'snippet' => 'one']], actual: $answer['hits']);
		$this->assertSame(expected: [], actual: $answer['unread']);

	}//end testAHitOutsideTheScopeIsDropped()

	/**
	 * An OpenRegister without the query says "not available" and asks nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function testAnOpenRegisterWithoutTheQueryIsNotAvailable(): void {
		$client = $this->client(query: null);

		$this->assertFalse(condition: $client->isAvailable());
		$this->assertSame(
			expected: ['available' => false, 'hits' => [], 'unread' => []],
			actual: $client->search(words: 'Lindenlaan', fileIds: [11])
		);

	}//end testAnOpenRegisterWithoutTheQueryIsNotAvailable()

	/**
	 * A service without the query method, a disabled OpenRegister and no caller are not available either.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function testNoMethodNoOpenRegisterOrNoCallerIsNotAvailable(): void {
		$this->assertFalse(condition: $this->client(query: new stdClass())->isAvailable());
		$this->assertFalse(condition: $this->client(query: $this->query(answer: []), enabled: false)->isAvailable());

		$answer = $this->client(query: $this->query(answer: []), loggedIn: false)->search(words: 'x', fileIds: [1]);
		$this->assertFalse(condition: $answer['available']);

	}//end testNoMethodNoOpenRegisterOrNoCallerIsNotAvailable()

	/**
	 * Empty words or an empty list ask nothing; a failing or odd answer gives no hits.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function testNothingToAskOrAFailingQueryGivesNoHits(): void {
		$query  = $this->query(answer: [['fileId' => 1, 'rank' => 1.0, 'snippet' => 'x']]);
		$client = $this->client(query: $query);

		$this->assertSame(expected: [], actual: $client->search(words: '  ', fileIds: [1])['hits']);
		$this->assertSame(expected: [], actual: $client->search(words: 'x', fileIds: [])['hits']);
		$this->assertSame(expected: [], actual: $query->called);

		$failing = $this->client(query: $this->query(answer: new RuntimeException(message: 'index down')))->search(words: 'x', fileIds: [1]);
		$this->assertTrue(condition: $failing['available']);
		$this->assertSame(expected: [], actual: $failing['hits']);

		$odd = $this->client(query: $this->query(answer: 'not an array'))->search(words: 'x', fileIds: [1]);
		$this->assertSame(expected: [], actual: $odd['hits']);

		$oddRows = $this->client(query: $this->query(answer: ['results' => 'nothing', 'unextracted' => 'none']))->search(words: 'x', fileIds: [1]);
		$this->assertSame(expected: ['available' => true, 'hits' => [], 'unread' => []], actual: $oddRows);

	}//end testNothingToAskOrAFailingQueryGivesNoHits()
}//end class
