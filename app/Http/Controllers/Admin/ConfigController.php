<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ConfigController extends Controller
{
    private static $messages = [
        'config:clear' => 'Configuration cache cleared!',
        'config:cache' => 'Configuration cached successfully!',
        'cache:clear' => 'Application cache cleared!',
        'view:clear' => 'Compiled views cleared!',
        'route:cache' => 'Routes cached successfully!',
        'route:clear' => 'Route cache cleared!',
        'optimize:clear' => 'Caches cleared successfully!',
        'optimize' => 'Files cached successfully!',
        'down' => 'Application is now in maintenance mode.',
        'up' => 'Application is now live.',
    ];
    public function __construct()
    {
    }

    public function index()
    {
        return view('admin.config.index');
    }
    public function updateLogo(Request $request)
    {
        $configs = '';
        if ($request->has('img')) {
            if (Storage::disk('local')->exists(@$configs->img)) {
                Storage::disk('local')->delete($configs->img);
            }
            $slider_img = $request->file('img')->store($this->uploadPath);
            $data['img'] = $slider_img;
        }
    }
    public function clearCache(Request $request)
    {
        $command = $request->action;

        $res['title'] = ucwords(str_replace(':', ' ', $command));
        if (!App::isDownForMaintenance() && $request->action == 'down') {
            $command .= ' --allow=127.0.0.1 --allow=192.168.0.0/16';
        } elseif (App::isDownForMaintenance() && $request->action == 'down') {
            $res['message'] = 'Application is already down.';
            return $res;
        } elseif (!App::isDownForMaintenance() && $request->action == 'up') {
            $res['message'] = 'Application is already up.';
            return $res;
        }
        try {
            Artisan::call($command);
            $res['message'] = self::$messages[Str::before($command, ' --')];
        } catch (Throwable $throwable) {
            $res['message'] = $throwable->getMessage();
            return $res;
        }
        return $res;
    }
}
