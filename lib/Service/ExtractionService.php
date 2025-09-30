<?php
/**
 * Service for extracting text content from various file types
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Service;

use Exception;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Psr\Log\LoggerInterface;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use OCP\Files\IRootFolder;

/**
 * Service for extracting text content from various file types
 *
 * This service provides methods to extract text content from different file formats
 * including PDF, Word documents, Excel spreadsheets, PowerPoint presentations,
 * and plain text files.
 */
class ExtractionService
{

    /**
     * Logger instance for error reporting
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * App config for getting app config
     *
     * @var IAppConfig
     */
    private IAppConfig $appConfig;

    /**
     * Root folder for file operations
     *
     * @var IRootFolder
     */
    private IRootFolder $rootFolder;


    /**
     * Constructor for ExtractionService
     *
     * @param LoggerInterface $logger     Logger for error reporting
     * @param IAppConfig      $appConfig  App config for getting app config
     * @param IRootFolder     $rootFolder Root folder for file operations
     *
     * @return void
     */
    public function __construct(
        LoggerInterface $logger,
        IAppConfig $appConfig,
        IRootFolder $rootFolder
    ) {
        $this->logger     = $logger;
        $this->appConfig  = $appConfig;
        $this->rootFolder = $rootFolder;

    }//end __construct()


    /**
     * Extract text content from a file node
     *
     * This method detects the file type based on extension and calls the appropriate
     * extraction method.
     *
     * @param \OCP\Files\Node $node The file node to extract text from
     *
     * @return array{text: ?string, errorMessage: ?string} Extraction result with text content and error message
     *
     * @throws \InvalidArgumentException If the node is not a file
     * @throws Exception If the file type is not supported or extraction fails
     *
     * @psalm-return   array{text: ?string, errorMessage: ?string}
     * @phpstan-return array{text: ?string, errorMessage: ?string}
     */
    public function extractText(\OCP\Files\Node $node): array
    {
        // Check if node is a file.
        if ($node->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
            throw new \InvalidArgumentException('Node must be a file');
        }

        // Get file path and MIME type.
        $filePath  = $node->getPath();
        $mimeType  = $node->getMimeType();
        $extension = $node->getExtension();

        // Initialize extraction result.
        $extraction = [
            'text'         => null,
            'errorMessage' => null,
        ];

        // Extract text based on file type.
        switch ($extension) {
            case 'pdf':
                $this->logger->debug('File is a pdf, extracting text: '.$filePath);
                $extraction['text'] = $this->extractFromPdf($filePath);
                break;
            case 'doc':
            case 'docx':
                $this->logger->debug('File is a word document, extracting text: '.$filePath);
                $extraction['text'] = $this->extractFromWord($filePath);
                break;
            case 'xls':
            case 'xlsx':
            case 'csv':
                $this->logger->debug('File is a spreadsheet, extracting text: '.$filePath);
                $extraction['text'] = $this->extractFromSpreadsheet($filePath);
                break;
            case 'ppt':
            case 'pptx':
                $this->logger->debug('File is a presentation, extracting text: '.$filePath);
                $extraction['text'] = $this->extractFromPresentation($filePath);
                break;
            case 'txt':
            case 'md':
            case 'html':
            case 'htm':
            case 'xml':
            case 'json':
                $this->logger->debug('File is a text file, extracting text: '.$filePath);
                $extraction['text'] = $node->getContent();
                break;
            // Image files - return empty string with a log message.
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'bmp':
            case 'webp':
            case 'svg':
            case 'tiff':
                $this->logger->debug('File is an image, no text extraction possible: '.$filePath);
                $extraction['errorMessage'] = 'File is an image, no text extraction possible';
                break;
            // Video files - return empty string with a log message.
            case 'mp4':
            case 'avi':
            case 'mov':
            case 'wmv':
            case 'flv':
            case 'webm':
            case 'mkv':
                $this->logger->debug('File is a video, no text extraction possible: '.$filePath);
                $extraction['errorMessage'] = 'File is a video, no text extraction possible';
                break;
            // Audio files - return empty string with a log message.
            case 'mp3':
            case 'wav':
            case 'ogg':
            case 'flac':
            case 'aac':
            case 'm4a':
                $this->logger->debug('File is an audio file, no text extraction possible: '.$filePath);
                $extraction['errorMessage'] = 'File is an audio file, no text extraction possible';
                break;
            default:
                // Log warning for unsupported file type.
                $this->logger->warning('Unsupported file type: '.$extension.' with MIME type: '.$mimeType);
                $extraction['errorMessage'] = 'Unsupported file type: '.$extension.' with MIME type: '.$mimeType;
        }//end switch

        return $extraction;

    }//end extractText()


