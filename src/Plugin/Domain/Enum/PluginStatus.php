<?php

namespace Ivy\Plugin\Domain\Enum;

enum PluginStatus: string
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
