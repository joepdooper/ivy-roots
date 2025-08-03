<?php

namespace Ivy\Controller;

use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use GUMP;
use Ivy\Abstract\Controller;
use Ivy\Core\Path;
use Ivy\Helper\File;
use Ivy\Model\Profile;
use Ivy\Model\Template;
use Ivy\Model\User;
use Ivy\Service\Mail;
use Ivy\View\View;

class ProfileController extends Controller
{
    private Profile $profile;
    private File $file;

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
        $this->profile->policy('post');

        $data = [
            'user_id' => $this->request->get('user_id'),
            'username' => $this->request->get('username'),
            'email' => $this->request->get('email'),
            'avatar' => $this->request->get('avatar') ?? $this->request->files->get('avatar')
        ];

        if ((int) $this->request->get('user_id') !== (int) $_SESSION['auth_user_id']) {
            $this->flashBag->add('error', 'Invalid user ID');
            return;
        }

        GUMP::add_validator("image_or_delete", function($field, $input, $param = null) {
            if (!isset($input[$field])) {
                return false;
            }
            if ($input[$field] === "delete") {
                return true;
            }
            if (isset($_FILES[$field]) && is_uploaded_file($_FILES[$field]['tmp_name'])) {
                $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
                $fileExtension = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);

                return in_array(strtolower($fileExtension), $allowedExtensions, true);
            }
            return false;
        }, "The {field} must be an image.");

        $validated = GUMP::is_valid($data, [
            'username' => 'required|alpha_numeric_dash',
            'email' => 'required|valid_email',
            'avatar' => 'image_or_delete'
        ]);

        if ($validated === true) {

            $this->profile = (new Profile)->where('user_id', $this->request->get('user_id'))->fetchOne();

            if(User::getAuth()->getUsername() !== $data['username']) {
                (new User)->where('id', $this->request->get('user_id'))->populate(
                    [
                        'username' => $data['username']
                    ]
                )->update();
            }
            if (User::getAuth()->getEmail() !== $data['email']) {
                try {
                    User::getAuth()->changeEmail($data['email'], function ($selector, $token) use($data) {
                        $url = Path::get('PUBLIC_URL') . 'admin/profile/' . urlencode($selector) . '/' . urlencode($token);
                        // send email
                        $mail = new Mail();
                        $mail->addAddress($data['email'], $data['username']);
                        $mail->setSubject('Reset email address');
                        $mail->setBody('Reset your email address with this link: ' . $url);
                        $mail->setAltBody('Reset your email address with this link: ' . $url);
                        $mail->send();
                    });
                    $this->flashBag->add('success', 'An email has been sent to ' . $email . ' with a link to confirm the email address');
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

            if($this->request->get('avatar') === 'delete') {
                $this->profile->populate(['user_image' => ''])->update();
            }

            if (!empty($this->request->files->get('avatar')['name'])) {
                $this->profile->populate(['user_image' => $this->saveAvatar()])->update();
            }

        } else {
            foreach ($validated as $string) {
                $this->flashBag->add('error', $string);
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

    private function saveAvatar(): string
    {
        $this->file = new File;
        $this->file->setName(bin2hex(random_bytes(16)));
        $this->file->setAllowed(array('image/*'));
        $this->file->setDirectory(Path::get('MEDIA_PATH') . 'profile' . DIRECTORY_SEPARATOR);
        $this->file->setWidth('120');
        $avatar = $this->file->upload($this->request->files->get('avatar'));
        $this->file->setImageConvert( 'webp');
        $this->file->upload($this->request->files->get('avatar'));

        return $avatar;
    }

    private function deleteAvatar(): null
    {
        $this->file = new File;
        $this->file->setDirectory(Path::get('MEDIA_PATH') . 'profile' . DIRECTORY_SEPARATOR);
        $this->file->delete($this->profile->getUserImage());

        return null;
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
