<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ChildProfile;
use App\Models\DoctorProfile;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Build the base doctors query with optional search/status filters.
     */
    private function professionalsQuery(Request $request)
    {
        $query = User::whereRaw('LOWER(role) = ?', ['doctor'])->with('doctorProfile');

        $status = strtolower((string) $request->query('status', 'all'));
        if ($status !== '' && $status !== 'all') {
            $query->whereRaw('LOWER(status) = ?', [$status]);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%");

                if (Schema::hasColumn('user', 'first_name')) {
                    $q->orWhere('first_name', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('user', 'last_name')) {
                    $q->orWhere('last_name', 'like', "%{$search}%");
                }

                if (Schema::hasTable('doctor_profile')) {
                    $q->orWhereExists(function ($sub) use ($search) {
                        $sub->select(DB::raw(1))
                            ->from('doctor_profile')
                            ->whereColumn('doctor_profile.user_id', 'user.user_id');

                        $sub->where(function ($profileWhere) use ($search) {
                            if (Schema::hasColumn('doctor_profile', 'clinic')) {
                                $profileWhere->orWhere('doctor_profile.clinic', 'like', "%{$search}%");
                            }
                            if (Schema::hasColumn('doctor_profile', 'specialty')) {
                                $profileWhere->orWhere('doctor_profile.specialty', 'like', "%{$search}%");
                            }
                            if (Schema::hasColumn('doctor_profile', 'location')) {
                                $profileWhere->orWhere('doctor_profile.location', 'like', "%{$search}%");
                            }
                            if (Schema::hasColumn('doctor_profile', 'license_number')) {
                                $profileWhere->orWhere('doctor_profile.license_number', 'like', "%{$search}%");
                            }
                            if (Schema::hasColumn('doctor_profile', 'phone')) {
                                $profileWhere->orWhere('doctor_profile.phone', 'like', "%{$search}%");
                            }
                        });
                    });
                }
            });
        }

        return $query->orderByDesc('created_at')->orderByDesc('user_id');
    }

    /**
     * Format a professional row for dashboard API/UI.
     */
    private function formatProfessional(User $professional): array
    {
        $profile = $professional->doctorProfile;

        $patientCount = 0;
        if ($profile) {
            $patientCount = \App\Models\PatientAccessLog::where('clinician_id', $profile->user_id)->count();
        }

        return [
            'id' => $professional->user_id,
            'name' => $this->displayName($professional),
            'first_name' => $professional->first_name ?? '',
            'last_name' => $professional->last_name ?? '',
            'email' => $professional->email,
            'phone' => $profile->phone ?? ($professional->phone ?? 'N/A'),
            'location' => $profile->location ?? ($professional->location ?? 'N/A'),
            'clinic' => $profile->clinic ?? ($professional->clinic ?? 'N/A'),
            'specialty' => $profile->specialty ?? ($professional->specialty ?? 'N/A'),
            'license_number' => $profile->license_number ?? ($professional->license_number ?? 'N/A'),
            'is_verified' => !is_null($professional->email_verified_at),
            'status' => strtolower($professional->status ?? 'active'),
            'created_at' => $professional->created_at ? $professional->created_at->format('M d, Y') : null,
            'patients' => $patientCount,
            'last_active' => 'Never',
            'joined_date' => $professional->created_at ? $professional->created_at->format('M d, Y') : 'N/A',
        ];
    }

    private function countPatients(): int
    {
        return (int) ChildProfile::count();
    }

    /**
     * Show the admin dashboard
     */
    public function dashboard(Request $request)
    {
        $admin = Auth::user();
        if (is_null($admin->email_verified_at)) {
            // If not verified, show pending verification view
            return view('admin.pending-verification', compact('admin'));
        }

        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [5, 10], true)) {
            $perPage = 10;
        }

        $professionalsPaginator = $this->professionalsQuery($request)->paginate($perPage)->withQueryString();
        $professionals = $professionalsPaginator->getCollection()->map(function ($professional) {
            return $this->formatProfessional($professional);
        })->values()->toArray();

        $pagination = [
            'current_page' => $professionalsPaginator->currentPage(),
            'last_page' => $professionalsPaginator->lastPage(),
            'per_page' => $professionalsPaginator->perPage(),
            'total' => $professionalsPaginator->total(),
            'from' => $professionalsPaginator->firstItem(),
            'to' => $professionalsPaginator->lastItem(),
        ];

        $doctorQuery = User::whereRaw('LOWER(role) = ?', ['doctor']);
        $stats = [
            'total_professionals' => (clone $doctorQuery)->count(),
            'active_professionals' => (clone $doctorQuery)->whereRaw('LOWER(status) = ?', ['active'])->count(),
            'total_patients' => $this->countPatients(),
            'suspended_professionals' => (clone $doctorQuery)->whereRaw('LOWER(status) = ?', ['suspended'])->count(),
        ];

        $auditLogs = \App\Models\AuditLog::with('admin.user')
            ->orderBy('log_id', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($log) {
                $adminName = 'System';
                if ($log->admin && $log->admin->user) {
                    $adminName = trim(($log->admin->user->first_name ?? '') . ' ' . ($log->admin->user->last_name ?? ''));
                } elseif ($log->actor_user_id) {
                    $actor = User::find($log->actor_user_id);
                    $adminName = $actor?->display_name ?? 'User';
                }

                return [
                    'id' => $log->log_id,
                    'admin_name' => $adminName !== '' ? $adminName : 'System',
                    'action' => $log->action_taken,
                    'target' => $log->target_entity,
                    'ip' => $log->ip_address,
                ];
            })->toArray();

        return view('admin.dashboard', compact('admin', 'professionals', 'stats', 'pagination', 'auditLogs'));
    }

    /**
     * Get all professionals
     */
    public function getProfessionals(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [5, 10], true)) {
            $perPage = 10;
        }

        $paginator = $this->professionalsQuery($request)->paginate($perPage);
        $data = $paginator->getCollection()->map(function ($professional) {
            return $this->formatProfessional($professional);
        })->values();

        return response()->json([
            'success' => true,
            'professionals' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Build a resilient display name across legacy schemas.
     */
    private function displayName(User $user): string
    {
        $first = trim((string) ($user->first_name ?? ''));
        $last = trim((string) ($user->last_name ?? ''));
        $combined = trim($first . ' ' . $last);

        return $combined !== '' ? $combined : 'Unnamed User';
    }

    /**
     * Build doctor_profile payload only for columns that exist.
     */
    private function buildDoctorProfilePayload(array $input): array
    {
        $payload = [];

        foreach ($input as $column => $value) {
            if (Schema::hasColumn('doctor_profile', $column)) {
                $payload[$column] = $value;
            }
        }

        return $payload;
    }

    /**
     * Add a new professional
     */
    public function addProfessional(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:user',
            'phone' => 'required|string',
            'clinic' => 'required|string',
            'specialty' => 'required|string',
            'license_number' => 'required|string',
            'location' => 'required|string',
        ]);

        // Generate a temporary password
        $tempPassword = Str::random(12);

        $createData = $this->buildUserPayload(
            $validated['first_name'],
            $validated['last_name'],
            $validated['email'],
            Hash::make($tempPassword),
            'Doctor',
            [
                'status' => 'pending',
                'email_verified_at' => null,
                'must_change_password' => 1,
            ]
        );

        $professional = User::create($createData);
        if (empty($professional->user_id) && !empty($validated['email'])) {
            $professional = User::where('email', $validated['email'])->firstOrFail();
        }

        if (Schema::hasTable('doctor_profile')) {
            $doctorProfile = DoctorProfile::where('user_id', $professional->user_id)->first();
            $profilePayload = $this->buildDoctorProfilePayload([
                'phone' => $validated['phone'],
                'clinic' => $validated['clinic'],
                'specialty' => $validated['specialty'],
                'location' => $validated['location'],
                'license_number' => $validated['license_number'],
            ]);

            if ($doctorProfile) {
                $doctorProfile->fill($profilePayload);
                $doctorProfile->save();
            } else {
                DB::table('doctor_profile')->insert(array_merge([
                    'user_id' => $professional->user_id,
                ], $profilePayload));
            }
        }

        try {
            Mail::send('emails.professional-account-created', [
                'name' => $this->displayName($professional),
                'email' => $professional->email,
                'tempPassword' => $tempPassword,
                'loginUrl' => route('login'),
            ], function ($message) use ($professional) {
                $message->to($professional->email)->subject('Your LUMI Professional Account');
            });
        } catch (\Throwable $e) {
            // Do not block account creation if mail fails.
            logger()->error('Failed to send professional account email: ' . $e->getMessage());
        }

        $professional->load('doctorProfile');

        $patientCount = 0;
        if (isset($professional->doctorProfile)) {
            $patientCount = \App\Models\PatientAccessLog::where('clinician_id', $professional->user_id)->count();
        }

        $this->logAdminAction('Created Professional Account', 'User Email: ' . $professional->email);

        return response()->json([
            'success' => true,
            'message' => 'Professional added successfully. Login credentials sent by email.',
            'professional' => [
                'id' => $professional->user_id,
                'name' => $this->displayName($professional),
                'first_name' => $professional->first_name ?? $validated['first_name'],
                'last_name' => $professional->last_name ?? $validated['last_name'],
                'email' => $professional->email,
                'phone' => $professional->doctorProfile->phone ?? $validated['phone'],
                'clinic' => $professional->doctorProfile->clinic ?? $validated['clinic'],
                'specialty' => $professional->doctorProfile->specialty ?? $validated['specialty'],
                'license_number' => $professional->doctorProfile->license_number ?? $validated['license_number'],
                'is_verified' => false,
                'location' => $professional->doctorProfile->location ?? $validated['location'],
                'status' => strtolower($professional->status ?? 'pending'),
                'patients' => $patientCount,
                'last_active' => 'Just now',
                'joined_date' => $professional->created_at ? $professional->created_at->format('M d, Y') : now()->format('M d, Y'),
                'created_at' => $professional->created_at ? $professional->created_at->format('M d, Y') : now()->format('M d, Y'),
            ],
        ]);
    }

    /**
     * Edit a professional
     */
    public function editProfessional(Request $request, $professionalId)
    {
        $professional = User::findOrFail($professionalId);

        $admin = Auth::user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:user,email,' . $professional->user_id . ',user_id',
            'phone' => 'required|string',
            'clinic' => 'required|string',
            'specialty' => 'required|string',
            'license_number' => 'required|string',
            'location' => 'required|string',
            'status' => 'required|in:active,inactive,suspended,pending',
        ]);

        $updateData = $this->buildUserPayload(
            $validated['first_name'],
            $validated['last_name'],
            $validated['email'],
            null,
            null,
            [
                'status' => $validated['status'],
            ]
        );

        $professional->update($updateData);

        if (Schema::hasTable('doctor_profile')) {
            $doctorProfile = DoctorProfile::where('user_id', $professional->user_id)->first();
            $profilePayload = $this->buildDoctorProfilePayload([
                'phone' => $validated['phone'],
                'clinic' => $validated['clinic'],
                'specialty' => $validated['specialty'],
                'location' => $validated['location'],
                'license_number' => $validated['license_number'],
            ]);

            if ($doctorProfile) {
                $doctorProfile->fill($profilePayload);
                $doctorProfile->save();
            } else {
                DB::table('doctor_profile')->insert(array_merge([
                    'user_id' => $professional->user_id,
                ], $profilePayload));
            }
        }

        $professional->load('doctorProfile');

        $patientCount = 0;
        if (isset($professional->doctorProfile)) {
            $patientCount = \App\Models\PatientAccessLog::where('clinician_id', $professional->user_id)->count();
        }

        $this->logAdminAction('Updated Professional Account', 'User ID: ' . $professional->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Professional updated successfully',
            'professional' => [
                'id' => $professional->user_id,
                'name' => $this->displayName($professional),
                'first_name' => $professional->first_name ?? $validated['first_name'],
                'last_name' => $professional->last_name ?? $validated['last_name'],
                'email' => $professional->email,
                'phone' => $professional->doctorProfile->phone ?? $validated['phone'],
                'clinic' => $professional->doctorProfile->clinic ?? $validated['clinic'],
                'specialty' => $professional->doctorProfile->specialty ?? $validated['specialty'],
                'license_number' => $professional->doctorProfile->license_number ?? $validated['license_number'],
                'location' => $professional->doctorProfile->location ?? $validated['location'],
                'status' => strtolower($professional->status ?? $validated['status']),
                'is_verified' => !is_null($professional->email_verified_at),
                'patients' => $patientCount,
                'last_active' => 'Never',
                'joined_date' => $professional->created_at ? $professional->created_at->format('M d, Y') : 'N/A',
            ],
        ]);
    }

    /**
     * Delete a professional
     */
    public function deleteProfessional($professionalId)
    {
        $professional = User::findOrFail($professionalId);

        DB::transaction(function () use ($professional) {
            $doctorProfile = Schema::hasTable('doctor_profile')
                ? DB::table('doctor_profile')->where('user_id', $professional->user_id)->first()
                : null;

            if (Schema::hasTable('doctor_profile')) {
                DB::table('doctor_profile')->where('user_id', $professional->user_id)->delete();
            }

            $professional->delete();
        });

        $this->logAdminAction('Updated Professional Account', 'User ID: ' . $professional->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Professional deleted successfully',
        ]);
    }

    /**
     * Update admin account settings
     */
    public function updateSettings(Request $request)
    {
        $admin = Auth::user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:user,email,' . $admin->user_id . ',user_id',
            'phone' => 'nullable|string',
            'current_password' => 'required_with:new_password',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        // Verify current password if changing password
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $admin->password_hash)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'current_password' => ['The provided password does not match your current password.'],
                ]);
            }
            $admin->password_hash = Hash::make($request->new_password);
        }

        $adminUpdate = $this->buildUserPayload(
            $validated['first_name'],
            $validated['last_name'],
            $validated['email'],
            null,
            null,
            ['phone' => $validated['phone'] ?? null]
        );

        $admin->fill($adminUpdate);
        $admin->save();

        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    /**
     * Get dashboard statistics
     */
    public function getStats()
    {
        $professionals = User::whereRaw('LOWER(role) = ?', ['doctor'])->get();
        
        return response()->json([
            'total_professionals' => $professionals->count(),
            'active_professionals' => $professionals->filter(fn($p) => strtolower($p->status ?? '') === 'active')->count(),
            'total_patients' => $this->countPatients(),
            'suspended' => $professionals->filter(fn($p) => strtolower($p->status ?? '') === 'suspended')->count(),
        ]);
    }

    /**
     * Toggle user status (active/inactive)
     */
    public function toggleUserStatus($userId)
    {
        $user = User::findOrFail($userId);

        if ($user->user_id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot disable your own account.',
            ], 422);
        }

        $user->status = (strtolower($user->status ?? 'active') === 'active') ? 'inactive' : 'active';
        $user->save();
        
        $actionStr = $user->status === 'active' ? 'Enabled Account' : 'Disabled Account';
        $this->logAdminAction($actionStr, 'User ID: ' . $user->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Account ' . ($user->status === 'active' ? 'enabled' : 'disabled') . ' successfully.',
            'status' => $user->status,
        ]);
    }

    /**
     * Toggle professional verification status
     */
    public function toggleVerification($userId)
    {
        $user = User::findOrFail($userId);

        if (!Schema::hasColumn('user', 'email_verified_at')) {
            return response()->json(['success' => false, 'message' => 'Verification column is missing.'], 422);
        }

        // Toggle between verified (now) and unverified (null)
        $user->email_verified_at = $user->email_verified_at ? null : now();
        $user->save();

        // Sync the doctor_profile.is_validated column
        $doctorProfile = \App\Models\DoctorProfile::where('user_id', $userId)->first();
        if ($doctorProfile) {
            $doctorProfile->is_validated = $user->email_verified_at ? 1 : 0;
            $doctorProfile->save();
        }

        $actionStr = $user->email_verified_at ? 'Verified Clinician' : 'Revoked Clinician Verification';
        $this->logAdminAction($actionStr, 'User ID: ' . $user->user_id);

        return response()->json([
            'success' => true,
            'is_verified' => !is_null($user->email_verified_at),
            'message' => $user->email_verified_at ? 'Professional verified successfully.' : 'Verification revoked successfully.',
        ]);
    }

    /**
     * Build a user payload that adapts to legacy/current schema differences.
     */
    private function buildUserPayload(?string $firstName, ?string $lastName, ?string $email, ?string $passwordHash, ?string $role, array $extra = []): array
    {
        $payload = [];

        if ($email !== null) {
            $payload['email'] = $email;
        }
        if ($passwordHash !== null) {
            $payload['password_hash'] = $passwordHash;
        }
        if ($role !== null) {
            $payload['role'] = $role;
        }

        if ($firstName !== null && Schema::hasColumn('user', 'first_name')) {
            $payload['first_name'] = $firstName;
        }
        if ($lastName !== null && Schema::hasColumn('user', 'last_name')) {
            $payload['last_name'] = $lastName;
        }

        foreach ($extra as $column => $value) {
            if (Schema::hasColumn('user', $column)) {
                $payload[$column] = $value;
            }
        }

        return $payload;
    }

    /**
     * Split full name into first/last name for schemas without a single name column.
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        if (count($parts) <= 1) {
            return [trim($fullName), trim($fullName) !== '' ? 'N/A' : 'Unknown'];
        }

        $lastName = array_pop($parts);
        $firstName = implode(' ', $parts);

        return [$firstName, $lastName];
    }

    /**
     * Helper to log administrative actions to the audit_logs table
     */
    private function logAdminAction(string $action, string $targetEntity)
    {
        $adminProfile = \App\Models\AdminProfile::where('user_id', Auth::id())->first();
        
        if ($adminProfile) {
            \App\Models\AuditLog::create([
                'admin_id' => $adminProfile->admin_id,
                'action_taken' => $action,
                'target_entity' => $targetEntity,
                'ip_address' => request()->ip(),
            ]);
        }
    }
}
