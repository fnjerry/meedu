<?php

/*
 * This file is part of the Qsnh/meedu.
 *
 * (c) XiaoTeng <616896861@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Http\Controllers\Auth;

use Socialite;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Socialite as SocialiteModel;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
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

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/member';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except(
            'logout',
            'redirectToProvider',
            'handleProviderCallback'
        );
    }

    protected function username()
    {
        return 'mobile';
    }

    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($app)
    {
        return Socialite::driver($app)->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback($app)
    {
        $user = Socialite::driver($app)->user();
        if (Auth::check()) {
            // 已登录，绑定第三方账号
            if (Auth::user()->socialite()->whereApp($app)->whereAppUserId($user->getId())->exists()) {
                flash('当前用户已经绑定过该应用啦。请不要重复绑定', 'warning');
            } else {
                Auth::user()->socialite()->save(new SocialiteModel([
                    'app' => $app,
                    'app_user_id' => $user->getId(),
                    'data' => serialize($user),
                ]));
                flash('绑定成功', 'success');
            }

            return redirect('member');
        }

        // 未登录，使用第三方账号登录
        $socialite = SocialiteModel::whereApp($app)->whereAppUserId($user->getId())->first();
        if (! $socialite) {
            return redirect('login');
        }
        if ($socialite->user_id == 0) {
            return redirect('login');
        }
        // 尝试登录
        Auth::loginUsingId($socialite->user_id, true);
        flash('登录成功', 'success');

        return redirect('member');
    }
}
