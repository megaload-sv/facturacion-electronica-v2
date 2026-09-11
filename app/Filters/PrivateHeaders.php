<?php

namespace App\Filters;

use CodeIgniter\Filters\SecureHeaders;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PrivateHeaders extends SecureHeaders
{
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        parent::after($request, $response, $arguments);
        $response->setHeader('Cache-Control', 'no-store, private, max-age=0');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Referrer-Policy', 'no-referrer');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; object-src 'none'; form-action 'self'");
        $response->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        return $response;
    }
}
