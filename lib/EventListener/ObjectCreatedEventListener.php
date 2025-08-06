<?php

namespace OCA\OpenConnector\EventListener;

use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCP\Files\Events\Node\AbstractNodeEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeTouchedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\FileInfo;
use Psr\Log\LoggerInterface;

class ObjectCreatedEventListener implements IEventListener
{

	public function __construct(
		private readonly SynchronizationService $synchronizationService,
        private readonly LoggerInterface $logger,
	)
	{
	}

	/**
     * @inheritDoc
     */
    public function handle(Event $event): void
    {


        try {
            match (true) {
                $event instanceof ObjectCreatedEvent => $this->handleObjectCreated($event),
                $event instanceof ObjectUpdatedEvent => $this->handleObjectUpdated($event),
                $event instanceof ObjectDeletedEvent => $this->handleObjectDeleted($event),
                default => throw new InvalidArgumentException('Unsupported event type: '.get_class($event)),
            };
        } catch (\Exception $e) {

        }

    }//end handle()

    private function handleObjectCreated(ObjectCreatedEvent $event): void
    {
        $object = $event->getObject();
    }

    private function handleObjectUpdated(ObjectCreatedEvent $event): void
    {
        $object = $event->getObject();

    }

    private function handleObjectDeleted(ObjectDeletedEvent $event): void
    {
        $object = $event->getObject();

    }
}
