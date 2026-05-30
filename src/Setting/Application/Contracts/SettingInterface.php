<?php

namespace Ivy\Setting\Application\Contracts;

use Ivy\Setting\Domain\Entity\Setting;

interface SettingInterface {
    public function handle(Setting $setting, bool $bool): void;
}
