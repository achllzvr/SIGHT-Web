<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DoctorProfile;
use App\Services\PhpMailerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminApiController extends Controller
{
    public function __construct(
        private readonly PhpMailerService $mailer,
    ) {
    }

    /**
     * GET /api/web/admin/analytics
     */
    public function getAnalytics()
    {
        if (strtolower((string) auth()->user()->role) !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activeDoctors = User::whereRaw('LOWER(role) = ?', ['doctor'])->count();
        $activeGuardians = User::whereRaw('LOWER(role) = ?', ['guardian'])->count();
        $activeChildren = User::whereRaw('LOWER(role) = ?', ['child'])->count();
        $admins = User::whereRaw('LOWER(role) = ?', ['admin'])->count();

        return response()->json([
            'platform_health' => [
                'total_users' => $activeDoctors + $activeGuardians + $activeChildren + $admins,
                'doctors' => $activeDoctors,
                'guardians' => $activeGuardians,
                'children' => $activeChildren,
                'admins' => $admins,
            ],
        ], 200);
    }

    /**
     * POST /api/web/admin/create-professional
     */
    public function createProfessional(Request $request)
    {
        if (strtolower((string) auth()->user()->role) !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:user,email',
            'license_number' => 'required|string|unique:doctor_profile,license_number',
        ]);

        $tempPassword = Str::random(12);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password_hash' => Hash::make($tempPassword),
            'role' => 'Doctor',
        ]);

        $doctor = DoctorProfile::create([
            'user_id' => $user->user_id,
            'license_number' => $request->license_number,
            'is_validated' => 1,
        ]);

        try {
            $this->mailer->sendProfessionalInvitation($user, $tempPassword);
        } catch (\Throwable $e) {
            logger()->error('Failed to send professional account email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Professional account created successfully. Temporary password sent by email.',
            'doctor' => [
                'doctor_id' => $doctor->doctor_id,
                'user_id' => $user->user_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'license_number' => $doctor->license_number,
                'is_validated' => (bool) $doctor->is_validated,
            ],
        ], 201);
    }
}
