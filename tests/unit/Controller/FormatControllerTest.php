<?php

/**
 * The matrix endpoints: authenticated, never cached, 404 for an unreadable template.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-3.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\FormatController;
use OCA\Filinq\Service\Conversion\ConversionBackendInterface;
use OCA\Filinq\Service\FormatMatrixService;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Service\TemplateService;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * FormatController over the real matrix service.
 */
class FormatControllerTest extends TestCase {

	/**
	 * The controller; LibreOffice is off.
	 *
	 * @param bool        $loggedIn Whether a user is logged in.
	 * @param string|null $flow     The flow query parameter.
	 *
	 * @return FormatController
	 */
	private function controller(bool $loggedIn = true, ?string $flow = null): FormatController {
		$libreOffice = $this->createMock(ConversionBackendInterface::class);
		$libreOffice->method('name')->willReturn('libreoffice_headless');
		$libreOffice->method('isAvailable')->willReturn(false);
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnMap([['flow', null, $flow]]);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($loggedIn === true ? $this->createMock(IUser::class) : null);
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturnCallback(
			static fn (string $id): array => $id === 'mine' ? ['id' => 'mine', 'content' => ''] : throw new Exception('Template not found', 404)
		);

		return new FormatController(
			'filinq',
			$request,
			new FormatMatrixService(new PdfConversionService([$libreOffice], new NullLogger())),
			$templates,
			$session
		);
	}

	public function testTheInstanceMatrixIsNeverCached(): void {
		$response = $this->controller()->instance();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('no-store', $response->getHeaders()['Cache-Control']);
		$this->assertFalse($response->getData()['formats']['docx']['available']);
		$this->assertSame(['pdf', 'docx', 'odf', 'html'], array_keys($response->getData()['formats']));
	}

	public function testTheCorrespondenceFlowIncludesEmail(): void {
		$this->assertArrayHasKey('email', $this->controller(flow: 'correspondence')->instance()->getData()['formats']);
	}

	public function testATemplateTheCallerCannotReadIsA404(): void {
		$this->assertSame(404, $this->controller()->template('theirs')->getStatus());
		$response = $this->controller()->template('mine');
		$this->assertSame(200, $response->getStatus());
		$this->assertSame('mine', $response->getData()['templateId']);
		$this->assertSame('no-store', $response->getHeaders()['Cache-Control']);
	}

	public function testNobodyLoggedInIsA401(): void {
		$this->assertSame(401, $this->controller(loggedIn: false)->instance()->getStatus());
		$this->assertSame(401, $this->controller(loggedIn: false)->template('mine')->getStatus());
	}
}//end class
