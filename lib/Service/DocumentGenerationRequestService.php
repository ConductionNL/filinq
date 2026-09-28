<?php

/**
 * Document Generation Request Service
 *
 * The one place a generation request from OUTSIDE Filinq's own UI is carried
 * out: the `filinq.generate-document` flow node and the
 * DocumentGenerationRequestedEvent command listener both call generate(), so
 * the two published entry points cannot drift apart.
 *
 * It adds nothing to how a document is rendered. It resolves the template
 * (by id, by namespace + slug, or inline text), then hands it to
 * {@see DocumentService::generateFromTemplate()}, which is the same render,
 * store and audit path the `document#generate` endpoint uses. What it adds is
 * what a caller without a browser session needs: an object the document
 * belongs to, an optional write of the rendered text into one field of that
 * object, a metadata passthrough, and the DocumentGeneratedEvent that tells
 * the rest of the fleet the document exists.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-one-generation-path-for-the-flow-node-the-command-event-and-the-api
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Event\DocumentGeneratedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserSession;
use RuntimeException;
use UnexpectedValueException;

/**
 * Carries out a generation request from the flow node or the command event.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-one-generation-path-for-the-flow-node-the-command-event-and-the-api
 */
class DocumentGenerationRequestService {

	/**
	 * The output formats, and the mime type each produces.
	 *
	 * `html` is the source format: the rendered template before conversion.
	 * These are exactly the formats DocumentRenderPipeline::produceOutput()
	 * produces; a format outside this list would fall through to PDF there.
	 *
	 * @var array<string, string>
	 */
	public const FORMATS = [
		'pdf' => 'application/pdf',
		'odf' => 'application/vnd.oasis.opendocument.text',
		'html' => 'text/html',
	];

	/**
	 * The name recorded for a template given as inline text.
	 *
	 * @var string
	 */
	private const INLINE_TEMPLATE_NAME = 'Inline template';

	/**
	 * The namespace an inline template's default folder is filed under.
	 *
	 * @var string
	 */
	private const INLINE_NAMESPACE = 'flow';

	/**
	 * Constructor.
	 *
	 * @param DocumentService               $documents       The render, store and audit path.
	 * @param TemplateService               $templates       Looks a template up by id.
	 * @param TemplateSlugResolver          $slugs           Looks a template up by namespace and slug.
	 * @param TemplateRenderer              $renderer        Renders a templated filename.
	 * @param DocumentObjectServiceResolver $objectResolver  Reaches OpenRegister for the field write.
	 * @param IUserSession                  $userSession     The acting user, whose Files receive the document.
	 * @param IEventDispatcher              $eventDispatcher Announces the generated document.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentService $documents,
		private readonly TemplateService $templates,
		private readonly TemplateSlugResolver $slugs,
		private readonly TemplateRenderer $renderer,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly IUserSession $userSession,
		private readonly IEventDispatcher $eventDispatcher,
	) {

	}//end __construct()

	/**
	 * Refuse a request that cannot be carried out.
	 *
	 * Shared by the flow node's validateConfig() (run when a flow is saved)
	 * and by generate() (run for every request, because a flow imported from
	 * a declaration reaches execution unvalidated, and an event carries
	 * whatever the dispatcher built).
	 *
	 * @param array<string, mixed> $request The request or the node's configuration.
	 *
	 * @return void
	 *
	 * @throws UnexpectedValueException When the request is unusable.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-configuration-that-cannot-run-is-refused-when-the-flow-is-saved
	 */
	public function validate(array $request): void {
		$sources = array_filter(
			[
				'templateId' => $this->text(value: ($request['templateId'] ?? null)),
				'templateSlug' => $this->text(value: ($request['templateSlug'] ?? null)),
				'template' => $this->text(value: ($request['template'] ?? null)),
			],
			static fn (string $value): bool => $value !== ''
		);

		if (count($sources) !== 1) {
			throw new UnexpectedValueException(
				'Name exactly one template: "templateId", "templateSlug" or "template" (inline text).'
			);
		}

		if (isset($sources['templateSlug']) === true
			&& $this->text(value: ($request['templateNamespace'] ?? null)) === ''
		) {
			throw new UnexpectedValueException('"templateSlug" needs "templateNamespace", the app the template belongs to.');
		}

		$format = $this->text(value: ($request['format'] ?? 'pdf'));
		if (array_key_exists($format, self::FORMATS) === false) {
			throw new UnexpectedValueException(
				'"format" must be one of: ' . implode(', ', array_keys(self::FORMATS)) . '.'
			);
		}

		$targetField = ($request['targetField'] ?? null);
		if ($targetField !== null && is_string($targetField) === false) {
			throw new UnexpectedValueException('"targetField" must be the name of one field on the object.');
		}

		if ($this->storesFile(request: $request) === false && $this->text(value: $targetField) === '') {
			throw new UnexpectedValueException(
				'With "storeFile" off the document goes nowhere. Set "targetField", or leave "storeFile" on.'
			);
		}

		if (array_key_exists('metadata', $request) === true && is_array($request['metadata']) === false) {
			throw new UnexpectedValueException('"metadata" must be an object.');
		}

		if (array_key_exists('output', $request) === true && $this->text(value: $request['output']) === '') {
			throw new UnexpectedValueException('"output" must name the item field the document is written to.');
		}
	}//end validate()

