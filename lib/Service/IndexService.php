<?php

namespace OCA\DocuDesk\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use OCA\OpenRegister\Db\ObjectEntity;
use OCP\IAppConfig;

/**
 * Service to index files to an search engine (Apache SOLR)
 */
class IndexService
{
    public function __construct(
        private readonly IAppConfig $config,
    )
    {

    }

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

    public function indexObject(ObjectEntity $object): void
    {
        $register = $this->config->getValueString(app: 'docudesk',  key:'report_register');
        $schema = $this->config->getValueString(app: 'docudesk', key: 'report_schema');

        if ($register !== $object->getRegister() || $schema !== $object->getSchema()) {
            var_dump($register, $schema);
            return;
        }

        $indexing = $this->config->getValueBool(app: 'docudesk', key: 'indexing_enabled');
        $solrUrl = $this->config->getValueString(app:'docudesk', key: 'solr_url');
        $solrCollection = $this->config->getValueString(app:'docudesk', key: 'solr_collection');

        if ($indexing === false || $solrUrl === '' || $solrCollection === '') {
            return;
        }

        $client = new Client([
            'base_uri' => $solrUrl,
        ]);

        $data = $object->jsonSerialize();

        $this->checkParamSets($client, $solrCollection);

        if(isset($data['text']) === false) {
            return;
        }

        $this->deleteDocument($data['nodeId']);

        $response = $client->post(uri: "/api/collections/$solrCollection/update/json?useParams=docudesk", options: ['json' => $data, 'headers' => ['Content-Type' => 'application/json']]);
        
    }

    public function deleteDocument(int $nodeId): void
    {

        $solrUrl = $this->config->getValueString(app:'docudesk', key: 'solr_url');
        $solrCollection = $this->config->getValueString(app:'docudesk', key: 'solr_collection');

        if ($solrUrl === '' || $solrCollection === '') {
            return;
        }

        $client = new Client([
            'base_uri' => $solrUrl,
        ]);


        $client->post("/solr/$solrCollection/update?commit=true", options: ['headers' => ['Content-Type' => 'application/json'], 'json' => ['delete' => ['query' => "nodeId:{$nodeId}"]]]);
    }
}
