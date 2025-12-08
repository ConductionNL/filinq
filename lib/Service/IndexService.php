<?php

namespace OCA\DocuDesk\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use OCA\OpenRegister\Db\ObjectEntity;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Search\SearchResultEntry;
use OCP\SystemTag\ISystemTagManager;

/**
 * Service to index files to an search engine (Apache SOLR)
 */
class IndexService
{

    /**
     * File tag type identifier.
     *
     * @var        string
     * @readonly
     * @psalm-readonly
     */
    private const FILE_TAG_TYPE = 'files';

    public function __construct(
        private readonly IAppConfig $appConfig,
        private readonly IConfig $config,
        private readonly IRootFolder $rootFolder,
        private readonly IsystemTagObjectMapper $systemTagMapper,
    )
    {

    }

    /**
     * Check if the parameter set in Solr has been set up, and if not, set it up.
     *
     * @param Client $client The client connecting to Solr
     * @param string $collection The collection in Solr that should be connected to.
     *
     * @return void
     * @throws GuzzleException
     */
    private function checkParamSets (Client $client, string $collection): void
    {
        try {

            $response = $client->get(uri:"/api/collections/$collection/config/params", options: ['headers' => ['Accept' => 'application/json', 'http_errors' => false]]);
        } catch (GuzzleException $e) {
        }

        $responseBody = json_decode(json: $response->getBody()->getContents(), associative:  true);

        if(isset($responseBody['response']['params']) === true) {
            $params = $responseBody['response']['params'];
        } else {
            $params = [];
        }

        if (array_key_exists(key: 'docudesk', array: $params) === false) {
            // @TODO: Make this more dynamic
            $postBody = [
                'set' => [
                    'docudesk' => [
                        'f' => [
                            'nodeId:/nodeId',
                            'fileName:/fileName',
                            'text:/text',
                        ]
                    ]
                ]
            ];
            $response = $client->post(uri:"/api/collections/$collection/config/params", options: ['headers' => ['Content-Type' => 'application/json'], 'json' => $postBody]);

        }
    }

    /**
     * Get the tags for a file.
     *
     * @param string $fileId The id of the file to get the tags for.
     *
     * @return array The resulting list of tags
     */
    private function getFileTags(string $fileId): array
    {
        // @TODO: This method takes a file ID instead of a Node, so we can't check ownership here
        // @TODO: The ownership check should be done on the Node before calling this method

        $tagIds = $this->systemTagMapper->getTagIdsForObjects(
            objIds: [$fileId],
            objectType: $this::FILE_TAG_TYPE
        );
        if (isset($tagIds[$fileId]) === false || empty($tagIds[$fileId]) === true) {
            return [];
        }

        $tags = $this->systemTagManager->getTagsByIds(tagIds: $tagIds[$fileId]);

        $tagNames = array_map(static function ($tag) {
            return $tag->getName();
        }, $tags);

        return array_values($tagNames);
    }//end getFileTags()

    /**
     * Index an object into solr
     *
     * @param ObjectEntity $object The report to index
     * @return void
     *
     * @throws GuzzleException
     * @throws \OCP\Files\InvalidPathException
     * @throws \OCP\Files\NotFoundException
     */
    public function indexObject(ObjectEntity $object): void
    {
        $register = $this->appConfig->getValueString(app: 'docudesk',  key:'report_register');
        $schema = $this->appConfig->getValueString(app: 'docudesk', key: 'report_schema');

        if ($register !== $object->getRegister() || $schema !== $object->getSchema()) {
            return;
        }

        $indexing = $this->config->getSystemValueBool(key: 'docudesk_index_documents');
        $solrUrl = $this->config->getSystemValueString(key: 'docudesk_solr_url');
        $solrCollection = $this->config->getSystemValueString(key: 'docudesk_solr_collection');

        if ($indexing === false || $solrUrl === '' || $solrCollection === '') {
            return;
        }

        $client = new Client([
            'base_uri' => $solrUrl,
        ]);

        $data = $object->jsonSerialize();

        $file = $this->rootFolder->getFirstNodeById($data['nodeId']);
        $data['tags'] = $this->getFileTags($file->getId());

        $this->checkParamSets($client, $solrCollection);

        if(isset($data['text']) === false) {
            return;
        }

        $this->deleteDocument($data['nodeId']);

        $response = $client->post(uri: "/api/collections/$solrCollection/update/json?useParams=docudesk", options: ['json' => $data, 'headers' => ['Content-Type' => 'application/json']]);

    }

    /**
     * Delete documents from solr
     *
     * @param int $nodeId The nodeId of the documents to delete
     * @return void
     * @throws GuzzleException
     */
    public function deleteDocument(int $nodeId): void
    {

        $solrUrl = $this->config->getSystemValueString(key: 'docudesk_solr_url');
        $solrCollection = $this->config->getSystemValueString(key: 'docudesk_solr_collection');

        if ($solrUrl === '' || $solrCollection === '') {
            return;
        }

        $client = new Client([
            'base_uri' => $solrUrl,
        ]);


        $client->post("/solr/$solrCollection/update?commit=true", options: ['headers' => ['Content-Type' => 'application/json'], 'json' => ['delete' => ['query' => "nodeId:{$nodeId}"]]]);
    }

    /**
     * Search for documents on a term.
     *
     * @param string $term The term to look for
     * @return array The search result entries.
     * @throws GuzzleException
     */
    public function searchDocuments(string $term): array
    {
        $solrUrl = $this->config->getSystemValueString(key: 'docudesk_solr_url');
        $solrCollection = $this->config->getSystemValueString(key: 'docudesk_solr_collection');

        if ($solrUrl === '' || $solrCollection === '') {
            return [];
        }

        $client = new Client([
            'base_uri' => $solrUrl,
        ]);

        $faultTolerance = ceil(num:strlen(string: $term)/10);

        $result = $client->get("/solr/$solrCollection/query", [
            'query' => [
                'q' => 'text:'.$term .'~'.$faultTolerance,
            ]
        ]);

        $body = json_decode($result->getBody()->getContents(), associative: true);

        $results = [];
        foreach($body['response']['docs'] as $doc) {
            $results[] = new SearchResultEntry(
                thumbnailUrl: '/core/preview?x=32&y=32&fileId='.$doc['nodeId'],
                title: $doc['fileName'],
                subline: 'testing 1 2 3',
                resourceUrl: '/apps/files/files/'.$doc['nodeId'].'?openfile=true',
            );
        }

        return $results;
    }
}
