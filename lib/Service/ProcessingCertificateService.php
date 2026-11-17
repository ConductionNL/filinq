<?php

/**
 * Processing Certificate Service
 *
 * Builds a sealed anonymisation certificate from the recorded run.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\Waarmerk\SealedFileWriter;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCP\Files\Folder;
use Throwable;

/**
 * The anonymisation processing certificate (design.md D6).
 *
 * It attests the RECORD of a run: the `anonymizationLink` fields, the
 * per-entity-type counts the run recorded, the detection backend and the
 * acting user. It never reads the document and never carries an entity value
 * (AVG Art. 5(1)(c)). The certificate PDF is sealed with
 * `sealType: processing-certificate` and the link's id.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-anonymization-runs-can-be-certified-with-a-sealed-processing-certificate-req-ddwmk-003
 */
class ProcessingCertificateService {
	use ReadsStoredObjectRows;

	private const TEMPLATE = __DIR__ . '/../Resources/templates/waarmerk/processing_certificate.twig';

	/**
	 * The link fields the certificate states; nothing else of the link is copied.
	 *
	 * @var array<int, string>
	 */
	public const LINK_FIELDS = [
		'sourceFileName', 'sourceFilePath', 'anonymizedFileName', 'anonymizedFilePath', 'outputFormat',
		'replacementCount', 'runCount', 'anonymizedAt', 'anonymizedBy', 'verificationVerdict',
	];

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Reads the anonymizationLink.
	 * @param ReadableFileResolver $files The caller's access to the anonymised file.
	 * @param AnonymiserBackendStateClient $backends The configured detection backend.
	 * @param PdfService $pdfService Renders the certificate.
	 * @param WaarmerkService $waarmerk Seals it.
	 * @param SealedFileWriter $writer Writes and records it.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly ReadableFileResolver $files,
		private readonly AnonymiserBackendStateClient $backends,
		private readonly PdfService $pdfService,
		private readonly WaarmerkService $waarmerk,
		private readonly SealedFileWriter $writer,
	) {

	}//end __construct()

	/**
	 * Certify one anonymisation run.
	 *
	 * @param string $linkId The anonymizationLink uuid.
	 * @param string $userId The requesting user.
	 *
	 * @return array{waarmerk: array<string, mixed>, fileId: int, fileName: string, certificate: array<string, mixed>}
	 *
	 * @throws WaarmerkRefusedException 404 for an unknown link or an unreadable anonymised file.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-anonymization-runs-can-be-certified-with-a-sealed-processing-certificate-req-ddwmk-003
	 */
	public function certify(string $linkId, string $userId): array {
		$link = $this->link(linkId: $linkId);
		$node = $this->files->node(fileId: (int) ($link['anonymizedFileId'] ?? 0));
		if ($link === null || $node === null) {
			// One answer for "no such run" and "not your file".
			throw new WaarmerkRefusedException(message: 'Anonymisation run not found', status: 404);
		}

		$certificate = $this->certificateData(link: $link);
		$name = SealedFileWriter::siblingName(sourceName: (string) ($link['anonymizedFileName'] ?? $node->getName()), suffix: 'anonimisatiecertificaat');
		$pdf = $this->pdfService->renderPdf(
			templateContent: (string) file_get_contents(self::TEMPLATE),
			data: ['certificate' => $certificate],
			options: ['pdfa' => true, 'title' => 'Anonimisatiecertificaat']
		);

		$artifact = $this->waarmerk->prepare(
			pdf: $pdf,
			documentName: $name,
			userId: $userId,
			sealType: 'processing-certificate',
			anonymizationLinkId: $linkId
		);

		$folder = $node->getParent();
		if (($folder instanceof Folder) === false) {
			throw new WaarmerkRefusedException(message: 'The anonymised file has no folder to write the certificate into', status: 422);
		}

		return array_merge($this->writer->write(folder: $folder, name: $name, artifact: $artifact), ['certificate' => $certificate]);

	}//end certify()

	/**
	 * What the certificate states: recorded fields, type counts, backend. No values.
	 *
	 * @param array<string, mixed> $link The stored anonymizationLink.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-anonymization-runs-can-be-certified-with-a-sealed-processing-certificate-req-ddwmk-003
	 */
	public function certificateData(array $link): array {
		$data = array_intersect_key($link, array_flip(self::LINK_FIELDS));

		$counts = [];
		foreach ((array) ($link['entityTypeCounts'] ?? []) as $type => $count) {
			// Keys are entity TYPES (BSN, PERSON, ...); anything else is dropped.
			if (is_string($type) === true && preg_match('/^[A-Z][A-Z0-9_]{0,63}$/', $type) === 1 && is_numeric($count) === true) {
				$counts[$type] = (int) $count;
			}
		}

		ksort($counts);
		$data['entityTypeCounts'] = $counts;
		$data['entityTypeCountsRecorded'] = isset($link['entityTypeCounts']) === true;
		$data['detectionBackend'] = $this->backend();
		$data['anonymizationLinkId'] = (string) ($link['uuid'] ?? '');

		return $data;

	}//end certificateData()

	/**
	 * The configured detection method, or "unknown".
	 *
	 * @return string
	 */
	private function backend(): string {
		try {
			$state = $this->backends->getState();
		} catch (Throwable) {
			return 'unknown';
		}

		if (($state['known'] ?? false) !== true) {
			return 'unknown';
		}

		return (string) ($state['effectiveMethod'] ?? 'unknown');

	}//end backend()

	/**
	 * The stored link, or null.
	 *
	 * @param string $linkId The uuid.
	 *
	 * @return array<string, mixed>|null
	 */
	private function link(string $linkId): ?array {
		if ($linkId === '') {
			return null;
		}

		try {
			$object = $this->objectResolver->resolve()->find(
				id: $linkId,
				options: new ObjectFindOptions(register: 'filinq', schema: 'anonymizationLink'),
				_rbac: false,
				_multitenancy: false
			);
		} catch (Throwable) {
			return null;
		}

		if ($object === null) {
			return null;
		}

		$row = $this->storedEntityFields(row: $object);
		if (($row['uuid'] ?? '') === '') {
			$row['uuid'] = $linkId;
		}

		return $row;

	}//end link()
}//end class
