<?php

namespace App\Http\Controllers;

class GuardianController extends Controller
{
    /**
     * Direct guardians to the LUMI mobile app.
     */
    public function useMobile()
    {
        return view('guardian.use-mobile');
    }
}
