<?php

namespace Ivy\Template\Application\Handler;

use Ivy\Plugin\Domain\Entity\SettingModel;
use Ivy\Shared\Config\Environment;
use Ivy\Plugin\Contracts\SettingInterface;
use Ivy\Shared\Core\Path;
use Ivy\Plugin\Infrastructure\Manager\AssetManager;
use MatthiasMullie\Minify\JS;

class MinifyJsHandler implements SettingInterface
{
    private $minifiedJsPath = '/js/minified.js';

    public function handle(SettingModel $setting, bool $bool): void
    {
        if ($bool && Environment::isProd()) {
            $minifier = new JS;
            foreach (AssetManager::getJs() as $js) {
                if (str_ends_with($js, '.editor.js')) {
                    continue;
                }
                $minifier->add(Path::get('PUBLIC_PATH').ltrim($js, '/'));
            }
            $minifier->minify(Path::get('PUBLIC_PATH').$this->minifiedJsPath);
        } elseif (file_exists(Path::get('PUBLIC_PATH').$this->minifiedJsPath)) {
            unlink(Path::get('PUBLIC_PATH').$this->minifiedJsPath);
        }
    }
}
