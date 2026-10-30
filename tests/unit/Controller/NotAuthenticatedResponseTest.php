<?php

/**
 * Unit tests for the not-authenticated answer of the controllers.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * Every endpoint that checks for a session user answers 401 without one.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Controller\AnswersNotAuthenticated
 * @covers \OCA\Filinq\Controller\ConsentController
 * @covers \OCA\Filinq\Controller\CorrespondenceController
 * @covers \OCA\Filinq\Controller\DocumentController
 * @covers \OCA\Filinq\Controller\EmlPreviewController
 * @covers \OCA\Filinq\Controller\ExtractionController
 * @covers \OCA\Filinq\Controller\GlAccountSuggestionController
 * @covers \OCA\Filinq\Controller\PrintJobController
 * @covers \OCA\Filinq\Controller\SettingsController
 * @covers \OCA\Filinq\Controller\SigningController
 * @covers \OCA\Filinq\Controller\TemplatePreviewController
 * @covers \OCA\Filinq\Controller\TemplateVersionsController
 * @covers \OCA\Filinq\Controller\TemplatesController
 * @covers \OCA\Filinq\Controller\ValidationController
 */
class NotAuthenticatedResponseTest extends TestCase {

	/**
	 * Controller, method, and whether the message is translated.
	 *
	 * @return array<string, array{0: class-string, 1: string, 2: bool}>
	 */
	public static function endpoints(): array {
		$cases = [];
		foreach ([
			['ConsentController', 'index', true],
			['ConsentController', 'byDocument', true],
			['CorrespondenceController', 'generate', true],
			['CorrespondenceController', 'generateBatch', true],
			['CorrespondenceController', 'jobStatus', true],
			['DocumentController', 'generate', true],
			['DocumentController', 'preview', true],
			['DocumentController', 'mergePreview', true],
			['DocumentController', 'generateBulk', true],
			['DocumentController', 'jobStatus', true],
			['EmlPreviewController', 'verifyFileAccess', true],
			['ExtractionController', 'financial', true],
			['ExtractionController', 'corrections', true],
			['GlAccountSuggestionController', 'suggestAccount', true],
			['PrintJobController', 'create', false],
			['PrintJobController', 'index', false],
			['PrintJobController', 'show', false],
			['PrintJobController', 'download', false],
			['PrintJobController', 'updateStatus', false],
			['PrintJobController', 'batch', false],
			['SettingsController', 'create', false],
			['SettingsController', 'load', false],
			['SigningController', 'createRequest', true],
			['SigningController', 'listRequests', true],
			['SigningController', 'showRequest', true],
			['SigningController', 'cancelRequest', true],
			['SigningController', 'sign', true],
			['SigningController', 'decline', true],
			['SigningController', 'bulkSign', true],
			['SigningController', 'getAudit', true],
			['TemplatePreviewController', 'preview', false],
			['TemplatePreviewController', 'previewTemplate', false],
			['TemplateVersionsController', 'versions', false],
			['TemplateVersionsController', 'restoreVersion', false],
			['TemplateVersionsController', 'diffVersions', false],
			['TemplatesController', 'index', false],
			['TemplatesController', 'show', false],
			['TemplatesController', 'create', false],
			['TemplatesController', 'update', false],
			['TemplatesController', 'destroy', false],
			['TemplatesController', 'duplicate', false],
			['TemplatesController', 'lock', false],
			['TemplatesController', 'unlock', false],
			['ValidationController', 'validate', true],
		] as [$controller, $method, $translated]) {
			$cases[$controller . '::' . $method] = ['OCA\\Filinq\\Controller\\' . $controller, $method, $translated];
		}
		return $cases;
	}

	/**
	 * @dataProvider endpoints
	 *
	 * @param class-string $class The controller.
	 * @param string $method The endpoint.
	 * @param bool $translated Whether the message goes through l10n.
	 */
	public function testAnswersNotAuthenticatedWithoutASessionUser(string $class, string $method, bool $translated): void {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text): string => '[nl] ' . $text);

		$controller = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
		(function () use ($session, $l10n): void {
			$this->userSession = $session;
			if (property_exists($this, 'l10n') === true) {
				$this->l10n = $l10n;
			}
		})->call($controller);

		$reflection = new \ReflectionMethod($class, $method);
		$args = [];
		foreach ($reflection->getParameters() as $parameter) {
			$args[] = ($parameter->getType()?->getName() === 'int') ? 7 : 'id-7';
		}
		$response = $reflection->invokeArgs($controller, $args);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(401, $response->getStatus());
		$this->assertSame(['error' => ($translated ? '[nl] ' : '') . 'Not authenticated'], $response->getData());
	}
}
