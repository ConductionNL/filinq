<?php

namespace OCA\docudesk\lib\Service;

use GuzzleHttp\Client;
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
        $response = $client->get(uri:"/api/collections/$collection/config/params", options: ['headers' => ['Accept' => 'application/json']]);
        $responseBody = json_decode(json: $response->getBody()->getContents(), associative:  true);

        $params = $responseBody['response']['params'];

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
            $client->post(uri:"/api/collections/$collection/config/params", options: ['headers' => ['Content-Type' => 'application/json'], 'json' => $postBody]);
        }
    }

    public function indexObject(ObjectEntity $object): void
    {
        $register = $this->config->getValueInt(app: 'docudesk',  key:'report_register');
        $schema = $this->config->getValueInt(app: 'docudesk', key: 'report_schema');

        if ($register !== $object->getRegister() || $schema !== $object->getSchema()) {
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

        $client->post(uri: "/api/collections/$solrCollection/update/json?useParams=docudesk", options: ['json' => $data, 'headers' => ['Content-Type' => 'application/json']]);

    }
}
