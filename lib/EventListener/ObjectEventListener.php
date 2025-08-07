<?php

namespace OCA\DocuDesk\EventListener;

use OCA\DocuDesk\Service\IndexService;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use Psr\Log\LoggerInterface;

class ObjectEventListener implements IEventListener
{

	public function __construct(
		private readonly IndexService $indexService,
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
        var_dump($object->getUuid());
        $this->indexService->indexObject($object);
    }

    private function handleObjectUpdated(ObjectUpdatedEvent $event): void
    {
        $object = $event->getNewObject();
        var_dump($object->getId());
        $this->indexService->indexObject($object);

    }

    private function handleObjectDeleted(ObjectDeletedEvent $event): void
    {
        $object = $event->getObject();

    }
}
