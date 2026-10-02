<?php

/**
 * Email ingestion test doubles
 *
 * An in-memory OpenRegister that validates every emailDocument it stores
 * against the real fragment in filinq_register.json (Opis), and a small
 * filesystem: inbox folders whose files really leave when they are moved or
 * deleted, and dossier folders they arrive in.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\EmailIngestion
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

namespace OCA\Filinq\Tests\Unit\Service\EmailIngestion;

use DateTimeImmutable;
use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DossierObjectRepository;
use OCA\Filinq\Service\EmailIngestion\EmailDocumentRepository;
use OCA\Filinq\Service\EmailIngestion\EmailFiling;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionService;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCA\Filinq\Service\EmailIngestion\EmailMessageReader;
use OCA\Filinq\Service\EmailIngestion\EmailThreadHeaders;
use OCA\Filinq\Service\PdfConversionService;
use OCA\OpenRegister\Exception\EmlParseException;
use OCA\OpenRegister\Service\ObjectService;
use OCA\OpenRegister\Service\TextExtraction\EmlAttachment;
use OCA\OpenRegister\Service\TextExtraction\EmlBody;
use OCA\OpenRegister\Service\TextExtraction\EmlStructure;
use OCA\OpenRegister\Service\TextExtractionService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IAppConfig;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * In-memory OpenRegister for emailDocument rows.
 */
class EmailObjectStore extends ObjectService {

	/**
	 * Stored rows by uuid.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $rows = [];

	/**
	 * Every save: [payload, rbac].
	 *
	 * @var list<array{0: array<string, mixed>, 1: bool}>
	 */
	public array $saves = [];

	/**
	 * Find a row.
	 *
	 * @param string $id            Id
	 * @param string $register      Register
	 * @param string $schema        Schema
	 * @param bool   $_rbac         RBAC
	 * @param bool   $_multitenancy Multitenancy
	 * @param bool   $_render       Render
	 * @param bool   $_audit        Audit
	 *
	 * @return array<string, mixed>|null
	 */
	public function find(
		string $id = '',
		string $register = '',
		string $schema = '',
		bool $_rbac = true,
		bool $_multitenancy = true,
		bool $_render = true,
		bool $_audit = true,
	) {
		if (isset($this->rows[$id]) === false) {
			return null;
		}

		return $this->rows[$id] + ['@self' => ['id' => $id]];

	}//end find()

	/**
	 * Save a row after validating it against the real schema fragment.
	 *
	 * @param array<string, mixed> $object        Object
	 * @param string               $register      Register
	 * @param string               $schema        Schema
	 * @param string|null          $uuid          Uuid
	 * @param bool                 $_rbac         RBAC
	 * @param bool                 $_multitenancy Multitenancy
	 *
	 * @return array<string, mixed>
	 */
	public function saveObject(
		array $object = [],
		string $register = '',
		string $schema = '',
		?string $uuid = null,
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		if ($register !== 'filinq' || $schema !== 'emailDocument') {
			throw new RuntimeException('unexpected target ' . $register . '/' . $schema);
		}

		self::assertValidEmailDocument(payload: $object);
		$this->saves[] = [$object, $_rbac];
		if ($uuid === null) {
			$uuid = 'email-' . (count($this->rows) + 1);
		}

		$this->rows[$uuid] = $object;

		return $object + ['@self' => ['id' => $uuid]];

	}//end saveObject()

	/**
	 * Equality search.
	 *
	 * @param string               $registerSlug  Register
	 * @param string               $schemaSlug    Schema
	 * @param array<string, mixed> $filters       Filters
	 * @param bool                 $_rbac         RBAC
	 * @param bool                 $_multitenancy Multitenancy
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function searchObjectsBySlug(
		string $registerSlug,
		string $schemaSlug,
		array $filters = [],
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		$hits = [];
		foreach ($this->rows as $id => $row) {
			foreach ($filters as $key => $value) {
				if (($row[$key] ?? null) !== $value) {
					continue 2;
				}
			}

			$hits[] = $row + ['@self' => ['id' => $id]];
		}

		return $hits;

	}//end searchObjectsBySlug()

	/**
	 * Assert a payload passes the emailDocument fragment of filinq_register.json.
	 *
	 * @param array<string, mixed> $payload The object as written.
	 *
	 * @return void
	 */
	public static function assertValidEmailDocument(array $payload): void {
		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'));
		$schema = $register->components->schemas->emailDocument;
		$properties = new \stdClass();
		foreach ($schema->properties as $name => $property) {
			$copy = clone $property;
			unset($copy->required, $copy->facetable);
			$properties->{$name} = $copy;
		}

		$jsonSchema = (object) [
			'type' => 'object',
			'required' => $schema->required,
			'properties' => $properties,
			'additionalProperties' => false,
		];
		unset($payload['uuid']);
		$result = (new Validator())->validate(json_decode((string) json_encode($payload)), json_encode($jsonSchema));
		if ($result->isValid() === false) {
			throw new RuntimeException('emailDocument refused: ' . json_encode((new ErrorFormatter())->format($result->error())));
		}

	}//end assertValidEmailDocument()
}//end class