    /**
     * Extract text from a PDF file
     *
     * @param string $filePath Path to the PDF file
     *
     * @return string Extracted text content
     *
     * @throws Exception If PDF parsing fails
     *
     * @psalm-return   string
     * @phpstan-return string
     */
    private function extractFromPdf(string $filePath): string
    {
        try {
            // Get the file node from the path.
            $node = $this->rootFolder->get($filePath);

            // Get the file content as a stream.
            $stream = $node->fopen('r');

            // Create a temporary file.
            $tempFile = tempnam(sys_get_temp_dir(), 'docudesk_pdf_');
            if ($tempFile === false) {
                throw new Exception('Failed to create temporary file');
            }

            // Write the stream content to the temporary file.
            $tempStream = fopen($tempFile, 'w');
            if ($tempStream === false) {
                unlink($tempFile);
                throw new Exception('Failed to open temporary file for writing');
            }

            // Copy the content from the source stream to the temporary file.
            stream_copy_to_stream($stream, $tempStream);
            fclose($tempStream);
            fclose($stream);

            // Create PDF parser instance.
            $parser = new PdfParser();

            // Parse PDF file from temporary file.
            $pdf = $parser->parseFile($tempFile);

            // Extract text from all pages.
            $text = $pdf->getText();

            // Clean up text (remove excessive whitespace).
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);

            // Clean up temporary file.
            unlink($tempFile);

            return $text;
        } catch (Exception $e) {
            // Clean up temporary file if it exists.
            if (isset($tempFile) === true && file_exists($tempFile) === true) {
                unlink($tempFile);
            }

            $this->logger->error('Error extracting text from PDF: '.$e->getMessage(), ['exception' => $e]);
            throw new Exception('Failed to extract text from PDF: '.$e->getMessage(), 0, $e);
        }//end try

    }//end extractFromPdf()


    /**
     * Extract text from a Word document
     *
     * @param string $filePath Path to the Word document
     *
     * @return string Extracted text content
     *
     * @throws Exception If Word document parsing fails
     *
     * @psalm-return   string
     * @phpstan-return string
     */
    private function extractFromWord(string $filePath): string
    {
        try {
            // Get the file node from the path.
            $node = $this->rootFolder->get($filePath);

            // Get the file content as a stream.
            $stream = $node->fopen('r');

            // Create a temporary file.
            $tempFile = tempnam(sys_get_temp_dir(), 'docudesk_word_');
            if ($tempFile === false) {
                throw new Exception('Failed to create temporary file');
            }

            // Write the stream content to the temporary file.
            $tempStream = fopen($tempFile, 'w');
            if ($tempStream === false) {
                unlink($tempFile);
                throw new Exception('Failed to open temporary file for writing');
            }

            // Copy the content from the source stream to the temporary file.
            stream_copy_to_stream($stream, $tempStream);
            fclose($tempStream);
            fclose($stream);

            // Load the document from the temporary file.
            $phpWord = WordIOFactory::load($tempFile);

            $text = '';

            // Extract text from headers.
            foreach ($phpWord->getSections() as $section) {
                $headers = $section->getHeaders();
                foreach ($headers as $header) {
                    $text .= $this->extractTextFromElements($header->getElements())."\n";
                }
            }

            // Extract text from main content.
            foreach ($phpWord->getSections() as $section) {
                // Get all elements in the section.
                $elements = $section->getElements();
                $text    .= $this->extractTextFromElements($elements);

                // Add section break.
                $text .= "\n\n";
            }

            // Extract text from footers.
            foreach ($phpWord->getSections() as $section) {
                $footers = $section->getFooters();
                foreach ($footers as $footer) {
                    $text .= $this->extractTextFromElements($footer->getElements())."\n";
                }
            }

            // Clean up text.
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);

            // Clean up temporary file.
            unlink($tempFile);

            return $text;
        } catch (Exception $e) {
            // Clean up temporary file if it exists.
            if (isset($tempFile) === true && file_exists($tempFile) === true) {
                unlink($tempFile);
            }

            $this->logger->error('Error extracting text from Word document: '.$e->getMessage(), ['exception' => $e]);
            throw new Exception('Failed to extract text from Word document: '.$e->getMessage(), 0, $e);
        }//end try

    }//end extractFromWord()


    /**
     * Extract text from document elements
     *
     * @param array $elements Array of document elements
     *
     * @return string Extracted text
     *
     * @psalm-return   string
     * @phpstan-return string
     */
    private function extractTextFromElements(array $elements): string
    {
        $text = '';

        foreach ($elements as $element) {
            // Handle text runs.
            if (method_exists($element, 'getText') === true) {
                $text .= $element->getText().' ';
            }

            // Handle tables.
            if (method_exists($element, 'getRows') === true) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $text .= $this->extractTextFromElements($cell->getElements())."\t";
                    }

                    $text .= "\n";
                }
            }

            // Handle lists.
            if (method_exists($element, 'getItems') === true) {
                foreach ($element->getItems() as $item) {
                    $text .= "• ".$this->extractTextFromElements($item->getElements())."\n";
                }
            }

            // Handle nested elements.
            if (method_exists($element, 'getElements') === true) {
                $text .= $this->extractTextFromElements($element->getElements());
            }
        }//end foreach

        return $text;

    }//end extractTextFromElements()


    /**
     * Extract text from a spreadsheet (Excel, ODS)
     *
     * @param string $filePath Path to the spreadsheet file
     *
     * @return string Extracted text content
     *
     * @throws Exception If spreadsheet parsing fails
     *
     * @psalm-return   string
     * @phpstan-return string
     */
    private function extractFromSpreadsheet(string $filePath): string
    {
        try {
            // Get the file node from the path.
            $node = $this->rootFolder->get($filePath);

            // Get the file content as a stream.
            $stream = $node->fopen('r');

            // Load the spreadsheet from stream.
            $spreadsheet = SpreadsheetIOFactory::load($stream);

            $text = '';

            // Get document properties.
            $properties = $spreadsheet->getProperties();
            if ($properties->getTitle() !== null && $properties->getTitle() !== '') {
                $text .= "Title: ".$properties->getTitle()."\n";
            }

            if ($properties->getDescription() !== null && $properties->getDescription() !== '') {
                $text .= "Description: ".$properties->getDescription()."\n";
            }

            $text .= "\n";

            // Iterate through all worksheets.
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                // Add worksheet title.
                $text .= "Sheet: ".$worksheet->getTitle()."\n";

                // Get the highest row and column.
                $highestRow         = $worksheet->getHighestRow();
                $highestColumn      = $worksheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                // Extract column headers if they exist.
                $hasHeaders = false;
                $headers    = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
                    if (empty($cellValue) === false) {
                        $hasHeaders    = true;
                        $headers[$col] = $cellValue;
                    }
                }

                // If headers exist, add them to the text.
                if ($hasHeaders === true) {
                    $text    .= implode("\t", $headers)."\n";
                    $startRow = 2;
                } else {
                    $startRow = 1;
                }

                // Extract data rows.
                for ($row = $startRow; $row <= $highestRow; $row++) {
                    $rowData = [];
                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $cell = $worksheet->getCellByColumnAndRow($col, $row);

                        // Get cell value.
                        $value = $cell->getValue();

                        // Handle formulas.
                        if ($cell->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA) {
                            $value = $cell->getCalculatedValue();
                        }

                        // Format the value.
                        if (is_numeric($value) === true) {
                            // Format numbers according to cell format.
                            $value = $cell->getFormattedValue();
                        } else if ($value instanceof \DateTime) {
                            // Format dates.
                            $value = $value->format('Y-m-d H:i:s');
                        }

                        $rowData[] = $value ?? '';
                    }//end for

                    // Only add non-empty rows.
                    if (empty(array_filter($rowData)) === false) {
                        $text .= implode("\t", $rowData)."\n";
                    }
                }//end for

                $text .= "\n";
            }//end foreach

            return trim($text);
        } catch (Exception $e) {
            $this->logger->error('Error extracting text from spreadsheet: '.$e->getMessage(), ['exception' => $e]);
            throw new Exception('Failed to extract text from spreadsheet: '.$e->getMessage(), 0, $e);
        }//end try

    }//end extractFromSpreadsheet()


    /**
     * Extract text from a presentation (PowerPoint)
     *
     * @param string $filePath Path to the presentation file
     *
     * @return string Extracted text content
     *
     * @throws Exception If presentation parsing fails
     *
     * @psalm-return   string
     * @phpstan-return string
     */
    private function extractFromPresentation(string $filePath): string
    {
        try {
            // Get the file node from the path.
            $node = $this->rootFolder->get($filePath);

            // Get the file content as a stream.
            $stream = $node->fopen('r');

            // Load the presentation from stream.
            $presentation = PresentationIOFactory::load($stream);

            $text = '';

            // Iterate through all slides.
            $slideCount = $presentation->getSlideCount();
            for ($i = 0; $i < $slideCount; $i++) {
                $slide = $presentation->getSlide($i);

                // Add slide number.
                $text .= 'Slide '.($i + 1).":\n";

                // Extract text from shapes.
                foreach ($slide->getShapeCollection() as $shape) {
                    if (method_exists($shape, 'getText') === true) {
                        $shapeText = $shape->getText();
                        if (empty($shapeText) === false) {
                            $text .= $shapeText."\n";
                        }
                    }

                    // Extract text from rich text elements.
                    if (method_exists($shape, 'getRichTextElements') === true) {
                        foreach ($shape->getRichTextElements() as $richText) {
                            if (method_exists($richText, 'getText') === true) {
                                $richTextContent = $richText->getText();
                                if (empty($richTextContent) === false) {
                                    $text .= $richTextContent."\n";
                                }
                            }
                        }
                    }
                }

                $text .= "\n";
            }//end for

            return trim($text);
        } catch (Exception $e) {
            $this->logger->error('Error extracting text from presentation: '.$e->getMessage(), ['exception' => $e]);
            throw new Exception('Failed to extract text from presentation: '.$e->getMessage(), 0, $e);
        }//end try

    }//end extractFromPresentation()


    /**
     * Get metadata from a file
     *
     * @param string $filePath Path to the file
     *
     * @return array<string, mixed> Metadata information
     *
     * @throws Exception If metadata extraction fails
     *
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function extractMetadata(string $filePath): array
    {
        // Basic file metadata.
        $metadata = [
            'filename'      => basename($filePath),
            'filesize'      => filesize($filePath),
            'filetype'      => mime_content_type($filePath),
            'extension'     => strtolower(pathinfo($filePath, PATHINFO_EXTENSION)),
            'last_modified' => filemtime($filePath),
        ];

        // Get file extension.
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        try {
            // Extract additional metadata based on file type.
            switch ($extension) {
                case 'pdf':
                    $metadata = array_merge($metadata, $this->extractPdfMetadata($filePath));
                    break;
                case 'doc':
                case 'docx':
                    $metadata = array_merge($metadata, $this->extractWordMetadata($filePath));
                    break;
                case 'xls':
                case 'xlsx':
                    $metadata = array_merge($metadata, $this->extractSpreadsheetMetadata($filePath));
                    break;
                case 'ppt':
                case 'pptx':
                    $metadata = array_merge($metadata, $this->extractPresentationMetadata($filePath));
                    break;
            }
        } catch (Exception $e) {
            $this->logger->warning('Error extracting metadata: '.$e->getMessage(), ['exception' => $e]);
            // Continue with basic metadata if advanced extraction fails.
        }//end try

        return $metadata;

    }//end extractMetadata()


    /**
     * Extract metadata from a PDF file
     *
     * @param string $filePath Path to the PDF file
     *
     * @return array<string, mixed> PDF metadata
     *
     * @throws Exception If PDF parsing fails
     *
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    private function extractPdfMetadata(string $filePath): array
    {
        $parser  = new PdfParser();
        $pdf     = $parser->parseFile($filePath);
        $details = $pdf->getDetails();

        // Extract relevant metadata.
        $metadata = [];

        // Common PDF metadata fields.
        $metadataFields = [
            'Title',
            'Author',
            'Subject',
            'Keywords',
            'Creator',
            'Producer',
            'CreationDate',
            'ModDate',
            'Pages',
        ];

        foreach ($metadataFields as $field) {
            if (isset($details[$field]) === true) {
                $metadata[strtolower($field)] = $details[$field];
            }
        }

        // Add page count.
        if (isset($metadata['pages']) === false && $pdf->getPages() !== null) {
            $metadata['pages'] = count($pdf->getPages());
        }

        return $metadata;

    }//end extractPdfMetadata()


    /**
     * Extract metadata from a Word document
     *
     * @param string $filePath Path to the Word document
     *
     * @return array<string, mixed> Word document metadata
     *
     * @throws Exception If Word document parsing fails
     *
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    private function extractWordMetadata(string $filePath): array
    {
        $phpWord    = WordIOFactory::load($filePath);
        $properties = $phpWord->getDocInfo();

        $metadata = [];

        // Extract document properties.
        if ($properties->getCreator() !== null && $properties->getCreator() !== '') {
            $metadata['creator'] = $properties->getCreator();
        }

        if ($properties->getLastModifiedBy() !== null && $properties->getLastModifiedBy() !== '') {
            $metadata['last_modified_by'] = $properties->getLastModifiedBy();
        }

        if ($properties->getCreated() !== null) {
            $metadata['created'] = $properties->getCreated();
        }

        if ($properties->getModified() !== null) {
            $metadata['modified'] = $properties->getModified();
        }

        if ($properties->getTitle() !== null && $properties->getTitle() !== '') {
            $metadata['title'] = $properties->getTitle();
        }

        if ($properties->getDescription() !== null && $properties->getDescription() !== '') {
            $metadata['description'] = $properties->getDescription();
        }

        if ($properties->getSubject() !== null && $properties->getSubject() !== '') {
            $metadata['subject'] = $properties->getSubject();
        }

        if ($properties->getKeywords() !== null && $properties->getKeywords() !== '') {
            $metadata['keywords'] = $properties->getKeywords();
        }

        if ($properties->getCategory() !== null && $properties->getCategory() !== '') {
            $metadata['category'] = $properties->getCategory();
        }

        // Count sections, paragraphs, and words.
        $sections = $phpWord->getSections();
        $metadata['sections'] = count($sections);

        $paragraphCount = 0;
        $wordCount      = 0;

        foreach ($sections as $section) {
            $elements = $section->getElements();
            foreach ($elements as $element) {
                if (get_class($element) === 'PhpOffice\PhpWord\Element\TextRun') {
                    $paragraphCount++;
                    // Count words in text.
                    if (method_exists($element, 'getText') === true) {
                        $text       = $element->getText();
                        $wordCount += str_word_count($text);
                    }
                }
            }
        }

        $metadata['paragraphs'] = $paragraphCount;
        $metadata['words']      = $wordCount;

        return $metadata;

    }//end extractWordMetadata()


    /**
     * Extract metadata from a spreadsheet
     *
     * @param string $filePath Path to the spreadsheet file
     *
     * @return array<string, mixed> Spreadsheet metadata
     *
     * @throws Exception If spreadsheet parsing fails
     *
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    private function extractSpreadsheetMetadata(string $filePath): array
    {
        $spreadsheet = SpreadsheetIOFactory::load($filePath);
        $properties  = $spreadsheet->getProperties();

        $metadata = [];

        // Extract document properties.
        if ($properties->getCreator() !== null && $properties->getCreator() !== '') {
            $metadata['creator'] = $properties->getCreator();
        }

        if ($properties->getLastModifiedBy() !== null && $properties->getLastModifiedBy() !== '') {
            $metadata['last_modified_by'] = $properties->getLastModifiedBy();
        }

        if ($properties->getCreated() !== null) {
            $metadata['created'] = $properties->getCreated();
        }

        if ($properties->getModified() !== null) {
            $metadata['modified'] = $properties->getModified();
        }

        if ($properties->getTitle() !== null && $properties->getTitle() !== '') {
            $metadata['title'] = $properties->getTitle();
        }

        if ($properties->getDescription() !== null && $properties->getDescription() !== '') {
            $metadata['description'] = $properties->getDescription();
        }

        if ($properties->getSubject() !== null && $properties->getSubject() !== '') {
            $metadata['subject'] = $properties->getSubject();
        }

        if ($properties->getKeywords() !== null && $properties->getKeywords() !== '') {
            $metadata['keywords'] = $properties->getKeywords();
        }

        if ($properties->getCategory() !== null && $properties->getCategory() !== '') {
            $metadata['category'] = $properties->getCategory();
        }

        // Count worksheets and cells.
        $worksheets = $spreadsheet->getAllSheets();
        $metadata['worksheets'] = count($worksheets);

        $cellCount         = 0;
        $nonEmptyCellCount = 0;

        foreach ($worksheets as $worksheet) {
            $highestRow         = $worksheet->getHighestRow();
            $highestColumn      = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $cellCount += $highestRow * $highestColumnIndex;

            // Count non-empty cells.
            for ($row = 1; $row <= $highestRow; $row++) {
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellValue = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                    if (empty($cellValue) === false) {
                        $nonEmptyCellCount++;
                    }
                }
            }
        }

        $metadata['total_cells']     = $cellCount;
        $metadata['non_empty_cells'] = $nonEmptyCellCount;

        return $metadata;

    }//end extractSpreadsheetMetadata()


    /**
     * Extract metadata from a presentation
     *
     * @param string $filePath Path to the presentation file
     *
     * @return array<string, mixed> Presentation metadata
     *
     * @throws Exception If presentation parsing fails
     *
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    private function extractPresentationMetadata(string $filePath): array
    {
        $presentation = PresentationIOFactory::load($filePath);
        $properties   = $presentation->getDocumentProperties();

        $metadata = [];

        // Extract document properties.
        if ($properties->getCreator() !== null && $properties->getCreator() !== '') {
            $metadata['creator'] = $properties->getCreator();
        }

        if ($properties->getLastModifiedBy() !== null && $properties->getLastModifiedBy() !== '') {
            $metadata['last_modified_by'] = $properties->getLastModifiedBy();
        }

        if ($properties->getCreated() !== null) {
            $metadata['created'] = $properties->getCreated();
        }

        if ($properties->getModified() !== null) {
            $metadata['modified'] = $properties->getModified();
        }

        if ($properties->getTitle() !== null && $properties->getTitle() !== '') {
            $metadata['title'] = $properties->getTitle();
        }

        if ($properties->getDescription() !== null && $properties->getDescription() !== '') {
            $metadata['description'] = $properties->getDescription();
        }

        if ($properties->getSubject() !== null && $properties->getSubject() !== '') {
            $metadata['subject'] = $properties->getSubject();
        }

        if ($properties->getKeywords() !== null && $properties->getKeywords() !== '') {
            $metadata['keywords'] = $properties->getKeywords();
        }

        if ($properties->getCategory() !== null && $properties->getCategory() !== '') {
            $metadata['category'] = $properties->getCategory();
        }

        // Count slides and shapes.
        $slideCount         = $presentation->getSlideCount();
        $metadata['slides'] = $slideCount;

        $shapeCount = 0;
        for ($i = 0; $i < $slideCount; $i++) {
            $slide       = $presentation->getSlide($i);
            $shapeCount += count($slide->getShapeCollection());
        }

        $metadata['shapes'] = $shapeCount;

        return $metadata;

    }//end extractPresentationMetadata()


}//end class
