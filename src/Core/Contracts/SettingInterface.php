<?php

namespace Ivy\Core\Contracts;

use Ivy\Model\Setting;

interface SettingInterface {
    public function handle(Setting $setting, bool $bool): void;
}