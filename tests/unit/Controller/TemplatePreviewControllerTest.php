<?php

/**
 * The template preview endpoints, asserted from the caller.
 *
 * Both previews refuse without a session user, read their input from the
 * request body, and turn a failure into an error response through the shared
 * template request handler: a client error keeps its 4xx, anything else is a
 * 500, and both are logged.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\TemplatePreviewController;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Controller\TemplateRequestHandler;
use OCA\Filinq\Kernel\Contracts\TemplatePreviewServiceInterface;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * `TemplatePreviewController`.
 *
 * @covers \OCA\Filinq\Controller\TemplatePreviewController
 * @uses   \OCA\Filinq\Controller\TemplateRequestHandler
 * @uses   \OCA\Filinq\Controller\AnswersNotAuthenticated
 */
class TemplatePreviewControllerTest extends TestCase {

	/**
	 * The preview service double.
	 *
	 * @var TemplatePreviewServiceInterface&MockObject
	 */
	private TemplatePreviewServiceInterface&MockObject $previews;

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Set up the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->previews = $this->createMock(TemplatePreviewServiceInterface::class);
		$this->logger   = $this->createMock(LoggerInterface::class);
	}//end setUp()

	/**
	 * The controller over a request with the given body.
	 *
	 * @param array<string, mixed> $body     The request parameters.
	 * @param bool                 $loggedIn Whether a user is logged in.
	 * @param DataResolverService|null $dataResolver The listRefs resolver, when the test needs one.
	 *
	 * @return TemplatePreviewController The controller.
	 */
	private function controller(array $body, bool $loggedIn = true, ?DataResolverService $dataResolver = null): TemplatePreviewController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($body);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($loggedIn === true ? $this->createMock(IUser::class) : null);

		return new TemplatePreviewController(
			appName: 'filinq',
			request: $request,
			requestHandler: new TemplateRequestHandler(logger: $this->logger),
			previewService: $this->previews,
			userSession: $session,
			dataResolver: $dataResolver,
		);
	}//end controller()

	/**
	 * Without a session user nothing is rendered.
	 *
	 * @return void
	 */
	public function testWithoutAUserNothingIsRendered(): void {
		$this->previews->expects($this->never())->method($this->anything());

		foreach ([$this->controller(['content' => 'x'], false)->preview(), $this->controller([], false)->previewTemplate(id: 't1')] as $response) {
			$this->assertSame(401, $response->getStatus());
			$this->assertSame(['error' => 'Not authenticated'], $response->getData());
		}
	}//end testWithoutAUserNothingIsRendered()

	/**
	 * A preview renders the body's content with its data, ignoring the route key.
	 *
	 * @return void
	 */
	public function testAPreviewRendersTheContentWithItsData(): void {
		$this->previews->expects($this->once())->method('previewWithLint')
			->with('Dear {{ name }}', ['name' => 'Anna'])
			->willReturn(['html' => 'Dear Anna', 'lint' => []]);

		$response = $this->controller(['_route' => 'filinq.preview', 'content' => 'Dear {{ name }}', 'data' => ['name' => 'Anna']])->preview();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['html' => 'Dear Anna', 'lint' => []], $response->getData());
	}//end testAPreviewRendersTheContentWithItsData()

	/**
	 * A preview with listRefs fills the template's table from the related
	 * records, resolved the way generation resolves them; without listRefs the
	 * resolver is not asked.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/finish-half-built-capabilities/tasks.md#3-generation-and-finality
	 */
	public function testAPreviewFillsATableFromRelatedRecords(): void {
		$listRefs = [['register' => 'zaken', 'schema' => 'besluit', 'as' => 'besluiten']];
		$resolver = $this->createMock(DataResolverService::class);
		$resolver->expects($this->once())->method('resolve')
			->with([], $listRefs, ['name' => 'Anna'])
			->willReturn(['data' => ['name' => 'Anna', 'besluiten' => [['t' => 'A'], ['t' => 'B']]], 'errors' => [], 'warnings' => []]);
		$this->previews->expects($this->exactly(2))->method('previewWithLint')
			->willReturnCallback(static fn (string $content, array $data): array => ['html' => (string)count($data['besluiten'] ?? []), 'lint' => []]);

		$filled = $this->controller(['content' => 'x', 'data' => ['name' => 'Anna'], 'listRefs' => $listRefs], dataResolver: $resolver)->preview();
		$plain = $this->controller(['content' => 'x', 'data' => ['name' => 'Anna'], 'listRefs' => []], dataResolver: $resolver)->preview();

		$this->assertSame('2', $filled->getData()['html']);
		$this->assertSame('0', $plain->getData()['html']);
	}//end testAPreviewFillsATableFromRelatedRecords()

	/**
	 * A preview without content is a logged 400.
	 *
	 * @return void
	 */
	public function testAPreviewWithoutContentIsRefused(): void {
		$this->previews->expects($this->never())->method('previewWithLint');
		$this->logger->expects($this->once())->method('error')
			->with('Failed to preview template: Content is required for preview', $this->arrayHasKey('exception'));

		$response = $this->controller(['data' => ['a' => 1]])->preview();

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'Content is required for preview'], $response->getData());
	}//end testAPreviewWithoutContentIsRefused()

	/**
	 * A stored template renders by id with the body's data, empty when none is given.
	 *
	 * @return void
	 */
	public function testAStoredTemplateRendersById(): void {
		$this->previews->expects($this->exactly(2))->method('previewTemplate')
			->willReturnCallback(static fn (string $templateId, array $data): string => $templateId . ':' . json_encode($data));
		$controller = $this->controller(['data' => ['case' => 'Z-1']]);

		$this->assertSame(['html' => 't1:{"case":"Z-1"}'], $controller->previewTemplate(id: 't1')->getData());
		$this->assertSame(['html' => 't2:[]'], $this->controller([])->previewTemplate(id: 't2')->getData());
	}//end testAStoredTemplateRendersById()

	/**
	 * A failing render keeps a client error code and turns anything else into a 500.
	 *
	 * @return void
	 */
	public function testARenderFailureKeepsAClientCodeOtherwise500(): void {
		$this->previews->method('previewTemplate')->willReturnCallback(
			static function (string $templateId): string {
				throw match ($templateId) {
					'missing' => new Exception('Template not found', 404),
					default => new RuntimeException('engine crashed', 7),
				};
			}
		);
		$this->logger->expects($this->exactly(2))->method('error');
		$controller = $this->controller([]);

		$missing = $controller->previewTemplate(id: 'missing');
		$this->assertSame([404, ['error' => 'Template not found']], [$missing->getStatus(), $missing->getData()]);

		$crashed = $controller->previewTemplate(id: 'other');
		$this->assertSame([500, ['error' => 'engine crashed']], [$crashed->getStatus(), $crashed->getData()]);
	}//end testARenderFailureKeepsAClientCodeOtherwise500()
}//end class
