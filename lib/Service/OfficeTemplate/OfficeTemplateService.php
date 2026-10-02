<?php

/**
 * Office template service
 *
 * Makes an uploaded DOCX or ODT into a template object and takes a new
 * source revision for an existing one: refuses what may not be a template,
 * converts an ODT to DOCX (keeping the ODT), reads the merge tags, checks
 * them against the bound schema, stores the source and writes the template
 * through TemplateService, so versions, locks and namespaces work exactly as
 * they do for Twig templates.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use DateTimeImmutable;
use OCA\Filinq\Service\TemplateService;
use Throwable;

/**
 * Creates office templates from uploads and takes new source revisions.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
 */
class OfficeTemplateService {

	/**
	 * Fields a caller may set on an office template besides the file.
	 *
	 * @var string[]
	 */
	private const META_FIELDS = ['name', 'namespace', 'description', 'category', 'tags', 'boundRegister', 'boundSchema', 'fieldMap', 'slug', 'tenantId'];

	/**
	 * How long an edit lock holds, as TemplateService counts it.
	 *
	 * @var int
	 */
	private const LOCK_MINUTES = 15;

	/**
	 * Constructor.
	 *
	 * @param OfficeSourceInspector $inspector  Accepts the file and reads its tags.
	 * @param OfficeConverter       $converter  Converts an ODT.
	 * @param TagClassifier         $classifier Checks the tags against the bound schema.
	 * @param OfficeSourceStore     $store      Stores the sources.
	 * @param TemplateService       $templates  Writes the template object.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly OfficeSourceInspector $inspector,
		private readonly OfficeConverter $converter,
		private readonly TagClassifier $classifier,
		private readonly OfficeSourceStore $store,
		private readonly TemplateService $templates,
	) {

	}//end __construct()

	/**
	 * Create an office template from an upload.
	 *
	 * @param string               $fileName The uploaded name.
	 * @param string               $bytes    The content.
	 * @param array<string, mixed> $meta     name and namespace (required), description, category,
	 *                                       tags, boundRegister, boundSchema, fieldMap.
	 *
	 * @return array{template: array, converted: bool, tagReport: array}
	 *
	 * @throws OfficeTemplateRefused 422 (or 503 for a failed ODT conversion); nothing is stored then.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-2
	 */
	public function createFromUpload(string $fileName, string $bytes, array $meta): array {
		$fields = array_intersect_key($meta, array_flip(self::META_FIELDS));
		$source = $this->accept(
			fileName: $fileName,
			bytes: $bytes,
			boundSchema: ($fields['boundSchema'] ?? null),
			fieldMap: (array) ($fields['fieldMap'] ?? [])
		);

		$template = $this->templates->createTemplate(data: $fields + ['templateType' => 'office'] + $source['fields']);

		return ['template' => $template, 'converted' => $source['converted'], 'tagReport' => $source['fields']['tagReport']];

	}//end createFromUpload()

	/**
	 * Take a new source revision for an office template. The current state
	 * is kept as a version first (TemplateService::updateTemplate).
	 *
	 * @param string $templateId The template.
	 * @param string $fileName   The uploaded name.
	 * @param string $bytes      The content.
	 * @param string $userId     Who uploads it.
	 *
	 * @return array{template: array, converted: bool, tagReport: array}
	 *
	 * @throws OfficeTemplateRefused 400 for a Twig template, 409 while another user holds the lock, 422 as on create.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-6
	 */
	public function replaceSource(string $templateId, string $fileName, string $bytes, string $userId): array {
		$current = $this->officeTemplate(templateId: $templateId);
		$this->assertNotLockedByOther(template: $current, userId: $userId);
		$source = $this->accept(
			fileName: $fileName,
			bytes: $bytes,
			boundSchema: ($current['boundSchema'] ?? null),
			fieldMap: (array) ($current['fieldMap'] ?? [])
		);

		$template = $this->templates->updateTemplate(
			id: $templateId,
			data: $source['fields'] + ['_changelog' => 'New office source: ' . basename($fileName)]
		);

		return ['template' => $template, 'converted' => $source['converted'], 'tagReport' => $source['fields']['tagReport']];

	}//end replaceSource()

	/**
	 * Store a field mapping (tag to schema property) and recheck the tags.
	 *
	 * @param string                $templateId The template.
	 * @param array<string, string> $fieldMap   The mapping.
	 * @param string                $userId     Who changes it.
	 *
	 * @return array The updated template.
	 *
	 * @throws OfficeTemplateRefused 400 for a Twig template or a malformed mapping, 409 while locked by another user.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
	 */
	public function updateFieldMap(string $templateId, array $fieldMap, string $userId): array {
		$current = $this->officeTemplate(templateId: $templateId);
		$this->assertNotLockedByOther(template: $current, userId: $userId);
		foreach ($fieldMap as $tag => $path) {
			if (is_string($tag) === false || is_string($path) === false || trim($path) === '') {
				throw new OfficeTemplateRefused(message: 'fieldMap maps each tag to a property path (text).', reason: 'field-map', code: 400);
			}
		}

		$report = $this->classifier->classify(
			tags: (array) ($current['mergeFields'] ?? []),
			boundSchema: ($current['boundSchema'] ?? null),
			fieldMap: $fieldMap
		);

		return $this->templates->updateTemplate(
			id: $templateId,
			data: ['fieldMap' => $fieldMap, 'tagReport' => $report, '_changelog' => 'Field mapping changed']
		);

	}//end updateFieldMap()

