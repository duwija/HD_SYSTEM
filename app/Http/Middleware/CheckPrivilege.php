<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPrivilege
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $privilege)
    {
        // Cek apakah pengguna terautentikasi
        if (!Auth::check()) {
            return redirect('login'); // Redirect ke halaman login jika belum terautentikasi
        }
        // dd(Auth::user()->privilege);
        // Cek apakah pengguna memiliki privilege yang diperlukan
        // if (Auth::user()->privilege !== $privilege) {
        //     return response()->json(['error' => 'Unauthorized'], 403); // Akses ditolak
        // }

        return $next($request); // Lanjutkan ke request berikutnya
    }
    
}
