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