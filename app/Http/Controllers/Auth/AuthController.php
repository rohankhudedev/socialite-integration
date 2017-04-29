<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

use Auth;
use Socialite;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware($this->guestMiddleware(), ['except' => 'logout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }

    public function redirectToProvider($provider=null)
    {
        if (!config("services.$provider")) {
            abort('404');
        } //just to handle providers that doesn't exist
        return Socialite::driver($provider)->redirect();

        /*
        ***Snippet2*** - for extra details pass fields to facebook
        if ($provider=='google') {
            return Socialite::driver($provider)->redirect();
        } elseif ($provider=='facebook') {
            return Socialite::driver($provider)->fields([
                'first_name', 'last_name', 'email', 'gender', 'birthday'
            ])->scopes([
                'email', 'user_birthday'
            ])->redirect();
        }
         */
    }

    /**
     * Obtain the user information from provider.  Check if the user already exists in our
     * database by looking up their provider_id in the database.
     * If the user exists, log them in. Otherwise, create a new user then log them in. After that
     * redirect them to the authenticated users homepage.
     *
     * @return Response
     */
    public function handleProviderCallback($provider)
    {
        if ($user = $this->socialite->with($provider)->user()) {
            $user = Socialite::driver($provider)->user();

            /*
            ***Snippet2***
            if ($provider=='google') {
                $user = Socialite::driver($provider)->user();
            } elseif ($provider=='facebook') {
                $user = Socialite::driver($provider)->fields([
                    'first_name', 'last_name', 'email', 'gender', 'birthday'
                ])->user();
            }
             */

            $authUser = $this->findOrCreateUser($user, $provider);
            Auth::login($authUser, true);
            //this will redirect to website index page of your website
            return redirect($this->redirectTo);
            /*This will redirect to last requested page by user.
            Note - suppose user manually went to login then below code will show 404 error, because there
            is no last requested url, so write handler for redirecting all 404 to index page
            */
            //return Redirect::intended('home');
        } else {
            return 'something went wrong';
        }
    }

    /**
    * If a user has registered before using social auth, return the user
    * else, create a new user object.
    * @param  $user Socialite user object
    * @param $provider Social auth provider
    * @return  User
    */
    public function findOrCreateUser($user, $provider)
    {
        /*
         ***Snippet2***
         $name=$lastname=$email=$phone=$country=$state=$city=$address=$zipcode=$gender="";
            if ($provider=='google') {
                $email=$user->email;
                $name=$user->user['name']['givenName'];
                $lastname=$user->user['name']['familyName'];
                $gender=$user->user['gender'];
            } elseif ($provider=='facebook') {
                $email=isset($user->email)?$user->email:"";
                $name=$user->user['first_name'];
                $lastname=$user->user['last_name'];
                $gender=$user->user['gender'];
            }
         */
        $authUser = User::where('provider_id', $user->id)->first();
        if ($authUser) {
            return $authUser;
        }
        return User::create([
            'name'     => $user->name,
            'email'    => $user->email,
            'provider' => $provider,
            'provider_id' => $user->id
        ]);
    }
    /**
     * The redirectToProvider method takes care of sending the user to the OAuth provider, while the handleProviderCallback method will read the incoming request and retrieve the user's information from the provider. Notice we also have a findOrCreateUser method which looks up the user object in the database to see if it exists. If a user exists, the existing user object will be returned. Otherwise, a new user will be created. This prevents users from signing up twice.
     */
}
