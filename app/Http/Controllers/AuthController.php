<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function unauthorized()
    {
        return response()->json(['message' => 'Not Authorized'], 401);
    }

    public function login(Request $request)
    {
        $log = ['message' => ''];

        $email    = $request->input('email');
        $password = $request->input('password');

        if ($email && $password) {
            $token = Auth::attempt([
                'email'    => $email,
                'password' => $password,
            ]);

            if (!$token) {
                return $log = ['message' => 'Email or Password incorrect.'];
            }

            $log['token'] = $token;
            
            return $log;
        }
            $log = ['message' => 'Data was not sent.'];
            return $log;
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return ['message' => ''];
    }

    public function refresh(Request $request)
    {
        $token = Auth::refresh();
        return [
            'message' => '',
            'token'   => $token,
        ];
    }

    public function create(Request $request)
    {
        $log = ['message' => ''];

        $name      = $request->input('name');
        $email     = $request->input('email');
        $password  = $request->input('password');
        $birthdate = $request->input('birthdate');

        if ($name && $email && $password && $birthdate) {
            if (strtotime($birthdate) === false) {
                $log['message'] = 'Invalid birthdate';
                return $log;
            };

            $emailExist = User::where('email', $email)->count();

            if ($emailExist === 0) {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $newUser = new User();
                $newUser->name      = $name;
                $newUser->email     = $email;
                $newUser->password  = $hash;
                $newUser->birthdate = $birthdate;
                $newUser->save();

                $token = Auth::attempt([
                    'email'    => $email,
                    'password' => $password,
                ]);

                if (!$token) {
                    $log['message'] = 'Incorrect email or password';
                    return $log;
                }

                    $log['token'] = $token;

            } else {
                $log['message'] = 'Email Already Exist';
                return $log;
            }

        } else {
             $log['message'] = 'All fields must be filled in';
             return $log;
        }

        return $log;
    }
}
