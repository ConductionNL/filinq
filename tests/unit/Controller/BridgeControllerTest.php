<?php

/**
 * Unit tests for the ZGW document bridge endpoints
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
 * @spec openspec/changes/zgw-document-bridge
 */

declare(strict_types=1);

// phpcs:disable PEAR.Commenting.FunctionComment.Missing -- arrange/act/assert PHPUnit conventions.

namespace OCA\Filinq\Tests\Unit\Controller;

use InvalidArgumentException;
use OCA\Filinq\Controller\BridgeController;
use OCA\Filinq\Service\BridgeDossierPickUp;
use OCA\Filinq\Service\BridgeService;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Controller\BridgeController
 */
final class BridgeControllerTest extends TestCase {

	private IRequest&MockObject $request;

	private BridgeService&MockObject $bridge;

	private BridgeDossierPickUp&MockObject $pickUp;

	private LoggerInterface&MockObject $logger;

	private function controller(): BridgeController {
		$this->request = $this->createMock(IRequest::class);
		$this->bridge  = $this->createMock(BridgeService::class);
		$this->pickUp  = $this->createMock(BridgeDossierPickUp::class);
		$this->logger  = $this->createMock(LoggerInterface::class);

		return new BridgeController(request: $this->request, bridge: $this->bridge, pickUp: $this->pickUp, logger: $this->logger);
	}//end controller()

	public function testSourcesAreAnAdminSettingAndTheRestNeedsASignedInUser(): void {
		$admin = (new ReflectionMethod(BridgeController::class, 'sources'))->getAttributes(AuthorizedAdminSetting::class);
		$this->assertSame([FilinqAdmin::class], $admin[0]->getArguments());
		foreach (['externalDocuments', 'attach', 'release', 'retry'] as $method) {
			$this->assertCount(1, (new ReflectionMethod(BridgeController::class, $method))->getAttributes(NoAdminRequired::class), $method);
		}
	}//end testSourcesAreAnAdminSettingAndTheRestNeedsASignedInUser()

	public function testSourcesAnswerWithTheirHealth(): void {
		$controller = $this->controller();
		$this->bridge->method('sources')->willReturn([['uuid' => 's-1', 'health' => 'fresh']]);

		$this->assertSame(['results' => [['uuid' => 's-1', 'health' => 'fresh']]], $controller->sources()->getData());
	}//end testSourcesAnswerWithTheirHealth()

	public function testTheListingIsFilteredByTheGivenSource(): void {
		$controller = $this->controller();
		$this->request->method('getParam')->with('sourceId', '')->willReturn(' s-1 ');
		$this->bridge->expects($this->once())->method('stagedDocuments')->with('s-1')->willReturn([['uuid' => 'd-1']]);

		$this->assertSame(['results' => [['uuid' => 'd-1']]], $controller->externalDocuments()->getData());
	}//end testTheListingIsFilteredByTheGivenSource()

	public function testAttachPassesTheDossierAndTheDocuments(): void {
		$controller = $this->controller();
		$this->request->method('getParam')->with('documentIds', [])->willReturn(['d-1', 'd-2']);
		$this->pickUp->expects($this->once())->method('attach')->with('dossier-21', ['d-1', 'd-2'])->willReturn([['uuid' => 'd-1'], ['uuid' => 'd-2']]);

		$response = $controller->attach(dossierId: 'dossier-21');

		$this->assertSame(200, $response->getStatus());
		$this->assertCount(2, $response->getData()['results']);
	}//end testAttachPassesTheDossierAndTheDocuments()

	public function testARefusalIsA400WithItsReason(): void {
		$controller = $this->controller();
		$this->bridge->method('release')->willThrowException(new InvalidArgumentException('A document can only be released with its redacted result file.'));

		$response = $controller->release(id: 'd-1');

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'A document can only be released with its redacted result file.'], $response->getData());
	}//end testARefusalIsA400WithItsReason()

	public function testAMissingDocumentIsA404(): void {
		$controller = $this->controller();
		$this->bridge->method('retry')->willThrowException(new RuntimeException('External document not found.', 404));

		$this->assertSame(404, $controller->retry(id: 'gone')->getStatus());
	}//end testAMissingDocumentIsA404()

	public function testAnyOtherFailureIsA500WithoutDetails(): void {
		$controller = $this->controller();
		$this->pickUp->method('attach')->willThrowException(new RuntimeException('disk full on /mnt/x'));
		$this->logger->expects($this->once())->method('error');

		$response = $controller->attach(dossierId: 'dossier-21');

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'The bridge request failed.'], $response->getData());
	}//end testAnyOtherFailureIsA500WithoutDetails()
}//end class
