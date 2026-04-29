<?php

namespace Ivy\Handler;

use Ivy\Config\Environment;
use Ivy\Core\Contracts\SettingInterface;
use Ivy\Core\Path;
use Ivy\Manager\AssetManager;
use Ivy\Model\Setting;
use MatthiasMullie\Minify\JS;

class MinifyJsHandler implements SettingInterface
{
    private $minifiedJsPath = '/js/minified.js';

    public function handle(Setting $setting, bool $bool): void
    {
        if ($bool && Environment::isProd()) {
            $minifier = new JS;
            foreach (AssetManager::getJs() as $js) {
                $minifier->add(Path::get('PUBLIC_PATH').ltrim($js, '/'));
            }
            $minifier->minify(Path::get('PUBLIC_PATH').$this->minifiedJsPath);
        } elseif (file_exists(Path::get('PUBLIC_PATH').$this->minifiedJsPath)) {
            unlink(Path::get('PUBLIC_PATH').$this->minifiedJsPath);
        }

        return;
    }
}