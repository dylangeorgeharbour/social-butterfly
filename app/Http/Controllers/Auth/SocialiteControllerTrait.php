<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

trait SocialiteControllerTrait{



    public function socialAuthRedirect($provider=null)
    {
        //make sure its a valid provider.
        if (!config("services.$provider")) abort('404');
        return Socialite::with($provider)->redirect();
    }


    public function socialAuthCallback($provider, User $user)
    {

        $socialite_user = Socialite::with($provider)->user();

        $user = $this->findOrCreateAuthUser($socialite_user,  $provider, $user)->registerProvider($socialite_user, $provider);

        Auth::loginUsingId($user->id);
        return redirect()->intended($this->redirectPath());

    }

    protected function findOrCreateAuthUser($socialite_user, $provider,  User $user_injected)
    {

        $user = $user_injected->findAuthUserFromSocialUser($socialite_user, $provider);

        if (empty($user->id))

        {
            $user = $user_injected->createUserFromSocialite($socialite_user);
        }
        //User has either been found or created. Either way, lets return it.
        return $user;

    }


}