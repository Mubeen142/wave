<?php

namespace App\Http\Controllers\Auth;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;

class SocialiteController
{

    public function discord()
    {
        return Socialite::driver('discord')->redirect();
    }

    public function discordCallback()
    {
        $discord = Socialite::driver('discord')->user();

        // Check If user already exists, if yes, login through existing account.
        if (User::where('email', '=', $discord->email)->exists()) {
            $user = User::where('email', '=', $discord->email)->first();
            
            $user->discord = json_decode($user->discord);

            if(!isset($user->discord->id)) {
                $this->UpdateDiscordId($discord->email, $discord->user);
                return redirect('/login')->with(['message' => 'Something went wrong, please try again.', 'message_type' => 'warning']);  
            }

            if(intval($user->discord->id) !== intval($discord->id)) {
                return redirect('/login')->with(['message' => 'You are not authorized to login using this Discord account.', 'message_type' => 'danger']);  
            }

            $this->UpdateDiscordId($discord->email, $discord->user);
            Auth::login($user);
            return redirect(route('wave.dashboard'))->with(['message' => 'You have logged in to WemX', 'message_type' => 'success']);  
         }
        
         // If user does not already exists, create it below
        $password = Str::random(8);
        $user = User::updateOrCreate([
            'name' => $discord->user['username'],
            'email' => $discord->email,
            'username' => $discord->user['username'],
            'password' => bcrypt($password),
            'role_id' => 2,
            'verified' => 1,
            'discord' => $discord->user,
        ]);

        $this->UpdateDiscordId($discord->email, $discord->user);
        Auth::login($user);
        return redirect(route('wave.dashboard'))->with(['message' => 'You have logged in to WemX', 'message_type' => 'success']);
    }

    private function UpdateDiscordId($user_email, $discord)
    {
        User::where('email', $user_email)->update([
            'discord' => $discord,
        ]);

        return true;
    }

}
