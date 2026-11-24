<?php

/**
 * Vendor Render Service
 *
 * Hands a render to a document generation source in integriq: dispatches
 * integriq's `DocumentRenderRequestedEvent` with the template's vendor
 * template id and the merge data, and records a `pending` generatedDocument
 * carrying the job id integriq answered. A vendor render never falls back to
 * filinq's own engine: every reason it cannot be taken refuses the call.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Vendor
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-a-vendor-render-goes-to-integriq-and-never-falls-back-req-tev-002
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Vendor;

use Exception;
use OCA\Filinq\Service\DocumentGenerationRecord;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use Throwable;

/**
 * Renders a template through a vendor source in integriq.
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-a-vendor-render-goes-to-integriq-and-never-falls-back-req-tev-002
 */
class VendorRenderService {

	/**
	 * integriq's render command, reached by name so filinq runs without integriq.
	 *
	 * @var string
	 */
	public const REQUEST_EVENT = 'OCA\\Integriq\\Event\\DocumentRenderRequestedEvent';

	/**
	 * The app filinq names itself as on a render request.
	 *
	 * @var string
	 */
	public const REQUESTED_BY_APP = 'filinq';

	/**
	 * The status of a generatedDocument a vendor is still rendering.
	 *
	 * @var string
	 */
	public const STATUS_PENDING = 'pending';

	/**
	 * Constructor.
	 *
	 * @param VendorRenderSources     $sources      integriq's document generation sources.
	 * @param IEventDispatcher        $dispatcher   Dispatches the render command.
	 * @param GeneratedDocumentLogger $log          Records the pending generatedDocument.
	 * @param RenderEngine            $engine       Reads and names the engine.
	 * @param string                  $requestEvent The render command's class; a test names a double.
	 * @param VendorRenderOutcome|null $outcome     Settles an outcome integriq announced before the entry was written.
	 */
	public function __construct(
		private readonly VendorRenderSources $sources,
		private readonly IEventDispatcher $dispatcher,
		private readonly GeneratedDocumentLogger $log,
		private readonly RenderEngine $engine = new RenderEngine(),
		private readonly string $requestEvent = self::REQUEST_EVENT,
		private readonly ?VendorRenderOutcome $outcome = null,
	) {

	}//end __construct()

	/**
	 * The source slug a render's `options.engine` names, or null for filinq's own render.
	 *
	 * @param mixed $engine The `options.engine` value.
	 *
	 * @return string|null The source slug, or null when filinq renders.
	 *
	 * @throws Exception 400 naming the value when it is no engine.
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-a-render-names-its-engine-req-tev-001
	 */
	public function vendorSlug(mixed $engine): ?string {
		return $this->engine->vendorSlug(engine: $engine);

	}//end vendorSlug()

	/**
	 * Hand one render to the vendor source of a slug and record it as pending.
	 *
	 * @param string               $slug The source slug from `vendor:<slug>`.
	 * @param array<string, mixed> $run  The generation run: templateId, template, dataRefs, options,
	 *                                   recordFields and format.
	 * @param array<string, mixed> $data The resolved merge data.
	 *
	 * @return array<string, mixed> The generation result: status `pending`, the engine, the job id
	 *                              and the pending entry; no content yet.
	 *
	 * @throws Exception When the render cannot be taken; nothing is rendered by filinq's engine.
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-a-vendor-render-goes-to-integriq-and-never-falls-back-req-tev-002
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-the-vendors-file-is-stored-like-a-filinq-render-req-tev-003
	 */
	public function render(string $slug, array $run, array $data): array {
		$source           = $this->source(slug: $slug);
		$vendorTemplateId = trim((string) ($run['template']['vendorTemplateId'] ?? ''));
		if ($vendorTemplateId === '') {
			throw new Exception(
				message: 'The template has no vendorTemplateId, so it cannot be rendered through source "' . $slug . '".',
				code: 400
			);
		}

		$jobId = $this->dispatch(
			source: $source,
			vendorTemplateId: $vendorTemplateId,
			data: $data,
			userId: (string) ($run['options']['userId'] ?? '')
		);

		$entry = $this->log->log(
			generation: new DocumentGenerationRecord(
				template: [
					'id' => (string) $run['templateId'],
					'version' => ($run['template']['version'] ?? null),
					'name' => (string) ($run['template']['name'] ?? ''),
				],
				dataRefs: (array) $run['dataRefs'],
				format: (string) $run['format'],
				userId: (string) ($run['options']['userId'] ?? ''),
				extra: array_merge(
					(array) ($run['recordFields'] ?? []),
					['vendorJobId' => $jobId, 'engine' => $this->engine->forVendor(slug: $slug)]
				)
			),
			outcome: [
				'status' => self::STATUS_PENDING,
				'warnings' => [],
				'caseId' => ($run['options']['caseId'] ?? null),
				'errorMessage' => null,
				'fileId' => null,
				'filePath' => null,
			]
		);

		// A vendor that settled inside the request announced its outcome
		// before the entry above existed; settle it now.
		$settled = $this->outcome?->settleEarly(jobId: $jobId);
		if ($settled !== null) {
			$entry = $settled;
		}

		if (($entry['status'] ?? null) === VendorRenderOutcome::STATUS_FAILED) {
			throw new Exception(
				message: 'Source "' . $slug . '" could not render the document: ' . (string) ($entry['errorMessage'] ?? ''),
				code: 502
			);
		}

		return [
			'status' => (string) ($entry['status'] ?? self::STATUS_PENDING),
			'engine' => $this->engine->forVendor(slug: $slug),
			'vendorJobId' => $jobId,
			'content' => '',
			'html' => '',
			'format' => (string) $run['format'],
			'metadata' => $entry,
			'templateVersion' => ($run['template']['version'] ?? null),
			'warnings' => [],
			'output' => [
				'mode' => 'files',
				'fileId' => ($entry['fileId'] ?? null),
				'path' => ($entry['filePath'] ?? null),
				'name' => null,
				'size' => null,
			],
		];

	}//end render()

