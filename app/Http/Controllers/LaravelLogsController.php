<?php

namespace App\Http\Controllers;

use App\Logs;
use Illuminate\Http\Request;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;

class LaravelLogsController extends LogViewerController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware(['admin','can:viewAny,App\Logs']);
    }
}
