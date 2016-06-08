
# Laravel Social-Butterfly. 

This is a quick implementation of the Socialite package for Laravel. It should help as a quick start guide or bootstrap code to getting your users signing in with Social apps like facebook, github or twitter. This is in no way a complete production ready solution, more testing and tightening up is required but it should get you started in the right direction. 

I've put this together as a helpful guide for people looking to add in Socialite to their app. I found the socialite documentaiton quite limited and had to piece most of it together from other sources. Thanks to rappasoft for the excellent laravel-5-boilerplate project which helped a lot in my understanding of this. If you're looking for a more comprehensive app, I suggest you look there.  However, if you just want to understand and implement Socialite thiis might work for you. 

## Installation:

### Option 1: Clone the repo
This is recommended if you'd like to just poke around and have a look at the code in action. It will still require some setup (DB, Client ID's etc) but should work out of the box. This is a full Laravel App so it probably won't make sense for most people to just clone. 

### Option 2: Follow these steps (recommended):
These steps should allow you to quickly add social integration to your application without interfering too much in your current base of code. The code should be fairly self explnatory as it isn't that complicated but feel free to request additional notes if needed. 

#### Assumptions:
1. Laravel 5.2 Framework
2. Laravel Authentication Quickstart using the following command

    ````
    php artisan make:auth
    ```` 
