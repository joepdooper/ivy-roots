<?php

namespace Ivy\Shared\Domain\Enum;

enum Status: string
{
    case PENDING = 'pending';
    case DOWNLOADING = 'downloading';
    case DOWNLOADED = 'downloaded';
    case INSTALLING = 'installing';
    case INSTALLED = 'installed';
    case ACTIVATING = 'activating';
    case ACTIVE = 'active';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
}

