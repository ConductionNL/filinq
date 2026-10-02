<?php

/**
 * The slug contract of OpenRegister's object reads
 *
 * Every other suite in this repository doubles the read that the service under
 * test happens to call, so it can only report that the service called it.
 * That is how eighteen reads spread over fourteen files could ask
 * `searchObjects` for a slug, get zero rows and no error back on a live
 * instance, and still go green here.
 *
 * This suite doubles the CONTRACT instead. Its OpenRegister answers
 * `searchObjects` the way the real one does when handed a non-numeric register
 * or schema - with an empty result and no exception - and answers
 * `searchObjectsBySlug` with the rows. A service that asks the wrong way reads
 * nothing, and the assertion below fails on the answer the user receives.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\DocumentFinalException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Editing\DocumentGuard;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\UploadPolicyService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Reads that hand OpenRegister a slug must go through searchObjectsBySlug.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SlugContractReadPathTest extends TestCase {

	/**
	 * Which read each service reached for, in order.
	 *
	 * @var array<int, string>
	 */
	private array $calls = [];

	/**
	 * A resolver whose OpenRegister honours the numeric-id contract.
	 *
	 * `searchObjects` answers an empty result whenever `@self.register` or
	 * `@self.schema` is not numeric, which is exactly what the live service
	 * does: no rows, no exception, nothing in the log.
	 *
	 * @param array<int, array<string, mixed>> $rows The rows a slug read finds.
	 *
	 * @return DocumentObjectServiceResolver The resolver.
	 */
	private function resolver(array $rows, bool $schemaAbsent = false): DocumentObjectServiceResolver {
		$objectService = $this->createMock(ObjectService::class);

		if ($schemaAbsent === true) {
			// What the real service does when the register or the schema is
			// not on this instance: it raises, where the numeric-id call used
			// to answer an empty set. Every site that moved gained this path,
			// and each one has to answer "there are none" rather than "could
			// not read" - otherwise a feature nobody enabled would freeze
			// every document or 500 the worklist.
			$objectService->method('searchObjectsBySlug')
				->willThrowException(new DoesNotExistException('no such schema'));

			$resolver = $this->createMock(DocumentObjectServiceResolver::class);
			$resolver->method('resolve')->willReturn($objectService);

			return $resolver;
		}

		$objectService->method('searchObjects')->willReturnCallback(
			function (array $query = []) use ($rows): array {
				$this->calls[] = 'searchObjects';

				$register = ($query['@self']['register'] ?? '');
				$schema   = ($query['@self']['schema'] ?? '');
				if (is_numeric($register) === false || is_numeric($schema) === false) {
					// The defect, reproduced: a slug is answered with the
					// empty set and no error.
					return [];
				}

				return $rows;
			}
		);

		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters = []) use ($rows): array {
				$this->calls[] = 'searchObjectsBySlug';

				return $rows;
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return $resolver;

	}//end resolver()

	/**
	 * A root folder answering with one readable file.
	 *
	 * @return IRootFolder The root folder.
	 */
	private function rootFolder(): IRootFolder {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(4711);
		$file->method('getName')->willReturn('Besluit.docx');
		$file->method('getContent')->willReturn('besluit');

		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturn([$file]);

		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);

		return $root;

	}//end rootFolder()

	/**
	 * A session holding one signed-in person.
	 *
	 * @return IUserSession The session.
	 */
	private function userSession(): IUserSession {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$user->method('getDisplayName')->willReturn('Anna de Boer');

		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return $session;

	}//end userSession()

	/**
	 * A frozen document refuses the write, on a register that answers slugs
	 * only through searchObjectsBySlug.
	 *
	 * This is the assertion the defect broke. `searchObjects` handed the slugs
	 * `filinq` and `documentVersion` answered zero rows, zero rows arrived at
	 * FinalDocumentService as an empty list rather than as null, refusalFor()
	 * found nothing to refuse, and every final document was writable.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAFinalDocumentRefusesAWriteWhenTheRegisterAnswersSlugsOnlyBySlug(): void {
		$resolver = $this->resolver(
			rows: [
				[
					'uuid' => 'version-1',
					'fileId' => 4711,
					'versionLabel' => '',
					'documentName' => 'Besluit.docx',
					'status' => 'final',
					'finalisedBy' => 'anna',
					'finalisedByName' => 'Anna de Boer',
					'finalisedAt' => '2026-09-03T14:05:00+00:00',
					'finalReason' => 'Het besluit is genomen',
					'fileChecksum' => hash('sha256', 'besluit'),
					'unfrozen' => false,
				],
			]
		);

		$service = new FinalDocumentService(
			new FinalDocumentRepository($resolver, new NullLogger()),
			$this->rootFolder(),
			$this->userSession(),
			new NullLogger()
		);

		$this->expectException(DocumentFinalException::class);
		$service->assertWritable(fileId: 4711, action: 'replace the file behind this document');

	}//end testAFinalDocumentRefusesAWriteWhenTheRegisterAnswersSlugsOnlyBySlug()

	/**
	 * The finalisation read asks by slug, and never the numeric-id call.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testTheFinalisationReadAsksBySlug(): void {
		$repository = new FinalDocumentRepository($this->resolver(rows: []), new NullLogger());
		$repository->findForFile(fileId: 4711);

		$this->assertSame(
			['searchObjectsBySlug'],
			$this->calls,
			'the finalisation read must ask by slug: searchObjects answers filinq/documentVersion with nothing'
		);

	}//end testTheFinalisationReadAsksBySlug()

	/**
	 * A declared upload policy is found, so an upload is actually checked.
	 *
	 * With the numeric-id call the policy read as absent and
	 * UploadPolicyService::check() returned `checked: false` for every upload
	 * on an instance that had declared one.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testADeclaredUploadPolicyIsFound(): void {
		$service = new UploadPolicyService(
			$this->resolver(
				rows: [
					[
						'name' => 'Standaardbeleid voor uploads',
						'allowedExtensions' => ['pdf'],
						'allowedMediaTypes' => ['application/pdf'],
						'maxSizeBytes' => 209715200,
						'refuseUnknownType' => true,
						'active' => true,
					],
				]
			),
			new NullLogger()
		);

		$policy = $service->activePolicy();

		$this->assertIsArray($policy, 'a declared upload policy must be found, not read as absent');
		$this->assertSame('Standaardbeleid voor uploads', $policy['name']);
		$this->assertSame(['searchObjectsBySlug'], $this->calls);

	}//end testADeclaredUploadPolicyIsFound()

	/**
	 * A document under a live signing request is refused, not served.
	 *
	 * DocumentGuard::signatureRefusal() documents itself as failing CLOSED.
	 * With the numeric-id call it failed open: zero rows arrived as an empty
	 * list rather than as null, the loop found no signing request, and the
	 * document was editable.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-documents-under-signature-or-produced-by-anonymisation-are-not-editable
	 */
	public function testADocumentUnderSignatureIsRefused(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(4711);

		$guard = new DocumentGuard(
			$this->resolver(rows: [['documentFileId' => '4711', 'status' => 'pending']]),
			new NullLogger(),
			$this->createMock(FinalDocumentService::class)
		);

		$refusal = $guard->signatureRefusal($file);

		$this->assertIsString(
			$refusal,
			'a document under a live signing request must be refused: the guard says it fails closed'
		);
		$this->assertStringContainsString('signing request', $refusal);

	}//end testADocumentUnderSignatureIsRefused()

	/**
	 * An instance without the documentVersion schema freezes nothing.
	 *
	 * searchObjectsBySlug raises where searchObjects answered an empty set,
	 * so this path is new. It must read as "there are none", not as "could
	 * not read": null here makes FinalDocumentService refuse every write.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAnInstanceWithoutTheVersionSchemaFreezesNothing(): void {
		$repository = new FinalDocumentRepository($this->resolver(rows: [], schemaAbsent: true), new NullLogger());

		$this->assertSame(
			[],
			$repository->findForFile(fileId: 4711),
			'an absent schema means nothing was ever finalised, which is [] and not null: null makes the guard refuse every write'
		);

	}//end testAnInstanceWithoutTheVersionSchemaFreezesNothing()

	/**
	 * An instance without the signing schema still lets documents be edited.
	 *
	 * DocumentGuard treats null as "could not read" and refuses. An absent
	 * schema is not a failed read, so it must arrive as an empty list.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-documents-under-signature-or-produced-by-anonymisation-are-not-editable
	 */
	public function testAnInstanceWithoutTheSigningSchemaStillAllowsEditing(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(4711);

		$guard = new DocumentGuard(
			$this->resolver(rows: [], schemaAbsent: true),
			new NullLogger(),
			$this->createMock(FinalDocumentService::class)
		);

		$this->assertNull(
			$guard->signatureRefusal($file),
			'an instance that never imported signingRequest has no signing requests, so editing is not refused'
		);

	}//end testAnInstanceWithoutTheSigningSchemaStillAllowsEditing()

	/**
	 * An instance without the intake schema answers an empty inbox, not a 500.
	 *
	 * IntakeRepository::search raises RuntimeException on a failed read, and
	 * the worklist endpoint turns that into an error. An absent schema is not
	 * a failed read.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testAnInstanceWithoutTheIntakeSchemaHasAnEmptyInbox(): void {
		$repository = new IntakeRepository($this->resolver(rows: [], schemaAbsent: true), new NullLogger());

		$this->assertSame([], $repository->findWaiting(), 'an absent schema is an empty inbox, not a 500');
		$this->assertNull($repository->findBySourceRef('msg-7'));

	}//end testAnInstanceWithoutTheIntakeSchemaHasAnEmptyInbox()

	/**
	 * An instance without the upload policy schema does not block uploads.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testAnInstanceWithoutTheUploadPolicySchemaDeclaresNoPolicy(): void {
		$service = new UploadPolicyService($this->resolver(rows: [], schemaAbsent: true), new NullLogger());

		$this->assertNull($service->activePolicy(), 'no policy schema means no policy, not a refusal');

	}//end testAnInstanceWithoutTheUploadPolicySchemaDeclaresNoPolicy()

	/**
	 * The intake inbox finds the row a channel already delivered.
	 *
	 * With the numeric-id call findBySourceRef() never found anything, so a
	 * channel redelivering the same message wrote a second inbox row, which
	 * is the one thing that method exists to prevent.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function testARedeliveredMessageIsRecognised(): void {
		$repository = new IntakeRepository($this->resolver(rows: [['uuid' => 'intake-1', 'sourceRef' => 'msg-7']]), new NullLogger());

		$found = $repository->findBySourceRef('msg-7');

		$this->assertIsArray($found, 'a redelivered message must be recognised, or the inbox grows a duplicate');
		$this->assertSame('intake-1', $found['uuid']);

	}//end testARedeliveredMessageIsRecognised()
}//end class
