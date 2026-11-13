<?php

/**
 * Filinq Office Template Service
 *
 * Turns an uploaded DOCX or ODT with `${field}` merge tags into a template:
 * checks the file, normalises an ODT to DOCX, reads and checks its tags
 * against the bound schema, stores the source, and creates the template.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use Exception;
use OCA\Filinq\Exception\OfficeSourceRefusedException;
use OCA\Filinq\Kernel\Contracts\OpenRegisterResolverInterface;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\OpenRegister\Db\SchemaMapper;
use OCP\IAppConfig;
use Throwable;

/**
 * Creates office templates from uploaded files.
 *
 * Nothing is stored before every check has passed: a refused file leaves no
 * stored source and no template object behind.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */
class OfficeTemplateService {

	/**
	 * The app whose settings hold the template limits.
	 */
	private const APP_ID = 'filinq';

	/**
	 * The setting that makes unknown tags block an upload.
	 */
	public const SEVERITY_KEY = 'filinq.templates.unknown_tag_severity';

	/**
	 * The setting that caps the upload size, in bytes.
	 */
	public const MAX_BYTES_KEY = 'filinq.templates.max_upload_bytes';

	/**
	 * The template fields an upload may set besides the file.
	 */
	private const FIELDS = ['name', 'namespace', 'description', 'category', 'tags', 'slug', 'tenantId', 'boundRegister', 'boundSchema'];

	/**
	 * Constructor.
	 *
	 * @param OfficeSourceGuard             $guard      Checks the uploaded file.
	 * @param MergeTagReader                $reader     Reads the tags and the text.
	 * @param MergeTagClassifier            $classifier Checks the tags against the schema.
	 * @param OfficeSourceStore             $store      Stores the source file.
	 * @param LibreOfficeHeadlessBackend    $libreOffice Converts an ODT to DOCX.
	 * @param TemplateServiceInterface      $templates  Creates the template object.
	 * @param OpenRegisterResolverInterface $resolver   Checks the namespace.
	 * @param SchemaMapper                  $schemas    Reads the bound schema's properties.
	 * @param IAppConfig                    $config     The template limits.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function __construct(
		private readonly OfficeSourceGuard $guard,
		private readonly MergeTagReader $reader,
		private readonly MergeTagClassifier $classifier,
		private readonly OfficeSourceStore $store,
		private readonly LibreOfficeHeadlessBackend $libreOffice,
		private readonly TemplateServiceInterface $templates,
		private readonly OpenRegisterResolverInterface $resolver,
		private readonly SchemaMapper $schemas,
		private readonly IAppConfig $config,
	) {
	}//end __construct()

	/**
	 * Create an office template from an uploaded file.
	 *
	 * @param array<string, mixed> $fields   The template fields (name and namespace required).
	 * @param string               $fileName The uploaded file's name.
	 * @param string               $bytes    The uploaded file's content.
	 *
	 * @return array{template: array<string, mixed>, converted: bool, tagReport: array<string, mixed>}
	 *
	 * @throws OfficeSourceRefusedException When the file or its tags are refused (422).
	 * @throws Exception                    When name or namespace is missing or invalid (400).
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function create(array $fields, string $fileName, string $bytes): array {
		$data = array_intersect_key($fields, array_flip(self::FIELDS));
		if (trim((string) ($data['name'] ?? '')) === '') {
			throw new Exception(message: 'Name is required', code: 400);
		}

		if (trim((string) ($data['namespace'] ?? '')) === '') {
			throw new Exception(message: 'Namespace is required', code: 400);
		}

		$this->resolver->validateNamespace(namespace: (string) $data['namespace']);

		$kind = $this->guard->inspect(fileName: $fileName, bytes: $bytes, maxBytes: $this->maxBytes());
		$docx = $bytes;
		if ($kind === 'odt') {
			$docx = $this->libreOffice->convertDocument(bytes: $bytes, fromExtension: 'odt', toExtension: 'docx');
		}

		$tags = $this->reader->tags(docx: $docx);
		$report = $this->tagReport(tags: $tags, boundSchema: trim((string) ($data['boundSchema'] ?? '')));
		if ($this->classifier->blocks(report: $report) === true) {
			throw new OfficeSourceRefusedException(
				message: 'The file has merge tags the schema does not know: '.implode(', ', $report['unknown']).'.',
				reason: OfficeSourceRefusedException::REASON_UNKNOWN_TAGS,
				unknownTags: $report['unknown']
			);
		}

		$source = $this->store->store(bytes: $docx, extension: 'docx');
		if ($kind === 'odt') {
			// The original is kept beside the DOCX it became, under the same name.
			$this->store->store(bytes: $bytes, extension: 'odt', name: $source['contentHash']);
		}

		$text = $this->reader->text(docx: $docx);
		$data['templateType'] = 'office';
		$data['sourceFileId'] = $source['fileId'];
		$data['contentHash'] = $source['contentHash'];
		$data['mergeFields'] = array_map(static fn (string $tag): array => ['path' => $tag], $tags);
		$data['tagReport'] = $report;
		$data['content'] = $text;
		if ($text === '') {
			$data['content'] = (string) $data['name'];
		}

		return [
			'template' => $this->templates->createTemplate(data: $data),
			'converted' => $kind === 'odt',
			'tagReport' => $report,
		];
	}//end create()

	/**
	 * The tag report against the bound schema, if there is one.
	 *
	 * @param array<int, string> $tags        The tags.
	 * @param string             $boundSchema The bound schema's slug, or ''.
	 *
	 * @return array<string, mixed>
	 */
	private function tagReport(array $tags, string $boundSchema): array {
		$severity = $this->config->getValueString(self::APP_ID, self::SEVERITY_KEY, MergeTagClassifier::SEVERITY_WARNING);
		if ($boundSchema === '') {
			return $this->classifier->report(tags: $tags, properties: null, severity: $severity);
		}

		$properties = $this->schemaProperties(slug: $boundSchema);
		$report = $this->classifier->report(tags: $tags, properties: $properties, severity: $severity);
		if ($properties === null) {
			$report['notice'] = 'Not validated: the schema "'.$boundSchema.'" was not found.';
		}

		return $report;
	}//end tagReport()

	/**
	 * The properties of a schema in OpenRegister.
	 *
	 * @param string $slug The schema's slug or id.
	 *
	 * @return array<string, mixed>|null The properties, or null when the schema cannot be read.
	 */
	private function schemaProperties(string $slug): ?array {
		try {
			$schema = $this->schemas->find(id: $slug);
		} catch (Throwable) {
			return null;
		}

		if (is_object($schema) === false || method_exists($schema, 'getProperties') === false) {
			return null;
		}

		$properties = $schema->getProperties();
		if (is_array($properties) === false) {
			return null;
		}

		return $properties;
	}//end schemaProperties()

	/**
	 * The upload size cap.
	 *
	 * @return int Bytes.
	 */
	private function maxBytes(): int {
		$value = $this->config->getValueString(self::APP_ID, self::MAX_BYTES_KEY, '');
		if (is_numeric($value) === false || (int) $value <= 0) {
			return OfficeSourceGuard::DEFAULT_MAX_BYTES;
		}

		return (int) $value;
	}//end maxBytes()
}//end class
