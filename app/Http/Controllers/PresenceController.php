<?php

namespace App\Http\Controllers;

use App\Services\UserPresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresenceController extends Controller
{
    public function __construct(
        private readonly UserPresenceService $presence,
    ) {
    }

    /**
     * Lightweight heartbeat while a doctor/admin dashboard tab is open.
     */
    public function ping(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        $this->presence->touch($user);
        $summary = $this->presence->summarize($user->fresh());

        return response()->json([
            'status' => 'success',
            'data' => $summary,
        ]);
    }
}
