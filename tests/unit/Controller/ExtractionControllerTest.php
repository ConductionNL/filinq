<?php

/**
 * Unit tests for ExtractionController
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\ExtractionController;
use OCA\DocuDesk\Service\FinancialExtractionService;
use OCA\DocuDesk\Service\GlAccountSuggestionService;
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
 * Tests for ExtractionController.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ExtractionControllerTest extends TestCase
{

    private ExtractionController $controller;

    private IRequest|MockObject $request;

    private FinancialExtractionService|MockObject $extractionService;

    private GlAccountSuggestionService|MockObject $suggestionService;

    private IUserSession|MockObject $userSession;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->request           = $this->createMock(IRequest::class);
        $this->extractionService = $this->createMock(FinancialExtractionService::class);
        $this->suggestionService = $this->createMock(GlAccountSuggestionService::class);

        $l10n = $this->createMock(IL10N::class);
        $l10n->method('t')->willReturnCallback(static fn ($text, $params=[]): string => vsprintf($text, $params));

        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('annemarie');

        $this->userSession = $this->createMock(IUserSession::class);
        $this->userSession->method('getUser')->willReturn($user);

        $logger = $this->createMock(LoggerInterface::class);

        $this->controller = new ExtractionController(
            'docudesk',
            $this->request,
            $this->extractionService,
            $this->suggestionService,
            $this->userSession,
            $l10n,
            $logger,
        );

    }//end setUp()

    /**
     * A valid request returns the extracted fields and confidence.
     *
     * @return void
     */
    public function testFinancialReturnsExtractedFields(): void
    {
        $this->request->method('getParams')->willReturn([
            'fileId'  => 1,
            'docType' => 'receipt',
        ]);

        $this->extractionService->method('extractFinancial')->willReturn([
            'id'                => 'extraction-1',
            'fields'            => ['totalIncl' => 18.50],
            'fieldConfidence'   => ['totalIncl' => 0.71],
            'overallConfidence' => 0.71,
        ]);

        $result = $this->controller->financial();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertSame(201, $result->getStatus());
        $this->assertSame('extraction-1', $result->getData()['id']);

    }//end testFinancialReturnsExtractedFields()

    /**
     * An invalid docType surfaces as HTTP 400 (service throws code 400).
     *
     * @return void
     */
    public function testFinancialReturns400OnInvalidDocType(): void
    {
        $this->request->method('getParams')->willReturn(['docType' => 'invoice', 'fileId' => 1]);
        $this->extractionService->method('extractFinancial')->willThrowException(
            new RuntimeException('docType must be "receipt" or "supplier-invoice"', 400)
        );

        $result = $this->controller->financial();

        $this->assertSame(400, $result->getStatus());

    }//end testFinancialReturns400OnInvalidDocType()

    /**
     * An unauthenticated caller gets 401 without invoking the service.
     *
     * @return void
     */
    public function testFinancialReturns401WhenNotAuthenticated(): void
    {
        $this->userSession = $this->createMock(IUserSession::class);
        $this->userSession->method('getUser')->willReturn(null);

        $l10n = $this->createMock(IL10N::class);
        $l10n->method('t')->willReturnCallback(static fn ($text, $params=[]): string => vsprintf($text, $params));

        $controller = new ExtractionController(
            'docudesk',
            $this->request,
            $this->extractionService,
            $this->suggestionService,
            $this->userSession,
            $l10n,
            $this->createMock(LoggerInterface::class),
        );

        $this->extractionService->expects($this->never())->method('extractFinancial');

        $result = $controller->financial();

        $this->assertSame(401, $result->getStatus());

    }//end testFinancialReturns401WhenNotAuthenticated()

    /**
     * Corrections for an unknown extraction id surface as HTTP 404.
     *
     * @return void
     */
    public function testCorrectionsReturns404ForUnknownId(): void
    {
        $this->request->method('getParam')->with('fields', [])->willReturn(['supplierName' => 'ACME B.V.']);
        $this->extractionService->method('addCorrection')->willThrowException(
            new RuntimeException('Financial extraction not found: missing', 404)
        );

        $result = $this->controller->corrections('missing');

        $this->assertSame(404, $result->getStatus());

    }//end testCorrectionsReturns404ForUnknownId()

    /**
     * A valid correction request returns the updated extraction object.
     *
     * @return void
     */
    public function testCorrectionsReturnsUpdatedObject(): void
    {
        $this->request->method('getParam')->with('fields', [])->willReturn(['supplierName' => 'ACME B.V.']);
        $this->extractionService->method('addCorrection')->willReturn([
            'id'     => 'extraction-3',
            'fields' => ['supplierName' => null],
        ]);

        $result = $this->controller->corrections('extraction-3');

        $this->assertSame(200, $result->getStatus());
        $this->assertSame('extraction-3', $result->getData()['id']);

    }//end testCorrectionsReturnsUpdatedObject()

    /**
     * A correction whose `fields` includes `glAccountCode` delegates to
     * GlAccountSuggestionService::recordBooking() (ai-gl-account-suggestion,
     * REQ-GLS-05).
     *
     * @return void
     */
    public function testCorrectionsWithGlAccountCodeDelegatesToRecordBooking(): void
    {
        $this->request->method('getParam')->with('fields', [])->willReturn([
            'glAccountCode'  => '4300',
            'glAccountLabel' => 'Kantoorkosten',
        ]);
        $this->extractionService->method('addCorrection')->willReturn(['id' => 'extraction-3']);

        $this->suggestionService->expects($this->once())
            ->method('recordBooking')
            ->with('extraction-3', '4300', 'Kantoorkosten', 'annemarie');

        $result = $this->controller->corrections('extraction-3');

        $this->assertSame(200, $result->getStatus());

    }//end testCorrectionsWithGlAccountCodeDelegatesToRecordBooking()

    /**
     * A correction without `glAccountCode` does NOT call recordBooking().
     *
     * @return void
     */
    public function testCorrectionsWithoutGlAccountCodeDoesNotRecordBooking(): void
    {
        $this->request->method('getParam')->with('fields', [])->willReturn(['supplierName' => 'ACME B.V.']);
        $this->extractionService->method('addCorrection')->willReturn(['id' => 'extraction-3']);

        $this->suggestionService->expects($this->never())->method('recordBooking');

        $result = $this->controller->corrections('extraction-3');

        $this->assertSame(200, $result->getStatus());

    }//end testCorrectionsWithoutGlAccountCodeDoesNotRecordBooking()

    /**
     * A failure recording the GL-account booking history is logged but does
     * not turn a successful correction into an error response.
     *
     * @return void
     */
    public function testCorrectionsToleratesRecordBookingFailure(): void
    {
        $this->request->method('getParam')->with('fields', [])->willReturn(['glAccountCode' => '4300']);
        $this->extractionService->method('addCorrection')->willReturn(['id' => 'extraction-3']);
        $this->suggestionService->method('recordBooking')->willThrowException(new RuntimeException('boom'));

        $result = $this->controller->corrections('extraction-3');

        $this->assertSame(200, $result->getStatus());

    }//end testCorrectionsToleratesRecordBookingFailure()
}//end class
