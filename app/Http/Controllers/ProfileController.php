<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function editProfile(Request $request){
        $request->validate([
            'firstname'=>'required',
            'lastname'=>'required',
            'telephone'=>'required',
            'email'=>'required',
        ]);
        User::find(Auth::user()->id)->update([
            'firstname'=>$request->firstname,
            'lastname'=>$request->lastname,
            'telephone'=>$request->telephone,
            'email'=>$request->email
        ]);
        return to_route('profile')->with('succes','Informations modifiées');
    }
    public function changePassword(Request $request){
        $request->validate([
            'password'=>'required',
            'newpassword'=>'required',
            'confirmpassword'=>'required',
        ]);
        if (!Hash::check($request->password,Auth::user()->password)) {
            /* return back()->withErrors([
                'password'=>'Mot de passe invalide'
            ]); */
            if($request->ajax()){
                return response()->json([
                    'notMatch'=>true
                ]);
            }
        }
        if ($request->newpassword!==$request->confirmpassword) {
            return response()->json([
                'newNotConfirm'=>true
            ]);
        }
        User::find(Auth::user()->id)->update([
            'password'=>Hash::make($request->newpassword)
        ]);
        session()->flash('succes','Mot de passe modifié');
        return response()->json([
            'redirect'=>true
        ]);
    }
}
