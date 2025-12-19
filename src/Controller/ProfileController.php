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
use Ivy\Core\Language;
use Ivy\Core\Path;
use Ivy\Manager\SessionManager;
use Ivy\Model\Profile;
use Ivy\Model\Template;
use Ivy\Model\User;
use Ivy\Service\Mail;
use Ivy\View\View;
use BlakvGhost\PHPValidator\Validator;
use BlakvGhost\PHPValidator\ValidatorException;

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

        $data = [
            'username' => $this->request->get('username'),
            'email' => $this->request->get('email'),
            'avatar' => $this->request->get('avatar') ?? $this->request->files->get('avatar'),
        ];

//        GUMP::add_validator("image_or_delete", function($field, $input, $param = null) {
//            if (!isset($input[$field])) {
//                return false;
//            }
//            if ($input[$field] === "delete") {
//                return true;
//            }
//            if (isset($_FILES[$field]) && is_uploaded_file($_FILES[$field]['tmp_name'])) {
//                $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
//                $fileExtension = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
//
//                return in_array(strtolower($fileExtension), $allowedExtensions, true);
//            }
//            return false;
//        }, "The {field} must be an image.");

        $validated = new Validator($data, [
            'username' => 'required|alpha_numeric_dash',
            'email' => 'required|valid_email',
            'avatar' => 'image_or_delete',
            'birthday' => 'date'
        ]);

        if ($validated === true) {

            $this->profile = (new Profile)->where('user_id', $_SESSION['auth_user_id'])->fetchOne();

            $this->profile->populate([
                'birthday' => $data['birthday']
            ])->update();

            if(User::getAuth()->getUsername() !== $data['username']) {
                (new User)->where('id', $_SESSION['auth_user_id'])->populate(
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
                    $this->flashBag->add('success', 'An email has been sent to ' . $data['email'] . ' with a link to confirm the email address');
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
                    $this->profile->user_image = '';
                    $this->profile->update();
                }
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