/**
 * A tiny filesystem of inbox and dossier folders built from PHPUnit mocks.
 */
trait EmailInboxWorld {

	protected const INBOX = 100;

	protected const DOSSIER_FOLDER = 200;

	/**
	 * The in-memory register.
	 *
	 * @var EmailObjectStore
	 */
	protected EmailObjectStore $store;

	/**
	 * Whether the conversion cascade fails.
	 *
	 * @var bool
	 */
	protected bool $conversionDown = false;

	/**
	 * Whether OpenRegister's parser is installed.
	 *
	 * @var bool
	 */
	protected bool $parserInstalled = true;

	/**
	 * App config values.
	 *
	 * @var array<string, string>
	 */
	protected array $config = [];

	/**
	 * Files per folder id: file id => [name, content].
	 *
	 * @var array<int, array<int, array{0: string, 1: string}>>
	 */
	protected array $tree = [];

	/**
	 * Folder paths by folder id.
	 *
	 * @var array<int, string>
	 */
	protected array $folderPaths = [];

	/**
	 * Next file id.
	 *
	 * @var int
	 */
	protected int $nextFileId = 5000;

	/**
	 * Files deleted from anywhere.
	 *
	 * @var list<int>
	 */
	protected array $deleted = [];

	/**
	 * Create a folder.
	 *
	 * @param int    $folderId The id.
	 * @param string $path     The path.
	 *
	 * @return void
	 */
	protected function addFolder(int $folderId, string $path): void {
		$this->tree[$folderId] = [];
		$this->folderPaths[$folderId] = $path;

	}//end addFolder()

	/**
	 * Drop a file into a folder.
	 *
	 * @param int    $folderId The folder.
	 * @param string $name     The file name.
	 * @param string $content  The bytes.
	 *
	 * @return int The file id.
	 */
	protected function drop(int $folderId, string $name, string $content): int {
		$fileId = $this->nextFileId++;
		$this->tree[$folderId][$fileId] = [$name, $content];

		return $fileId;

	}//end drop()

	/**
	 * The names in a folder.
	 *
	 * @param int $folderId The folder.
	 *
	 * @return list<string> The names, sorted.
	 */
	protected function namesIn(int $folderId): array {
		$names = array_map(static fn (array $file): string => $file[0], array_values($this->tree[$folderId]));
		sort($names);

		return $names;

	}//end namesIn()

	/**
	 * A root folder whose getFirstNodeById answers from the tree.
	 *
	 * @return IRootFolder&MockObject
	 */
	protected function rootFolder(): IRootFolder {
		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturnCallback(fn (int $id): ?Node => $this->node(id: $id));

		return $root;

	}//end rootFolder()

	/**
	 * A node by id.
	 *
	 * @param int $id The id.
	 *
	 * @return Node|null The folder or file.
	 */
	protected function node(int $id): ?Node {
		if (isset($this->tree[$id]) === true) {
			return $this->folderNode(folderId: $id);
		}

		foreach ($this->tree as $folderId => $files) {
			if (isset($files[$id]) === true) {
				return $this->fileNode(folderId: $folderId, fileId: $id);
			}
		}

		return null;

	}//end node()

