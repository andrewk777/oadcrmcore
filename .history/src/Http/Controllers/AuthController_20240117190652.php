<?php

namespace Oadsoft\Crmcore\Http\Controllers;

use App\Jobs\DeviceVerificationJob;
use App\Models\UsersDevice;
use Auth, Hash, DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = false;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function login(Request $request)
    {
        $user = \User::select('id','hash','name','email','password','sys_access', 'last_login')->where('email',$request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {

            return response()->json([ 'status' => 'error', 'res' => 'Invalid Credentials' ], 250);

        } else if ($user->sys_access != 1) {

            return response()->json([ 'status' => 'error', 'res' => 'Access Denied' ], 250);

        }
        if (app()->environment() === 'production' && auth()->user()->id != 1) {
            $device_token = Cookie::get('device_token');
            if ($device_token && $user->last_login) {
                $device_token = Cookie::get('device_token');
                $device = UsersDevice::where('token', $device_token)
                    ->where('users_id', $user->id)
                    ->where('verified', true)
                    ->first();
                if (!$device) {
                    $code = '';

                    for($i = 0; $i < 6; $i++) {
                        $code .= mt_rand(0, 9);
                    }

                    $user_device = new UsersDevice();
                    $user_device->users_id = $user->id;
                    $user_device->token = $device_token;
                    $user_device->verification_code = $code;
                    $user_device->expire_date = Carbon::now()->addMonth(3);
                    $user_device->save();

                    dispatch(new DeviceVerificationJob([
                        'email' => $user->email,
                        'code' => $user_device->verification_code
                    ]))->delay(Carbon::now());

                    return response()->json([ 'status' => 'warning', 'res' => 'Please verify your email', 'action' => 'verify', 'id' => $user_device->id ], 250);
                }
            }
        }

        //delete all other device sessions for this user
        $user->tokens()->where('tokenable_id',$user->id)->delete();

        auth()->login($user);
        \User::where('id', $user->id)->update(['last_login' => Carbon::now()]);
        $session = $user->createToken('device-session');

        return response()->json([
            'status'        => 'success',
            'res'           => 'Signed In',
            'token'         => $session->plainTextToken,
            'user'          => collect($user)->except(['id','sys_access']),
            'expiration'    => config('sanctum.expiration')
        ],
            200);

    }

    public function logout(Request $request) {

        if (Auth::check()) {
            Auth::logout();
        }
        if ($request->bearerToken()) {
            $model = \Laravel\Sanctum\Sanctum::$personalAccessTokenModel;
            $tokenSession = $model::findToken( $request->bearerToken() );
            if ($tokenSession) $tokenSession->delete();
        }

        return response()->json([ 'status' => 'success' ], 200);
    }

    public function resetPasswordSendEmail(Request $request) {

        if ($user = \User::active()->where('email',$request->email)->first()) {

            $token = Str::random(40);

            DB::table(config('auth.passwords.users.table'))->insert([
                'email'         => $request->email,
                'token'         => $token,
                'created_at'    => now()
            ]);

            $user->notify(new ResetPasswordNotification($token));

            return response()->json([ 'status' => 'success', 'res' => 'Password Reset Email Has Been Sent' ], 200);

        } else {

            return response()->json([ 'status' => 'error', 'res' => 'Email is not recognized' ], 250);

        }

    }

    public function resetPasswordLink(Request $request) {

        if (DB::table(config('auth.passwords.users.table'))->where([
            ['email',$request->email],
            ['token',$request->token],
            ['created_at', '>=' ,\Carbon::now()->subMinutes(60)]
        ])->latest()->first()) {

            return view('app');

        } else {

            return redirect('/auth/reset_password_link/expired');

        }
    }

    public function resetPasswordComplete(Request $request) {

        $validator = \Validator::make(
            $request->only('email','token','password','password_confirm'),
            [
                'email'             => 'required',
                'token'             => 'required',
                'password_confirm'  => 'required|same:password',
                'password'          => config('project.password_rules')
            ],
            [],
            [
                'password'          => 'Password',
                'password_confirm'  => 'Confirm Password'
            ]

        );

        if ( $validator->fails() ) {
            return response()->json(['status' => 'error', 'res' => implode('<br>',$validator->errors()->all()) ], 200);
        }

        \User::where('email',$request->email)->update(['password' => \Hash::make($request->password)]);

        return response()->json(['status' => 'success', 'res' => 'Password Updated'], 200);
    }


    public function auth_check() {

        return response()->json(['status' => auth()->user()->currentAccessToken()->id]);

    }

    public function verifyDevice(Request $request)
    {
        if ($request->has('id') && $request->has('code')) {
            $device = UsersDevice::find($request->id);
            if ($device->verification_code == $request->code) {
                UsersDevice::where('token', $device->token)->update(['verified' => true]);

                $user = $device->user;
                $user->tokens()->where('tokenable_id',$user->id)->delete();

                auth()->login($user);
                \User::where('id', $user->id)->update(['last_login' => Carbon::now()]);
                $session = $user->createToken('device-session');

                return response()->json([
                    'status'        => 'success',
                    'res'           => 'Signed In',
                    'token'         => $session->plainTextToken,
                    'user'          => collect($user)->except(['id','sys_access']),
                    'expiration'    => config('sanctum.expiration')
                ],
                    200);

            }
        }

        return response()->json([ 'status' => 'error', 'res' => 'Verification code is wrong' ], 250);
    }

}
