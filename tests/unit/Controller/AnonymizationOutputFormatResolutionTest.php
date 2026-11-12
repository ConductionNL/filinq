<?php

/**
 * Unit tests for how both anonymisation controllers resolve outputFormat
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
 */

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\AnonymizationController;
use OCA\Filinq\Controller\BatchAnonymizationController;
use OCP\IAppConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Pins the per-call value, the tenant default and the malformed-tenant
 * fallback of resolveOutputFormat() on the single and the batch endpoint.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationOutputFormatResolutionTest extends TestCase {

	/**
	 * Both controllers that resolve an outputFormat.
	 *
	 * @return array<string,array{0:class-string}>
	 */
	public static function controllers(): array {
		return [
			'single' => [AnonymizationController::class],
			'batch'  => [BatchAnonymizationController::class],
		];

	}//end controllers()

	/**
	 * A valid per-call value wins and the tenant setting is never read.
	 *
	 * @param class-string $class The controller under test.
	 *
	 * @return void
	 */
	#[DataProvider('controllers')]
	public function testValidPerCallValueWins(string $class): void {
		$config = $this->createMock(IAppConfig::class);
		$config->expects($this->never())->method('getValueString');

		foreach (['pdf-only', 'pdf', 'preserve'] as $format) {
			$this->assertSame($format, $this->resolve($this->controllerWith($class, $config), ['outputFormat' => $format]));
		}

	}//end testValidPerCallValueWins()

	/**
	 * An unknown or non-string per-call value resolves to null (HTTP 400),
	 * even when the tenant default is valid.
	 *
	 * @param class-string $class The controller under test.
	 *
	 * @return void
	 */
	#[DataProvider('controllers')]
	public function testInvalidPerCallValueIsNull(string $class): void {
		$config = $this->createMock(IAppConfig::class);
		$config->expects($this->never())->method('getValueString');

		foreach (['docx', '', null, 1, ['pdf']] as $value) {
			$this->assertNull($this->resolve($this->controllerWith($class, $config), ['outputFormat' => $value]));
		}

	}//end testInvalidPerCallValueIsNull()

	/**
	 * Without a per-call value the tenant default is read with the documented
	 * key and a pdf-only fallback, and returned when valid.
	 *
	 * @param class-string $class The controller under test.
	 *
	 * @return void
	 */
	#[DataProvider('controllers')]
	public function testTenantDefaultIsUsed(string $class): void {
		$config = $this->createMock(IAppConfig::class);
		$config->expects($this->once())->method('getValueString')
			->with('filinq', 'filinq.anonymisation.default_output_format', 'pdf-only')
			->willReturn('preserve');

		$this->assertSame('preserve', $this->resolve($this->controllerWith($class, $config), ['other' => 'x']));

	}//end testTenantDefaultIsUsed()

	/**
	 * A malformed tenant default falls back to pdf-only instead of refusing.
	 *
	 * @param class-string $class The controller under test.
	 *
	 * @return void
	 */
	#[DataProvider('controllers')]
	public function testMalformedTenantDefaultFallsBackToPdfOnly(string $class): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn('docx');

		$this->assertSame('pdf-only', $this->resolve($this->controllerWith($class, $config), []));

	}//end testMalformedTenantDefaultFallsBackToPdfOnly()

	/**
	 * Build the controller with only the tenant configuration wired in.
	 *
	 * @param class-string $class  The controller under test.
	 * @param IAppConfig   $config The tenant configuration.
	 *
	 * @return object The controller.
	 */
	private function controllerWith(string $class, IAppConfig $config): object {
		$controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		(function (IAppConfig $config): void {
			$this->appConfig = $config;
		})->call($controller, $config);

		return $controller;

	}//end controllerWith()

	/**
	 * Call the controller's private resolveOutputFormat().
	 *
	 * @param object              $controller The controller under test.
	 * @param array<string,mixed> $params     The request params.
	 *
	 * @return string|null The resolved format.
	 */
	private function resolve(object $controller, array $params): ?string {
		return (function (array $params): ?string {
			return $this->resolveOutputFormat($params);
		})->call($controller, $params);

	}//end resolve()

}//end class
