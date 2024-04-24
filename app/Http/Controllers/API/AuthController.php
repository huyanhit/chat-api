<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class AuthController extends Controller
{
    public function register(Request $request){
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8'
        ]);

        if($validator->fails()){
            return $this->responseError(self::VALIDATE_ERROR_CODE, 'Validate error');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        $data  = ['data' => $user,'access_token' => $token, 'token_type' => 'Bearer'];

        return $this->responseSuccess($data, 'Register Success');
    }

    public function login(Request $request){
        if (!Auth::attempt($request->only('email', 'password'))){
            return $this->responseError([], self::AUTH_ERROR_CODE, 'Unauthorized');
        }
        $user  = User::where('email', $request['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;
        $data  = ['user' => $user, 'access_token' => $token, 'token_type' => 'Bearer'];

        return $this->responseSuccess($data, 'Login Success');
    }

    // method for user logout and delete token
    public function logout() {
        $data = Auth::user()->tokens()->delete();
        return $this->responseSuccess($data, 'You have successfully logged out and the token was successfully deleted');;
    }
}
