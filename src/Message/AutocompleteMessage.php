<?php

namespace App\Message;

use Symfony\Component\Uid\Uuid;

final class AutocompleteMessage implements AsyncMessageInterface
{
    public function __construct(
        private Uuid $uuid
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }
}