	/**
	 * A folder mock over the tree.
	 *
	 * @param int $folderId The folder.
	 *
	 * @return Folder&MockObject
	 */
	protected function folderNode(int $folderId): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('getId')->willReturn($folderId);
		$folder->method('getPath')->willReturn($this->folderPaths[$folderId]);
		$folder->method('getDirectoryListing')->willReturnCallback(
			function () use ($folderId): array {
				$nodes = [];
				foreach (array_keys($this->tree[$folderId]) as $fileId) {
					$nodes[] = $this->fileNode(folderId: $folderId, fileId: $fileId);
				}

				return $nodes;
			}
		);
		$folder->method('getNonExistingName')->willReturnCallback(
			function (string $name) use ($folderId): string {
				$taken = array_map(static fn (array $file): string => $file[0], $this->tree[$folderId]);
				$candidate = $name;
				$counter = 2;
				while (in_array($candidate, $taken, true) === true) {
					$candidate = pathinfo($name, PATHINFO_FILENAME) . ' (' . $counter . ').' . pathinfo($name, PATHINFO_EXTENSION);
					$counter++;
				}

				return $candidate;
			}
		);
		$folder->method('newFile')->willReturnCallback(
			function (string $name, string $content = '') use ($folderId): File {
				$fileId = $this->drop(folderId: $folderId, name: $name, content: $content);

				return $this->fileNode(folderId: $folderId, fileId: $fileId);
			}
		);

