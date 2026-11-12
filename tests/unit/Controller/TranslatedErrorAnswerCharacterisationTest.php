<?php

/**
 * Unit tests for the error answer of the extraction and GL account suggestion controllers
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

use Exception;
use OCA\Filinq\Controller\ExtractionController;
use OCA\Filinq\Controller\GlAccountSuggestionController;
use OCP\IL10N;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Pins errorResponse(): the exception text is logged, never answered; the
 * answer is the translated message under the exception's HTTP error code,
 * else 500.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TranslatedErrorAnswerCharacterisationTest extends TestCase {

	/**
	 * Each controller with each exception code and the status it answers.
	 *
	 * @return array<string,array{0:class-string,1:array{0:int,1:int}}>
	 */
	public static function cases(): array {
		$cases = [];
		foreach ([ExtractionController::class, GlAccountSuggestionController::class] as $class) {
			foreach ([[404, 404], [400, 400], [599, 599], [0, 500], [399, 500], [600, 500]] as [$code, $status]) {
				$cases[substr(strrchr($class, '\\'), 1) . ' ' . $code] = [$class, [$code, $status]];
			}
		}

		return $cases;

	}//end cases()

	/**
	 * The answer for one exception code.
	 *
	 * @param class-string        $class The controller under test.
	 * @param array{0:int,1:int}  $codes The exception code and the expected HTTP status.
	 *
	 * @return void
	 */
	#[DataProvider('cases')]
	public function testErrorResponse(string $class, array $codes): void {
		[$code, $status] = $codes;
		$exception = new Exception('secret detail', $code);

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')
			->with('Failed to run: secret detail', ['exception' => $exception]);

		$l10n = $this->createMock(IL10N::class);
		$l10n->expects($this->once())->method('t')->with('Failed to run')->willReturn('Mislukt');

		$controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		(function (LoggerInterface $logger, IL10N $l10n): void {
			$this->logger = $logger;
			$this->l10n   = $l10n;
		})->call($controller, $logger, $l10n);

		$response = (function (Exception $exception): mixed {
			return $this->errorResponse(message: 'Failed to run', exception: $exception);
		})->call($controller, $exception);

		$this->assertSame($status, $response->getStatus());
		$this->assertSame(['error' => 'Mislukt'], $response->getData());

	}//end testErrorResponse()

}//end class
