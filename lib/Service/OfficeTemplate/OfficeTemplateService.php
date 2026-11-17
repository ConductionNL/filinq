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
	 * @param OfficeSourceTools             $tools     Checks, reads, stores and converts the uploaded file.
	 * @param TemplateServiceInterface      $templates Creates the template object.
	 * @param OpenRegisterResolverInterface $resolver  Checks the namespace.
	 * @param SchemaMapper                  $schemas   Reads the bound schema's properties.
	 * @param IAppConfig                    $config    The template limits.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function __construct(
		private readonly OfficeSourceTools $tools,
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

		$source = $this->checkedSource(fileName: $fileName, bytes: $bytes, boundSchema: trim((string) ($data['boundSchema'] ?? '')));
		$data = array_merge($data, $this->storedSource(source: $source, bytes: $bytes));
		if ($data['content'] === '') {
			$data['content'] = (string) $data['name'];
		}

		return [
			'template' => $this->templates->createTemplate(data: $data),
			'converted' => $source['kind'] === 'odt',
			'tagReport' => $source['report'],
		];
	}//end create()

	/**
	 * Give an office template a new source file, as a new version.
	 *
	 * The previous source stays stored and its version keeps pointing at it.
	 * The template's edit lock applies: a template locked by someone else is
	 * refused (409) by the template service, which also snapshots the version.
	 *
	 * @param string      $id        The template's id.
	 * @param string      $fileName  The uploaded file's name.
	 * @param string      $bytes     The uploaded file's content.
	 * @param string|null $changelog What changed, for the version history.
	 *
	 * @return array{template: array<string, mixed>, converted: bool, tagReport: array<string, mixed>}
	 *
	 * @throws OfficeSourceRefusedException When the file or its tags are refused (422).
	 * @throws Exception                    When the template is unknown (404), not an office template (409) or locked (409).
	 *
	 * @spec openspec/changes/office-template-authoring/specs/template-management/spec.md
	 */
	public function replaceSource(string $id, string $fileName, string $bytes, ?string $changelog=null): array {
		$template = $this->templates->getTemplate(id: $id);
		if (($template['templateType'] ?? 'twig') !== 'office') {
			throw new Exception(message: 'Only an office template takes a new source file', code: 409);
		}

		$source = $this->checkedSource(fileName: $fileName, bytes: $bytes, boundSchema: trim((string) ($template['boundSchema'] ?? '')));
		$data = $this->storedSource(source: $source, bytes: $bytes);
		if ($data['content'] === '') {
			$data['content'] = (string) ($template['name'] ?? $fileName);
		}

		if ($changelog !== null && trim($changelog) !== '') {
			$data['_changelog'] = trim($changelog);
		}

		return [
			'template' => $this->templates->updateTemplate(id: $id, data: $data),
			'converted' => $source['kind'] === 'odt',
			'tagReport' => $source['report'],
		];
	}//end replaceSource()

	/**
	 * Set which data path each tag of an office template reads, such as an
	 * imported template's `naam_aanvrager` reading `aanvrager.naam`.
	 *
	 * An empty path removes that tag's mapping. Saved as a template change,
	 * so the edit lock and the version history apply.
	 *
	 * @param string               $id       The template's id.
	 * @param array<string, mixed> $fieldMap Tag => data path.
	 *
	 * @return array<string, mixed> The saved template.
	 *
	 * @throws Exception With code 400 for a tag the template does not have or a
	 *                   path that is no field path, 404 for an unknown template,
	 *                   409 for a Twig or locked template.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	public function setFieldMap(string $id, array $fieldMap): array {
		$template = $this->templates->getTemplate(id: $id);
		if (($template['templateType'] ?? 'twig') !== 'office') {
			throw new Exception(message: 'Only an office template has a field mapping', code: 409);
		}

		$tags = [];
		foreach ((array) ($template['mergeFields'] ?? []) as $field) {
			$tags[] = (string) ($field['path'] ?? '');
		}

		$map = [];
		foreach ($fieldMap as $tag => $path) {
			if (is_string($tag) === false || in_array($tag, $tags, true) === false) {
				throw new Exception(message: 'The template has no merge tag "'.$tag.'"', code: 400);
			}

			$path = trim((string) $path);
			if ($path === '') {
				continue;
			}

			if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z0-9_]+)*$/', $path) !== 1) {
				throw new Exception(message: '"'.$path.'" is not a field path', code: 400);
			}

			$map[$tag] = $path;
		}

		return $this->templates->updateTemplate(id: $id, data: ['fieldMap' => (object) $map, '_changelog' => 'Field mapping changed']);
	}//end setFieldMap()

	/**
	 * Check an uploaded file and its tags, storing nothing.
	 *
	 * @param string $fileName    The uploaded file's name.
	 * @param string $bytes       The uploaded file's content.
	 * @param string $boundSchema The bound schema's slug, or ''.
	 *
	 * @return array{kind: string, docx: string, tags: array<int, string>, report: array<string, mixed>}
	 *
	 * @throws OfficeSourceRefusedException When the file or its tags are refused (422).
	 */
	private function checkedSource(string $fileName, string $bytes, string $boundSchema): array {
		$kind = $this->tools->guard->inspect(fileName: $fileName, bytes: $bytes, maxBytes: $this->maxBytes());
		$docx = $bytes;
		if ($kind === 'odt') {
			$docx = $this->tools->libreOffice->convertDocument(bytes: $bytes, fromExtension: 'odt', toExtension: 'docx');
		}

		$tags = $this->tools->reader->tags(docx: $docx);
		$report = $this->tagReport(tags: $tags, boundSchema: $boundSchema);
		if ($this->tools->classifier->blocks(report: $report) === true) {
			throw new OfficeSourceRefusedException(
				message: 'The file has merge tags the schema does not know: '.implode(', ', $report['unknown']).'.',
				reason: OfficeSourceRefusedException::REASON_UNKNOWN_TAGS,
				unknownTags: $report['unknown']
			);
		}

		return ['kind' => $kind, 'docx' => $docx, 'tags' => $tags, 'report' => $report];
	}//end checkedSource()

	/**
	 * Store a checked source and describe it as template fields.
	 *
	 * @param array<string, mixed> $source The checked source.
	 * @param string               $bytes  The uploaded bytes (the ODT original, when converted).
	 *
	 * @return array<string, mixed> templateType, sourceFileId, contentHash, mergeFields, tagReport and content.
	 */
	private function storedSource(array $source, string $bytes): array {
		$stored = $this->tools->store->store(bytes: $source['docx'], extension: 'docx');
		if ($source['kind'] === 'odt') {
			// The original is kept beside the DOCX it became, under the same name.
			$this->tools->store->store(bytes: $bytes, extension: 'odt', name: $stored['contentHash']);
		}

		return [
			'templateType' => 'office',
			'sourceFileId' => $stored['fileId'],
			'contentHash' => $stored['contentHash'],
			'mergeFields' => array_map(static fn (string $tag): array => ['path' => $tag], $source['tags']),
			'tagReport' => $source['report'],
			'content' => $this->tools->reader->text(docx: $source['docx']),
		];
	}//end storedSource()

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
			return $this->tools->classifier->report(tags: $tags, properties: null, severity: $severity);
		}

		$properties = $this->schemaProperties(slug: $boundSchema);
		$report = $this->tools->classifier->report(tags: $tags, properties: $properties, severity: $severity);
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