		return $folder;

	}//end folderNode()

	/**
	 * A file mock over the tree.
	 *
	 * @param int $folderId The folder it is in.
	 * @param int $fileId   The file.
	 *
	 * @return File&MockObject
	 */
	protected function fileNode(int $folderId, int $fileId): File {
		[$name, $content] = $this->tree[$folderId][$fileId];
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($fileId);
		$file->method('getName')->willReturn($name);
		$file->method('getPath')->willReturn($this->folderPaths[$folderId] . '/' . $name);
		$file->method('getMimeType')->willReturn(str_ends_with($name, '.eml') === true ? 'message/rfc822' : 'application/octet-stream');
		$file->method('getContent')->willReturn($content);
		$file->method('fopen')->willReturnCallback(
			static function () use ($content) {
				$stream = fopen('php://memory', 'r+');
				fwrite($stream, $content);
				rewind($stream);

				return $stream;
			}
		);
		$file->method('getParent')->willReturnCallback(fn (): Folder => $this->folderNode(folderId: $folderId));
		$file->method('move')->willReturnCallback(
			function (string $target) use ($folderId, $fileId): Node {
				$targetFolder = array_search(dirname($target), $this->folderPaths, true);
				if ($targetFolder === false) {
					throw new RuntimeException('no folder at ' . dirname($target));
				}

				$entry = $this->tree[$folderId][$fileId];
				unset($this->tree[$folderId][$fileId]);
				$this->tree[$targetFolder][$fileId] = [basename($target), $entry[1]];

				return $this->fileNode(folderId: $targetFolder, fileId: $fileId);
			}
		);
		$file->method('delete')->willReturnCallback(
			function () use ($folderId, $fileId): void {
				unset($this->tree[$folderId][$fileId]);
				$this->deleted[] = $fileId;
			}
		);

		return $file;

	}//end fileNode()

	/**
	 * A raw RFC 5322 email.
	 *
	 * @param string $messageId  Message-ID without brackets.
	 * @param string $subject    Subject.
	 * @param string $extraHeads Extra header lines, CRLF separated.
	 *
	 * @return string The .eml bytes.
	 */
	protected static function eml(string $messageId, string $subject = 'Woo-verzoek', string $extraHeads = ''): string {
		$head = "From: Verzoeker <verzoeker@example.org>\r\n"
			. "To: woo@demostad.example\r\n"
			. 'Subject: ' . $subject . "\r\n"
			. "Date: Sat, 20 Jun 2026 08:41:00 +0000\r\n"
			. 'Message-ID: <' . $messageId . ">\r\n";
		if ($extraHeads !== '') {
			$head .= rtrim($extraHeads, "\r\n") . "\r\n";
		}

		return $head . "Content-Type: text/plain\r\n\r\nGeachte heer, mevrouw.\r\nIn-Reply-To: <not-a-header@example.org>\r\n";

	}//end eml()

	/**
	 * Build the world (call from setUp): one inbox mapped to one dossier.
	 *
	 * @return void
	 */
	protected function setUpWorld(): void {
		$this->addFolder(folderId: self::INBOX, path: '/admin/files/Inbox');
		$this->addFolder(folderId: self::DOSSIER_FOLDER, path: '/admin/files/Filinq/Woo 2025-017');
		$this->store = new EmailObjectStore();
		$this->config = [
			EmailIngestionSettings::KEY_INBOXES => json_encode([['folderId' => self::INBOX, 'dossierRef' => 'dossier-017']]),
		];

	}//end setUpWorld()

	/**
	 * The service with its real collaborators.
	 *
	 * @return EmailIngestionService
	 */
	protected function service(): EmailIngestionService {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => ($this->config[$key] ?? $default)
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->store);
		$clock = $this->createMock(ITimeFactory::class);
		$clock->method('getTime')->willReturn((new DateTimeImmutable('2026-09-30T12:00:00+00:00'))->getTimestamp());
		$logger = $this->createMock(LoggerInterface::class);
		$root = $this->rootFolder();

		return new EmailIngestionService(
			settings: new EmailIngestionSettings(appConfig: $appConfig),
			repository: new EmailDocumentRepository(objectResolver: $resolver),
			reader: new EmailMessageReader(container: $this->container(), threadHeaders: new EmailThreadHeaders(), logger: $logger),
			filing: new EmailFiling(dossiers: $this->dossiers(), rootFolder: $root, conversion: $this->conversion(), logger: $logger),
			rootFolder: $root,
			clock: $clock,
			logger: $logger,
		);

	}//end service()

	/**
	 * A container that answers OpenRegister's text extraction when it is installed.
	 *
	 * @return ContainerInterface
	 */
	protected function container(): ContainerInterface {
		$parser = $this->getMockBuilder(TextExtractionService::class)->onlyMethods(['parseEmlStructured'])->getMock();
		$parser->method('parseEmlStructured')->willReturnCallback(fn (File $file): EmlStructure => self::parse(raw: $file->getContent()));
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			function (string $id) use ($parser): object {
				if ($id !== TextExtractionService::class || $this->parserInstalled === false) {
					throw new \RuntimeException('not installed: ' . $id);
				}

				return $parser;
			}
		);

		return $container;

	}//end container()

	/**
	 * What OpenRegister's parser returns for the fixture emails.
	 *
	 * @param string $raw The .eml bytes.
	 *
	 * @return EmlStructure The structure.
	 */
	protected static function parse(string $raw): EmlStructure {
		if (preg_match('/^Message-ID: <([^>]+)>/mi', $raw, $id) !== 1) {
			throw new EmlParseException('malformed');
		}

		preg_match('/^Subject: (.*)$/mi', $raw, $subject);
		$headers = [
			'from' => 'verzoeker@example.org',
			'to' => ['woo@demostad.example'],
			'cc' => [],
			'subject' => trim($subject[1] ?? ''),
			'date' => new DateTimeImmutable('Sat, 20 Jun 2026 08:41:00 +0000'),
			'messageId' => $id[1],
		];
		$attachment = new EmlAttachment(filename: 'machtiging.pdf', mimeType: 'application/pdf', content: '%PDF', isInline: false, contentId: null, nestedEml: null);
		$inline = new EmlAttachment(filename: 'logo.png', mimeType: 'image/png', content: 'png', isInline: true, contentId: 'logo', nestedEml: null);

		return new EmlStructure(headers: $headers, body: new EmlBody(plainText: 'Geachte heer, mevrouw.', html: null), attachments: [$attachment, $inline]);

	}//end parse()

	/**
	 * The dossier repository: dossier-017 is bound to the dossier folder.
	 *
	 * @return DossierObjectRepository
	 */
	protected function dossiers(): DossierObjectRepository {
		$dossiers = $this->getMockBuilder(DossierObjectRepository::class)->disableOriginalConstructor()->onlyMethods(['loadDossierContext'])->getMock();
		$dossiers->method('loadDossierContext')->willReturnCallback(
			static function (string $dossierUuid): array {
				if ($dossierUuid !== 'dossier-017') {
					throw new \RuntimeException('dossier not found');
				}

				return ['name' => 'Woo 2025-017', 'description' => '', 'checkedOn' => '', 'folderRef' => self::DOSSIER_FOLDER, 'configuration' => []];
			}
		);

		return $dossiers;

	}//end dossiers()

	/**
	 * The conversion cascade: the EML backend writes `<stem>_anonymized.pdf` beside the source.
	 *
	 * @return PdfConversionService
	 */
	protected function conversion(): PdfConversionService {
		$conversion = $this->createMock(PdfConversionService::class);
		$conversion->method('convertToPdfReporting')->willReturnCallback(
			function (File $source): array {
				if ($this->conversionDown === true) {
					throw new ConversionFailedException();
				}

				$pdf = $source->getParent()->newFile(pathinfo($source->getName(), PATHINFO_FILENAME) . '_anonymized.pdf', '%PDF-1.7');

				return ['file' => $pdf, 'backend' => 'eml'];
			}
		);

		return $conversion;

	}//end conversion()
}//end trait