	/**
	 * Generate one document and announce it.
	 *
	 * Throws on every failure. The flow node lets it reach the engine, whose
	 * `onError` policy decides what happens next; the command listener turns
	 * it into the event's error slot. Nothing here catches and carries on,
	 * because a generation that reports success without a document is the
	 * failure the dossiq nodes this replaces were deleted for.
	 *
	 * @param array<string, mixed> $request       The request: a template (templateId, templateSlug +
	 *                                            templateNamespace [+ templateTenantId], or template
	 *                                            text [+ templateName]); data (the render context);
	 *                                            object ({register, schema, id}); format; storeFile;
	 *                                            targetField; targetPath; filename; huisstijlId;
	 *                                            metadata; userId.
	 * @param string               $requestingApp The app asking, echoed on DocumentGeneratedEvent.
	 *
	 * @return array<string, mixed> fileId, path, name, mime, size, format, template, object,
	 *                              targetField, metadata, requestingApp, warnings; plus `text`
	 *                              (the rendered template) when targetField was written.
	 *
	 * @throws UnexpectedValueException When the request is unusable.
	 * @throws RuntimeException When no user can own the file, or the field has no object to go to.
	 * @throws \Exception When the template cannot be found or rendering, storing or writing fails.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-one-generation-path-for-the-flow-node-the-command-event-and-the-api
	 */
	public function generate(array $request, string $requestingApp): array {
		$this->validate(request: $request);

		$storeFile = $this->storesFile(request: $request);
		$targetField = $this->text(value: ($request['targetField'] ?? null));
		$object = $this->objectRef(value: ($request['object'] ?? null));
		$data = (array)($request['data'] ?? []);
		$metadata = (array)($request['metadata'] ?? []);

		if ($targetField !== '' && $object === null) {
			throw new RuntimeException(
				'"targetField" is set but the request names no object (register, schema and id) to write it to.'
			);
		}

		// A field-only request files nothing, so it has no use for a PDF: the
		// source format is what is written, and 'return' keeps it off disk.
		$format = $this->text(value: ($request['format'] ?? 'pdf'));
		$mode = 'return';
		if ($storeFile === true) {
			$mode = 'files';
		} else {
			$format = 'html';
		}

		$resolved = $this->resolveTemplate(request: $request);
		$template = $resolved['template'];

		// The item's own fields at the top level, so `{{ title }}` works; the
		// whole item under `item` as well, so a template can reach a field
		// whose name a top-level helper shadows. A resolved object reference
		// is ALSO passed as a dataRef: that puts the stored object under its
		// schema key and, more to the point, names it in the audit record.
		$adHocData = array_merge($data, ['item' => $data]);
		$dataRefs = [];
		if ($object !== null) {
			$dataRefs[] = $object;
		}

		$options = [
			'format' => $format,
			'adHocData' => $adHocData,
			'huisstijlId' => $this->orNull(value: $this->text(value: ($request['huisstijlId'] ?? null))),
			'filename' => $this->filename(request: $request, template: $template, data: $adHocData),
			'output' => ['mode' => $mode],
		];

		if ($storeFile === true) {
			$options['userId'] = $this->userId(request: $request);
			$options['output']['targetPath'] = $this->targetPath(
				request: $request,
				templateId: $resolved['id'],
				template: $template,
				object: $object
			);
		}

		$generated = $this->documents->generateFromTemplate(
			templateId: $resolved['id'],
			template: $template,
			dataRefs: $dataRefs,
			options: $options
		);

		$document = [
			'fileId' => ($generated['output']['fileId'] ?? null),
			'path' => ($generated['output']['path'] ?? null),
			'name' => ($generated['output']['name'] ?? null),
			'mime' => self::FORMATS[$format],
			'size' => ($generated['output']['size'] ?? null),
			'format' => $format,
			'template' => $resolved['summary'],
			'object' => $object,
			'targetField' => null,
			'metadata' => $metadata,
			'requestingApp' => $requestingApp,
			'warnings' => (array)($generated['warnings'] ?? []),
		];

		if ($targetField !== '' && $object !== null) {
			$text = (string)($generated['html'] ?? $generated['content'] ?? '');
			$this->writeField(object: $object, field: $targetField, text: $text);
			$document['targetField'] = $targetField;
			$document['text'] = $text;
		}

		$this->eventDispatcher->dispatchTyped(
			new DocumentGeneratedEvent(document: $document, requestingApp: $requestingApp)
		);

		return $document;
	}//end generate()

