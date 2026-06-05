<?php

/**
 * Unit tests for ConsentScopeValidator
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
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\ConsentScopeValidator;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\AppFramework\OCS\OCSForbiddenException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConsentScopeValidator
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsentScopeValidatorTest extends TestCase
{

    /**
     * @var ConsentScopeValidator
     */
    private ConsentScopeValidator $validator;

    /**
     * @var IGroupManager|MockObject
     */
    private IGroupManager|MockObject $mockGroupManager;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockGroupManager = $this->createMock(IGroupManager::class);

        $this->validator = new ConsentScopeValidator(
            $this->mockGroupManager
        );

    }//end setUp()


    /**
     * Test that scope:document without documentId throws
     *
     * @return void
     */
    public function testValidateWriteScopeDocumentMissingDocumentIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('documentId is required for scope: document records');

        $this->validator->validateWrite(
            data: [
                'scope'      => 'document',
                'entityText' => 'John Doe',
            ]
        );

    }//end testValidateWriteScopeDocumentMissingDocumentIdThrows()


    /**
     * Test that scope:entity with documentId throws
     *
     * @return void
     */
    public function testValidateWriteScopeEntityWithDocumentIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('documentId must not be set on scope: entity records');

        $this->validator->validateWrite(
            data: [
                'scope'         => 'entity',
                'documentId'    => 'some-doc-uuid',
                'matchRules'    => ['John Doe'],
                'consentMethod' => 'explicit',
            ]
        );

    }//end testValidateWriteScopeEntityWithDocumentIdThrows()


    /**
     * Test that scope:entity without matchRules throws
     *
     * @return void
     */
    public function testValidateWriteScopeEntityMissingMatchRulesThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('matchRules is required for scope: entity records');

        $this->validator->validateWrite(
            data: [
                'scope'         => 'entity',
                'consentMethod' => 'explicit',
            ]
        );

    }//end testValidateWriteScopeEntityMissingMatchRulesThrows()


    /**
     * Test that scope:entity without consentMethod throws
     *
     * @return void
     */
    public function testValidateWriteScopeEntityMissingConsentMethodThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('consentMethod is required for scope: entity records');

        $this->validator->validateWrite(
            data: [
                'scope'      => 'entity',
                'matchRules' => ['John Doe'],
            ]
        );

    }//end testValidateWriteScopeEntityMissingConsentMethodThrows()


    /**
     * Test that scope:entity with policyMatch throws
     *
     * @return void
     */
    public function testValidateWritePolicyMatchOnEntityScopeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('policyMatch is only valid on scope: document records');

        $this->validator->validateWrite(
            data: [
                'scope'         => 'entity',
                'matchRules'    => ['John Doe'],
                'consentMethod' => 'explicit',
                'policyMatch'   => 'some-policy-uuid',
            ]
        );

    }//end testValidateWritePolicyMatchOnEntityScopeThrows()


    /**
     * Test that valid scope:entity data passes without throwing
     *
     * @return void
     */
    public function testValidateWriteScopeEntityValidDataPasses(): void
    {
        // Should not throw.
        $this->validator->validateWrite(
            data: [
                'scope'         => 'entity',
                'matchRules'    => ['John Doe'],
                'consentMethod' => 'explicit',
            ]
        );

        $this->assertTrue(true);

    }//end testValidateWriteScopeEntityValidDataPasses()


    /**
     * Test that transitioning a policy-matched record to a different terminal status throws
     *
     * @return void
     */
    public function testValidateTransitionPolicyMatchedRecordBlocksStatusChange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transition policy-pre-empted records to a different terminal status');

        $existing = [
            'consentStatus' => 'consent_given',
            'policyMatch'   => 'policy-uuid-abc',
        ];

        $update = [
            'consentStatus' => 'objection_received',
        ];

        $this->validator->validateTransition(
            existing: $existing,
            update: $update
        );

    }//end testValidateTransitionPolicyMatchedRecordBlocksStatusChange()


    /**
     * Test that transitioning a record with no policyMatch is allowed
     *
     * @return void
     */
    public function testValidateTransitionNoPolicyMatchAllowsTransition(): void
    {
        $existing = [
            'consentStatus' => 'pending',
            'policyMatch'   => null,
        ];

        $update = [
            'consentStatus' => 'no_response',
        ];

        // Should not throw.
        $this->validator->validateTransition(
            existing: $existing,
            update: $update
        );

        $this->assertTrue(true);

    }//end testValidateTransitionNoPolicyMatchAllowsTransition()


    /**
     * Test that applyOverrideUp sets publicationDecision=anonymize and keeps other fields
     *
     * @return void
     */
    public function testApplyOverrideUpSetsAnonymizeKeepsPolicyMatch(): void
    {
        $existing = [
            'consentStatus'       => 'consent_given',
            'policyMatch'         => 'policy-uuid-abc',
            'publicationDecision' => 'publish_with_consent',
        ];

        $result = $this->validator->applyOverrideUp(existing: $existing);

        $this->assertEquals('anonymize', $result['publicationDecision']);
        $this->assertEquals('consent_given', $result['consentStatus']);
        $this->assertEquals('policy-uuid-abc', $result['policyMatch']);

    }//end testApplyOverrideUpSetsAnonymizeKeepsPolicyMatch()


    /**
     * Test that isEntityScopeAdmin returns false for non-member
     *
     * @return void
     */
    public function testIsEntityScopeAdminReturnsFalseForNonMember(): void
    {
        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('test-user');

        $this->mockGroupManager->method('isInGroup')
            ->with('test-user', 'docudesk-standing-consent-admins')
            ->willReturn(false);

        $result = $this->validator->isEntityScopeAdmin(user: $mockUser);

        $this->assertFalse($result);

    }//end testIsEntityScopeAdminReturnsFalseForNonMember()


    /**
     * Test that requireStandingConsentAdminGroup throws for non-member
     *
     * @return void
     */
    public function testRequireStandingConsentAdminGroupThrowsForNonMember(): void
    {
        $this->expectException(OCSForbiddenException::class);

        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('test-user');

        $this->mockGroupManager->method('isInGroup')
            ->willReturn(false);

        $this->validator->requireStandingConsentAdminGroup(user: $mockUser);

    }//end testRequireStandingConsentAdminGroupThrowsForNonMember()


}//end class
