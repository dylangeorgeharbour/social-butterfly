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