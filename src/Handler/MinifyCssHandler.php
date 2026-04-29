<?php

namespace Ivy\Handler;

use Ivy\Config\Environment;
use Ivy\Core\Contracts\SettingInterface;
use Ivy\Core\Path;
use Ivy\Manager\AssetManager;
use Ivy\Model\Setting;
use MatthiasMullie\Minify\CSS;

class MinifyCssHandler implements SettingInterface
{
    private $minifiedCssPath = '/css/minified.css';

    public function handle(Setting $setting, bool $bool): void
    {
        if ($bool && Environment::isProd()) {
            $minifier = new CSS;
            foreach (AssetManager::getCSS() as $css) {
                $minifier->add(Path::get('PUBLIC_PATH').ltrim($css, '/'));
            }
            $minifier->minify(Path::get('PUBLIC_PATH').$this->minifiedCssPath);
        } elseif (file_exists(Path::get('PUBLIC_PATH').$this->minifiedCssPath)) {
            unlink(Path::get('PUBLIC_PATH').$this->minifiedCssPath);
        }

        return;
    }
}