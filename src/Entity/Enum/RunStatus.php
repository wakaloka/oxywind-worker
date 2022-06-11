<?php

namespace App\Entity\Enum;

enum RunStatus: string
{
    case Completed = 'completed';
    case ActionRequired = 'action_required';
    case Cancelled = 'cancelled';
    case Failure = 'failure';
    case Neutral = 'neutral';
    case Skipped = 'skipped';
    case Stale = 'stale';
    case Success = 'success';
    case TimedOut = 'timed_out';
    case InProgress = 'in_progress';
    case Queued = 'queued';
    case Requested = 'requested';
    case Waiting = 'waiting';
}
