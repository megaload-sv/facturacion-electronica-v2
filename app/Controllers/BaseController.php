<?php

namespace App\Controllers;

use App\Models\MenuModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{

    protected $menu;
    protected $session;

    protected function render($view, $data = [])
    {

        // Iniciar la sesión
        $this->session = \Config\Services::session();

        // Verificar si el usuario está logueado
        $this->checkLogin();

        if (isset($this->menu)) {
            $data['menu'] = $this->menu;
        }

        return view($view, $data);
    }
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        $userId = session()->get('user_id');

        if ($userId) {
            $menuModel = new MenuModel();
            $this->menu = $menuModel->getMenuByUser($userId);
        }

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
         $this->session = service('session');

        // Verificar si el usuario está logueado
        $this->checkLogin();
    }

    protected function checkLogin()
    {

        // Comprobar si la sesión tiene un valor de 'isLoggedIn'
        if (!$this->session->get('logged_in')) {


            return redirect()->to('/login')->send();
        }
    }
}
