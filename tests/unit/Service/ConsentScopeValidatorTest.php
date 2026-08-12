<?php

/**
 * Unit tests for ConsentScopeValidator.
 *
 * Exercises the four-corner contract from publication-consent-policy-fields task 5:
 *   - scope=document missing documentId   → reject
 *   - scope=entity carrying documentId    → reject
 *   - scope=entity missing matchRules     → reject
 *   - scope=entity missing consentMethod  → reject
 *   - scope=entity carrying policyMatch   → reject
 *   - unknown scope value                 → reject
 *   - valid document-scope                → accept
 *   - valid entity-scope                  → accept
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\DocuDesk\Service\ConsentScopeValidator;
use OCP\IGroupManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ConsentScopeValidator.
 *
 * @internal
 * @coversDefaultClass \OCA\DocuDesk\Service\ConsentScopeValidator
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConsentScopeValidatorTest extends TestCase {
	/**
	 * Create a ConsentScopeValidator with a no-op IGroupManager stub.
	 *
	 * All tests in this file exercise assertValid() which does not consult
	 * the group manager. The stub satisfies the constructor requirement
	 * without any side-effects.
	 *
	 * @return ConsentScopeValidator
	 */
	private function makeValidator(): ConsentScopeValidator {
		$groupManager = $this->createMock(IGroupManager::class);
		return new ConsentScopeValidator(groupManager: $groupManager);
	}//end makeValidator()

	/**
	 * Document-scope rows missing documentId are rejected.
	 *
	 * @return void
	 */
	public function testDocumentScopeRejectsMissingDocumentId(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->makeValidator()->assertValid(['scope' => 'document']);

	}//end testDocumentScopeRejectsMissingDocumentId()

	/**
	 * Document-scope rows with documentId pass.
	 *
	 * @return void
	 */
	public function testDocumentScopeAcceptsValidPayload(): void {
		$this->makeValidator()->assertValid([
			'scope' => 'document',
			'documentId' => 'doc-uuid-1',
			'entityType' => 'PERSON',
			'entityText' => 'Jan Jansen',
		]);
		$this->expectNotToPerformAssertions();

	}//end testDocumentScopeAcceptsValidPayload()

	/**
	 * Entity-scope rows carrying documentId are rejected.
	 *
	 * @return void
	 */
	public function testEntityScopeRejectsDocumentId(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->makeValidator()->assertValid([
			'scope' => 'entity',
			'documentId' => 'doc-uuid-1',
			'matchRules' => [['type' => 'exact', 'value' => 'Mayor']],
			'consentMethod' => 'paper',
		]);

	}//end testEntityScopeRejectsDocumentId()

	/**
	 * Entity-scope rows missing matchRules are rejected.
	 *
	 * @return void
	 */
	public function testEntityScopeRejectsMissingMatchRules(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->makeValidator()->assertValid([
			'scope' => 'entity',
			'consentMethod' => 'paper',
		]);

	}//end testEntityScopeRejectsMissingMatchRules()

	/**
	 * Entity-scope rows with an empty matchRules array are rejected.
	 *
	 * @return void
	 */
	public function testEntityScopeRejectsEmptyMatchRules(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->makeValidator()->assertValid([
			'scope' => 'entity',
			'matchRules' => [],
			'consentMethod' => 'paper',
		]);

	}//end testEntityScopeRejectsEmptyMatchRules()

	/**
	 * Entity-scope rows with an unknown match-rule type are rejected.
	 *
	 * @return void
	 */
	public function testEntityScopeRejectsUnknownMatchRuleType(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->makeValidator()->assertValid([
			'scope' => 'entity',
			'matchRules' => [['type' => 'made-up', 'value' => 'Mayor']],
			'consentMethod' => 'paper',
		]);

	}//end testEntityScopeRejectsUnknownMatchRuleType()

	/**
	 * Entity-scope rows missing consentMethod are rejected.
	 *
	 * @return void
	 */
	public function testEntityScopeRejectsMissingConsentMethod(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->makeValidator()->assertValid([
			'scope' => 'entity',
			'matchRules' => [['type' => 'exact', 'value' => 'Mayor']],
		]);

	}//end testEntityScopeRejectsMissingConsentMethod()

	/**
	 * Entity-scope rows carrying policyMatch are rejected.
	 *
	 * @return void
	 */
	public function testEntityScopeRejectsPolicyMatch(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->makeValidator()->assertValid([
			'scope' => 'entity',
			'matchRules' => [['type' => 'exact', 'value' => 'Mayor']],
			'consentMethod' => 'paper',
			'policyMatch' => 'some-uuid',
		]);

	}//end testEntityScopeRejectsPolicyMatch()

	/**
	 * Valid entity-scope rows pass.
	 *
	 * @return void
	 */
	public function testEntityScopeAcceptsValidPayload(): void {
		$this->makeValidator()->assertValid([
			'scope' => 'entity',
			'matchRules' => [
				['type' => 'exact', 'value' => 'Mayor of Den Haag'],
				['type' => 'bsn', 'value' => '123456789'],
			],
			'consentMethod' => 'paper',
			'validFrom' => '2026-01-01T00:00:00+01:00',
			'active' => true,
		]);
		$this->expectNotToPerformAssertions();

	}//end testEntityScopeAcceptsValidPayload()

	/**
	 * Unknown scope values are rejected.
	 *
	 * @return void
	 */
	public function testUnknownScopeIsRejected(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->makeValidator()->assertValid([
			'scope' => 'tenant',
			'documentId' => 'doc-1',
		]);

	}//end testUnknownScopeIsRejected()

}//end class
