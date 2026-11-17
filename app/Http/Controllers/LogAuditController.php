<?php

namespace App\Http\Controllers;

use App\Models\LogAudit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogAuditController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        return view('log-audit.index', ['logs' => LogAudit::latest('created_at')->paginate($this->perPage($request, 25))->withQueryString()]);
    }
}
