<?php

namespace Ivy\User\Presentation\Controller;

use Delight\Auth\AuthError;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use Ivy\Shared\Base\Controller;
use Ivy\Shared\Core\Path;
use Ivy\Plugin\Infrastructure\Registry\PluginRegistry;
use Ivy\Shared\Domain\ValueObject\ImageFile;
use Ivy\Shared\Infrastructure\Service\ImageFileService;
use Ivy\Shared\Infrastructure\Service\MailService;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Domain\Entity\Profile;
use Ivy\User\Presentation\Form\ProfileForm;
use Random\RandomException;

class ProfileController extends Controller
{
    private Profile $profile;
    private ProfileForm $profileForm;

    public function __construct()
    {
        parent::__construct();
        $this->profile = new Profile();
        $this->profileForm = new ProfileForm;
    }

    public function before(): void
    {
        if (! $this->authService->isLoggedIn()) {
            $this->redirect('user/login');
        }
    }

    /**
     * @throws AuthError
     * @throws RandomException
     */
    public function save(): void
    {
        $this->profile->authorize('save');

        $result = $this->profileForm->validate($this->request->request->all());

        if ($result->valid) {

            $profile = Profile::with('user')
                ->where('user_id', $_SESSION['auth_user_id'])
                ->first();

            if ($profile && $profile?->user) {

                $profile->user->fill([
                    'username' => $result->data['username'],
                ]);

                if ($profile->user->isDirty('username')) {
                    $profile->user->save();
                    $this->flashBag->add(
                        'success',
                        'Username ' . $result->data['username'] . ' succesfull updated'
                    );
                }

                $profile->user->fill([
                    'email' => $result->data['email']
                ]);

                if ($profile->user->isDirty('email')) {
                    try {
                        $this->authService->auth()->changeEmail(
                            $result->data['email'],
                            function ($selector, $token) use ($result) {

                                $url = Path::get('PUBLIC_URL')
                                    . 'admin/profile/'
                                    . urlencode($selector) . '/'
                                    . urlencode($token);

                                $mail = new MailService();
                                $mail->addAddress($result->data['email'], $result->data['username']);
                                $mail->setSubject('Reset email address');
                                $mail->setBody('Reset your email address with this link: ' . $url);
                                $mail->setAltBody('Reset your email address with this link: ' . $url);
                                $mail->send();
                            }
                        );

                        $this->flashBag->add(
                            'success',
                            'An email has been sent to ' . $result->data['email'] . ' with a link to confirm the email address'
                        );

                    } catch (InvalidEmailException) {
                        $this->flashBag->add('error', 'Invalid email address');
                    } catch (UserAlreadyExistsException) {
                        $this->flashBag->add('error', 'Email address already exists');
                    } catch (EmailNotVerifiedException) {
                        $this->flashBag->add('error', 'Account not verified');
                    } catch (NotLoggedInException) {
                        $this->flashBag->add('error', 'Not logged in');
                    } catch (TooManyRequestsException) {
                        $this->flashBag->add('error', 'Too many requests');
                    }
                }

                    if ($this->request->files->has('user_image')) {

                        $file = new ImageFile($this->request->files->get('user_image'));

                        $profile->user_image = $file
                            ->setUploadPath('profile')
                            ->setImageWidth(120)
                            ->generateFileName();

                        $imageFileService = new ImageFileService;
                        $imageFileService->add($file);

                        try {
                            $imageFileService->upload();
                        } catch(\RuntimeException $e) {
                            $this->flashBag->add('error', $e->getMessage());
                        }

                        $profile->save();
                    }

                    if ($this->request->request->has('delete_user_image')) {
                        $file = new ImageFile;
                        $file->setUploadPath('profile')->remove($profile->user_image);
                        $profile->user_image = null;
                        $profile->save();
                    }
            }
        } else {
            $this->flashBag->set('errors', $result->errors);
            $this->flashBag->set('old', $result->old);
        }

        $this->redirect('admin/profile');
    }

    public function user(): void
    {
        $profile = Profile::where('user_id', $_SESSION['auth_user_id'])->first();

        View::render('admin/profile.latte', ['profile' => $profile]);
    }

    public function public(int $id): void
    {
        $profile = Profile::where('id', $id)->first();

        View::render('include/profile.latte', ['profile' => $profile]);
    }

    public function verify(?string $selector = null, ?string $token = null): void
    {
        if ($selector && $token) {
            try {
                $this->authService->auth()->confirmEmail($selector, $token);
                $this->flashBag->add('success', 'Email address has been verified');

            } catch (InvalidSelectorTokenPairException|TooManyRequestsException) {
                $this->flashBag->add('error', 'Invalid token');
            } catch (TokenExpiredException) {
                $this->flashBag->add('error', 'Token expired');
            } catch (UserAlreadyExistsException) {
                $this->flashBag->add('warning', 'Email address already exists');
            } catch (AuthError $e) {
                $this->flashBag->add('error', 'An error occurred while confirming the email address');
            }
        }

        $this->redirect('admin/profile');
    }
}
