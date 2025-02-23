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
    private Profile $profile;

    public function post(): void
    {
        $this->requirePost();
        $this->requireLogin();

            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            try {
                $username = $purifier->purify($this->request->input('users')['username']);
                $email = $purifier->purify($this->request->input('users')['email']);
                if (!empty($username) && !empty($email)) {

                    if (User::getUsername() != $username) {
                        (new User)->where('id', User::getUserId())->update([
                            'username' => $username
                        ]);
                    }

                    if (User::getEmail() != $email) {
                        try {
                            User::changeEmail($purifier->purify($this->request->input('users')['email']), function ($selector, $token) use ($purifier) {
                                $url = _BASE_PATH . 'admin/profile/' . urlencode($selector) . '/' . urlencode($token);
                                // send email
                                $mail = new Mail();
                                $mail->addAddress($purifier->purify($this->request->input('users')['email']), $purifier->purify($this->request->input('users')['username']));
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

                    if ($this->request->input('users_image') !== null && $this->request->input('users_image') === 'delete') {
                        $profile = (new Profile)->where('user_id', User::getUserId())->fetchOne();
                        $profile->update(['users_image' => '']);
                        (new Image)->deleteSet($profile->users_image);
                    }

                    if ($request->input('users_image')['tmp_name']) {
                        (new Profile)->where('user_id', User::getUserId())->update([
                            'users_image' => (new Image)->upload($this->request->input('users_image'))
                        ]);
                    }

                    $message = 'Update successfully';
                } else {
                    if (empty($email)) {
                        $message = 'Please enter email';
                    }
                    if (empty($username)) {
                        $message = 'Please enter name';
                    }
                }
            } catch (Exception) {
                $message = 'Something went wrong';
            }

            Message::add($message, $this->path);
    }
}
