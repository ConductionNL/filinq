<?php

/**
 * Wire-contract tests for PreferencesController
 *
 * Covers `GET /api/preferences/{key}` (preferences#getPreference) and
 * `PUT /api/preferences/{key}` (preferences#setPreference): the documented
 * `{value: string|null}` success body, the 401 anonymous rejection, the 400
 * invalid-key rejection, the per-user storage scoping (`pref_<safeKey>` under
 * the docudesk app id) and the empty-value delete semantics.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\PreferencesController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the per-user preference read/write endpoints.
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
     * Mocked Nextcloud config service.
     *
     * @var IConfig|MockObject
     */
    private IConfig|MockObject $config;

    /**
     * Mocked user session.
     *
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $userSession;

    /**
     * Controller under test, with an authenticated session.
     *
     * @var PreferencesController
     */
    private PreferencesController $controller;


    /**
     * Set up an authenticated controller.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config      = $this->createMock(IConfig::class);
        $this->userSession = $this->createMock(IUserSession::class);

        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('alice');
        $this->userSession->method('getUser')->willReturn($user);

        $this->controller = new PreferencesController(
            $this->createMock(IRequest::class),
            $this->config,
            $this->userSession
        );

    }//end setUp()


    /**
     * Build a controller whose session has no logged-in user.
     *
     * @return PreferencesController The anonymous-session controller.
     */
    private function anonymousController(): PreferencesController
    {
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn(null);

        return new PreferencesController(
            $this->createMock(IRequest::class),
            $this->config,
            $session
        );

    }//end anonymousController()


    /**
     * GET returns 200 with `{value: <stored>}` and reads the per-user,
     * `pref_`-prefixed key under the docudesk app id.
     *
     * @return void
     */
    public function testGetPreferenceReturnsStoredValue(): void
    {
        $this->config->expects($this->once())
            ->method('getUserValue')
            ->with('alice', 'docudesk', 'pref_support-dialog-seen', '')
            ->willReturn('1');

        $response = $this->controller->getPreference('support-dialog-seen');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame(['value' => '1'], $response->getData());

    }//end testGetPreferenceReturnsStoredValue()


    /**
     * An unset preference is reported as `{value: null}`, not as an empty
     * string — the UI distinguishes "never stored" from "stored empty".
     *
     * @return void
     */
    public function testGetPreferenceReturnsNullWhenUnset(): void
    {
        $this->config->method('getUserValue')->willReturn('');

        $response = $this->controller->getPreference('support-dialog-seen');

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame(['value' => null], $response->getData());

    }//end testGetPreferenceReturnsNullWhenUnset()


    /**
     * The caller-supplied key is sanitised before it reaches storage:
     * uppercase is lowered, hyphens survive, and everything outside
     * `[a-z0-9-]` (including path separators) is dropped.
     *
     * @return void
     */
    public function testGetPreferenceSanitisesKeyBeforeReading(): void
    {
        $this->config->expects($this->once())
            ->method('getUserValue')
            ->with('alice', 'docudesk', 'pref_my-key12', '')
            ->willReturn('x');

        $response = $this->controller->getPreference('My-Key_1../2!');

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame(['value' => 'x'], $response->getData());

    }//end testGetPreferenceSanitisesKeyBeforeReading()


    /**
     * A key that sanitises to nothing is rejected with 400 and never reaches
     * storage.
     *
     * @return void
     */
    public function testGetPreferenceRejectsUnusableKey(): void
    {
        $this->config->expects($this->never())->method('getUserValue');

        $response = $this->controller->getPreference('***');

        $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertSame(['message' => 'Invalid key'], $response->getData());

    }//end testGetPreferenceRejectsUnusableKey()


    /**
     * An anonymous caller gets 401 and no storage read is attempted.
     *
     * @return void
     */
    public function testGetPreferenceRejectsAnonymousCaller(): void
    {
        $this->config->expects($this->never())->method('getUserValue');

        $response = $this->anonymousController()->getPreference('theme');

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
        $this->assertSame(['message' => 'Not logged in'], $response->getData());

    }//end testGetPreferenceRejectsAnonymousCaller()


    /**
     * PUT with a value writes it per-user and echoes it back with 200.
     *
     * @return void
     */
    public function testSetPreferenceStoresValue(): void
    {
        $this->config->expects($this->once())
            ->method('setUserValue')
            ->with('alice', 'docudesk', 'pref_theme', 'dark');
        $this->config->expects($this->never())->method('deleteUserValue');

        $response = $this->controller->setPreference('theme', 'dark');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame(['value' => 'dark'], $response->getData());

    }//end testSetPreferenceStoresValue()


    /**
     * PUT with an empty value deletes the stored preference and answers
     * `{value: null}` — the documented clear semantics.
     *
     * @return void
     */
    public function testSetPreferenceWithEmptyValueDeletesIt(): void
    {
        $this->config->expects($this->once())
            ->method('deleteUserValue')
            ->with('alice', 'docudesk', 'pref_theme');
        $this->config->expects($this->never())->method('setUserValue');

        $response = $this->controller->setPreference('theme', '');

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame(['value' => null], $response->getData());

    }//end testSetPreferenceWithEmptyValueDeletesIt()


    /**
     * A key that sanitises to nothing is rejected with 400 and nothing is
     * written.
     *
     * @return void
     */
    public function testSetPreferenceRejectsUnusableKey(): void
    {
        $this->config->expects($this->never())->method('setUserValue');
        $this->config->expects($this->never())->method('deleteUserValue');

        $response = $this->controller->setPreference('!!!', 'dark');

        $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertSame(['message' => 'Invalid key'], $response->getData());

    }//end testSetPreferenceRejectsUnusableKey()


    /**
     * An anonymous caller cannot write another session's preferences.
     *
     * @return void
     */
    public function testSetPreferenceRejectsAnonymousCaller(): void
    {
        $this->config->expects($this->never())->method('setUserValue');

        $response = $this->anonymousController()->setPreference('theme', 'dark');

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
        $this->assertSame(['message' => 'Not logged in'], $response->getData());

    }//end testSetPreferenceRejectsAnonymousCaller()


}//end class
