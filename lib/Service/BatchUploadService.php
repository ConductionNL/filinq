<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Service;
use Exception;
use OCP\IRequest;
/**
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 */
class BatchUploadService
{


    public function __construct(private readonly FileUploadService $uploadService, private readonly BatchStateService $stateService)
    {

    }//end __construct()


    public function getUserId(): string
    {
        return $this->uploadService->getCurrentUserId();

    }//end getUserId()


    public function collectFiles(IRequest $request): array
    {
        $files = [];
        $index = 0;
        while (true) {
            $file = $request->getUploadedFile('files'.$index);
            if (empty($file) || !isset($file['tmp_name'])) {
                if ($index === 0) {
                    $arr = $request->getUploadedFile('files');
                    if (!empty($arr) && is_array($arr['tmp_name'])) {
                        for ($i = 0; $i < count($arr['tmp_name']); $i++) {
                            $files[] = ['name' => $arr['name'][$i], 'tmp_name' => $arr['tmp_name'][$i], 'error' => $arr['error'][$i]];
                        }
                    }
                }

                break;
            }

            $files[] = $file;
            $index++;
        }

        return $files;

    }//end collectFiles()


    public function processBatchUpload(string $userId, array $files): array
    {
        $batchFiles = [];
        foreach ($files as $uploaded) {
            $base = ['fileId' => null, 'fileName' => $uploaded['name'], 'status' => 'error', 'entityCount' => 0, 'replacementCount' => 0, 'error' => null];
            if ($uploaded['error'] !== UPLOAD_ERR_OK) {
                $base['error'] = 'Upload error: '.$uploaded['error'];
                $batchFiles[]  = $base;
                continue;
            }

            $content = file_get_contents($uploaded['tmp_name']);
            if ($content === false) {
                $base['error'] = 'Failed to read file';
                $batchFiles[]  = $base;
                continue;
            }

            $result       = $this->uploadService->uploadFile($uploaded['name'], $content);
            $batchFiles[] = ['fileId' => $result['fileId'], 'fileName' => $result['fileName'], 'status' => 'uploaded', 'entityCount' => 0, 'replacementCount' => 0, 'error' => null];
        }

        return $this->stateService->createBatch($userId, $batchFiles);

    }//end processBatchUpload()


}//end class
