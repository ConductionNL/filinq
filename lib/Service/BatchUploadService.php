<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Service;
use Exception;
use OCP\IRequest;
/**
 * Service for batch file upload operations
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class BatchUploadService
{
    /**
     * @param FileUploadService $uploadService Upload service
     * @param BatchStateService $stateService  State service
     */
    public function __construct(
        private readonly FileUploadService $uploadService,
        private readonly BatchStateService $stateService
    ) {
    }//end __construct()
    /**
     * @return string User ID
     * @throws Exception If not logged in
     */
    public function getUserId(): string
    {
        return $this->uploadService->getCurrentUserId();
    }//end getUserId()
    /**
     * @param IRequest $request HTTP request
     * @return array<int, array<string, mixed>>
     */
    public function collectFiles(IRequest $request): array
    {
        $files = [];
        $index = 0;
        while (true) {
            $file = $request->getUploadedFile('files'.$index);
            if (empty($file) === true || isset($file['tmp_name']) === false) {
                if ($index === 0) {
                    $files = $this->collectArrayFiles(request: $request);
                }
                break;
            }
            $files[] = $file;
            $index++;
        }
        return $files;
    }//end collectFiles()
    /**
     * @param IRequest $request HTTP request
     * @return array<int, array<string, mixed>>
     */
    private function collectArrayFiles(IRequest $request): array
    {
        $file = $request->getUploadedFile('files');
        if (empty($file) === true || is_array($file['tmp_name']) === false) {
            return [];
        }
        $result = [];
        $count  = count($file['tmp_name']);
        for ($i = 0; $i < $count; $i++) {
            $result[] = [
                'name'     => $file['name'][$i],
                'tmp_name' => $file['tmp_name'][$i],
                'error'    => $file['error'][$i],
            ];
        }
        return $result;
    }//end collectArrayFiles()
    /**
     * @param string                           $userId User ID
     * @param array<int, array<string, mixed>> $files  Uploaded files
     * @return array<string, mixed> Created batch
     * @throws Exception If fails
     */
    public function processBatchUpload(string $userId, array $files): array
    {
        $batchFiles = [];
        foreach ($files as $uploaded) {
            $batchFiles[] = $this->processOneFile(uploaded: $uploaded);
        }
        return $this->stateService->createBatch(userId: $userId, files: $batchFiles);
    }//end processBatchUpload()
    /**
     * @param array<string, mixed> $uploaded File data
     * @return array<string, mixed>
     */
    private function processOneFile(array $uploaded): array
    {
        $base = [
            'fileId' => null, 'fileName' => $uploaded['name'], 'status' => 'error',
            'entityCount' => 0, 'replacementCount' => 0, 'error' => null,
        ];
        if ($uploaded['error'] !== UPLOAD_ERR_OK) {
            $base['error'] = 'Upload failed with error code: '.$uploaded['error'];
            return $base;
        }
        $content = file_get_contents($uploaded['tmp_name']);
        if ($content === false) {
            $base['error'] = 'Failed to read uploaded file';
            return $base;
        }
        $result = $this->uploadService->uploadFile(fileName: $uploaded['name'], fileContent: $content);
        return [
            'fileId' => $result['fileId'], 'fileName' => $result['fileName'], 'status' => 'uploaded',
            'entityCount' => 0, 'replacementCount' => 0, 'error' => null,
        ];
    }//end processOneFile()
}//end class
