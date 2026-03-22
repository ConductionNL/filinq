<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Service;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
/**
 * Service for consolidating entities across batch files
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class EntityConsolidationService
{
    /**
     * @param LoggerInterface    $logger       Logger
     * @param WooProfileService  $wooProfile   WOO profile service
     * @param IAppManager        $appManager   App manager
     * @param ContainerInterface $container    DI container
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WooProfileService $wooProfile,
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container
    ) {
    }//end __construct()
    /**
     * @param array<string, mixed> $batch         Batch state
     * @param float                $minConfidence Threshold
     * @return array<int, array<string, mixed>>
     */
    public function consolidateEntities(array $batch, float $minConfidence=0.0): array
    {
        $entityMap = $this->buildEntityMap(batch: $batch);
        $entityMap = $this->applyThreshold(entityMap: $entityMap, minConfidence: $minConfidence);
        $result = array_values($entityMap);
        usort(
            $result,
            static function (array $a, array $b): int {
                return $b['highestConfidence'] <=> $a['highestConfidence'];
            }
        );
        return $result;
    }//end consolidateEntities()
    /**
     * @param array<string, mixed> $batch Batch state
     * @return array<string, array<string, mixed>>
     */
    private function buildEntityMap(array $batch): array
    {
        $entityMap = [];
        foreach ($batch['files'] as $file) {
            if ($file['status'] !== 'extracted') {
                continue;
            }
            $fileEntities = $this->getEntitiesForFile(fileId: (int) $file['fileId']);
            foreach ($fileEntities as $entity) {
                $entityMap = $this->mergeEntity(entityMap: $entityMap, entity: $entity);
            }
        }
        return $entityMap;
    }//end buildEntityMap()
    /**
     * @param array<string, array<string, mixed>> $entityMap Map
     * @param mixed                               $entity    Entity
     * @return array<string, array<string, mixed>>
     */
    private function mergeEntity(array $entityMap, mixed $entity): array
    {
        $entityData = (array) $entity;
        if (is_object($entity) === true && method_exists($entity, 'jsonSerialize') === true) {
            $entityData = $entity->jsonSerialize();
        }
        $type       = $entityData['entity_type'] ?? $entityData['entityType'] ?? 'UNKNOWN';
        $value      = $entityData['entity_value'] ?? $entityData['entityValue'] ?? '';
        $confidence = (float) ($entityData['confidence'] ?? 0.0);
        $key        = mb_strtolower((string) $value);
        if ($key === '') {
            return $entityMap;
        }
        if (isset($entityMap[$key]) === true) {
            $entityMap[$key]['fileCount']++;
            if ($confidence > $entityMap[$key]['highestConfidence']) {
                $entityMap[$key]['highestConfidence'] = $confidence;
            }
            return $entityMap;
        }
        $entityMap[$key] = [
            'type'              => $type,
            'value'             => $value,
            'highestConfidence' => $confidence,
            'fileCount'         => 1,
            'included'          => $this->wooProfile->shouldAnonymize(entityType: (string) $type),
        ];
        return $entityMap;
    }//end mergeEntity()
    /**
     * @param array<string, array<string, mixed>> $entityMap     Map
     * @param float                               $minConfidence Threshold
     * @return array<string, array<string, mixed>>
     */
    private function applyThreshold(array $entityMap, float $minConfidence): array
    {
        foreach ($entityMap as $key => $entity) {
            if ($entity['highestConfidence'] < $minConfidence) {
                $entityMap[$key]['included'] = false;
            }
        }
        return $entityMap;
    }//end applyThreshold()
    /**
     * @param int $fileId File ID
     * @return array<mixed>
     */
    private function getEntitiesForFile(int $fileId): array
    {
        try {
            if (in_array('openregister', $this->appManager->getInstalledApps(), true) === false) {
                throw new RuntimeException('OpenRegister is not available.');
            }
            $mapper = $this->container->get('OCA\OpenRegister\Db\EntityRelationMapper');
            return $mapper->findEntitiesForFile($fileId);
        } catch (RuntimeException $e) {
            $this->logger->warning('Could not get entities: '.$e->getMessage(), ['fileId' => $fileId]);
            return [];
        }
    }//end getEntitiesForFile()
}//end class
