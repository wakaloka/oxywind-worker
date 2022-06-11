<?php

namespace App\Message;

use Symfony\Component\Uid\Uuid;

final class FailureSignalMessage
{
    public function __construct(
        private Uuid $uuid,
        private string $entity,
        private int $run_id,
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getRunId(): int
    {
        return $this->run_id;
    }
}
