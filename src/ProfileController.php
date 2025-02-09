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

class ProfileController extends Controller
{
    protected Profile $profile;

    public function post(): void
    {
        $this->requirePost();
        $this->requireLogin();

            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            $message = 'post profile';

            try {
                $name = $purifier->purify($this->request->input('users')['username']);
                $email = $purifier->purify($this->request->input('users')['email']);
                if (!empty($name) && !empty($email)) {

                    if (User::getUsername() != $name) {
                        $this->table = 'users';
                        $this->where('id', User::getUserId())->getRow();
                        $this->save([
                            'id' => User::getUserId(),
                            'username' => $name
                        ]);
                    }

                    if (User::getEmail() != $email) {
                        try {
                            User::changeEmail($purifier->purify($this->request->input('users')['email']), function ($selector, $token) use ($purifier) {
                                $url = _BASE_PATH . 'admin/profile/' . urlencode($selector) . '/' . urlencode($token);
                                // send email
                                $mail = new Mail();
                                $mail->Address = $purifier->purify($this->request->input('users')['email']);
                                $mail->Name = $purifier->purify($this->request->input('users')['username']);
                                $mail->Subject = 'Reset email address';
                                $mail->Body = 'Reset your email address with this link: ' . $url;
                                $mail->AltBody = 'Reset your email address with this link: ' . $url;
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

                    if ($this->request->input('users_image') !== null && $this->request->input('users_image') === 'delete') {
                        $this->table = 'profiles';
                        $this->where('user_id', User::getUserId())->getRow();
                        $this->save([
                            'id' => $this->single()->id,
                            'users_image' => ''
                        ]);
                        (new Image)->delete_set($this->single()->users_image);
                    }

                    if ($request->input('users_image')['tmp_name']) {
                        $this->table = 'profiles';
                        $this->where('user_id', User::getUserId())->getRow();
                        $this->save([
                            'id' => $this->single()->id,
                            'users_image' => (new Image)->upload($this->request->input('users_image'))
                        ]);
                    }

                    $message = 'Update successfully';
                } else {
                    if (empty($email)) {
                        $message = 'Please enter email';
                    }
                    if (empty($name)) {
                        $message = 'Please enter name';
                    }
                }
            } catch (Exception) {
                $message = 'Something went wrong';
            }

            Message::add($message, $this->path);
    }
}
