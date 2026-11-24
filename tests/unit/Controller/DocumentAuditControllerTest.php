<?php

/**
 * Unit tests for DocumentAuditController
 *
 * `GET api/documents/{fileId}/audit` serves the trail of a document the
 * caller may read, and answers 404 for one they may not
 * (specify-capability-rows REQ-SCR-007).
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
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\DocumentAuditController;
use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCA\Filinq\Service\ReadableFileResolver;
use OCP\Files\Node;
use OCP\IRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Controller\DocumentAuditController
 */
#[CoversClass(DocumentAuditController::class)]
class DocumentAuditControllerTest extends TestCase {

	/**
	 * A readable document's trail is served with its total.
	 *
	 * @return void
	 */
	public function testAReadableDocumentsTrailIsServed(): void {
		$entries = [['action' => 'create', 'user' => 'anna', 'userName' => 'Anna', 'created' => '2026-11-01T09:00:00+00:00']];
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->expects($this->once())->method('trail')->with(fileId: 41)->willReturn($entries);

		$response = $this->controller(audit: $audit, readable: true)->index(fileId: 41);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['results' => $entries, 'total' => 1], $response->getData());

	}//end testAReadableDocumentsTrailIsServed()

	/**
	 * A document the caller may not read answers 404 and its trail is not read.
	 *
	 * @return void
	 */
	public function testAnUnreadableDocumentAnswersNotFound(): void {
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->expects($this->never())->method('trail');

		$response = $this->controller(audit: $audit, readable: false)->index(fileId: 41);

		$this->assertSame(404, $response->getStatus());

	}//end testAnUnreadableDocumentAnswersNotFound()

	/**
	 * Filinq's viewer showing a readable document writes one `opened` entry.
	 *
	 * @return void
	 */
	public function testOpeningAReadableDocumentIsRecorded(): void {
		$audit = $this->createMock(originalClassName: DocumentActionAudit::class);
		$audit->expects($this->once())->method(constraint: 'record')
			->with(fileId: 41, action: 'opened', context: ['route' => 'ui'])
			->willReturn(value: true);

		$response = $this->controller(audit: $audit, readable: true)->opened(fileId: 41);

		$this->assertSame(expected: 200, actual: $response->getStatus());
		$this->assertSame(expected: ['recorded' => true], actual: $response->getData());

	}//end testOpeningAReadableDocumentIsRecorded()

	/**
	 * Opening a document the caller may not read answers 404 and writes nothing.
	 *
	 * @return void
	 */
	public function testOpeningAnUnreadableDocumentWritesNothing(): void {
		$audit = $this->createMock(originalClassName: DocumentActionAudit::class);
		$audit->expects($this->never())->method(constraint: 'record');

		$response = $this->controller(audit: $audit, readable: false)->opened(fileId: 41);

		$this->assertSame(expected: 404, actual: $response->getStatus());

	}//end testOpeningAnUnreadableDocumentWritesNothing()

	/**
	 * The opened route is declared for the controller method.
	 *
	 * @return void
	 */
	public function testTheOpenedRouteIsDeclared(): void {
		$routes = require dirname(__DIR__, 3) . '/appinfo/routes.php';
		$found  = array_filter(
			$routes['routes'],
			static fn (array $route): bool => $route['name'] === 'documentAudit#opened'
				&& $route['url'] === 'api/documents/{fileId}/opened'
				&& $route['verb'] === 'POST'
		);

		$this->assertCount(expectedCount: 1, haystack: $found);
		$this->assertTrue(condition: method_exists(DocumentAuditController::class, 'opened'));

	}//end testTheOpenedRouteIsDeclared()

	/**
	 * The controller over a resolver that does or does not open file 41.
	 *
	 * @param DocumentActionAudit $audit    The trail double.
	 * @param bool                $readable Whether the caller may read file 41.
	 *
	 * @return DocumentAuditController
	 */
	private function controller(DocumentActionAudit $audit, bool $readable): DocumentAuditController {
		$files = $this->createMock(ReadableFileResolver::class);
		$node = null;
		if ($readable === true) {
			$node = $this->createMock(Node::class);
		}

		$files->method('node')->with(fileId: 41)->willReturn($node);

		return new DocumentAuditController(appName: 'filinq', request: $this->createMock(IRequest::class), audit: $audit, files: $files);

	}//end controller()
}//end class
