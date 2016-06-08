<?php

namespace App\ModelTraits;

use App\SocialLogin;
use App\User;

trait UserSocialiteTrait {


    public function providers()
    {
        return $this->hasMany(SocialLogin::class);
    }

    public function hasProvider($socialite_user, $provider)
    {
        return $this->providers()->where('provider', $provider)->where('provider_id', $socialite_user->getId() )->first();

    }


    public function findAuthUserFromSocialUser($socialite_user,  $provider = '') {

        $email = $socialite_user->getEmail();
            if (empty($email)) $email =  $socialite_user->getId() . "@{$provider}.com";
        return $this->findByEmail($email);

    }


    protected function findByEmail($email) {

        $user = User::where('email', $email)->first();

        if (empty($user->id))
            return false;

        return $user;
    }


    public function createUserFromSocialite($socialite_user)
    {
        return User::create([
            'name' => $socialite_user->getName(),
            'email' => $socialite_user->getEmail(),
            'password' => bcrypt(uniqid()),
        ]);
    }

    public function registerProvider($socialite_user, $provider)
    {



        if (! $this->hasProvider($socialite_user, $provider)) {

            /**
             * Gather the provider data for saving and associate it with the user
             */
            $this->providers()->save(new SocialLogin([
                'provider'    => $provider,
                'provider_id' => $socialite_user->getId(),
                'token'       => $socialite_user->token,
                'avatar'      => $socialite_user->getAvatar(),
            ]));
        }

        else

        {
            /**
             * Update the users information, token and avatar can be updated.
             */
            $this->providers()->update([
                'token'       => $socialite_user->token,
                'avatar'      => $socialite_user->getAvatar()
            ]);
        }



        return $this;

    }








}