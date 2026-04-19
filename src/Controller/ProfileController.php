<?php

namespace Ivy\Controller;

use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use Items\Collection\Image\ImageFile;
use Items\Collection\Image\ImageFileService;
use Ivy\Abstract\Controller;
use Ivy\Core\Path;
use Ivy\Form\ProfileForm;
use Ivy\Manager\SessionManager;
use Ivy\Model\Profile;
use Ivy\Model\User;
use Ivy\Service\Mail;
use Ivy\View\View;

class ProfileController extends Controller
{
    private ProfileForm $profileForm;

    public function __construct()
    {
        parent::__construct();
        $this->profileForm = new ProfileForm;
    }

    public function before(): void
    {
        if (! User::getAuth()->isLoggedIn()) {
            $this->redirect('user/login');
        }
    }

    public function save(): void
    {
        $this->profile->authorize('save');

        $result = $this->profileForm->validate($this->request->request->all());

        if ($result->valid) {

            $profile = Profile::with('user')
                ->where('user_id', $_SESSION['auth_user_id'])
                ->first();

            if ($profile) {

                if ($profile->user && (User::getAuth()->getUsername() !== $result->data['username'])) {
                    $profile->user->fill([
                        'username' => $result->data['username']
                    ])->save();
                }

                if (User::getAuth()->getEmail() !== $result->data['email']) {
                    try {
                        User::getAuth()->changeEmail(
                            $result->data['email'],
                            function ($selector, $token) use ($result) {

                                $url = Path::get('PUBLIC_URL')
                                    . 'admin/profile/'
                                    . urlencode($selector) . '/'
                                    . urlencode($token);

                                $mail = new Mail;
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

                if (in_array('Image', SessionManager::get('plugin_actives'))) {

                    if ($this->request->files->get('avatar')) {

                        $file = new ImageFile($this->request->files->get('avatar'));

                        $profile->user_image = $file
                            ->setUploadPath('profile')
                            ->setImageWidth(120)
                            ->generateFileName();

                        (new ImageFileService)->add($file)->upload();

                        $profile->save();
                    }

                    if ($this->request->get('avatar') === 'delete') {

                        $file = new ImageFile;
                        $file->setUploadPath('profile')->remove($profile->user_image);

                        $profile->user_image = null;
                        $profile->save();
                    }
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

        View::set('admin/profile.latte', ['profile' => $profile]);
    }

    public function public(int $id): void
    {
        $profile = Profile::where('id', $id)->first();

        View::set('include/profile.latte', ['profile' => $profile]);
    }

    public function verify(?string $selector = null, ?string $token = null): void
    {
        if ($selector && $token) {
            try {
                User::getAuth()->confirmEmail($selector, $token);
                $this->flashBag->add('success', 'Email address has been verified');

            } catch (InvalidSelectorTokenPairException) {
                $this->flashBag->add('error', 'Invalid token');
            } catch (TokenExpiredException) {
                $this->flashBag->add('error', 'Token expired');
            } catch (UserAlreadyExistsException) {
                $this->flashBag->add('warning', 'Email address already exists');
            } catch (TooManyRequestsException) {
                $this->flashBag->add('error', 'Invalid token');
            }
        }

        $this->redirect('admin/profile');
    }
}