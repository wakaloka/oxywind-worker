<?php

namespace App\Entity\Enum;

enum Status: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Done = 'done';
    case Failed = 'failed';
}
