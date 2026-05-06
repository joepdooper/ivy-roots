<?php

namespace Ivy\Shared\Contract;

use Ivy\Domain\Entity\SettingEntity;

interface SettingInterface {
    public function handle(Setting $setting, bool $bool): void;
}
