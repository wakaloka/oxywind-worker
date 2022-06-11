<?php

namespace App\MessageHandler;

use App\Entity\Autocomplete;
use App\Entity\Enum\Status;
use App\Message\AutocompleteMessage;
use App\Repository\AutocompleteRepository;
use App\Service\Workflow;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
final class AutocompleteMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private Workflow $workflow,
        private AutocompleteRepository $autocompleteRepository,
        private UrlGeneratorInterface $router
    ) {
    }

    public function __invoke(AutocompleteMessage $message)
    {
        $workflow = $this->workflow->dispatch('autocomplete.yaml', [
            'uuid' => $message->getUuid(),
            'entity' => Autocomplete::class,
            'route_pull' => $this->router->generate('app_pool_worker_pull', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'route_push' => $this->router->generate('app_pool_worker_push', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $autocomplete = $this->autocompleteRepository->findOneByUuid($message->getUuid());

        switch ($workflow) {
            case Response::HTTP_NO_CONTENT:
                $autocomplete->setStatus(Status::Processing);
                break;

            case Response::HTTP_FORBIDDEN:
                throw new Exception('GitHub REST API hard limit reached');
                break;

            default:
                $autocomplete->setStatus(Status::Failed);
                throw new Exception('Uncatched Workflow HTTP status code: ' . $workflow);
                break;
        }

        $this->doctrine->getManager()->flush();
    }
}
