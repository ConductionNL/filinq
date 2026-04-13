<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Service;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
/**
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 */
class EntityConsolidationService
{


    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WooProfileService $wooProfile,
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container,
    ) {

    }//end __construct()


    public function consolidateEntities(array $batch, float $minConfidence=0.0): array
    {
        $map = [];
        foreach ($batch['files'] as $file) {
            if ($file['status'] !== 'extracted') {
                continue;
            }

            foreach ($this->getEntitiesForFile((int) $file['fileId']) as $entity) {
                $map = $this->mergeEntity($map, $entity);
            }
        }

        foreach ($map as $k => $e) {
            if ($e['highestConfidence'] < $minConfidence) {
                $map[$k]['included'] = false;
            }
        }

        $result = array_values($map);
        usort($result, static fn($a, $b) => $b['highestConfidence'] <=> $a['highestConfidence']);
        return $result;

    }//end consolidateEntities()


    private function mergeEntity(array $map, mixed $entity): array
    {
        if (is_object($entity) === true && method_exists($entity, 'jsonSerialize') === true) {
            $d = $entity->jsonSerialize();
        } else {
            $d = (array) $entity;
        }

        $type  = $d['entity_type'] ?? $d['entityType'] ?? 'UNKNOWN';
        $value = $d['entity_value'] ?? $d['entityValue'] ?? '';
        $conf  = (float) ($d['confidence'] ?? 0.0);
        $key   = mb_strtolower((string) $value);
        if ($key === '') {
            return $map;
        }

        if (isset($map[$key]) === true) {
            $map[$key]['fileCount']++;
            if ($conf > $map[$key]['highestConfidence']) {
                $map[$key]['highestConfidence'] = $conf;
            }
        } else {
            $map[$key] = [
                'type'              => $type,
                'value'             => $value,
                'highestConfidence' => $conf,
                'fileCount'         => 1,
                'included'          => $this->wooProfile->shouldAnonymize((string) $type),
            ];
        }

        return $map;

    }//end mergeEntity()


    private function getEntitiesForFile(int $fileId): array
    {
        try {
            if (in_array('openregister', $this->appManager->getInstalledApps(), true) === false) {
                throw new RuntimeException('OpenRegister not available');
            }

            return $this->container->get('OCA\\OpenRegister\\Db\\EntityRelationMapper')->findEntitiesForFile($fileId);
        } catch (RuntimeException $e) {
            $this->logger->warning('Could not get entities: '.$e->getMessage(), ['fileId' => $fileId]);
            return [];
        }

    }//end getEntitiesForFile()


}//end class
