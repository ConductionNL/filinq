<?php

namespace OCA\DocuDesk\Search;

use OCA\DocuDesk\AppInfo\Application;
use OCA\DocuDesk\Service\IndexService;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class DocumentSearchProvider implements IProvider
{

    public function __construct(
        private readonly IndexService $indexService,
    ) {

    }
    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return Application::APP_ID;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'Docudesk Document Search';
    }

    /**
     * @inheritDoc
     */
    public function getOrder(string $route, array $routeParameters): ?int
    {
        if (str_contains($route, Application::APP_ID)) {
            // Active app, prefer my results
            return -1;
        }

        return 55;
    }

    /**
     * @inheritDoc
     */
    public function search(IUser $user, ISearchQuery $query): SearchResult
    {
        $entries = $this->indexService->searchDocuments($query->getTerm());


        //var_dump($query->getTerm());
        return SearchResult::complete('DocuDesk', $entries);
    }
}
