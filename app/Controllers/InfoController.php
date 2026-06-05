<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class InfoController extends BaseController
{
    public function index()
    {
       phpinfo();
    }
}