<?php

namespace App\Filters;

use App\Libraries\AccessControl;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = (int) session()->get('user_id');
        if (!session()->get('logged_in') || !$userId) {
            return redirect()->to('/login');
        }
        if (empty($arguments[0]) || !(new AccessControl())->allows($userId, $arguments[0])) {
            return service('response')->setStatusCode(403)->setBody('No tiene permiso para realizar esta acción.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
