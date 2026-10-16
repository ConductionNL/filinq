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
	 * The output formats and their mime types ({@see DocumentGenerationRequestRules::FORMATS}).
	 *
	 * @var array<string, string>
	 */
	public const FORMATS = DocumentGenerationRequestRules::FORMATS;

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
	 * @param DocumentGenerationRequestRules $rules          What a request must hold, and how its values read.
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
		private readonly DocumentGenerationRequestRules $rules = new DocumentGenerationRequestRules(),
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
		$this->rules->validate(request: $request);
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

		$storeFile = $this->rules->storesFile(request: $request);
		$targetField = $this->rules->text(value: ($request['targetField'] ?? null));
		$object = $this->rules->objectRef(value: ($request['object'] ?? null));
		$data = (array)($request['data'] ?? []);
		$metadata = (array)($request['metadata'] ?? []);

		if ($targetField !== '' && $object === null) {
			throw new RuntimeException(
				'"targetField" is set but the request names no object (register, schema and id) to write it to.'
			);
		}

		// A field-only request files nothing, so it has no use for a PDF: the
		// source format is what is written, and 'return' keeps it off disk.
		$format = 'html';
		$mode = 'return';
		if ($storeFile === true) {
			$format = $this->rules->text(value: ($request['format'] ?? 'pdf'));
			$mode = 'files';
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
			'huisstijlId' => $this->rules->orNull(value: $this->rules->text(value: ($request['huisstijlId'] ?? null))),
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
			'mime' => DocumentGenerationRequestRules::FORMATS[$format],
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
		$templateId = $this->rules->text(value: ($request['templateId'] ?? null));
		if ($templateId !== '') {
			$template = $this->templates->getTemplate(id: $templateId);

			return [
				'id' => $templateId,
				'template' => $template,
				'summary' => $this->summary(id: $templateId, template: $template, source: 'id'),
			];
		}

		$slug = $this->rules->text(value: ($request['templateSlug'] ?? null));
		if ($slug !== '') {
			$tenant = $this->rules->text(value: ($request['templateTenantId'] ?? null));
			$template = $this->slugs->resolve(
				namespace: $this->rules->text(value: ($request['templateNamespace'] ?? null)),
				slug: $slug,
				tenantId: $this->rules->orNull(value: $tenant)
			);
			$id = (string)($template['id'] ?? ($template['uuid'] ?? $slug));

			return [
				'id' => $id,
				'template' => $template,
				'summary' => $this->summary(id: $id, template: $template, source: 'slug'),
			];
		}

		$name = $this->rules->orDefault(
			value: $this->rules->text(value: ($request['templateName'] ?? null)),
			default: self::INLINE_TEMPLATE_NAME
		);
		$template = [
			'name' => $name,
			'content' => (string)$request['template'],
			'namespace' => $this->rules->orDefault(
				value: $this->rules->text(value: ($request['templateNamespace'] ?? null)),
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
		$explicit = $this->rules->text(value: ($request['userId'] ?? null));
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
		$explicit = $this->rules->text(value: ($request['targetPath'] ?? null));
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
		$name = $this->rules->text(value: ($request['filename'] ?? null));
		if ($name === '') {
			$name = $this->rules->orDefault(value: $this->rules->text(value: ($template['name'] ?? null)), default: 'document');
		}

		if (str_contains($name, '{{') === true) {
			$name = html_entity_decode(
				trim(strip_tags($this->renderer->renderTemplate(templateContent: $name, data: $data))),
				ENT_QUOTES
			);
		}

		// A slash would turn the name into a path under the target folder.
		$name = trim(str_replace(['/', '\\'], '-', $name));

		return $this->rules->orDefault(value: $name, default: 'document');
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



}//end class
