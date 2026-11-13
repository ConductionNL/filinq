<?php

/**
 * Tests for HermiqAgentflowClient: the agentflow HTTP call.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Detection;
use Exception;
use OCA\Filinq\Service\Detection\HermiqAgentflowClient;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

/**
 * @covers \OCA\Filinq\Service\Detection\HermiqAgentflowClient
 */
class HermiqAgentflowClientTest extends TestCase {

	/**
	 * Mocked HTTP client.
	 *
	 * @var IClient&MockObject
	 */
	private IClient&MockObject $http;

	/**
	 * The client under test.
	 *
	 * @var HermiqAgentflowClient
	 */
	private HermiqAgentflowClient $client;

	/**
	 * Wire the HTTP mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->http = $this->createMock(IClient::class);
		$service    = $this->createMock(IClientService::class);
		$service->method('newClient')->willReturn($this->http);
		$this->client = new HermiqAgentflowClient(clientService: $service);
	}

	/**
	 * A response with this body.
	 *
	 * @param string $body The body.
	 *
	 * @return IResponse The response.
	 */
	private function response(string $body): IResponse {
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn($body);
		return $response;
	}

	/**
	 * The text and types are posted with the agentflow, and state.entities comes back.
	 *
	 * @return void
	 */
	public function testPostsTheTextAndReturnsStateEntities(): void {
		$this->http->expects($this->once())
			->method('post')
			->with(
				'http://hermiq:8000/run',
				[
					'json'      => ['agentflow' => 'detect', 'input' => ['text' => 'Jan', 'entityTypes' => ['PERSON']]],
					'timeout'   => 9,
					'nextcloud' => ['allow_local_address' => true],
				]
			)
			->willReturn($this->response(body: '{"state":{"entities":[{"value":"Jan","type":"PER"}]},"trace":[]}'));

		$this->assertSame(
			[['value' => 'Jan', 'type' => 'PER']],
			$this->client->entities(endpoint: 'http://hermiq:8000/run', agentflow: 'detect', text: 'Jan', entityTypes: ['PERSON'], timeout: 9)
		);
	}

	/**
	 * A failed call is a RuntimeException carrying the cause.
	 *
	 * @return void
	 */
	public function testAFailedCallIsRefused(): void {
		$this->http->method('post')->willThrowException(new Exception('Connection refused'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The agentflow could not be reached: Connection refused');

		$this->client->entities(endpoint: 'http://hermiq/run', agentflow: 'detect', text: 'x', entityTypes: null, timeout: 5);
	}

	/**
	 * An answer without state.entities is malformed.
	 *
	 * @return void
	 */
	public function testAnAnswerWithoutStateEntitiesIsMalformed(): void {
		foreach (['not json', '{"state":"done"}', '{"state":{"other":1}}', '[]'] as $body) {
			$this->setUp();
			$this->http->method('post')->willReturn($this->response(body: $body));

			try {
				$this->client->entities(endpoint: 'http://hermiq/run', agentflow: 'detect', text: 'x', entityTypes: null, timeout: 5);
				$this->fail('Expected a refusal for '.$body);
			} catch (UnexpectedValueException $e) {
				$this->assertSame('The agentflow answer carries no state.entities.', $e->getMessage());
			}
		}
	}

	/**
	 * Any HTTP answer counts as reachable; a failed connection does not.
	 *
	 * @return void
	 */
	public function testAnswers(): void {
		$this->http->expects($this->exactly(2))
			->method('get')
			->with('http://hermiq/run', ['timeout' => 3, 'http_errors' => false, 'nextcloud' => ['allow_local_address' => true]])
			->willReturnOnConsecutiveCalls($this->response(body: ''), $this->throwException(new Exception('timeout')));

		$this->assertTrue($this->client->answers(endpoint: 'http://hermiq/run', timeout: 3));
		$this->assertFalse($this->client->answers(endpoint: 'http://hermiq/run', timeout: 3));
	}
}
