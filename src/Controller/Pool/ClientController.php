<?php

namespace App\Controller\Pool;

use App\Entity\Autocomplete;
use App\Entity\Compile;
use App\Entity\Enum\Status;
use App\Message\AutocompleteMessage;
use App\Message\CompileMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/pool/client', name: 'app_pool_client_')]
class ClientController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private MessageBusInterface $bus,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/autocomplete', name: 'queue_autocomplete', methods: ['POST'])]
    public function autocomplete(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $constraints = new Assert\Collection([
            'package' => [],
            'nonce' => [
                new Assert\NotBlank()
            ],
            'site' => [
                new Assert\NotBlank(),
                new Assert\Url()
            ],
            'config' => [
                new Assert\NotBlank(),
                new Assert\Callback(function ($object, ExecutionContextInterface $context, $payload) {
                    if (strpos($object, 'module.exports') === false) {
                        return $context->buildViolation('The tailwind config preset doesn\'t have "module.exports".')
                            ->addViolation();
                    }
                }),
            ],
        ]);

        $errors = $this->validator->validate($data, $constraints);

        if (count($errors) > 0) {
            $messages = ['message' => 'validation_failed', 'errors' => []];

            /** @var \Symfony\Component\Validator\ConstraintViolationInterface $message */
            foreach ($errors as $message) {
                $messages['errors'][] = [
                    'property' => $message->getPropertyPath(),
                    'value' => $message->getInvalidValue(),
                    'message' => $message->getMessage(),
                ];
            }

            return $this->json($messages, Response::HTTP_BAD_REQUEST);
        }

        $entityManager = $this->doctrine->getManager();

        $autocomplete = new Autocomplete();
        $autocomplete->setConfig($data['config']);
        $autocomplete->setNonce($data['nonce']);
        $autocomplete->setSite($data['site']);

        if (isset($data['package'])) {
            $autocomplete->setPackage($data['package']);
        }

        $entityManager->persist($autocomplete);
        $entityManager->flush();

        $this->bus->dispatch(new AutocompleteMessage($autocomplete->getUuid()));

        return $this->json([
            'entity' => $autocomplete,
        ], Response::HTTP_OK, [], [
            'groups' => ['autocomplete:read']
        ]);
    }

    #[Route('/compile', name: 'queue_compile', methods: ['POST'])]
    public function compile(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $constraints = new Assert\Collection([
            'package' => [],
            'nonce' => [
                new Assert\NotBlank()
            ],
            'site' => [
                new Assert\NotBlank(),
                new Assert\Url()
            ],
            'css' => [
                new Assert\NotBlank(),
            ],
            'content' => [
                new Assert\NotBlank()
            ],
            'version' => [
                new Assert\NotBlank(),
                new Assert\Callback(function ($object, ExecutionContextInterface $context, $payload) {
                    if (version_compare($object, '3.0.0', '<')) {
                        return $context->buildViolation('The tailwind version supported is 3.x')
                            ->addViolation();
                    }
                }),
            ],
            'config' => [
                new Assert\NotBlank(),
                new Assert\Callback(function ($object, ExecutionContextInterface $context, $payload) {
                    if (strpos($object, 'module.exports') === false) {
                        return $context->buildViolation('The tailwind config preset doesn\'t have "module.exports".')
                            ->addViolation();
                    }
                }),
            ],
        ]);

        $errors = $this->validator->validate($data, $constraints);

        if (count($errors) > 0) {
            /** @var \Symfony\Component\Validator\ConstraintViolationInterface $message */
            foreach ($errors as $message) {
                $messages['errors'][] = [
                    'property' => $message->getPropertyPath(),
                    'value' => $message->getInvalidValue(),
                    'message' => $message->getMessage(),
                ];
            }

            return $this->json($messages, Response::HTTP_BAD_REQUEST);
        }

        $entityManager = $this->doctrine->getManager();

        $compile = new Compile();
        $compile->setConfig($data['config']);
        $compile->setNonce($data['nonce']);
        $compile->setSite($data['site']);
        $compile->setCss($data['css']);
        $compile->setContent($data['content']);
        $compile->setVersion($data['version']);

        if (isset($data['package'])) {
            $compile->setPackage($data['package']);
        }

        $entityManager->persist($compile);
        $entityManager->flush();

        $this->bus->dispatch(new CompileMessage($compile->getUuid()));

        return $this->json([
            'entity' => $compile,
        ], Response::HTTP_OK, [], [
            'groups' => ['compile:read']
        ]);
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        /** @var ServiceEntityRepository $autocompleteRepository */
        $autocompleteRepository = $this->doctrine->getRepository(Autocomplete::class);
        /** @var ServiceEntityRepository $compileRepository */
        $compileRepository = $this->doctrine->getRepository(Compile::class);

        return $this->json([
            'autocomplete' => [
                'pending' => $autocompleteRepository->count(['status' => Status::Pending]),
                'processing' => $autocompleteRepository->count(['status' => Status::Processing]),
                'done' => $autocompleteRepository->count(['status' => Status::Done]),
            ],
            'compile' => [
                'pending' => $compileRepository->count(['status' => Status::Pending]),
                'processing' => $compileRepository->count(['status' => Status::Processing]),
                'done' => $compileRepository->count(['status' => Status::Done]),
            ],
        ], Response::HTTP_OK, [], [
            'groups' => ['autocomplete:read', 'compile:read']
        ]);
    }

    #[Route('/jobs', name: 'jobs', methods: ['POST'])]
    public function jobs(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $constraints = new Assert\Sequentially([
            new Assert\Collection([
                'nonce' => [
                    new Assert\NotBlank(),
                ],
                'site' => [
                    new Assert\NotBlank(),
                    new Assert\Url(),
                ],
                'uuid' => [
                    new Assert\NotBlank(),
                    new Assert\Uuid(),
                ],
                'type' => [
                    new Assert\NotBlank(),
                    new Assert\Choice([
                        'choices' => ['autocomplete', 'compile'],
                    ]),
                ],
            ]),
            new Assert\Collection([
                'uuid' => [
                    new Assert\Callback(function ($object, ExecutionContextInterface $context, $payload) {
                        $data = $context->getRoot();

                        $type = match ($data['type']) {
                            'autocomplete' => Autocomplete::class,
                            'compile' => Compile::class,
                        };

                        $repository = $this->doctrine->getRepository($type);

                        $entity = $repository->findOneBy([
                            'uuid' => $data['uuid'],
                            'nonce' => $data['nonce'],
                            'site' => $data['site'],
                        ]);

                        if (!$entity) {
                            return $context->buildViolation('The uuid doesn\'t exist.')
                                ->addViolation();
                        }
                    }),
                ],
            ], allowExtraFields: true),
        ]);

        $errors = $this->validator->validate($data, $constraints);

        if (count($errors) > 0) {
            $messages = ['message' => 'validation_failed', 'errors' => []];

            /** @var \Symfony\Component\Validator\ConstraintViolationInterface $message */
            foreach ($errors as $message) {
                $messages['errors'][] = [
                    'property' => $message->getPropertyPath(),
                    'value' => $message->getInvalidValue(),
                    'message' => $message->getMessage(),
                ];
            }

            return $this->json($messages, Response::HTTP_BAD_REQUEST);
        }

        $type = match ($data['type']) {
            'autocomplete' => Autocomplete::class,
            'compile' => Compile::class,
        };

        $repository = $this->doctrine->getRepository($type);

        $entity = $repository->findOneBy([
            'uuid' => $data['uuid'],
            'nonce' => $data['nonce'],
            'site' => $data['site'],
        ]);

        return $this->json([
            'data' => $entity,
        ], Response::HTTP_OK, [], [
            'groups' => ['autocomplete:read', 'compile:read', 'run:read']
        ]);
    }

    #[Route('/log', name: 'log', methods: ['GET', 'POST'])]
    public function log(Request $request, Filesystem $filesystem, HttpClientInterface $githubClient): Response
    {
        $storageDir = $this->getParameter('app.local_storage_dir');

        $data = [];

        if ($request->getMethod() === 'GET') {
            $data = $request->query->all();
        } elseif ($request->getMethod() === 'POST') {
            $data = $request->toArray();
        }

        /** @var null|Autocomplete|Compile $entity */
        $entity = null;

        $constraints = new Assert\Sequentially([
            new Assert\Collection([
                'nonce' => [
                    new Assert\NotBlank(),
                ],
                'site' => [
                    new Assert\NotBlank(),
                    new Assert\Url(),
                ],
                'uuid' => [
                    new Assert\NotBlank(),
                    new Assert\Uuid(),
                ],
                'type' => [
                    new Assert\NotBlank(),
                    new Assert\Choice([
                        'choices' => ['autocomplete', 'compile'],
                    ]),
                ],
            ]),
            new Assert\Collection([
                'uuid' => [
                    new Assert\Callback(function ($object, ExecutionContextInterface $context, $payload) use (&$entity) {
                        $data = $context->getRoot();

                        $type = match ($data['type']) {
                            'autocomplete' => Autocomplete::class,
                            'compile' => Compile::class,
                        };

                        $repository = $this->doctrine->getRepository($type);

                        /** @var null|Autocomplete|Compile $entity */
                        $entity = $repository->findOneBy([
                            'uuid' => $data['uuid'],
                            'nonce' => $data['nonce'],
                            'site' => $data['site'],
                        ]);

                        if (!$entity) {
                            return $context->buildViolation('The uuid doesn\'t exist.')
                                ->addViolation();
                        }

                        if (!$entity->getRun()) {
                            return $context->buildViolation('The job not available.')
                                ->addViolation();
                        }
                    }),
                ],
            ], allowExtraFields: true),
        ]);

        $errors = $this->validator->validate($data, $constraints);

        if (count($errors) > 0) {
            $messages = ['message' => 'validation_failed', 'errors' => []];

            /** @var \Symfony\Component\Validator\ConstraintViolationInterface $message */
            foreach ($errors as $message) {
                $messages['errors'][] = [
                    'property' => $message->getPropertyPath(),
                    'value' => $message->getInvalidValue(),
                    'message' => $message->getMessage(),
                ];
            }

            return $this->json($messages, Response::HTTP_BAD_REQUEST);
        }

        $filesystem->mkdir($storageDir);

        $filePath = $storageDir . '/' . $entity->getRun()->getId() . '.zip';

        if (!$filesystem->exists($filePath)) {
            $repos = $this->getParameter('app.worker.repos');

            $endpoint = sprintf(
                '/repos/%s/%s/actions/runs/%s/logs',
                $repos['owner'],
                $repos['repo'],
                $entity->getRun()->getId()
            );
            $response = $githubClient->request('GET', $endpoint);

            if (Response::HTTP_OK !== $response->getStatusCode()) {
                return $this->json([
                    'message' => 'Failed to fetch the log.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $fileHandler = fopen($filePath, 'w');
            foreach ($githubClient->stream($response) as $chunk) {
                fwrite($fileHandler, $chunk->getContent());
            }
        }

        return $this->file($filePath);
    }
}
