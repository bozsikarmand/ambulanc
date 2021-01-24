<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  mixed  $user
     * @param  array  $input
     * @return void
     */
    public function update($user, array $input)
    {
        Validator::make($input, [
            'firstname' => ['required', 'string', 'max:255'],
            'middlename' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'image', 'max:1024'],
            'first_login' => ['nullable', 'integer', 'max:1'],
            'landlinetel' => ['required', 'string', 'max:15'],
            'mobiletel' => ['required', 'string', 'max:15'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            if ($user->first_login === null) {
                $user->forceFill([
                    'first_login' => 1,
                ])->save();
            }
            if ($user->first_login === 1) {
                $user->forceFill([
                    'status' => 2,
                ])->save();
            }
            $user->forceFill([
                'firstname' => $input['firstname'],
                'middlename' => $input['middlename'],
                'lastname' => $input['lastname'],
                'username' => $input['username'],
                'email' => $input['email'],
                'landlinetel' => $input['landlinetel'],
                'mobiletel' => $input['mobiletel'],
            ])->save();

            flash('Thank you, everything is set! Please wait for the approval of your account by one of our administrators. We will notify you via email once it is done.')->success();
            Auth::logout();
            redirect(route('login'));
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  mixed  $user
     * @param  array  $input
     * @return void
     */
    protected function updateVerifiedUser($user, array $input)
    {
        if ($user->first_login === null) {
            $user->forceFill([
                'first_login' => 1,
            ])->save();
        }
        if ($user->first_login === 1) {
            $user->forceFill([
                'status' => 2,
            ])->save();
        }
        $user->forceFill([
            'firstname' => $input['firstname'],
            'middlename' => $input['middlename'],
            'lastname' => $input['lastname'],
            'username' => $input['username'],
            'email' => $input['email'],
            'email_verified_at' => null,
            'landlinetel' => $input['landlinetel'],
            'mobiletel' => $input['mobiletel'],
        ])->save();

        $user->sendEmailVerificationNotification();

        flash('Thank you, everything is set! Please wait for the approval of your account by one of our administrators. We will notify you via email once it is done.')->success();
        Auth::logout();
        redirect(route('login'));
    }
}
