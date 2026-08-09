<?php

/**
 * Wire-contract tests for VersionController::restore()
 *
 * Covers `POST api/documents/{fileId}/versions/{versionTimestamp}/restore`
 * (`version#restore`): the documented `{restored: true}` success body, the 401
 * anonymous rejection, the IDOR-safe 404/`{error, reason}` mapping of a
 * ComparisonException, and the generic 500 fallback that must never let a
 * Throwable escape to a white-screen.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\VersionController;
use OCA\DocuDesk\Exception\ComparisonException;
use OCA\DocuDesk\Service\DocumentVersionService;
use OCP\AppFramework\Http;
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
 * Tests for the document file-version restore endpoint.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class VersionControllerTest extends TestCase
{

    /**
     * Mocked version service.
     *
     * @var DocumentVersionService|MockObject
     */
    private DocumentVersionService|MockObject $service;

    /**
     * Mocked localisation.
     *
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $l10n;

    /**
     * Controller under test, with an authenticated session.
     *
     * @var VersionController
     */
    private VersionController $controller;


    /**
     * Set up an authenticated controller.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(DocumentVersionService::class);
        $this->l10n    = $this->createMock(IL10N::class);
        $this->l10n->method('t')->willReturnCallback(
            static function (string $text): string {
                return $text;
            }
        );

        $user    = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('alice');
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn($user);

        $this->controller = new VersionController(
            'docudesk',
            $this->createMock(IRequest::class),
            $this->createMock(LoggerInterface::class),
            $this->service,
            $this->l10n,
            $session
        );

    }//end setUp()


    /**
     * A successful restore answers 200 with `{restored: true}` and forwards
     * both path parameters to the service.
     *
     * @return void
     */
    public function testRestoreReturnsRestoredTrue(): void
    {
        $this->service->expects($this->once())
            ->method('restoreVersion')
            ->with(1234, 1700000000);

        $response = $this->controller->restore(1234, 1700000000);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame(['restored' => true], $response->getData());

    }//end testRestoreReturnsRestoredTrue()


    /**
     * An anonymous caller is refused with 401 and no restore is attempted —
     * restore is a write, so this guard is the boundary, not a nicety.
     *
     * @return void
     */
    public function testRestoreRejectsAnonymousCaller(): void
    {
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn(null);

        $controller = new VersionController(
            'docudesk',
            $this->createMock(IRequest::class),
            $this->createMock(LoggerInterface::class),
            $this->service,
            $this->l10n,
            $session
        );

        $this->service->expects($this->never())->method('restoreVersion');

        $response = $controller->restore(1234, 1700000000);

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
        $this->assertSame(['error' => 'Not authenticated'], $response->getData());

    }//end testRestoreRejectsAnonymousCaller()


    /**
     * A file the caller cannot reach yields the service's 404 with a
     * `{error, reason}` body — the same answer as a file that does not exist,
     * so the endpoint discloses nothing (ADR-005).
     *
     * @return void
     */
    public function testRestoreMapsInaccessibleFileToNotFound(): void
    {
        $this->service->method('restoreVersion')
            ->willThrowException(new ComparisonException(404, 'not-found', 'no such file'));

        $response = $this->controller->restore(999, 1700000000);

        $this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
        $data = $response->getData();
        $this->assertSame('Document not found', $data['error']);
        $this->assertSame('not-found', $data['reason']);

    }//end testRestoreMapsInaccessibleFileToNotFound()


    /**
     * An instance without files_versions answers with the service's own status
     * and the dedicated `versions-unavailable` reason, so the UI can explain
     * the deployment gap instead of reporting a missing document.
     *
     * @return void
     */
    public function testRestoreReportsVersionsUnavailable(): void
    {
        $this->service->method('restoreVersion')
            ->willThrowException(new ComparisonException(501, 'versions-unavailable', 'app disabled'));

        $response = $this->controller->restore(1234, 1700000000);

        $this->assertSame(501, $response->getStatus());
        $data = $response->getData();
        $this->assertSame('File versions are not available on this instance', $data['error']);
        $this->assertSame('versions-unavailable', $data['reason']);

    }//end testRestoreReportsVersionsUnavailable()


    /**
     * Any other failure is contained as a localised 500 body — the Throwable
     * must not escape to the framework's raw error page.
     *
     * @return void
     */
    public function testRestoreContainsUnexpectedFailure(): void
    {
        $this->service->method('restoreVersion')
            ->willThrowException(new RuntimeException('storage backend exploded'));

        $response = $this->controller->restore(1234, 1700000000);

        $this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
        $this->assertSame(['error' => 'Could not restore version'], $response->getData());

    }//end testRestoreContainsUnexpectedFailure()


}//end class
