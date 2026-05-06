<?php

namespace Ivy\Application\Handler;

use Ivy\Shared\Config\Environment;
use Ivy\Shared\Core\Contracts\SettingInterface;
use Ivy\Shared\Core\Path;
use Ivy\Infrastructure\Manager\AssetManager;
use Ivy\Domain\Entity\SettingEntity;
use MatthiasMullie\Minify\CSS;

class MinifyCssHandler implements SettingInterface
{
    private $minifiedCssPath = '/css/minified.css';

    public function handle(Setting $setting, bool $bool): void
    {
        if ($bool && Environment::isProd()) {
            $minifier = new CSS;
            foreach (AssetManager::getCSS() as $css) {
                if (str_ends_with($css, '.editor.css')) {
                    continue;
                }

                $minifier->add(Path::get('PUBLIC_PATH').ltrim($css, '/'));
            }
            $minifier->minify(Path::get('PUBLIC_PATH').$this->minifiedCssPath);
        } elseif (file_exists(Path::get('PUBLIC_PATH').$this->minifiedCssPath)) {
            unlink(Path::get('PUBLIC_PATH').$this->minifiedCssPath);
        }

        return;
    }
}
