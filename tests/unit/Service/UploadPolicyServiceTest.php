<?php

/**
 * Unit tests for UploadPolicyService
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
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\UploadRefusedException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\UploadPolicyService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Asserts that the type is read from the BYTES, that the refusal names what was
 * detected, and that an instance with no policy keeps working.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class UploadPolicyServiceTest extends TestCase {

	/**
	 * A tiny but genuine PDF, so finfo reads it as one.
	 *
	 * @return string The bytes.
	 */
	private function pdfBytes(): string {
		return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

	}//end pdfBytes()

	/**
	 * A complete ELF header, which is what a renamed executable looks like.
	 *
	 * The header is complete on purpose: a TRUNCATED one reads as
	 * `application/octet-stream`, which this service treats as "type unknown"
	 * rather than as a type. Both are refused, but only a complete header
	 * exercises the branch that names the detected type in the refusal.
	 *
	 * @return string The bytes.
	 */
	private function executableBytes(): string {
		return "\x7fELF\x02\x01\x01\x00"
			. str_repeat("\x00", 8)
			. "\x02\x00\x3e\x00\x01\x00\x00\x00"
			. str_repeat("\x00", 100);

	}//end executableBytes()

	/**
	 * Build the service over a register holding the given policy.
	 *
	 * @param array<string, mixed>|null $policy The active policy, or null for none.
	 *
	 * @return UploadPolicyService The service under test.
	 */
	private function service(?array $policy): UploadPolicyService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturn(($policy === null ? [] : [$policy]));

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new UploadPolicyService($resolver, $this->createMock(LoggerInterface::class));

	}//end service()

	/**
	 * The standard policy: PDF, ODT and DOCX, 200 MB, unknown types refused.
	 *
	 * @return array<string, mixed> The policy.
	 */
	private function standardPolicy(): array {
		return [
			'name' => 'Standaardbeleid voor uploads',
			'allowedExtensions' => ['pdf', 'odt', 'docx'],
			'allowedMediaTypes' => ['application/pdf', 'application/vnd.oasis.opendocument.text'],
			'maxSizeBytes' => 209715200,
			'refuseUnknownType' => true,
			'active' => true,
		];

	}//end standardPolicy()

	/**
	 * A PDF that really is a PDF is allowed, and the detected type says so.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testAPdfThatIsAPdfIsAllowed(): void {
		$service = $this->service(policy: $this->standardPolicy());

		$result = $service->check(fileName: 'besluit.pdf', contents: $this->pdfBytes());

		$this->assertSame('application/pdf', $result['detectedType']);
		$this->assertTrue($result['checked']);

	}//end testAPdfThatIsAPdfIsAllowed()

	/**
	 * An executable renamed to .pdf is refused, and the refusal names the type.
	 *
	 * This is the case the check exists for: the extension passes, and only the
	 * bytes say what the file is.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testAnExecutableRenamedToPdfIsRefusedByItsBytes(): void {
		$service = $this->service(policy: $this->standardPolicy());

		try {
			$service->check(fileName: 'bijlage.pdf', contents: $this->executableBytes());
			$this->fail('An executable renamed to .pdf must be refused.');
		} catch (UploadRefusedException $refusal) {
			$this->assertStringContainsString('executable', $refusal->getMessage());
			$this->assertStringContainsString('executable', $refusal->getDetectedType());
			$this->assertSame('Standaardbeleid voor uploads', $refusal->getPolicy());
			$this->assertSame(
				400,
				$refusal->getCode(),
				'A refusal answers 400: the file is not allowed, the server is not broken.'
			);
		}

	}//end testAnExecutableRenamedToPdfIsRefusedByItsBytes()

	/**
	 * An extension the policy does not allow is refused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testAnExtensionThePolicyDoesNotAllowIsRefused(): void {
		$service = $this->service(policy: $this->standardPolicy());

		// 🔴 THE MESSAGE IS THE ASSERTION, NOT THE EXCEPTION TYPE. A `.sh` is
		// refused by the extension rule AND by the media-type rule, so asserting
		// only that something threw passes with the extension check deleted:
		// measured, by removing it. Naming the rule is what makes this test able
		// to fail for the reason it claims to test.
		try {
			$service->check(fileName: 'script.sh', contents: "#!/bin/sh\necho hoi\n");
			$this->fail('an extension the policy does not allow must be refused');
		} catch (UploadRefusedException $refusal) {
			$this->assertSame(
				'The policy does not allow .sh.',
				$refusal->getMessage(),
				'the extension rule refused it, and says so'
			);
		}

	}//end testAnExtensionThePolicyDoesNotAllowIsRefused()

	/**
	 * Too large is too large, and it is refused before the bytes are stored.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testAFileOverTheMaximumIsRefused(): void {
		$policy = ($this->standardPolicy() + []);
		$policy['maxSizeBytes'] = 64;
		$service = $this->service(policy: $policy);

		try {
			$service->check(fileName: 'besluit.pdf', contents: $this->pdfBytes());
			$this->fail('A file over the maximum must be refused.');
		} catch (UploadRefusedException $refusal) {
			$this->assertStringContainsString('MB', $refusal->getMessage());
		}

	}//end testAFileOverTheMaximumIsRefused()

	/**
	 * A type that cannot be read is refused when the policy says so.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testAnUnreadableTypeIsRefusedWhenThePolicySaysSo(): void {
		$service = $this->service(policy: $this->standardPolicy());

		$this->expectException(UploadRefusedException::class);
		$service->check(fileName: 'leeg.pdf', contents: '');

	}//end testAnUnreadableTypeIsRefusedWhenThePolicySaysSo()

	/**
	 * A policy that accepts unknown types accepts one, with a warning.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testAPolicyThatAcceptsUnknownTypesAcceptsOne(): void {
		$policy = ($this->standardPolicy() + []);
		$policy['refuseUnknownType'] = false;
		$service = $this->service(policy: $policy);

		$result = $service->check(fileName: 'leeg.pdf', contents: '');

		$this->assertSame('', $result['detectedType']);
		$this->assertTrue($result['checked']);

	}//end testAPolicyThatAcceptsUnknownTypesAcceptsOne()

	/**
	 * An instance that declared no policy keeps working.
	 *
	 * Refusing everything on an undeclared policy would take every upload down
	 * on upgrade, which is a worse failure than the one it prevents.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function testNoPolicyIsNotARefusal(): void {
		$service = $this->service(policy: null);

		$result = $service->check(fileName: 'wat-dan-ook.xyz', contents: $this->executableBytes());

		$this->assertFalse($result['checked']);
		$this->assertSame('application/x-executable', $result['detectedType']);

	}//end testNoPolicyIsNotARefusal()
}//end class
