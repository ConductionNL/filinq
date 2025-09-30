<?php
/**
 * FileEventListener
 *
 * Event listener for file-related node events
 *
 * @category  EventListener
 * @package   OCA\DocuDesk\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * DocuDesk is free software: you can redistribute it and/or modify
 * it under the terms of the European Union Public License (EUPL),
 * version 1.2 only (the "Licence"), appearing in the file LICENSE
 * included in the packaging of this file.
 *
 * DocuDesk is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * European Union Public License for more details.
 *
 * You should have received a copy of the European Union Public License
 * along with DocuDesk. If not, see <https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12>.
 */

declare(strict_types=1);

namespace OCA\DocuDesk\EventListener;

use InvalidArgumentException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\AbstractNodeEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeTouchedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\FileInfo;
use OCA\DocuDesk\Service\ReportingService;
use Psr\Log\LoggerInterface;

/**
 * Event listener for file-related node events
 *
 * This class listens for events related to file operations in Nextcloud
 * and handles them appropriately, including triggering report creation.
 *
 * @category EventListener
 * @package  OCA\DocuDesk\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://github.com/conductionnl/docudesk
 */
class FileEventListener implements IEventListener
{


    /**
     * Constructor for FileEventListener
     *
     * @param ReportingService $reportingService Service for generating reports
     * @param LoggerInterface  $logger           Logger for error reporting
     */
    public function __construct(
        private readonly ReportingService $reportingService,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()


    /**
     * Handle the event
     *
     * @param Event $event The event to handle
     *
     * @return void
     *
     * @throws InvalidArgumentException If the event type is unsupported
     */
    public function handle(Event $event): void
    {
        if (($event instanceof AbstractNodeEvent) === false) {
            return;
        }

        $node = $event->getNode();

        // Only process file events, not folder events.
        if ($node->getType() !== FileInfo::TYPE_FILE) {
            return;
        }

        try {
            match (true) {
                $event instanceof NodeCreatedEvent => $this->handleNodeCreated($event),
                $event instanceof NodeDeletedEvent => $this->handleNodeDeleted($event),
                $event instanceof NodeTouchedEvent => $this->handleNodeTouched($event),
                $event instanceof NodeWrittenEvent => $this->handleNodeWritten($event),
            default => throw new InvalidArgumentException('Unsupported event type: '.get_class($event)),
            };
        } catch (\Exception $e) {
            $this->logger->error(
                'Error handling file event: '.$e->getMessage(),
                [
                    'event'     => get_class($event),
                    'node_id'   => $node->getId(),
                    'exception' => $e,
                ]
            );
        }

    }//end handle()


    /**
     * Handle node created event
     *
     * @param NodeCreatedEvent $event The node created event
     *
     * @return void
     */
    private function handleNodeCreated(NodeCreatedEvent $event): void
    {
        $node = $event->getNode();
        
        // Skip reporting for anonymized files (ending with _anonymized)
        $fileName = $node->getName();
        $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
        
        if (str_ends_with($fileNameWithoutExtension, '_anonymized') === true) {
            $this->logger->debug(
                'Skipping report creation for anonymized file: '.$fileName,
                [
                    'node_id' => $node->getId(),
                    'path'    => $node->getPath(),
                ]
            );
            return;
        }
        
        $this->logger->debug(
            'File created: '.$fileName,
            [
                'node_id' => $node->getId(),
                'path'    => $node->getPath(),
            ]
        );

        // Always try to create a report, the ReportingService will check if reporting is enabled.
        $this->reportingService->createReport($node);

    }//end handleNodeCreated()


    /**
     * Handle node written event
     *
     * @param NodeWrittenEvent $event The node written event
     *
     * @return void
     */
    private function handleNodeWritten(NodeWrittenEvent $event): void
    {
        $node = $event->getNode();
        $this->logger->debug(
            'File written: '.$node->getName(),
            [
                'node_id' => $node->getId(),
                'path'    => $node->getPath(),
            ]
        );

    }//end handleNodeWritten()


    /**
     * Handle node deleted event
     *
     * @param NodeDeletedEvent $event The node deleted event
     *
     * @return void
     */
    private function handleNodeDeleted(NodeDeletedEvent $event): void
    {
        $node = $event->getNode();

        // No report creation needed for deleted files.
        $this->logger->debug(
            'File deleted: '.$node->getName(),
            [
                'node_id' => $node->getId(),
                'path'    => $node->getPath(),
            ]
        );

    }//end handleNodeDeleted()


    /**
     * Handle node touched event
     *
     * @param NodeTouchedEvent $event The node touched event
     *
     * @return void
     */
    private function handleNodeTouched(NodeTouchedEvent $event): void
    {
        $node = $event->getNode();

        // No report creation needed for touched files (metadata only changes).
        $this->logger->debug(
            'File touched: '.$node->getName(),
            [
                'node_id' => $node->getId(),
                'path'    => $node->getPath(),
            ]
        );

    }//end handleNodeTouched()


}//end class
