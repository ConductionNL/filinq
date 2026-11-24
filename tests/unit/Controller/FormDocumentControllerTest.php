<?php

/**
 * Unit tests for FormDocumentController
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
 * @spec openspec/changes/forms-as-documents/specs/document-creatie-sjablonen/spec.md#requirement-a-file-is-a-value-a-field-can-hold-and-a-form-becomes-a-document-req-dft-05
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\FormDocumentController;
use OCA\Filinq\Controller\FormDocumentRefused;
use OCA\Filinq\Controller\FormDocumentFiling;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The form routes answer the service's result, its refusal or a 500.
 *
 * @covers \OCA\Filinq\Controller\FormDocumentController
 */
class FormDocumentControllerTest extends TestCase {

	/**
	 * The service double.
	 *
	 * @var FormDocumentFiling&MockObject
	 */
	private FormDocumentFiling&MockObject $forms;

	/**
	 * Build a controller over a request with these parameters.
	 *
	 * @param array<string, mixed> $params The request parameters.
	 *
	 * @return FormDocumentController The controller.
	 */
	private function controller(array $params): FormDocumentController {
		$request = $this->createMock(originalClassName: IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default=null): mixed => ($params[$key] ?? $default)
		);
		$l10n = $this->createMock(originalClassName: IL10N::class);
		$l10n->method('t')->willReturnArgument(argumentIndex: 0);
		$this->forms = $this->createMock(originalClassName: FormDocumentFiling::class);

		return new FormDocumentController(
			request: $request,
			forms: $this->forms,
			l10n: $l10n,
			logger: $this->createMock(originalClassName: LoggerInterface::class)
		);

	}//end controller()

	/**
	 * A checked reference is answered 200 with the body's reference passed through.
	 *
	 * @return void
	 */
	public function testCheckReferencePassesTheBodyAndAnswers200(): void {
		$controller = $this->controller(params: ['id' => 'doc-1']);
		$this->forms->expects($this->once())->method('checkReference')
			->with(['register' => '', 'schema' => '', 'id' => 'doc-1'])
			->willReturn(['register' => 'filinq', 'schema' => 'document', 'id' => 'doc-1', 'title' => 'T']);

		$response = $controller->checkReference();

		$this->assertSame(expected: 200, actual: $response->getStatus());
		$this->assertSame(expected: 'doc-1', actual: $response->getData()['id']);

	}//end testCheckReferencePassesTheBodyAndAnswers200()

	/**
	 * A refusal answers its own status and message.
	 *
	 * @return void
	 */
	public function testARefusalAnswersItsStatus(): void {
		$controller = $this->controller(params: ['id' => 'x']);
		$this->forms->method('checkReference')->willThrowException(new FormDocumentRefused(message: 'nope', code: 404));

		$response = $controller->checkReference();

		$this->assertSame(expected: 404, actual: $response->getStatus());
		$this->assertSame(expected: ['error' => 'nope'], actual: $response->getData());

	}//end testARefusalAnswersItsStatus()

	/**
	 * A submission passes only the keys the body carries and answers 201.
	 *
	 * @return void
	 */
	public function testSubmitAnswers201(): void {
		$controller = $this->controller(params: ['templateId' => 't', 'record' => ['id' => 'c'], 'values' => ['a' => 1]]);
		$this->forms->expects($this->once())->method('renderSubmission')
			->with(['templateId' => 't', 'record' => ['id' => 'c'], 'values' => ['a' => 1]])
			->willReturn(['fileName' => 'form.pdf']);

		$response = $controller->submit();

		$this->assertSame(expected: 201, actual: $response->getStatus());

	}//end testSubmitAnswers201()

	/**
	 * An unexpected failure is a 500 without its detail.
	 *
	 * @return void
	 */
	public function testAnUnexpectedFailureIsA500(): void {
		$controller = $this->controller(params: []);
		$this->forms->method('renderSubmission')->willThrowException(new RuntimeException('secret detail'));

		$response = $controller->submit();

		$this->assertSame(expected: 500, actual: $response->getStatus());
		$this->assertStringNotContainsString(needle: 'secret', haystack: (string) $response->getData()['error']);

	}//end testAnUnexpectedFailureIsA500()

}//end class
