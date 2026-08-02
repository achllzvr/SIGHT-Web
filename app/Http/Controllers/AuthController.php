<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DoctorProfile;
use App\Services\UserPresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const PASSWORD_RESET_EXPIRY_MINUTES = 60;

    public function __construct(
        private readonly UserPresenceService $presence,
    ) {
    }

    /**
     * Show the unified login form
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle unified login - authenticates user and detects role for redirection
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt to authenticate the user without role filter
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Get the authenticated user and check their role
            $user = Auth::user();
            $this->presence->touch($user);
            
            // Check verification status
            if (is_null($user->email_verified_at)) {
                Auth::logout();
                $request->session()->invalidate();
                return redirect()->route('login')->with('info', 'Please verify your email using the link we sent you before signing in.');
            }

            if ($this->requiresFirstLoginReset($user)) {
                return redirect()->route('password.first.form');
            }

            // Redirect based on user role (case-insensitive)
            $role = strtolower($user->role);
            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($role === 'doctor') {
                return redirect()->route('doctor.dashboard');
            }
            
            // Fallback if role is not recognized
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')->with('error', 'Your account role is not recognized. Please contact support.');
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Show the doctor login form
     */
    public function showDoctorLogin()
    {
        return view('auth.doctor-login');
    }

    /**
     * Show the admin login form
     */
    public function showAdminLogin()
    {
        return view('auth.admin-login');
    }

    /**
     * Handle doctor login
     */
    public function doctorLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Case-insensitive role match (prod may store "Doctor")
        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $user = Auth::user();
            if (strtolower((string) $user->role) !== 'doctor') {
                Auth::logout();
                $request->session()->invalidate();
                throw ValidationException::withMessages([
                    'email' => 'Invalid credentials.',
                ]);
            }

            $request->session()->regenerate();
            $this->presence->touch($user);

            if (is_null($user->email_verified_at)) {
                Auth::logout();
                $request->session()->invalidate();
                return redirect()->route('doctor.login')->with('info', 'Please verify your email using the link we sent you before signing in.');
            }
            if ($this->requiresFirstLoginReset($user)) {
                return redirect()->route('password.first.form');
            }

            return redirect()->route('doctor.dashboard');
        }

        throw ValidationException::withMessages([
            'email' => 'Invalid credentials.',
        ]);
    }

    /**
     * Handle admin login
     */
    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $user = Auth::user();
            if (strtolower((string) $user->role) !== 'admin') {
                Auth::logout();
                $request->session()->invalidate();
                throw ValidationException::withMessages([
                    'email' => 'Invalid credentials.',
                ]);
            }

            $request->session()->regenerate();
            $this->presence->touch($user);

            if ($this->requiresFirstLoginReset($user)) {
                return redirect()->route('password.first.form');
            }

            return redirect()->route('admin.dashboard');
        }

        throw ValidationException::withMessages([
            'email' => 'Invalid credentials.',
        ]);
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $this->presence->markOffline($user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }

    /**
     * Show the signup form
     */
    public function showSignup()
    {
        return view('auth.signup');
    }

    /**
     * Show forgot password request form.
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send reset link to user email.
     */
    public function sendPasswordResetLink(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $this->ensurePasswordResetTableExists();

        $email = strtolower(trim($validated['email']));
        $user = User::where('email', $email)->first();

        // Do not reveal account existence.
        if (!$user) {
            return back()->with('status', 'If your email exists in our records, a password reset link has been sent.');
        }

        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $tokenHash,
                'created_at' => now(),
            ]
        );

        $resetUrl = route('password.reset', ['token' => $plainToken]) . '?email=' . urlencode($email);

        Mail::send('emails.password-reset', [
            'name' => $user->display_name,
            'resetUrl' => $resetUrl,
            'expiryMinutes' => self::PASSWORD_RESET_EXPIRY_MINUTES,
        ], function ($message) use ($email) {
            $message->to($email)->subject('LUMI Password Reset');
        });

        return back()->with('status', 'If your email exists in our records, a password reset link has been sent.');
    }

    /**
     * Show reset password page.
     */
    public function showResetPassword(Request $request, string $token)
    {
        $email = (string) $request->query('email', '');

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Perform password reset.
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->ensurePasswordResetTableExists();

        $email = strtolower(trim($validated['email']));
        $reset = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$reset) {
            return back()->withErrors(['email' => 'This reset link is invalid or has expired.'])->withInput();
        }

        $tokenMatches = hash_equals((string) $reset->token, hash('sha256', $validated['token']));
        $expiresAt = now()->subMinutes(self::PASSWORD_RESET_EXPIRY_MINUTES);
        $isExpired = $reset->created_at < $expiresAt;

        if (!$tokenMatches || $isExpired) {
            return back()->withErrors(['email' => 'This reset link is invalid or has expired.'])->withInput();
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Unable to reset password for this account.'])->withInput();
        }

        $user->password_hash = Hash::make($validated['password']);
        if (Schema::hasColumn('user', 'must_change_password')) {
            $user->must_change_password = 0;
        }
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()
            ->route('password.updated')
            ->with('password_reset_success', true);
    }

    /**
     * Post-reset success page (shows modal, then redirects to login).
     */
    public function showPasswordUpdated()
    {
        if (!session('password_reset_success')) {
            return redirect()->route('login');
        }

        return view('auth.reset-password', [
            'token' => '',
            'email' => '',
        ]);
    }

    /**
     * Signed email verification link for clinicians/admins.
     */
    public function verifyEmail(Request $request, int $id, string $hash)
    {
        if (!$request->hasValidSignature()) {
            return view('auth.verification-failed', [
                'message' => 'This verification link is invalid or has expired.',
            ]);
        }

        $user = User::find($id);
        if (!$user || !hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return view('auth.verification-failed', [
                'message' => 'This verification link does not match any account.',
            ]);
        }

        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            if (Schema::hasColumn('user', 'status') && strtolower((string) ($user->status ?? '')) === 'pending') {
                $user->status = 'active';
            }
            $user->save();

            if (Schema::hasTable('doctor_profile') && strtolower((string) $user->role) === 'doctor') {
                $profile = DoctorProfile::where('user_id', $user->user_id)->first();
                if ($profile) {
                    $profile->is_validated = 1;
                    $profile->save();
                }
            }
        }

        return view('auth.verification-success');
    }

    public function showVerificationSuccess()
    {
        return view('auth.verification-success');
    }

    public function showVerificationFailed()
    {
        return view('auth.verification-failed');
    }

    /**
     * Build a temporary signed verification URL for a user.
     */
    public static function verificationUrlFor(User $user): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addDays(2),
            [
                'id' => $user->user_id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }

    /**
     * Ensure password reset token table exists for legacy DBs without migrations table.
     */
    private function ensurePasswordResetTableExists(): void
    {
        if (Schema::hasTable('password_reset_tokens')) {
            return;
        }

        Schema::create('password_reset_tokens', function ($table) {
            $table->string('email')->primary();
            $table->string('token', 64);
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Show first-login password change form.
     */
    public function showFirstLoginPasswordForm()
    {
        if (!$this->requiresFirstLoginReset(Auth::user())) {
            return $this->redirectToRoleDashboard(Auth::user());
        }

        return view('auth.first-login-reset');
    }

    /**
     * Handle first-login mandatory password update.
     */
    public function updateFirstLoginPassword(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->password_hash = Hash::make($validated['password']);
        if (Schema::hasColumn('user', 'must_change_password')) {
            $user->must_change_password = 0;
        }
        $user->save();

        return $this->redirectToRoleDashboard($user)->with('status', 'Password updated successfully.');
    }

    /**
     * Check if current user must change password on first login.
     */
    private function requiresFirstLoginReset(?User $user): bool
    {
        if (!$user || !Schema::hasColumn('user', 'must_change_password')) {
            return false;
        }

        return (int) ($user->must_change_password ?? 0) === 1;
    }

    /**
     * Redirect to dashboard based on role.
     */
    private function redirectToRoleDashboard(User $user)
    {
        $role = strtolower((string) $user->role);
        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        if ($role === 'doctor') {
            return redirect()->route('doctor.dashboard');
        }

        return redirect()->route('login');
    }

    /**
     * Store new doctor account
     */
    public function storeSignup(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:user,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'clinic' => 'required|string|max:255',
            'specialty' => 'required|string|max:255',
            'license_number' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        // Create the user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'role' => 'doctor',
            'status' => 'active',
            'email_verified_at' => null, // Start as unverified
        ]);

        $doctorProfile = DoctorProfile::where('user_id', $user->user_id)->first();
        if ($doctorProfile) {
            $doctorProfile->phone = $validated['phone'];
            $doctorProfile->clinic = $validated['clinic'];
            $doctorProfile->specialty = $validated['specialty'];
            $doctorProfile->license_number = $validated['license_number'];
            $doctorProfile->location = $validated['location'] ?? '';
            $doctorProfile->is_validated = 0;
            $doctorProfile->save();
        } else {
            DoctorProfile::create([
                'user_id' => $user->user_id,
                'phone' => $validated['phone'],
                'clinic' => $validated['clinic'],
                'specialty' => $validated['specialty'],
                'license_number' => $validated['license_number'],
                'location' => $validated['location'] ?? '',
                'is_validated' => 0,
            ]);
        }

        return redirect()->route('login')->with('info', 'Clinician account created successfully. Please wait for administrator verification before logging in.');
    }
}
