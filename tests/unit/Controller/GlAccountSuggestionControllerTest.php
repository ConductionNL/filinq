<?php

/**
 * Unit tests for GlAccountSuggestionController
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
 * @spec openspec/changes/ai-gl-account-suggestion/specs/ai-gl-account-suggestion/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\GlAccountSuggestionController;
use OCA\Filinq\Service\GlAccountSuggestionService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for GlAccountSuggestionController.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class GlAccountSuggestionControllerTest extends TestCase {

	private GlAccountSuggestionController $controller;

	private IRequest|MockObject $request;

	private GlAccountSuggestionService|MockObject $suggestionService;

	private IUserSession|MockObject $userSession;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->suggestionService = $this->createMock(GlAccountSuggestionService::class);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn ($text, $params = []): string => vsprintf($text, $params));

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('annemarie');

		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession->method('getUser')->willReturn($user);

		$this->controller = new GlAccountSuggestionController(
			'filinq',
			$this->request,
			$this->suggestionService,
			$this->userSession,
			$l10n,
			$this->createMock(LoggerInterface::class),
		);

	}//end setUp()

	/**
	 * A valid request returns the ranked suggestion result.
	 *
	 * @return void
	 */
	public function testSuggestAccountReturnsRankedResult(): void {
		$this->request->method('getParam')->willReturnMap([
			['candidateAccounts', [], []],
			['sourceApp', '', 'shillinq'],
		]);

		$this->suggestionService->method('suggest')->willReturn([
			'extractionId' => 'extraction-1',
			'supplierIdentity' => '12345678',
			'identityType' => 'kvk',
			'suggestedAccounts' => [['code' => '4300', 'label' => 'Kantoorkosten', 'confidence' => 0.8, 'rationale' => '...']],
			'source' => 'history',
		]);

		$result = $this->controller->suggestAccount(id: 'extraction-1');

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertSame(200, $result->getStatus());
		$this->assertSame('history', $result->getData()['source']);

	}//end testSuggestAccountReturnsRankedResult()

	/**
	 * An unknown extraction id surfaces as HTTP 404 (service throws code 404).
	 *
	 * @return void
	 */
	public function testSuggestAccountReturns404ForUnknownId(): void {
		$this->request->method('getParam')->willReturnMap([
			['candidateAccounts', [], []],
			['sourceApp', '', ''],
		]);

		$this->suggestionService->method('suggest')->willThrowException(
			new RuntimeException('Financial extraction not found: missing', 404)
		);

		$result = $this->controller->suggestAccount(id: 'missing');

		$this->assertSame(404, $result->getStatus());

	}//end testSuggestAccountReturns404ForUnknownId()

	/**
	 * An unauthenticated caller gets 401 without invoking the service.
	 *
	 * @return void
	 */
	public function testSuggestAccountReturns401WhenNotAuthenticated(): void {
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn(null);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn ($text, $params = []): string => vsprintf($text, $params));

		$controller = new GlAccountSuggestionController(
			'filinq',
			$this->request,
			$this->suggestionService,
			$userSession,
			$l10n,
			$this->createMock(LoggerInterface::class),
		);

		$this->suggestionService->expects($this->never())->method('suggest');

		$result = $controller->suggestAccount(id: 'extraction-1');

		$this->assertSame(401, $result->getStatus());

	}//end testSuggestAccountReturns401WhenNotAuthenticated()
}//end class
