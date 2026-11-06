<?php

namespace Ivy\Template\Presentation\Controller;

use Ivy\Plugin\Domain\Entity\Plugin;
use Ivy\Plugin\Domain\Exception\PluginException;
use Ivy\Plugin\Infrastructure\Manager\PluginManager;
use Ivy\Setting\Domain\Entity\Setting;
use Ivy\Shared\Base\Controller;
use Ivy\Shared\Core\Language;
use Ivy\Shared\Core\Path;
use Ivy\Shared\Domain\Enum\Status;
use Ivy\Shared\Infrastructure\Composer\ComposerRunner;
use Ivy\Shared\Infrastructure\Composer\PackagistClient;
use Ivy\Shared\Infrastructure\Service\FileService;
use Ivy\Template\Application\Asset\AssetPublisher;
use Ivy\Template\Domain\Entity\Template;
use Ivy\Template\Infrastructure\Manager\TemplateManager;
use Ivy\Template\Presentation\Form\TemplateDataForm;
use Ivy\Template\Presentation\Form\TemplateForm;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Domain\Exception\AuthorizationException;
use Throwable;

class TemplateController extends Controller
{
    protected Template $template;

    protected TemplateForm $templateForm;

    private ComposerRunner $composerRunner;

    public function __construct()
    {
        parent::__construct();
        $this->template = new Template;
        $this->templateForm = new TemplateForm;
        $this->composerRunner = new ComposerRunner();
    }

    public function before(): void
    {
        if (! $this->authService->isLoggedIn() && Setting::stashGet('private')->bool) {

            if (! $this->isAlwaysPublicPath(Path::get('CURRENT_PAGE'))) {
                $this->redirect('user/login');
            }
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function index(): void
    {
        $this->template->authorize('index');

        $templates = Template::search($this->request)
            ->sort($this->request, 'name')
            ->pages($this->request)
            ->get();

        View::render('admin/template.latte', [
            'templates' => $templates
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function catalog(): void
    {
        $this->template->authorize('index');

        $packagist = PackagistClient::getCatalog('ivy-template', $this->request);

        View::render('admin/template.catalog.latte', [
            'catalog' => $packagist['catalog'],
            'pagination' => $packagist['pagination']
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function add(): void
    {
        $this->template->authorize('install');

        $package = $this->request->request->get('package');

        try {
            $data = PackagistClient::getPackageData((string) $package);
        } catch (\Exception $exception) {
            $this->flashBag->add(
                'error',
                $exception->getMessage()
            );
            return;
        }

        $result = (new TemplateDataForm)->validate($data);

        if (! $result->valid) {
            foreach ($result->errors as $error) {
                if (is_array($error)) {
                    $this->flashBag->add('error', $error[0]);
                }
            }
            return;
        }

        $template = Template::firstOrCreate(
            ['package' => $package],
            [
                ...$result->data,
                'status' => Status::DOWNLOADING,
            ]
        );

        $this->composerRunner->require((string) $package);

        $this->flashBag->add(
            'success',
            Language::translate('template.added_successfully', ['template' => $template->name])
        );

        $this->redirect('admin/template/catalog');
    }

    public function update(Template|int $template, mixed $data): void
    {
        if (is_int($template)) {
            $template = Template::find($template);
        }

        $template->fill($data);

        if (! $template->isDirty()) {
            return;
        }

        $template->authorize('update');

        $template->save();

        TemplateManager::init(true);
        new AssetPublisher()->publishTemplate();

        $this->flashBag->add(
            'success',
            $template->type.'-template updated successfully.'
        );
    }

    public function delete(Template|int $template): void
    {
        if (is_int($template)) {
            $template = Template::find($template);
        }

        $template->authorize('uninstall');

        try {
            $this->composerRunner->remove($template->package);

            $template->delete();

            $this->flashBag->add(
                'success',
                Language::translate('template.uninstalled_successfully', [
                    'template' => $template->name
                ])
            );

        } catch (Throwable $e) {
            $this->flashBag->add(
                'error',
                $e->getMessage(),
            );
        }

        $this->redirect('admin/template');
    }

    public function status(Template|int $template): void
    {
        if (is_int($template)) {
            $template = Template::find($template);
        }

        if ($template->status === Status::DOWNLOADING) {
            if (FileService::exists($template->url.DIRECTORY_SEPARATOR.'composer.json', Path::get('TEMPLATES_PATH'))) {
                $template->status = Status::DOWNLOADED;
                $template->save();
            }
        }
        if ($template->status === Status::DOWNLOADED) {
            $template->status = Status::INSTALLED;
            $template->save();
        }

        if ($template->status === Status::FAILED) {
            $this->redirect('admin/template/catalog');
        }

        View::render('include/status.latte', [
            'status' => $template->status,
            'url' => '/admin/template/'. $template->id.'/status',
        ]);
    }

    private function isAlwaysPublicPath(string $current): bool
    {
        $allowed = [
            Path::get('PUBLIC_URL').'user/login',
            Path::get('PUBLIC_URL').'user/reset',
            Path::get('PUBLIC_URL').'user/register',
        ];

        return array_any($allowed, fn ($prefix) => $current === $prefix || str_starts_with($current, $prefix.'/'));
    }
}
