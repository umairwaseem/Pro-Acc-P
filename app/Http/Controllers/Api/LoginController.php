<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Validator, Auth, DB, Gate, File, Mail, Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Helpers\Response as R;
use App\Http\Helpers\Helper;
use \Carbon\Carbon;
use Illuminate\Validation\ValidationException;

use \App\Models\User;

use Log;

class LoginController extends Controller
{
    public function __construct(Request $request, Helper $helper)
    {        
        $this->request = $request;
        $this->helper = $helper;
        if(Auth::check()) {
            $this->userId = Auth::user()->id;
        } else {
            $this->userId = 1;
        }
    }

    public function Signup()
    {
        $token = Str::random(60);
        $data = $this->request->all();

        $v = Validator::make($data, [
        	'email' => 'required|unique:users',
        	'name' => 'required|string|max:20',
        	'password' => 'required',
        ]);

        if($v->fails()){
            return R::ValidationError($v->errors());
        }

        $data['password'] = Hash::make($data['password']);
        $data['api_token'] = hash('sha256', $token);

        DB::beginTransaction();

        try{

            $user = User::create($data);


            // do {
                
            //     $code = mt_rand(1000, 9999);
            // } 
            // while (Verify_user::where('code', $code)->first());

            // Verify_user::create(['code' => $code, 'user_id' => $user->id]);

            DB::commit();

            $msg = '<div style="background-color: #C7C7C7; color: #000; padding: 34px; border: solid 1px #585858; display: inline-block; font-size: 60px;">'.$code.'</div>';

            Mail::to($this->request->input('email'))->send(new GeneralEmail(['name' => $this->request->input('name')],'Verify Code Matou',$msg));


            return R::Success('Registration Successful', ['token' => $token, 'user_id'=> $user->id]);

        }catch(\Exception $e){
            DB::rollback();
            //dd($e);
            return R::SimpleError('Internal server error try again later'); 
        } 
    } 

    public function login()
    {
        try{

            $this->request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);
        
            $user = User::where('email', $this->request->email)->first();
        
            $user->tokens()->delete();
        
            if (! $user || ! Hash::check($this->request->password, $user->password)) {
                // throw ValidationException::withMessages([
                //     'email' => ['The provided credentials are incorrect.'],
                // ]);
                return R::SimpleError('The provided credentials are incorrect.');
            }
        
            $token = $user->createToken($this->request->email)->plainTextToken;
        
            $user->remember_token = $token;
        
            $user->save();
        
            return R::Success($token, $user);
        }catch(\Exception $e){
            return R::SimpleError($e); 
        }
    }

    public function logout()
    {
        $user = Auth::user();
        $user->tokens()->delete();
        return R::Success('Logged out successfully');
    }
}