	/**
	 * Find the template the request names.
	 *
	 * @param array<string, mixed> $request The validated request.
	 *
	 * @return array{id: string, template: array<string, mixed>, summary: array<string, mixed>}
	 *
	 * @throws \Exception When a named template does not exist.
	 */
	private function resolveTemplate(array $request): array {
		$templateId = $this->text(value: ($request['templateId'] ?? null));
		if ($templateId !== '') {
			$template = $this->templates->getTemplate(id: $templateId);

			return [
				'id' => $templateId,
				'template' => $template,
				'summary' => $this->summary(id: $templateId, template: $template, source: 'id'),
			];
		}

		$slug = $this->text(value: ($request['templateSlug'] ?? null));
		if ($slug !== '') {
			$tenant = $this->text(value: ($request['templateTenantId'] ?? null));
			$template = $this->slugs->resolve(
				namespace: $this->text(value: ($request['templateNamespace'] ?? null)),
				slug: $slug,
				tenantId: $this->orNull(value: $tenant)
			);
			$id = (string)($template['id'] ?? ($template['uuid'] ?? $slug));

			return [
				'id' => $id,
				'template' => $template,
				'summary' => $this->summary(id: $id, template: $template, source: 'slug'),
			];
		}

		$name = $this->orDefault(
			value: $this->text(value: ($request['templateName'] ?? null)),
			default: self::INLINE_TEMPLATE_NAME
		);
		$template = [
			'name' => $name,
			'content' => (string)$request['template'],
			'namespace' => $this->orDefault(
				value: $this->text(value: ($request['templateNamespace'] ?? null)),
				default: self::INLINE_NAMESPACE
			),
		];

		return [
			'id' => 'inline',
			'template' => $template,
			'summary' => $this->summary(id: 'inline', template: $template, source: 'inline'),
		];
	}//end resolveTemplate()

	/**
	 * What the result and the event say about the template used.
	 *
	 * @param string               $id       The template identifier.
	 * @param array<string, mixed> $template The template.
	 * @param string               $source   How it was named: id, slug or inline.
	 *
	 * @return array<string, mixed> id, slug, name, version, source.
	 */
	private function summary(string $id, array $template, string $source): array {
		return [
			'id' => $id,
			'slug' => ($template['slug'] ?? null),
			'name' => (string)($template['name'] ?? ''),
			'version' => ($template['version'] ?? null),
			'source' => $source,
		];
	}//end summary()

	/**
	 * Whether the request stores a file (the default).
	 *
	 * @param array<string, mixed> $request The request.
	 *
	 * @return bool True unless storeFile is explicitly off.
	 */
	private function storesFile(array $request): bool {
		$value = ($request['storeFile'] ?? true);
		if (is_string($value) === true) {
			return in_array(strtolower(trim($value)), ['0', 'false', 'no', 'off', ''], true) === false;
		}

		return (bool)$value;
	}//end storesFile()

	/**
	 * The object reference, when the request carries a complete one.
	 *
	 * @param mixed $value The request's `object`.
	 *
	 * @return array{register: string, schema: string, id: string}|null Null when any part is missing.
	 */
	private function objectRef(mixed $value): ?array {
		if (is_array($value) === false) {
			return null;
		}

		$ref = [
			'register' => $this->text(value: ($value['register'] ?? null)),
			'schema' => $this->text(value: ($value['schema'] ?? null)),
			'id' => $this->text(value: ($value['id'] ?? null)),
		];

		if (in_array('', $ref, true) === true) {
			return null;
		}

		return $ref;
	}//end objectRef()

