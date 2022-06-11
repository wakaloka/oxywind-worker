<?php

namespace App\MessageHandler;

use App\Entity\Autocomplete;
use App\Entity\Compile;
use App\Message\FailureSignalMessage;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
final class FailureSignalMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private HttpClientInterface $client,
    ) {
    }

    public function __invoke(FailureSignalMessage $message)
    {
        $repository = $this->doctrine->getRepository($message->getEntity());

        /** @var Autocomplete|Compile $entity */
        $entity = $repository->findOneBy(['uuid' => $message->getUuid()]);

        switch ($message->getEntity()) {
            case Autocomplete::class:
                $action = 'autocomplete';
                break;
            case Compile::class:
                $action = 'compile';
                break;
            default:
                $action = 'unknown';
                break;
        }

        $response = $this->client->request('POST', $entity->getSite(), [
            'json' => [
                'uuid' => $message->getUuid()->__toString(),
                'action' => $action,
                'status' => 'failed',
            ],
            'headers' => [
                'Worker-Nonce' => $entity->getNonce(),
            ],
        ]);


        // $repos = $this->getParameter('app.worker.repos');

        // // TODO: move to message queue with 30s delay
        // $endpoint = sprintf(
        //     '/repos/%s/%s/actions/runs/%s/jobs',
        //     $repos['owner'],
        //     $repos['repo'],
        //     $entity->getRun()->getId()
        // );
        // $response = $githubClient->request('GET', $endpoint);

        // if (Response::HTTP_OK === $response->getStatusCode()) {
        //     $job = $response->toArray();

        //     $run->setJob($job);
        // }
    }
}
