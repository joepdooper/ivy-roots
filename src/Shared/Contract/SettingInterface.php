<?php

namespace Ivy\Shared\Contract;

use Ivy\Domain\Entity\SettingEntity;

interface SettingInterface {
    public function handle(SettingEntity $setting, bool $bool): void;
}
