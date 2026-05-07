<?php

namespace Ivy\Shared\Contracts;

use Ivy\Domain\Model\SettingModel;

interface SettingInterface {
    public function handle(SettingModel $setting, bool $bool): void;
}
