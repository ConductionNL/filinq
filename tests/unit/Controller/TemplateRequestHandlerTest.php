<?php

/**
 * Unit tests for TemplateRequestHandler
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 */

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\TemplateRequestHandler;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TemplateRequestHandler
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TemplateRequestHandlerTest extends TestCase {

	/**
	 * @var TemplateRequestHandler
	 */
	private TemplateRequestHandler $handler;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->handler = new TemplateRequestHandler($this->mockLogger);

	}//end setUp()

	/**
	 * Test parseListParams with namespace and search
	 *
	 * @return void
	 */
	public function testParseListParamsWithNamespaceAndSearch(): void {
		$mockRequest = $this->createMock(IRequest::class);
		$mockRequest->method('getParam')
			->willReturnMap([
				['namespace', null, 'filinq'],
				['_search', null, 'invoice'],
				['_limit', '20', '10'],
				['_offset', '0', '5'],
			]);

		$result = $this->handler->parseListParams($mockRequest);

		$this->assertEquals('filinq', $result['filters']['namespace']);
		$this->assertEquals('invoice', $result['filters']['_search']);
		$this->assertEquals(10, $result['limit']);
		$this->assertEquals(5, $result['offset']);

	}//end testParseListParamsWithNamespaceAndSearch()

	/**
	 * Test parseListParams with defaults
	 *
	 * @return void
	 */
	public function testParseListParamsWithDefaults(): void {
		$mockRequest = $this->createMock(IRequest::class);
		$mockRequest->method('getParam')
			->willReturnMap([
				['namespace', null, null],
				['_search', null, null],
				['_limit', '20', '20'],
				['_offset', '0', '0'],
			]);

		$result = $this->handler->parseListParams($mockRequest);

		$this->assertEmpty($result['filters']);
		$this->assertEquals(20, $result['limit']);
		$this->assertEquals(0, $result['offset']);

	}//end testParseListParamsWithDefaults()

	/**
	 * Test parseBodyParams strips route and extra keys
	 *
	 * @return void
	 */
	public function testParseBodyParamsStripsKeys(): void {
		$mockRequest = $this->createMock(IRequest::class);
		$mockRequest->method('getParams')
			->willReturn([
				'_route' => 'some.route',
				'id' => 'uuid-1',
				'name' => 'Template',
			]);

		$result = $this->handler->parseBodyParams($mockRequest, ['id']);

		$this->assertArrayNotHasKey('_route', $result);
		$this->assertArrayNotHasKey('id', $result);
		$this->assertEquals('Template', $result['name']);

	}//end testParseBodyParamsStripsKeys()

	/**
	 * Test buildErrorResponse returns proper status code
	 *
	 * @return void
	 */
	public function testBuildErrorResponseReturnsProperStatusCode(): void {
		$exception = new \Exception('Not found', 404);
		$result = $this->handler->buildErrorResponse($exception, 'Error: ');

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(404, $result->getStatus());

	}//end testBuildErrorResponseReturnsProperStatusCode()

	/**
	 * Test buildErrorResponse defaults to 500 for invalid codes
	 *
	 * @return void
	 */
	public function testBuildErrorResponseDefaults500(): void {
		$exception = new \Exception('Error', 0);
		$result = $this->handler->buildErrorResponse($exception, 'Error: ');

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(500, $result->getStatus());

	}//end testBuildErrorResponseDefaults500()

}//end class
