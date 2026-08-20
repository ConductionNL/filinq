<?php

/**
 * Unit tests for LanguageNegotiationMiddleware
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Middleware
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-3-2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Middleware;

use OCA\DocuDesk\Middleware\LanguageNegotiationMiddleware;
use OCA\OpenRegister\Service\LanguageService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for LanguageNegotiationMiddleware
 *
 * Verifies the request-side resolution priority (query → header → default)
 * and the response-side Content-Language emission.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Middleware
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @SuppressWarnings(PHPMD)
 */
final class LanguageNegotiationMiddlewareTest extends TestCase {

	private LanguageService $languageService;

	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->languageService = new LanguageService();
		$this->logger = $this->createMock(LoggerInterface::class);
	}

	/**
	 * Build a request mock whose getHeader/getParam/getMethod return the
	 * configured values.
	 *
	 * @param array<string,string|null> $headers Header values by name.
	 * @param array<string,mixed> $params Query/body parameter values.
	 * @param string $method HTTP method (GET, POST...).
	 *
	 * @return IRequest&MockObject
	 */
	private function buildRequest(array $headers = [], array $params = [], string $method = 'GET'): IRequest {
		$request = $this->createMock(IRequest::class);
		$request->method('getHeader')->willReturnCallback(
			static fn (string $name) => $headers[$name] ?? ''
		);
		$request->method('getParam')->willReturnCallback(
			static fn (string $name, $default = null) => array_key_exists($name, $params) ? $params[$name] : $default
		);
		$request->method('getMethod')->willReturn($method);
		return $request;
	}

	private function buildMiddleware(IRequest $request): LanguageNegotiationMiddleware {
		return new LanguageNegotiationMiddleware(
			request: $request,
			languageService: $this->languageService,
			logger: $this->logger
		);
	}

	public function testQueryLangOverridesAcceptLanguage(): void {
		$request = $this->buildRequest(
			headers: ['Accept-Language' => 'en-GB,en;q=0.9'],
			params:  ['_lang' => 'fr']
		);
		$middleware = $this->buildMiddleware($request);

		$middleware->beforeController(controller: null, methodName: 'index');

		self::assertSame('fr', $this->languageService->getPreferredLanguage());
		self::assertSame('query', $this->languageService->getRequestedLanguageSource());
	}

	public function testLanguageQueryParameterAliasResolves(): void {
		$request = $this->buildRequest(params: ['language' => 'de']);
		$middleware = $this->buildMiddleware($request);

		$middleware->beforeController(controller: null, methodName: 'show');

		self::assertSame('de', $this->languageService->getPreferredLanguage());
		self::assertSame('query', $this->languageService->getRequestedLanguageSource());
	}

	public function testAcceptLanguageHeaderResolvesWhenNoQueryOverride(): void {
		$request = $this->buildRequest(headers: ['Accept-Language' => 'nl,en;q=0.8']);
		$middleware = $this->buildMiddleware($request);

		$middleware->beforeController(controller: null, methodName: 'index');

		self::assertSame('nl', $this->languageService->getPreferredLanguage());
		self::assertSame('header', $this->languageService->getRequestedLanguageSource());
		self::assertSame(['nl', 'en'], $this->languageService->getAcceptedLanguages());
	}

	public function testInvalidQueryLangIsIgnoredAndLogged(): void {
		$this->logger
			->expects(self::atLeastOnce())
			->method('warning')
			->with(self::stringContains('Invalid ?_lang'));

		$request = $this->buildRequest(
			headers: ['Accept-Language' => 'fr,en;q=0.9'],
			params:  ['_lang' => '!!not-a-tag!!']
		);
		$middleware = $this->buildMiddleware($request);

		$middleware->beforeController(controller: null, methodName: 'index');

		// Falls through to Accept-Language.
		self::assertSame('fr', $this->languageService->getPreferredLanguage());
	}

	public function testWriteSideTargetLanguageHeaderIsCaptured(): void {
		$request = $this->buildRequest(
			headers: ['X-Translation-Target-Language' => 'es'],
			method:  'POST'
		);
		$middleware = $this->buildMiddleware($request);

		$middleware->beforeController(controller: null, methodName: 'create');

		self::assertSame('es', $this->languageService->getTargetLanguage());
	}

	public function testWriteSideTargetLanguageIgnoredOnGet(): void {
		$request = $this->buildRequest(
			headers: ['X-Translation-Target-Language' => 'es'],
			method:  'GET'
		);
		$middleware = $this->buildMiddleware($request);

		$middleware->beforeController(controller: null, methodName: 'show');

		self::assertNull($this->languageService->getTargetLanguage());
	}

	public function testInvalidTargetLanguageHeaderIsLoggedAndIgnored(): void {
		$this->logger
			->expects(self::atLeastOnce())
			->method('warning')
			->with(self::stringContains('Invalid X-Translation-Target-Language'));

		$request = $this->buildRequest(
			headers: ['X-Translation-Target-Language' => '!! bogus !!'],
			method:  'POST'
		);
		$middleware = $this->buildMiddleware($request);

		$middleware->beforeController(controller: null, methodName: 'create');

		self::assertNull($this->languageService->getTargetLanguage());
	}

	public function testReturnAllTranslationsParamIsForwarded(): void {
		$request = $this->buildRequest(params: ['_translations' => 'all']);
		$middleware = $this->buildMiddleware($request);

		$middleware->beforeController(controller: null, methodName: 'index');

		self::assertTrue($this->languageService->shouldReturnAllTranslations());
	}

	public function testAfterControllerAddsContentLanguageHeader(): void {
		$request = $this->buildRequest(params: ['_lang' => 'fr']);
		$middleware = $this->buildMiddleware($request);
		$middleware->beforeController(controller: null, methodName: 'index');

		$response = new JSONResponse(['ok' => true]);
		$result = $middleware->afterController(controller: null, methodName: 'index', response: $response);

		self::assertSame('fr', $result->getHeaders()['Content-Language'] ?? null);
		self::assertArrayNotHasKey('X-Content-Language-Fallback', $result->getHeaders());
	}

	public function testAfterControllerEmitsFallbackHeaderWhenFlagSet(): void {
		$request = $this->buildRequest();
		$middleware = $this->buildMiddleware($request);
		$middleware->beforeController(controller: null, methodName: 'index');
		$this->languageService->setFallbackUsed(true);

		$response = new JSONResponse([]);
		$result = $middleware->afterController(controller: null, methodName: 'index', response: $response);

		self::assertSame('true', $result->getHeaders()['X-Content-Language-Fallback'] ?? null);
	}
}//end class
