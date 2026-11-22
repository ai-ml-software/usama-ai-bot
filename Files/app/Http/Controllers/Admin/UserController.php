<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\Newsletter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    //return index of all users
    public function index()
    {
        $page_title = 'Users';

        $user_query = User::get();



        // if (request()->s) {
        //     $users = User::where('name', 'LIKE', '%' . request()->s . '%')
        //         ->orWhere('email', 'LIKE', '%' . request()->s . '%')
        //         ->orWhere('username', 'LIKE', '%' . request()->s . '%')
        //         ->paginate(site('pagination'));
        // } else {
        //     $users = User::paginate(site('pagination'));
        // }



        $users = $user_query;


        return view('admin.users.index', compact(
            'page_title',
            'users',
            'user_query'
        ));
    }


    // return a single user
    public function viewUser(Request $request)
    {
        $user = User::withSum('deposits', 'amount')
            ->withSum('withdrawals', 'amount')
            ->withSum('botActivations', 'capital')
            ->withSum('botHistory', 'profit')
            ->withCount('botActivations')
            ->withCount('referredUsers')
            ->find($request->route('id'));
        if (!$user) {
            return redirect(route('admin.users.index'));
        }


        $page_title = $user->name ?? 'View User';

        return view('admin.users.view', compact(
            'page_title',
            'user'
        ));
    }


    // change user status
    public function status(Request $request)
    {
        $user = User::find($request->route('id'));

        if (!$user) {
            return response()->json(validationError('User not found'), 422);
        }

        $newStatus = $user->status == 1 ? 0 : 1;
        $user->update(['status' => $newStatus]);

        if ($newStatus == 1) {
            return response()->json(['message' => 'User has been reactivated']);
        } else {
            return response()->json(['message' => 'User has been suspended successfully']);
        }
    }


    //Edit user
    public function edit(Request $request)
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


        if (user($request->route('id'))->email !== $request->email) {
            $validationRules['email'] = 'required|email|unique:users';
        }

        if (user($request->route('id'))->username !== $request->username) {
            $validationRules['username'] = 'required|unique:users|min:3|max:10';
        }

        $request->validate($validationRules);

        //update the user
        $user = User::find($request->route('id'));
        $user->name = $request->name;
        $user->email = $request->email;
        $user->username = $request->username;
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

    // Credit-debit user
    public function creditDebit(Request $request)
    {
        // get the user
        $user = user($request->route('id'));
        // validate input
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required',
            'description' => 'required'
        ]);

        $amount = $request->amount;
        $type = $request->type;
        $description = $request->description;
        //credit if credit
        if ($type == 'credit') {

            $new_balance = $user->balance + $amount;
            $credit = User::find($user->id);
            $credit->balance = $new_balance;
            $credit->save();
            // log transaction
            recordNewTransaction($amount, $user->id, $type, $description);
            return response()->json(['message' => $user->username . ' has been credited']);
        } elseif ($type == 'debit') {
            // debit the user
            $new_balance = $user->balance - $amount;
            $debit = User::find($user->id);
            $debit->balance = $new_balance;
            $debit->save();
            // log transaction
            recordNewTransaction($amount, $user->id, $type, $description);
            return response()->json(['message' => $user->username . ' has been debited']);
        } else {
            return response()->json(validationError('Action not recognized'), 422);
        }
    }

    // Send Email
    public function sendEmail(Request $request) {}

    // change password
    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|confirmed'
        ]);

        $user = User::find($request->route('id'));
        $user->password = Hash::make($request->password);
        $user->save();
        $name = $user->username ?? 'User';
        return response()->json(['message' =>  $name . "'s password has been changed"]);
    }

    // login as user
    public function loginAsUser(Request $request)
    {
        session()->put('user', $request->route('id'));
        session()->put('login.as.user', $request->route('id'));
        return response()->json(['message' => 'Logged in sucessfully']);
    }

    // delete user
    public function delete(Request $request)
    {
        $user_id = $request->route('id');
        $user = User::find($user_id);
        if ($user) {
            // Delete the user
            $user->delete();


            // Clear any associated cached data
            Cache::forget('user:' . $user_id);


            return response()->json(['message' => 'User deleted successfully']);
        } else {
            return response()->json(validationError('Failed to delete user'), 422);
        }
    }

    // bulk action
    public function bulkAction(Request $request)
    {
        $allowed_actions = [
            'suspend',
            'activate',
            'loser_mode_on',
            'loser_mode_off',
            'email',
            'delete'
        ];

        $request->validate([
            'selected_ids' => 'required',
            'selected_action' => 'required'
        ]);

        $action = $request->selected_action;

        if (!in_array($action, $allowed_actions)) {
            return response()->json(validationError('Operation not recognized'), 422);
        }

        $user_ids = explode(',', $request->selected_ids);

        // Operation message to return in response
        $operation = ucfirst(str_replace('_', ' ', $action));

        switch ($action) {
            case 'suspend':
                // Set user status to inactive (0)
                User::whereIn('id', $user_ids)->update(['status' => 0]);
                break;

            case 'activate':
                // Set user status to active (1)
                User::whereIn('id', $user_ids)->update(['status' => 1]);
                break;

            case 'loser_mode_on':
                // Enable loser mode
                User::whereIn('id', $user_ids)->update(['loser_mode' => 1]);
                break;

            case 'loser_mode_off':
                // Disable loser mode
                User::whereIn('id', $user_ids)->update(['loser_mode' => 0]);
                break;

            case 'delete':
                // Delete the users
                User::whereIn('id', $user_ids)->delete();
                break;

            case 'email':
                // Store the user ids in session for email page
                session(['email_user_ids' => $user_ids]);
                break;

            default:
                return response()->json(validationError('Invalid operation'), 422);
        }

        return response()->json(['message' => "{$operation} performed successfully"]);
    }


    // bulk email
    public function bulkEmail()
    {
        $user_ids = session()->get('email_user_ids', []);
        if (empty($user_ids)) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No users selected for email');
        }
        // Get the user records
        $users = User::whereIn('id', $user_ids)->get();
        $page_title = "Newsletter";

        // Pass users to the email form view
        return view('admin.users.email', compact('users', 'page_title'));
    }

    // validate bulk email and queue with intervals
    public function bulkEmailValidate(Request $request)
    {
        // Validate the request
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Get user IDs from session
        $user_ids = session()->get('email_user_ids', []);

        // If no users selected, redirect back with message
        if (empty($user_ids)) {
            return response()->json(validationError('No users found for email'), 422);
        }

        // Get the user records
        $users = User::whereIn('id', $user_ids)->get();

        // Queue emails to each user with 10-minute intervals
        $delayMinutes = 0;

        foreach ($users as $user) {
            // Calculate a delay with some variation (between 9-11 minutes)
            $delayMinutes += 10 + rand(-1, 1);
            $delaySeconds = $delayMinutes * 60;

            // Queue the email with the specified delay
            Mail::to($user->email)
                ->later(now()->addSeconds($delaySeconds), new Newsletter($request->subject, $request->message, $user));
        }

        // Clear the session data
        session()->forget('email_user_ids');

        // Calculate approximate completion time
        $totalMinutes = $delayMinutes;
        $completionTime = now()->addMinutes($totalMinutes)->format('h:i A');

        // Return success message with estimated completion time
        return response()->json([
            'message' => 'Emails have been queued for ' . $users->count() . ' users. All emails should be sent by approximately ' . $completionTime
        ]);
    }
}
