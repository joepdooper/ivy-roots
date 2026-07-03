<?php

namespace Ivy\Setting\Domain\Handler;

use Ivy\Setting\Domain\Entity\Setting;

interface SettingHandler {
    public function handle(Setting $setting, bool|int $bool): void;
}