	/**
	 * The stored DOCX of an office template, for download.
	 *
	 * @param string $templateId The template.
	 *
	 * @return array{name: string, bytes: string}
	 *
	 * @throws OfficeTemplateRefused 400 for a Twig template, 404 when the file is gone.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
	 */
	public function source(string $templateId): array {
		$template = $this->officeTemplate(templateId: $templateId);
		$name = preg_replace('/[^A-Za-z0-9 ._-]/', '', (string) ($template['name'] ?? 'template'));

		return ['name' => trim((string) $name) . '.docx', 'bytes' => $this->store->read(fileId: (int) ($template['sourceFileId'] ?? 0))];

	}//end source()

	/**
	 * Accept, convert, inspect, check and store a source.
	 *
	 * @param string                $fileName    The uploaded name.
	 * @param string                $bytes       The content.
	 * @param string|null           $boundSchema The bound schema slug.
	 * @param array<string, string> $fieldMap    Aliases.
	 *
	 * @return array{converted: bool, fields: array<string, mixed>} The template fields of the source.
	 *
	 * @throws OfficeTemplateRefused When the upload is refused; nothing is stored then.
	 */
	private function accept(string $fileName, string $bytes, ?string $boundSchema, array $fieldMap): array {
		$extension = $this->inspector->assertAcceptable(fileName: $fileName, bytes: $bytes);
		$docx = $bytes;
		if ($extension === 'odt') {
			$docx = $this->converter->odtToDocx(odtBytes: $bytes);
		}

		$tags = $this->inspector->extractTags(docxBytes: $docx);
		$report = $this->classifier->classify(tags: $tags, boundSchema: $boundSchema, fieldMap: $fieldMap);
		if ($this->classifier->blocks(report: $report) === true) {
			throw new OfficeTemplateRefused(
				message: 'The document has tags that match nothing in schema ' . $boundSchema . ': ' . implode(', ', $report['unknown']),
				reason: 'unknown-tags',
				details: ['unknownTags' => $report['unknown']]
			);
		}

		$originalId = null;
		if ($extension === 'odt') {
			$originalId = $this->store->put(bytes: $bytes, extension: 'odt');
		}

		$projection = $this->inspector->textProjection(docxBytes: $docx);
		if ($projection === '') {
			$projection = basename($fileName);
		}

		return [
			'converted' => $extension === 'odt',
			'fields' => [
				'sourceFileId' => $this->store->put(bytes: $docx, extension: 'docx'),
				'originalFileId' => $originalId,
				'contentHash' => hash('sha256', $docx),
				'mergeFields' => $tags,
				'tagReport' => $report,
				'content' => $projection,
			],
		];

	}//end accept()

	/**
	 * An office template by id.
	 *
	 * @param string $templateId The template.
	 *
	 * @return array The template.
	 *
	 * @throws OfficeTemplateRefused 400 for a Twig template.
	 */
	private function officeTemplate(string $templateId): array {
		$template = $this->templates->getTemplate(id: $templateId);
		if (($template['templateType'] ?? 'twig') !== 'office') {
			throw new OfficeTemplateRefused(message: 'Template ' . $templateId . ' is not an office template.', reason: 'not-office', code: 400);
		}

		return $template;

	}//end officeTemplate()

	/**
	 * Refuse while another user holds a live edit lock.
	 *
	 * @param array  $template The template.
	 * @param string $userId   The caller.
	 *
	 * @return void
	 *
	 * @throws OfficeTemplateRefused 409 when locked by someone else.
	 */
	private function assertNotLockedByOther(array $template, string $userId): void {
		$holder = (string) ($template['lockedBy'] ?? '');
		if ($holder === '' || $holder === $userId || (string) ($template['lockedAt'] ?? '') === '') {
			return;
		}

		try {
			$age = (new DateTimeImmutable())->getTimestamp() - (new DateTimeImmutable((string) ($template['lockedAt'] ?? '')))->getTimestamp();
		} catch (Throwable) {
			return;
		}

		if ($age < self::LOCK_MINUTES * 60) {
			throw new OfficeTemplateRefused(message: 'Template is locked by ' . $holder . '.', reason: 'locked', details: ['lockedBy' => $holder], code: 409);
		}

	}//end assertNotLockedByOther()
}//end class
