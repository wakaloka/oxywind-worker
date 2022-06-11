<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class Workflow
{
    public function __construct(
        private HttpClientInterface $githubClient,
        private string $token,
        private array $repos
    ) {
    }

    /**
     * @param int|string $workflowId The ID of the workflow. You can also pass the workflow file name as a string.
     */
    public function dispatch($workflowId, array $inputs = []): int
    {
        $endpoint = sprintf(
            '/repos/%s/%s/actions/workflows/%s/dispatches',
            $this->repos['owner'],
            $this->repos['repo'],
            $workflowId
        );

        $response = $this->githubClient->request('POST', $endpoint, [
            'json' => [
                'ref' => $this->repos['ref'],
                'inputs' => [
                    ...$inputs,
                    'worker_token' => $this->token,
                ],
            ],
        ]);

        return $response->getStatusCode();
    }
}
