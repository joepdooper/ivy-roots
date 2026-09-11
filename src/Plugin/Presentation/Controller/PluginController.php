<?php

namespace Ivy\Plugin\Presentation\Controller;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Plugin\Domain\Enum\PluginStatus;
use Ivy\Plugin\Domain\Exception\PluginException;
use Ivy\Plugin\Infrastructure\Manager\PluginManager;
use Ivy\Plugin\Infrastructure\Service\PluginService;
use Ivy\Plugin\Presentation\Form\PluginDataForm;
use Ivy\Shared\Base\Controller;
use Ivy\Shared\Core\Language;
use Ivy\Shared\Core\Path;
use Ivy\Shared\Infrastructure\Composer\ComposerRunner;
use Ivy\Shared\Infrastructure\Composer\PackagistClient;
use Ivy\Shared\Infrastructure\Service\FileService;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Domain\Exception\AuthorizationException;
use ReflectionException;
use Throwable;

class PluginController extends Controller
{
    private Plugin $plugin;

    private ComposerRunner $composerRunner;

    public function __construct()
    {
        parent::__construct();
        $this->plugin = new Plugin;
        $this->composerRunner = new ComposerRunner();
    }

    /**
     * @throws ReflectionException
     * @throws AuthorizationException
     * @throws BindingResolutionException
     */
    public function before(): void
    {
        if ($this->authService->isLoggedIn()) {
            if ($this->plugin->policy('index')) {
                $this->redirect();
            }
        } else {
            $this->redirect('user/login');
        }
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function index(): void
    {
        $this->plugin->authorize('index');

        $plugins = Plugin::all();

        View::render('admin/plugin.latte', [
            'plugins' => $plugins,
        ]);
    }

    public function catalog(): void
    {
        $this->plugin->authorize('index');

        $catalog = PackagistClient::getCatalog('ivy-plugin');

        View::render('admin/plugin.catalog.latte', [
            'catalog' => $catalog,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function add(): void
    {
        $this->plugin->authorize('install');

        $package = $this->request->request->get('package');

        try {
            $data = PackagistClient::getPackageData((string) $package);
        } catch (Exception $exception) {
            $this->flashBag->add(
                'error',
                $exception->getMessage()
            );
            return;
        }

        $result = (new PluginDataForm)->validate($data);

        if (! $result->valid) {
            foreach ($result->errors as $error) {
                if (is_array($error)) {
                    $this->flashBag->add('error', $error[0]);
                }
            }
            return;
        }

        $plugin = Plugin::firstOrCreate(
            ['package' => $package],
            [
                ...$result->data,
                'status' => PluginStatus::DOWNLOADING,
            ]
        );

        $this->composerRunner->require((string) $package);

        $this->flashBag->add(
            'success',
            Language::translate('plugin.added_successfully', ['plugin' => $plugin->name])
        );

        $this->redirect('admin/plugin');
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Plugin|int $plugin, mixed $data): void
    {

        if (is_int($plugin)) {
            $plugin = Plugin::find($plugin);
        }

        $plugin->fill($data);

        if (!$plugin->isDirty()) {
            return;
        }

        $plugin->authorize('update');

        $plugin->save();

        $this->flashBag->add(
            'success',
            'Plugin ' . $plugin->name . ' updated successfully.'
        );
    }

    /**
     * @throws Exception
     */
    public function delete(Plugin|int $plugin): void
    {
        if (is_int($plugin)) {
            $plugin = Plugin::find($plugin);
        }

        $plugin->authorize('uninstall');

        try {
            $this->composerRunner->remove($plugin->package);

            PluginManager::uninstall($plugin);

            $this->flashBag->add(
                'success',
                Language::translate('plugin.uninstalled_successfully', [
                    'plugin' => $plugin->name
                ])
            );

        } catch (Throwable $e) {
            $this->flashBag->add(
                'error',
                $e->getMessage(),
            );
        }

        $this->redirect('admin/plugin');
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function status(Plugin|int $plugin): void
    {
        if (is_int($plugin)) {
            $plugin = Plugin::find($plugin);
        }

        if ($plugin->status === PluginStatus::DOWNLOADING) {
            if (FileService::exists($plugin->url.DIRECTORY_SEPARATOR.'composer.json', Path::get('PLUGINS_PATH'))) {
                $plugin->status = PluginStatus::DOWNLOADED;
            }
        }
        if ($plugin->status === PluginStatus::DOWNLOADED) {
            try {
                PluginManager::install($plugin);
            } catch (PluginException $e) {
                $this->flashBag->add('error', $e->getMessage());
            }
        }

        if ($plugin->status === PluginStatus::FAILED) {
            $this->redirect('admin/plugin');
        }

        View::render('include/plugin-status.latte', [
            'plugin' => $plugin,
        ]);
    }
}
