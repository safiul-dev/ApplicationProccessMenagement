<?php

namespace App\Http\Controllers;

use App\Mail\CodeSendMail;
use App\Models\Code;
use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class HomeController extends Controller
{
    public function index()
    {
        if (Gate::allows('is-teacher', auth()->user())){
            $totalApps = StudentApplication::where('teacher_id', auth()->id())->count('id');
            $totalStudent = User::where('role', 'student')->count('id');
            $totalRejected = StudentApplication::where('teacher_id', auth()->id())
                                                ->where('status', 0)->count('id');
            $totalAccepted = StudentApplication::where('teacher_id', auth()->id())
                                                 ->where('status', 1)->count('id');
            return view('contents.dashboard.teacher',
                ['totalApps' => $totalApps, 'totalStudent' => $totalStudent,
                 'totalRejected' => $totalRejected, 'totalAccepted' => $totalAccepted
                ]
            );
        }else {
            $totalApps = StudentApplication::where('user_id', auth()->id())
                                             ->count('id');
            $totalRejected = StudentApplication::where('user_id', auth()->id())
                                             ->where('status', 0)->count('id');
            $totalAccepted = StudentApplication::where('user_id', auth()->id())
                                              ->where('status', 1)->count('id');
            $totalPanding = StudentApplication::where('user_id', auth()->id())
                                              ->where('status', 2)->count('id');
            return view('contents.dashboard.student',
                ['totalApps' => $totalApps, 'totalPanding' => $totalPanding,
                 'totalRejected' => $totalRejected, 'totalAccepted' => $totalAccepted
                    ]
        );

        }

    }

    public function registerIndex () {
        return view('auth.register');
    }

    public function register (Request $request)
    {
        $user = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'g-recaptcha-response' => 'recaptcha'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email'=> $request->email,
            'phone' => $request->phone,
            'password'=> bcrypt($request->password),
            'role' => $request->role
        ]);
        $details = [
            'code' => rand(5666, 9555),
            'user' =>$user
        ];
        Code::create(['user_id'=> $user->id, 'code' => $details['code']]);
        Mail::to('safiul7303@gmail.com')->send(new CodeSendMail($details));
        $user_id = $user->id;
        return view('auth.code-to-login', compact('user_id'));
    }

    public function codeIndex() {
        return view('auth.code-to-login');
    }

    public function codeToLogin (Request $request) {
        $request->validate([
            'code' => 'required',
            'user_id' => 'required',
        ]);

        $code = Code::where('code', $request->code)->first();
        if ($code->code != $request->code) {
            return redirect()->back()->with('error', 'Invalid code');
        }

        $user = User::find($request->user_id);
        auth()->login($user);
        return redirect('/');

    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerate();
        return redirect()->route('login');
    }
}
