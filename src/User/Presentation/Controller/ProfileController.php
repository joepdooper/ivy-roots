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
use Ivy\Shared\Core\Language;
use Ivy\Shared\Core\Path;
use Ivy\Shared\Domain\Exception\ImageFileException;
use Ivy\Shared\Domain\ValueObject\ImageFile;
use Ivy\Shared\Infrastructure\Service\ImageFileService;
use Ivy\Shared\Infrastructure\Service\MailService;
use Ivy\Template\Presentation\View\View;
use Ivy\User\Domain\Entity\Profile;
use Ivy\User\Domain\Exception\AuthorizationException;
use Ivy\User\Presentation\Form\ProfileForm;
use Random\RandomException;

class ProfileController extends Controller
{
    private Profile $profile;

    private ProfileForm $profileForm;

    public function __construct()
    {
        parent::__construct();
        $this->profile = new Profile;
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
     * @throws RandomException|AuthorizationException
     */
    public function save(): void
    {
        $this->profile->authorize('save');

        $result = $this->profileForm->validate($this->request->request->all());

        if ($result->valid) {

            $profile = Profile::with('user')
                ->where('user_id', $_SESSION['auth_user_id'])
                ->first();

            if ($profile && $profile->user) {

                $profile->user->fill([
                    'username' => $result->data['username'],
                    'email' => $result->data['email'],
                ]);

                if ($profile->user->isDirty('username')) {
                    $profile->user->save();
                    $this->flashBag->add(
                        'success',
                        Language::translate('user.username.updated', ['username' => $result->data['username']])
                    );
                }

                if ($profile->user->isDirty('email')) {
                    try {
                        $this->authService->auth()->changeEmail(
                            $result->data['email'],
                            function ($selector, $token) use ($result) {

                                $url = Path::get('PUBLIC_URL')
                                    .'admin/profile/'
                                    .urlencode($selector).'/'
                                    .urlencode($token);

                                $subject = Language::translate('mail.reset.subject');
                                $body = Language::translate('mail.reset.body', ['url' => $url]);

                                $mail = new MailService;
                                $mail->addAddress($result->data['email'], $result->data['username']);
                                $mail->setSubject($subject);
                                $mail->setBody($body);
                                $mail->setAltBody($body);
                                $mail->send();
                            }
                        );

                        $this->flashBag->add(
                            'success',
                            Language::translate('mail.reset.confirm', ['email' => $result->data['email']])
                        );

                    } catch (InvalidEmailException) {
                        $this->flashBag->add('error', Language::translate('error.email.invalid'));
                    } catch (UserAlreadyExistsException) {
                        $this->flashBag->add('error', Language::translate('error.email.exists'));
                    } catch (EmailNotVerifiedException) {
                        $this->flashBag->add('error', Language::translate('error.email.unverified'));
                    } catch (NotLoggedInException) {
                        $this->flashBag->add('error', Language::translate('error.not_logged_in'));
                    } catch (TooManyRequestsException) {
                        $this->flashBag->add('error', Language::translate('error.too_many_requests'));
                    }
                }

                if ($this->request->files->get('image')) {
                    $file = new ImageFile($this->request->files->get('image'));

                    $profile->image = $file
                        ->setUploadPath('profile')
                        ->setImageWidth(120)
                        ->generateFileName();

                    try {
                        $imageFileService = new ImageFileService;
                        $imageFileService->add($file)->upload();
                        $profile->save();
                        $this->flashBag->add(
                            'success',
                            Language::translate('profile.image.saved')
                        );
                    } catch (ImageFileException $e) {
                        $this->flashBag->add('error', $e->getMessage());
                    }
                }

                if ($this->request->request->has('delete_image')) {
                    $file = new ImageFile;
                    $file->setUploadPath('profile')->remove($profile->image);
                    $profile->image = null;
                    $profile->save();
                    $this->flashBag->add(
                        'success',
                        Language::translate('profile.image.deleted')
                    );
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
