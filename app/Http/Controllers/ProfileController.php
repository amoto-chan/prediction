<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use App\Rules\SafeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', new SafeEmail, 'max:150', 'unique:users,email,'.$user->id],
            'current_password' => ['nullable', 'string', 'max:4096', 'required_with:password'],
            'password' => ['nullable', 'string', 'confirmed', 'min:8', 'max:4096'],
        ]);

        if (! empty($data['password']) && ! Hash::check($data['current_password'] ?? '', $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        SystemLog::create([
            'user_id' => $user->id,
            'action' => 'Updated profile',
            'description' => 'Updated their own profile settings.',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Profile updated.');
    }
}