	/**
	 * The active source of a slug, refusing when integriq or the source is not there.
	 *
	 * @param string $slug The source slug.
	 *
	 * @return array{id: string, slug: string, name: string} The source.
	 *
	 * @throws Exception 503 without integriq, 400 when no active source carries the slug.
	 */
	private function source(string $slug): array {
		if ($this->sources->isAvailable() === false || class_exists($this->requestEvent) === false) {
			throw new Exception(
				message: 'Source "' . $slug . '" renders through integriq, which is not enabled on this instance.',
				code: 503
			);
		}

		$source = $this->sources->bySlug(slug: $slug);
		if ($source === null) {
			throw new Exception(
				message: 'No active document generation source "' . $slug . '" in integriq.',
				code: 400
			);
		}

		return $source;

	}//end source()

	/**
	 * Dispatch the render command and read integriq's answer.
	 *
	 * @param array{id: string, slug: string, name: string} $source           The source.
	 * @param string                                        $vendorTemplateId The vendor's template id.
	 * @param array<string, mixed>                          $data             The merge data.
	 * @param string                                        $userId           The user the render is made for.
	 *
	 * @return string The job id integriq answered.
	 *
	 * @throws Exception 502 when integriq refuses the render or answers nothing.
	 */
	private function dispatch(array $source, string $vendorTemplateId, array $data, string $userId): string {
		$class = $this->requestEvent;
		try {
			/** @var Event $event */
			$event = new $class(
				sourceId: $source['id'],
				templateId: $vendorTemplateId,
				data: $data,
				requestedBy: $userId,
				requestedByApp: self::REQUESTED_BY_APP
			);
			$this->dispatcher->dispatchTyped($event);
		} catch (Throwable $e) {
			throw new Exception(
				message: 'Source "' . $source['slug'] . '" could not take the render: ' . $e->getMessage(),
				code: 502
			);
		}

		$refusal = $this->answer(event: $event, method: 'getRefusal');
		if (is_array($refusal) === true) {
			throw new Exception(
				message: 'Source "' . $source['slug'] . '" refused the render: ' . (string) ($refusal['reason'] ?? 'no reason given'),
				code: 502
			);
		}

		$jobId = trim((string) $this->answer(event: $event, method: 'getJobId'));
		if ($jobId === '') {
			throw new Exception(
				message: 'Source "' . $source['slug'] . '" did not answer the render request.',
				code: 502
			);
		}

		return $jobId;

	}//end dispatch()

	/**
	 * What integriq wrote on the command, read by name; null when the command has no such reader.
	 *
	 * The command is integriq's class, reached by name, so its readers are
	 * looked up at runtime rather than declared to static analysis.
	 *
	 * @param Event  $event  The dispatched command.
	 * @param string $method The reader: getRefusal or getJobId.
	 *
	 * @return mixed What the reader answered.
	 */
	private function answer(Event $event, string $method): mixed {
		if (method_exists($event, $method) === false) {
			return null;
		}

		return $event->{$method}();

	}//end answer()
}//end class
