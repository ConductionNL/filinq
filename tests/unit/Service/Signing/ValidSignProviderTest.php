<?php

/**
 * Unit tests for ValidSignProvider
 *
 * Covers identifier, level support, and the stub behaviour of initiateSigning,
 * checkStatus, downloadSignedDocument, and cancelSigning per REQ-SIGN-03.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-signing/tasks.md#2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Exception\SigningCancellationNotSupportedException;
use OCA\Filinq\Service\Signing\ValidSignProvider;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for ValidSignProvider stub implementation
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ValidSignProviderTest extends TestCase {

	/**
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $config;

	/**
	 * @var ValidSignProvider
	 */
	private ValidSignProvider $provider;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IAppConfig::class);

		$this->provider = new ValidSignProvider(
			config: $this->config
		);

	}//end setUp()

	/**
	 * getIdentifier() returns the canonical 'validsign' identifier.
	 *
	 * @return void
	 */
	public function testGetIdentifierReturnsValidsign(): void {
		$this->assertSame('validsign', $this->provider->getIdentifier());

	}//end testGetIdentifierReturnsValidsign()

	/**
	 * supportsLevel() returns true for all three eIDAS levels.
	 *
	 * @return void
	 */
	public function testSupportsLevelForEidasLevels(): void {
		$this->assertTrue($this->provider->supportsLevel(level: 'SES'));
		$this->assertTrue($this->provider->supportsLevel(level: 'AdES'));
		$this->assertTrue($this->provider->supportsLevel(level: 'QES'));

	}//end testSupportsLevelForEidasLevels()

	/**
	 * supportsLevel() returns false for unknown levels.
	 *
	 * @return void
	 */
	public function testSupportsLevelReturnsFalseForUnknown(): void {
		$this->assertFalse($this->provider->supportsLevel(level: 'UNKNOWN'));
		$this->assertFalse($this->provider->supportsLevel(level: ''));
		$this->assertFalse($this->provider->supportsLevel(level: 'ses'));

	}//end testSupportsLevelReturnsFalseForUnknown()

	/**
	 * initiateSigning() throws when the provider is not configured (no sourceId).
	 *
	 * @return void
	 */
	public function testInitiateSigningThrowsWhenNotConfigured(): void {
		$this->config->method('getValueString')->willReturn('{}');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('ValidSign provider is not configured');

		$this->provider->initiateSigning(
			documentPath: '/tmp/test.pdf',
			documentName: 'test.pdf',
			signers: [['userId' => 'alice']],
			level: 'SES'
		);

	}//end testInitiateSigningThrowsWhenNotConfigured()

	/**
	 * initiateSigning() returns a success result when sourceId is configured.
	 *
	 * @return void
	 */
	public function testInitiateSigningSucceedsWhenConfigured(): void {
		$this->config->method('getValueString')->willReturn(json_encode(['sourceId' => 'vs-source-123']));

		$result = $this->provider->initiateSigning(
			documentPath: '/tmp/test.pdf',
			documentName: 'test.pdf',
			signers: [['userId' => 'alice']],
			level: 'SES'
		);

		$this->assertTrue($result['success']);
		$this->assertArrayHasKey('externalId', $result);
		$this->assertStringStartsWith('validsign-', $result['externalId']);

	}//end testInitiateSigningSucceedsWhenConfigured()

	/**
	 * checkStatus() returns a pending status response (stub).
	 *
	 * @return void
	 */
	public function testCheckStatusReturnsPendingStub(): void {
		$result = $this->provider->checkStatus(externalId: 'validsign-abc123');

		$this->assertSame('pending', $result['status']);
		$this->assertIsArray($result['signers']);
		$this->assertNull($result['completedAt']);

	}//end testCheckStatusReturnsPendingStub()

	/**
	 * downloadSignedDocument() always throws (not yet implemented).
	 *
	 * @return void
	 */
	public function testDownloadSignedDocumentAlwaysThrows(): void {
		$this->expectException(RuntimeException::class);

		$this->provider->downloadSignedDocument(externalId: 'validsign-abc123');

	}//end testDownloadSignedDocumentAlwaysThrows()

	/**
	 * cancelSigning() REFUSES rather than claiming a withdrawal it did not perform.
	 *
	 * This test previously asserted `assertTrue($result)` against a body that was,
	 * in full, `return true;` — no call to ValidSign. It was green BECAUSE of the
	 * defect: it pinned in place a method that tells a user their signing request
	 * is withdrawn while it stays live at the provider, with signatories still able
	 * to sign and produce a legally valid signature.
	 *
	 * openspec/changes/signing-cancellation.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testCancelSigningRefusesRatherThanClaimingSuccess(): void {
		$this->expectException(SigningCancellationNotSupportedException::class);
		$this->expectExceptionMessageMatches('/ValidSign.*still live.*can still sign/s');

		$this->provider->cancelSigning(externalId: 'validsign-abc123');

	}//end testCancelSigningRefusesRatherThanClaimingSuccess()

	/**
	 * The refusal tells the user what to do instead.
	 *
	 * A refusal a user cannot act on is only marginally better than the lie it
	 * replaced — they still do not know their request is live.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function testTheRefusalNamesTheRemedy(): void {
		try {
			$this->provider->cancelSigning(externalId: 'validsign-abc123');
			$this->fail('ValidSign cancellation must refuse');
		} catch (SigningCancellationNotSupportedException $e) {
			$this->assertStringContainsString('Withdraw it directly with the provider', $e->getMessage());
		}

	}//end testTheRefusalNamesTheRemedy()
}//end class
