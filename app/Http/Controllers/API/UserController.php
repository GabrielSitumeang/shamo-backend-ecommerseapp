<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Rules\Password;
use Exception;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try{
            $request->validate([
                'name' => ['required','string','max:255'],
                'username' => ['required','string','max:255','unique:users'],
                'email'=>['required', 'string', 'email', 'max:255', 'unique:users'],
                'phone'=> ['required', 'string', 'max:255'],
                'password'=>['required', 'string', new Password],
            ]);

            User::create([
                'name'=>$request->name,
                'username'=>$request->username,
                'email'=>$request->email,
                'phone'=>$request->phone,
                'password'=>Hash::make($request->password),
            ]);
            
            $user = User::where('email', $request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token'=>$tokenResult,
                'token_type'=>'Bearer',
                'user'=>$user
            ], 'User Registered');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message'=> 'Something went wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function login(Request $request)
    {
        try{
            $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);

            $credentials = request(['email', 'password']);

            if(!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message'=> 'Unauthorized'
                ], 'Authentication Failed', 500);
            }

            $user = User::where('email', $request->email)->first();

            if(! Hash::check($request->password, $user->password, [])){
                throw new \Exception('Invalid Credentials');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token'=>$tokenResult,
                'token_type'=>'Bearer',
                'user'=>$user
            ], 'Authenticated');


        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message'=> 'Something went wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Data Profile user berhasil diambil');
    }

    // public function updateProfile(Request $request)
    // {
    //     try{
    //         $request->validate([
    //             'name' => ['required','string','max:255'],
    //             'username' => ['required','string','max:255','unique:users'],
    //             'email'=>['required', 'string', 'email', 'max:255', 'unique:users', Auth::id()],
    //             'phone'=> ['required', 'string', 'max:255'],
    //             'password'=>['required', 'string', new Password],
    //         ]);

    //        $user = Auth::user();

    //        $user->update($request->all());

    //        return ResponseFormatter::success($user, 'Profile updated');

    //     } catch (Exception $error) {
    //         return ResponseFormatter::error(null, 'Failed to update profile', 500);
    //     }

    // }

    public function updateProfile(Request $request)
    {
        $data = $request->all();

        // Get the authenticated user
        $user = Auth::user();

        // Ensure $user is an instance of the User model
        if (!($user instanceof User)) {
            return ResponseFormatter::error(null, 'User not found', 404);
        }

        // Update the user profile with the validated data
        try {
            $user->update($data);

            // Return a success response
            return ResponseFormatter::success($user, 'Profile updated');
        } catch (\Exception $e) {
            // Return an error response if an exception occurs during update
            return ResponseFormatter::error(null, 'Failed to update profile', 500);
        }
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken()->delete();

        return ResponseFormatter::success($token, 'Token Revoked');
    }
}