3. A basic understanding of Laravel, Traits and how OAuth works. 
4. Initial Socialite Setup completed. This includes running the below command and adding the class to your providers and aliases array in config/app.php

    ````
    composer require laravel/socialite

    `````
    ````
    // Other service providers...

    'providers' => [
        Laravel\Socialite\SocialiteServiceProvider::class,
    ],

	//aliases  
   'Socialite' => Laravel\Socialite\Facades\Socialite::class,

    ````




#### Installation Steps:
1. Lets Start with the DB migrations (We'll store Social login data in a new table linked to the user table). Create a new Migration and copy the following code into it. Run the Migration. 

    ````
    <?php

    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Migrations\Migration;

    class CreateSocialLoginsTable extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('social_logins', function (Blueprint $table) {
                $table->increments('id')->unsigned();
                $table->integer('user_id')->unsigned();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->string('provider', 32);
                $table->string('provider_id');
                $table->string('token')->nullable();
                $table->string('avatar')->nullable();
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at');
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::drop('social_logins');

        }
    }



    ````

####Step 2: Create a new Trait for the Auth Controller
To keep the code seperate from your normal classes, I've put them in a trait. My file is located in App\Http\Controllers\Auth\SocialiteControllerTrait.php. 


````
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
````


####Step 3:  Create a new Trait for your Model User. 
This is in App\ModelTraits\UserSocialiteTrait.php

````
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
````

####Step 4: Create a new SocialLogins Class
This is the App directory. This corresponds to the table that you created in the DB migration in step 1. 


````
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SocialLogin
 * @package App\Models\Access\User
 */
class SocialLogin extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'social_logins';

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];





    public static function getAvailableProviders()
    {


        $providers = [];


        if (strlen(getenv('BITBUCKET_CLIENT_ID'))) {
            $providers[] = 'bitbucket';
        }

        if (strlen(getenv('FACEBOOK_CLIENT_ID'))) {
            $providers[] = 'facebook';
        }

        if (strlen(getenv('GOOGLE_CLIENT_ID'))) {
            $providers[] = 'google';
        }

        if (strlen(getenv('GITHUB_CLIENT_ID'))) {
            $providers[] = 'github';
        }

        if (strlen(getenv('LINKEDIN_CLIENT_ID'))) {
            $providers[] = 'linkedin';
        }

        if (strlen(getenv('TWITTER_CLIENT_ID'))) {
            $providers[] = 'twitter';
        }


        return $providers;

    }
}
````

#### Step 5: Use the traits in your main models. 

In App\Users.php add the following below your namespace

    use App\ModelTraits\UserSocialiteTrait;

And in your class use the trait

	  use UserSocialiteTrait;


And in App\http\Controllers\Auth\AuthController.php import the trait with the following

    use App\Http\Controllers\Auth\SocialiteControllerTrait;

And use the class together with the other traits that are already being used

    use AuthenticatesAndRegistersUsers, ThrottlesLogins, SocialiteControllerTrait;


(The new one would be SocialiteControllerTrait)

#### Step 6: Register the Services 
Add the following into App\Config\services.php

 

        /* Socailite Links */
        'bitbucket' => [
            'client_id' => env('BITBUCKET_CLIENT_ID'),
            'client_secret' => env('BITBUCKET_CLIENT_SECRET'),
            'redirect' => env('BITBUCKET_REDIRECT'),
            'scopes' => [],
            'with' => [],
        ],

        'facebook' => [
            'client_id' => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('FACEBOOK_REDIRECT'),
            'scopes' => [],
            'with' => [],
        ],

        'github' => [
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'redirect' => env('GITHUB_REDIRECT'),
            'scopes' => [],
            'with' => [],
        ],

        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT'),

            /**
             * Only allows google to grab email address
             * Default scopes array also has: 'https://www.googleapis.com/auth/plus.login'
             * https://medium.com/@njovin/fixing-laravel-socialite-s-google-permissions-2b0ef8c18205
             */
            'scopes' => [
                'https://www.googleapis.com/auth/plus.me',
                'https://www.googleapis.com/auth/plus.profile.emails.read',
            ],

            'with' => [],
        ],

        'linkedin' => [
            'client_id' => env('LINKEDIN_CLIENT_ID'),
            'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
            'redirect' => env('LINKEDIN_REDIRECT'),
            'scopes' => [],
            'with' => [],
        ],

        'twitter' => [
            'client_id' => env('TWITTER_CLIENT_ID'),
            'client_secret' => env('TWITTER_CLIENT_SECRET'),
            'redirect' => env('TWITTER_REDIRECT'),
            'scopes' => [],
            'with' => [],
        ]

 


#### Step 6: Add in your Routes
Routes.php 

  
    //Social Login
    Route::get('/login/{provider}', 'Auth\AuthController@socialAuthRedirect')->name('auth.social.redirect');
    Route::get('/login/{provider}/callback', 'Auth\AuthController@socialAuthCallback')->name('auth.social.callback');


#### Step 7: Register your app 
Do this on each of the providers website complete the corresponding client_id's and secrets. The Callback URL should follow the pattern of yourappname/login/{provider-name}/callback. So for my app the Github callback may look like this 


   
    http://socialite.dev/login/github/callback

 

Put the relevant client_id's etc into your .env file. 

    FACEBOOK_CLIENT_ID=
    FACEBOOK_CLIENT_SECRET=
    FACEBOOK_REDIRECT=

    BITBUCKET_CLIENT_ID=
    BITBUCKET_CLIENT_SECRET=
    BITBUCKET_REDIRECT=

    GITHUB_CLIENT_ID=
    GITHUB_CLIENT_SECRET=
    GITHUB_REDIRECT=

    GOOGLE_CLIENT_ID=
    GOOGLE_CLIENT_SECRET=
    GOOGLE_REDIRECT=

    LINKEDIN_CLIENT_ID=
    LINKEDIN_CLIENT_SECRET=
    LINKEDIN_REDIRECT=

    TWITTER_CLIENT_ID
    TWITTER_CLIENT_SECRET
    TWITTER_REDIRECT=


#### Step 8: Add the links to your login page. 
Lastly, add the following code to your Login page directly or with a view partial. 

I did this just above the login button. 


````
    <hr />
    <p class="text-center">
    or log in with
    <ul class="list list-inline text-center">
        @foreach (\App\SocialLogin::getAvailableProviders() as $provider)
            <li><a href="{!!  route('auth.social.redirect', [ $provider])  !!}">{{ $provider }}</a></li>
        @endforeach
    
    </ul>
    
    </p>
    <hr />
````
