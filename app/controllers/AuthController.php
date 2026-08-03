<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Session;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * @Route(path="/admin/login", methods="GET,POST", name="admin.login")
     */
    public function login(Request $request): Response
    {
        if ($request->getMethod() === 'GET') {
            if (Session::get('admin_id')) {
                return $this->redirect('/admin');
            }
            return $this->render('admin/login.html.twig', ['error' => null]);
        }

        $email    = trim($request->post('email', ''));
        $password = $request->post('password', '');

        $user = User::findByEmail($email);

        if (!$user || !$user->verifyPassword($password)) {
            return $this->render('admin/login.html.twig', ['error' => 'Invalid email or password.']);
        }

        Session::set('admin_id', $user->id);
        Session::set('admin_username', $user->username);
        Session::set('admin_role', $user->role);

        return $this->redirect('/admin');
    }

    /**
     * @Route(path="/admin/logout", methods="GET", name="admin.logout")
     */
    public function logout(Request $request): Response
    {
        Session::destroy();
        return $this->redirect('/admin/login');
    }
}
