<?php

/**
 * Unit tests for PreferencesController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-5.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\PreferencesController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PreferencesController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PreferencesControllerTest extends TestCase
{

    /**
     * @var PreferencesController
     */
    private PreferencesController $controller;

    /**
     * @var IConfig|MockObject
     */
    private IConfig|MockObject $mockConfig;

    /**
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * @var IUser|MockObject
     */
    private IUser|MockObject $mockUser;

    protected function setUp(): void
    {
        parent::setUp();

        $mockRequest           = $this->createMock(IRequest::class);
        $this->mockConfig      = $this->createMock(IConfig::class);
        $this->mockUserSession = $this->createMock(IUserSession::class);
        $this->mockUser        = $this->createMock(IUser::class);

        $this->mockUser->method('getUID')->willReturn('testuser');
        $this->mockUserSession->method('getUser')->willReturn($this->mockUser);

        $this->controller = new PreferencesController(
            request: $mockRequest,
            config: $this->mockConfig,
            userSession: $this->mockUserSession,
        );

    }//end setUp()

    /**
     * Test getPreference returns 401 when no user logged in.
     *
     * @return void
     */
    public function testGetPreferenceReturns401WhenNoUser(): void
    {
        $mockRequest = $this->createMock(IRequest::class);
        $mockConfig  = $this->createMock(IConfig::class);
        $mockSession = $this->createMock(IUserSession::class);
        $mockSession->method('getUser')->willReturn(null);

        $controller = new PreferencesController(
            request: $mockRequest,
            config: $mockConfig,
            userSession: $mockSession,
        );

        $result = $controller->getPreference('some-key');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertSame(401, $result->getStatus());

    }//end testGetPreferenceReturns401WhenNoUser()

    /**
     * Test getPreference returns null value when key not stored.
     *
     * @return void
     */
    public function testGetPreferenceReturnsNullWhenNotStored(): void
    {
        $this->mockConfig->method('getUserValue')->willReturn('');

        $result = $this->controller->getPreference('my-key');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertSame(200, $result->getStatus());
        $data = $result->getData();
        $this->assertNull($data['value']);

    }//end testGetPreferenceReturnsNullWhenNotStored()

    /**
     * Test getPreference returns stored value.
     *
     * @return void
     */
    public function testGetPreferenceReturnsStoredValue(): void
    {
        $this->mockConfig->method('getUserValue')->willReturn('true');

        $result = $this->controller->getPreference('my-key');

        $this->assertSame(200, $result->getStatus());
        $data = $result->getData();
        $this->assertSame('true', $data['value']);

    }//end testGetPreferenceReturnsStoredValue()

    /**
     * Test getPreference returns 400 for invalid key.
     *
     * @return void
     */
    public function testGetPreferenceReturns400ForInvalidKey(): void
    {
        $result = $this->controller->getPreference('');

        $this->assertSame(400, $result->getStatus());

    }//end testGetPreferenceReturns400ForInvalidKey()

    /**
     * Test setPreference clears value when empty string provided.
     *
     * @return void
     */
    public function testSetPreferenceClearsValueOnEmptyString(): void
    {
        $this->mockConfig->expects($this->once())
            ->method('deleteUserValue')
            ->with('testuser', 'docudesk', 'pref_my-key');

        $result = $this->controller->setPreference('my-key', '');

        $this->assertSame(200, $result->getStatus());
        $data = $result->getData();
        $this->assertNull($data['value']);

    }//end testSetPreferenceClearsValueOnEmptyString()

    /**
     * Test setPreference stores value when non-empty.
     *
     * @return void
     */
    public function testSetPreferenceStoresValue(): void
    {
        $this->mockConfig->expects($this->once())
            ->method('setUserValue')
            ->with('testuser', 'docudesk', 'pref_my-key', 'true');

        $result = $this->controller->setPreference('my-key', 'true');

        $this->assertSame(200, $result->getStatus());
        $data = $result->getData();
        $this->assertSame('true', $data['value']);

    }//end testSetPreferenceStoresValue()
}//end class