	/**
	 * The user whose Files receive the document.
	 *
	 * On the flow path OpenRegister runs a contributed node inside the run's
	 * acting identity, so the session user IS that identity.
	 *
	 * @param array<string, mixed> $request The request.
	 *
	 * @return string The user id.
	 *
	 * @throws RuntimeException When there is no user to own the file.
	 */
	private function userId(array $request): string {
		$explicit = $this->text(value: ($request['userId'] ?? null));
		if ($explicit !== '') {
			return $explicit;
		}

		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new RuntimeException(
				'There is no acting user to store the document for. Run the flow as a user, or pass "userId".'
			);
		}

		return $user->getUID();
	}//end userId()

	/**
	 * Where the document is filed.
	 *
	 * An explicit targetPath wins. Otherwise Filinq's own default folder for
	 * the template's namespace, with a folder per object under it, so every
	 * document generated for one object sits together.
	 *
	 * @param array<string, mixed>                                   $request    The request.
	 * @param string                                                 $templateId The template id.
	 * @param array<string, mixed>                                   $template   The template.
	 * @param array{register: string, schema: string, id: string}|null $object     The object, if any.
	 *
	 * @return string The folder path.
	 */
	private function targetPath(array $request, string $templateId, array $template, ?array $object): string {
		$explicit = $this->text(value: ($request['targetPath'] ?? null));
		if ($explicit !== '') {
			return $explicit;
		}

		$base = $this->documents->buildOutputTargetPath(
			templateId: $templateId,
			explicitTargetPath: null,
			template: $template
		);

		if ($object === null) {
			return $base;
		}

		return $base . '/' . $object['id'];
	}//end targetPath()

	/**
	 * The file name, rendered when it holds placeholders.
	 *
	 * @param array<string, mixed> $request  The request.
	 * @param array<string, mixed> $template The template.
	 * @param array<string, mixed> $data     The render context.
	 *
	 * @return string The file name, without extension.
	 */
	private function filename(array $request, array $template, array $data): string {
		$name = $this->text(value: ($request['filename'] ?? null));
		if ($name === '') {
			$name = $this->orDefault(value: $this->text(value: ($template['name'] ?? null)), default: 'document');
		}

		if (str_contains($name, '{{') === true) {
			$name = html_entity_decode(
				trim(strip_tags($this->renderer->renderTemplate(templateContent: $name, data: $data))),
				ENT_QUOTES
			);
		}

		// A slash would turn the name into a path under the target folder.
		$name = trim(str_replace(['/', '\\'], '-', $name));

		return $this->orDefault(value: $name, default: 'document');
	}//end filename()

	/**
	 * Write the rendered text into one field of the object, and nothing else.
	 *
	 * The patch merges into the STORED object, so a flow item that is a
	 * stale snapshot cannot overwrite what other writers stored since.
	 *
	 * @param array{register: string, schema: string, id: string} $object The object.
	 * @param string                                              $field  The field.
	 * @param string                                              $text   The rendered text.
	 *
	 * @return void
	 *
	 * @throws \Exception When OpenRegister is absent or refuses the write.
	 */
	private function writeField(array $object, string $field, string $text): void {
		$this->objectResolver->resolve()->patchObject(
			objectId: $object['id'],
			data: [$field => $text],
			register: $object['register'],
			schema: $object['schema']
		);
	}//end writeField()

	/**
	 * The value, or null when it is empty.
	 *
	 * @param string $value The value.
	 *
	 * @return string|null The value, or null.
	 */
	private function orNull(string $value): ?string {
		if ($value === '') {
			return null;
		}

		return $value;
	}//end orNull()

	/**
	 * The value, or the default when it is empty.
	 *
	 * @param string $value   The value.
	 * @param string $default The default.
	 *
	 * @return string The value or the default.
	 */
	private function orDefault(string $value, string $default): string {
		if ($value === '') {
			return $default;
		}

		return $value;
	}//end orDefault()

	/**
	 * A scalar as trimmed text, anything else as ''.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string The text.
	 */
	private function text(mixed $value): string {
		if (is_scalar($value) === false) {
			return '';
		}

		return trim((string)$value);
	}//end text()
}//end class
