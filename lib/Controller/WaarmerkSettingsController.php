<?php

/**
 * Waarmerk Settings Controller
 *
 * Admin configuration of the waarmerk organisation certificate.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Kernel\Contracts\PdfServiceInterface;
use OCA\Filinq\Kernel\Contracts\WaarmerkCmsInterface;
use OCA\Filinq\Kernel\Contracts\WaarmerkServiceInterface;
use OCA\Filinq\Kernel\Contracts\WaarmerkSettingsInterface;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * Certificate panel endpoints (REQ-DDWMK-001); admin only.
 *
 * Answers never contain key material: the summary carries the public
 * certificate facts, the credential reference and the custody label.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
 */
class WaarmerkSettingsController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string $appName The app id.
	 * @param IRequest $request The request.
	 * @param WaarmerkSettingsInterface $settings The certificate settings.
	 * @param WaarmerkServiceInterface $waarmerk Seals the test document.
	 * @param WaarmerkCmsInterface $cms Checks the test seal.
	 * @param PdfServiceInterface $pdfService Renders the test document.
	 * @param IUserSession $userSession The admin.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly WaarmerkSettingsInterface $settings,
		private readonly WaarmerkServiceInterface $waarmerk,
		private readonly WaarmerkCmsInterface $cms,
		private readonly PdfServiceInterface $pdfService,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The certificate summary.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function show(): JSONResponse {
		return new JSONResponse($this->settings->summary());

	}//end show()

	/**
	 * Save the certificate, credential reference and custody mode.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function update(): JSONResponse {
		$input = [];
		foreach (['certificate', 'credentialRef', 'custody', 'localKey'] as $field) {
			$value = $this->request->getParam($field);
			if (is_string($value) === true) {
				$input[$field] = $value;
			}
		}

		try {
			return new JSONResponse($this->settings->save(input: $input));
		} catch (WaarmerkRefusedException $e) {
			return new JSONResponse(['error' => $e->getMessage()], $e->getStatus());
		}

	}//end update()

	/**
	 * Seal a throwaway test document in memory and verify it; nothing is stored.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-organisation-certificate-is-managed-in-admin-settings-with-credentialref-key-custody-req-ddwmk-001
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function testSeal(): JSONResponse {
		try {
			$pdf = $this->pdfService->generatePdfFromHtml(html: '<p>Testwaarmerk</p>', options: ['title' => 'Testwaarmerk']);
			$artifact = $this->waarmerk->prepare(
				pdf: $pdf,
				documentName: 'testwaarmerk.pdf',
				userId: (string) $this->userSession->getUser()?->getUID()
			);
		} catch (WaarmerkRefusedException $e) {
			return new JSONResponse(['ok' => false, 'error' => $e->getMessage()], $e->getStatus());
		} catch (Throwable) {
			return new JSONResponse(['ok' => false, 'error' => 'The test seal failed'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$record = $artifact->record;
		$ok = hash_equals(hash('sha256', $artifact->pdf), (string) $record['documentHash'])
			&& $this->cms->verify(
				hash: (string) $record['documentHash'],
				cmsBase64: (string) $record['cmsSignature'],
				fingerprint: (string) $record['certificateFingerprint']
			) !== null;

		return new JSONResponse(['ok' => $ok, 'fingerprint' => $record['certificateFingerprint']]);

	}//end testSeal()
}//end class
