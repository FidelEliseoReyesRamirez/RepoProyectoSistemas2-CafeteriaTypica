<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditoriaController extends Controller
{
     public function index()
    {
        return Inertia::render('Audit/Index'); 
    }
}
