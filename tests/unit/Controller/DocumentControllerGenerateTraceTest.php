<?php

/**
 * Characterization tests for DocumentController::generate() and generateBulk()
 *
 * Pins the arguments the document services receive, that the session user's
 * id is stamped into the options over any forged value, the multi-format
 * branch, the refusals and the failure answers.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\DocumentController;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\MultiFormatOutputProducerInterface;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the generate endpoints.
 *
 * @covers \OCA\Filinq\Controller\DocumentController
 */
class DocumentControllerGenerateTraceTest extends TestCase {

	private const FORGED = ['userId' => 'boss', 'requestedBy' => 'boss', 'owner' => 'boss'];

	private const FILES_RESULT = ['format' => 'pdf', 'output' => ['mode' => 'files', 'fileId' => 5, 'path' => '/p', 'name' => 'n.pdf', 'size' => 9]];

	private DocumentServiceInterface&MockObject $documents;

	private ?MultiFormatOutputProducerInterface $multiFormat = null;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->documents = $this->createMock(DocumentServiceInterface::class);

	}//end setUp()

	/**
	 * The session user's id and the file name are stamped into the options, over forged values.
	 *
	 * @return void
	 */
	public function testGenerateStampsTheSessionUser(): void {
		$this->documents->expects($this->once())->method('generateDocument')->with(
			$this->identicalTo('t-1'),
			$this->identicalTo(['case' => 'c-1']),
			$this->identicalTo(['userId' => 'alice', 'filename' => 'brief', 'pdfa' => true])
		)->willReturn(self::FILES_RESULT);

		$response = $this->controller(
			params: ['templateId' => 't-1', 'dataRefs' => ['case' => 'c-1'], 'options' => ['userId' => 'boss', 'filename' => 'x', 'pdfa' => true], 'filename' => 'brief']
		)->generate();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['fileId' => 5, 'path' => '/p', 'name' => 'n.pdf', 'size' => 9, 'format' => 'pdf'], $response->getData());

	}//end testGenerateStampsTheSessionUser()

	/**
	 * A formats request goes to the multi-format producer and answers its outputs, metadata and warnings only.
	 *
	 * @return void
	 */
	public function testGenerateWithFormatsUsesTheProducer(): void {
		$producer = $this->createMock(MultiFormatOutputProducerInterface::class);
		$producer->expects($this->once())->method('generate')->with(
			$this->identicalTo('t-1'),
			$this->identicalTo(['case' => 'c-2']),
			$this->identicalTo(['formats' => ['pdf', 'docx'], 'userId' => 'alice', 'filename' => 'document'])
		)->willReturn(['outputs' => ['o'], 'metadata' => ['m'], 'warnings' => ['w'], 'extra' => 1]);
		$this->multiFormat = $producer;
		$this->documents->expects($this->never())->method('generateDocument');

		$response = $this->controller(params: ['templateId' => 't-1', 'dataRefs' => ['case' => 'c-2'], 'options' => ['formats' => ['pdf', 'docx']]])->generate();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['outputs' => ['o'], 'metadata' => ['m'], 'warnings' => ['w']], $response->getData());

	}//end testGenerateWithFormatsUsesTheProducer()

	/**
	 * Without a producer a formats request is an ordinary generation.
	 *
	 * @return void
	 */
	public function testGenerateWithFormatsButNoProducer(): void {
		$this->documents->expects($this->once())->method('generateDocument')->willReturn(self::FILES_RESULT);

		$this->assertSame(200, $this->controller(params: ['templateId' => 't-1', 'options' => ['formats' => ['pdf']]])->generate()->getStatus());

	}//end testGenerateWithFormatsButNoProducer()

	/**
	 * With a producer but no formats the document service renders it, and an inline download carries the request's file name.
	 *
	 * @return void
	 */
	public function testProducerWithoutFormatsAndInlineDownload(): void {
		$producer = $this->createMock(MultiFormatOutputProducerInterface::class);
		$producer->expects($this->never())->method('generate');
		$this->multiFormat = $producer;
		$this->documents->expects($this->once())->method('generateDocument')->willReturn(['format' => 'docx', 'content' => 'bytes']);

		$response = $this->controller(params: ['templateId' => 't-1', 'filename' => 'brief.pdf'])->generate();

		$this->assertSame(200, $response->getStatus());
		$this->assertStringContainsString('brief.docx', (string)$response->getHeaders()['Content-Disposition']);

	}//end testProducerWithoutFormatsAndInlineDownload()

	/**
	 * Refusals and failures of generate().
	 *
	 * @return void
	 */
	public function testGenerateRefusalsAndFailures(): void {
		$this->documents->method('generateDocument')->willThrowException(new Exception('template broken', 422));

		$missing = $this->controller(params: ['dataRefs' => []])->generate();
		$this->assertSame(400, $missing->getStatus());
		$this->assertSame(['error' => 'templateId is required'], $missing->getData());

		$this->assertSame(401, $this->controller(params: ['templateId' => 't-1'], signedIn: false)->generate()->getStatus());

		$failed = $this->controller(params: ['templateId' => 't-1'])->generate();
		$this->assertSame(422, $failed->getStatus());
		$this->assertSame(['error' => 'template broken'], $failed->getData());

	}//end testGenerateRefusalsAndFailures()

	/**
	 * A bulk run gets the session user's id over a forged one, and a job answers 202.
	 *
	 * @return void
	 */
	public function testBulkStampsTheSessionUserAndQueues(): void {
		$this->documents->expects($this->once())->method('generateBulk')->with(
			$this->identicalTo('t-1'),
			$this->identicalTo(['o1', 'o2']),
			$this->identicalTo(['userId' => 'alice', 'zip' => true])
		)->willReturn(['jobId' => 'j-1']);

		$response = $this->controller(params: ['templateId' => 't-1', 'objectIds' => ['o1', 'o2'], 'options' => ['userId' => 'boss', 'zip' => true]])->generateBulk();

		$this->assertSame(202, $response->getStatus());
		$this->assertSame(['jobId' => 'j-1'], $response->getData());

	}//end testBulkStampsTheSessionUserAndQueues()

	/**
	 * A small bulk run answers 200, and non-array options become just the user id.
	 *
	 * @return void
	 */
	public function testBulkWithoutJobAnswers200(): void {
		$this->documents->expects($this->once())->method('generateBulk')->with(
			$this->identicalTo('t-9'),
			$this->identicalTo(['o1']),
			$this->identicalTo(['userId' => 'alice'])
		)->willReturn(['results' => ['r']]);

		$response = $this->controller(params: ['templateId' => 't-9', 'objectIds' => ['o1'], 'options' => 'zip'])->generateBulk();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['results' => ['r']], $response->getData());

	}//end testBulkWithoutJobAnswers200()

	/**
	 * Refusals and failures of generateBulk(), the template checked first.
	 *
	 * @return void
	 */
	public function testBulkRefusalsAndFailures(): void {
		$template = ['error' => 'templateId is required'];
		$objects = ['error' => 'objectIds is required and must be an array'];
		$cases = [
			[[], $template],
			[['templateId' => '0', 'objectIds' => ['o1']], $template],
			[['templateId' => 't-1'], $objects],
			[['templateId' => 't-1', 'objectIds' => []], $objects],
			[['templateId' => 't-1', 'objectIds' => 'o1'], $objects],
		];
		foreach ($cases as [$params, $error]) {
			$response = $this->controller(params: $params)->generateBulk();
			$this->assertSame(400, $response->getStatus());
			$this->assertSame($error, $response->getData());
		}

		$this->assertSame(401, $this->controller(params: ['templateId' => 't-1', 'objectIds' => ['o1']], signedIn: false)->generateBulk()->getStatus());

		$this->documents->method('generateBulk')->willThrowException(new Exception('queue full', 503));
		$failed = $this->controller(params: ['templateId' => 't-1', 'objectIds' => ['o1']])->generateBulk();
		$this->assertSame(503, $failed->getStatus());
		$this->assertSame(['error' => 'queue full'], $failed->getData());

	}//end testBulkRefusalsAndFailures()

	/**
	 * The controller over a request carrying forged identity values through getParams() and getParam().
	 *
	 * @param array<string, mixed> $params   The request values on top of the forged ones.
	 * @param bool                 $signedIn Whether a session user exists.
	 *
	 * @return DocumentController The controller.
	 */
	private function controller(array $params, bool $signedIn = true): DocumentController {
		$all = array_merge(self::FORGED, $params);
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($all);
		$request->method('getParam')->willReturnCallback(
			fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $all) === true ? $all[$key] : $default)
		);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('alice');
		}

		$session->method('getUser')->willReturn($user);

		return new DocumentController(
			appName: 'filinq',
			request: $request,
			documentSvc: $this->documents,
			userSession: $session,
			logger: $this->createMock(LoggerInterface::class),
			l10n: $l10n,
			multiFormat: $this->multiFormat,
		);
	}//end controller()
}//end class
