<?php

namespace App\MessageHandler;

use App\Entity\Compile;
use App\Entity\Enum\Status;
use App\Message\CompileMessage;
use App\Repository\CompileRepository;
use App\Service\Workflow;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
final class CompileMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private Workflow $workflow,
        private CompileRepository $compileRepository,
        private UrlGeneratorInterface $router
    ) {
    }

    public function __invoke(CompileMessage $message)
    {
        $workflow = $this->workflow->dispatch('compile.yaml', [
            'uuid' => $message->getUuid(),
            'entity' => Compile::class,
            'route_pull' => $this->router->generate('app_pool_worker_pull', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'route_push' => $this->router->generate('app_pool_worker_push', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $compile = $this->compileRepository->findOneByUuid($message->getUuid());

        switch ($workflow) {
            case Response::HTTP_NO_CONTENT:
                $compile->setStatus(Status::Processing);
                break;

            case Response::HTTP_FORBIDDEN:
                throw new Exception('GitHub REST API hard limit reached');
                break;

            default:
                $compile->setStatus(Status::Failed);
                throw new Exception('Uncatched Workflow HTTP status code: ' . $workflow);
                break;
        }

        $this->doctrine->getManager()->flush();
    }
}
