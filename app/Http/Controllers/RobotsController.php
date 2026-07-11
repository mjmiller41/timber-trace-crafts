<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RobotsController extends Controller
{
    /**
     * Serve public/robots.txt through the router.
     *
     * The static file is the single source of truth; this route reads and
     * returns its exact bytes as text/plain so the file and the route can
     * never drift.
     */
    public function index(): Response|BinaryFileResponse
    {
        $path = public_path('robots.txt');

        if (! is_file($path)) {
            abort(404);
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
