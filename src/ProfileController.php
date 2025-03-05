<?php

namespace Ivy;

use Delight\Auth\AuthError;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\Role;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UnknownIdException;
use Delight\Auth\UserAlreadyExistsException;
use GUMP;

class ProfileController extends Controller
{
    private Profile $profile;
    private File $file;

    public function post(): void
    {
        $this->requirePost();
        $this->requireLogin();

        $data = [
            'user_id' => $this->request->get('user_id'),
            'username' => $this->request->get('username'),
            'email' => $this->request->get('email'),
            'avatar' => $this->request->get('avatar') ?? $this->request->files->get('avatar')
        ];

        if ((int) $this->request->get('user_id') !== (int) $_SESSION['auth_user_id']) {
            Message::add('Invalid user ID');
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
                    User::getAuth()->changeEmail($data['email'], function ($selector, $token) {
                        $url = _BASE_PATH . 'admin/profile/' . urlencode($selector) . '/' . urlencode($token);
                        // send email
                        $mail = new Mail();
                        $mail->addAddress($data['email'], $data['username']);
                        $mail->setSubject('Reset email address');
                        $mail->setBody('Reset your email address with this link: ' . $url);
                        $mail->setAltBody('Reset your email address with this link: ' . $url);
                        $mail->send();
                    });
                    Message::add('An email has been sent to ' . $email . ' with a link to confirm the email address');
                } catch (InvalidEmailException) {
                    Message::add('Invalid email address');
                } catch (UserAlreadyExistsException) {
                    Message::add('Email address already exists');
                } catch (EmailNotVerifiedException) {
                    Message::add('Account not verified');
                } catch (NotLoggedInException) {
                    Message::add('Not logged in');
                } catch (TooManyRequestsException) {
                    Message::add('Too many requests');
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
                Message::add($string);
            }
        }

        Message::add('Update successfully', Path::get('BASE_PATH') . 'admin/profile');
    }

    private function saveAvatar(): string
    {
        $this->file = new File;
        $this->file->setName(bin2hex(random_bytes(16)));
        $this->file->setAllowed(array('image/*'));
        $this->file->setDirectory(Path::get('PUBLIC_PATH') . Path::get('MEDIA_PATH') . 'profile' . DIRECTORY_SEPARATOR);
        $this->file->setWidth('120');
        $avatar = $this->file->upload($this->request->files->get('avatar'));
        $this->file->setImageConvert( 'webp');
        $this->file->upload($this->request->files->get('avatar'));

        return $avatar;
    }

    private function deleteAvatar(): null
    {
        $this->file = new File;
        $this->file->setDirectory(Path::get('PUBLIC_PATH') . Path::get('MEDIA_PATH') . 'profile' . DIRECTORY_SEPARATOR);
        $this->file->delete($this->profile->getUserImage());

        return null;
    }
}
