<?php

/**
 * Without a session user, these controller actions answer 401 with an error message.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec exclude Characterization of the shared not-authenticated answer across controllers.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\BatchAnonymizationController;
use OCA\Filinq\Controller\ComparisonController;
use OCA\Filinq\Controller\ConsentController;
use OCA\Filinq\Controller\MetadataController;
use OCA\Filinq\Controller\PdfController;
use OCA\Filinq\Controller\StandingConsentController;
use OCA\Filinq\Controller\VersionController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Each action is called on a controller whose collaborators are all doubles and whose session has no user.
 */
class NotAuthenticatedVariantsTest extends TestCase {

	/**
	 * The actions: [controller, method, arguments, message].
	 *
	 * @return array<string, array{0: class-string, 1: string, 2: array<int, mixed>, 3: string}> The cases.
	 */
	public static function actions(): array {
		return [
			'batch folderBatch' => [BatchAnonymizationController::class, 'folderBatch', [], 'Not authenticated'],
			'batch batchEntities' => [BatchAnonymizationController::class, 'batchEntities', ['b-1'], 'Not authenticated'],
			'batch batchAnonymize' => [BatchAnonymizationController::class, 'batchAnonymize', ['b-1'], 'Not authenticated'],
			'batch updateProfiles' => [BatchAnonymizationController::class, 'updateProfiles', [], 'Not authenticated'],
			'comparison compare' => [ComparisonController::class, 'compare', [], 'Not authenticated'],
			'consent create' => [ConsentController::class, 'create', [], 'Not authenticated'],
			'consent show' => [ConsentController::class, 'show', ['c-1'], 'Not authenticated'],
			'consent update' => [ConsentController::class, 'update', ['c-1'], 'Not authenticated'],
			'metadata enrich' => [MetadataController::class, 'enrich', [], 'Not authenticated'],
			'pdf render' => [PdfController::class, 'render', [], 'Not authenticated'],
			'pdf renderPdfA' => [PdfController::class, 'renderPdfA', [], 'Not authenticated'],
			'standing index' => [StandingConsentController::class, 'index', [], 'Not authenticated'],
			'standing show' => [StandingConsentController::class, 'show', ['s-1'], 'Not authenticated'],
			'standing create' => [StandingConsentController::class, 'create', [], 'Not authenticated'],
			'standing update' => [StandingConsentController::class, 'update', ['s-1'], 'Not authenticated'],
			'standing destroy' => [StandingConsentController::class, 'destroy', ['s-1'], 'Not authenticated'],
			'version index' => [VersionController::class, 'index', [7], 'Not authenticated'],
			'version download' => [VersionController::class, 'download', [7, 1700000000], 'Not authenticated'],
		];

	}//end actions()

	/**
	 * The action answers 401 with the message under `error`.
	 *
	 * @param class-string     $controller The controller.
	 * @param string           $method     The action.
	 * @param array<int,mixed> $arguments  Its arguments.
	 * @param string           $message    The expected message.
	 *
	 * @return void
	 */
	#[DataProvider('actions')]
	public function testWithoutAUserTheActionAnswers401(string $controller, string $method, array $arguments, string $message): void {
		$response = $this->controller(class: $controller)->$method(...$arguments);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(401, $response->getStatus());
		$this->assertSame(['error' => $message], $response->getData());

	}//end testWithoutAUserTheActionAnswers401()

	/**
	 * A controller over doubles: no session user, a translator that returns its input.
	 *
	 * @param class-string $class The controller.
	 *
	 * @return object The controller.
	 */
	private function controller(string $class): object {
		$arguments = [];
		foreach ((new ReflectionClass($class))->getConstructor()->getParameters() as $parameter) {
			$type = $parameter->getType();
			$name = ($type instanceof ReflectionNamedType) ? $type->getName() : 'mixed';
			$arguments[] = match (true) {
				$name === IUserSession::class => $this->createMock(IUserSession::class),
				$name === IL10N::class => $this->translator(),
				$name === 'string' => 'filinq',
				$parameter->isDefaultValueAvailable() === true && ($type === null || $type->isBuiltin() === true) => $parameter->getDefaultValue(),
				$type !== null && $type->allowsNull() === true && $parameter->isDefaultValueAvailable() === true => null,
				default => $this->createMock($name),
			};
		}

		return new $class(...$arguments);

	}//end controller()

	/**
	 * A translator that returns the text it is given.
	 *
	 * @return IL10N The translator.
	 */
	private function translator(): IL10N {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(fn (string $text): string => $text);

		return $l10n;

	}//end translator()
}//end class
