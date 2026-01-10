<?php

namespace Ivy\Controller;

use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\NotLoggedInException;
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
    private Profile $profile;

    public function __construct()
    {
        parent::__construct();
        $this->profile = new Profile;
    }

    public function before(): void
    {
        if (!User::getAuth()->isLoggedIn()) {
            $this->redirect('user/login');
        }
    }

    public function post(): void
    {
        $this->profile->authorize('post');

        $result = (new ProfileForm)->validate($this->request->request->all());

        if (!$result->valid) {
            $this->flashBag->set('errors', $result->errors);
            $this->flashBag->set('old', $result->old);
            $this->redirect('admin/profile');
        } else {
            $this->profile = (new Profile)->with(['user'])->where('user_id', $_SESSION['auth_user_id'])->fetchOne();

            if(User::getAuth()->getUsername() !== $result->data['username']) {
                $this->profile->user->populate(['username' => $result->data['username']])->update();
            }

            if (User::getAuth()->getEmail() !== $result->data['email']) {
                try {
                    User::getAuth()->changeEmail($result->data['email'], function ($selector, $token) use($result) {
                        $url = Path::get('PUBLIC_URL') . 'admin/profile/' . urlencode($selector) . '/' . urlencode($token);
                        // send email
                        $mail = new Mail();
                        $mail->addAddress($result->data['email'], $result->data['username']);
                        $mail->setSubject('Reset email address');
                        $mail->setBody('Reset your email address with this link: ' . $url);
                        $mail->setAltBody('Reset your email address with this link: ' . $url);
                        $mail->send();
                    });
                    $this->flashBag->add('success', 'An email has been sent to ' . $result->data['email'] . ' with a link to confirm the email address');
                } catch (InvalidEmailException) {
                    $this->flashBag->add('error', 'Invalid email address');
                } catch (UserAlreadyExistsException) {
                    $this->flashBag->add('error', 'Email address already exists');
                } catch (EmailNotVerifiedException) {
                    $this->flashBag->add('error', 'Account not verified');
                } catch (NotLoggedInException) {
                    $this->flashBag->add('error', 'Not logged in');
                } catch (TooManyRequestsException $e) {
                    $this->flashBag->add('error', 'Too many requests');
                }
            }

            if(in_array("Image", SessionManager::get('plugin_actives'))) {
                if ($this->request->files->get('avatar')) {
                    $file = new ImageFile($this->request->files->get('avatar'));
                    $this->profile->user_image = $file
                        ->setUploadPath('profile')
                        ->setImageWidth(120)
                        ->generateFileName();
                    (new ImageFileService)->add($file)->upload();
                    $this->profile->update();
                }
                if ($this->request->get('avatar') === 'delete') {
                    $file = new ImageFile();
                    $file->setUploadPath('profile')->remove($this->profile->user_image);
                    $this->profile->user_image = null;
                    $this->profile->update();
                }
            }
        }

        $this->flashBag->add('success', 'Update successfully');
        $this->redirect('admin/profile');
    }

    public function user(): void
    {
        $profile = (new Profile)->where('user_id', $_SESSION['auth_user_id'])->fetchOne();
        View::set('admin/profile.latte', ['profile' => $profile]);
    }

    public function public($id): void
    {
        $profile = (new Profile)->where('id', $id)->fetchOne();
        View::set('include/profile.latte', ['profile' => $profile]);
    }

    public function verify($selector = null, $token = null) {
        if (isset($selector) && isset($token)) {
            try {
                User::getAuth()->confirmEmail($selector, $token);
                $this->flashBag->add('success', 'Email address has been verified');
            } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
                $this->flashBag->add('error', 'Invalid token');
            } catch (\Delight\Auth\TokenExpiredException $e) {
                $this->flashBag->add('error', 'Token expired');
            } catch (\Delight\Auth\UserAlreadyExistsException $e) {
                $this->flashBag->add('warning', 'Email address already exists');
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                $this->flashBag->add('error', 'Invalid token');
            }
        }
        $this->redirect('admin/profile');
    }
}
