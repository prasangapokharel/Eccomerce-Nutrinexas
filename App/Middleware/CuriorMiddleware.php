<?php
namespace App\Middleware;

use App\Core\Session;

class CuriorMiddleware
{
    /**
     * Check if user is logged in as curior
     */
    public static function requireCurior()
    {
        if (!Session::has('curior_id')) {
            // Redirect to curior login
            header('Location: ' . \App\Core\View::url('curior/login'));
            exit;
        }
    }

    /**
     * Check if user is logged in as curior and return curior data
     */
    public static function getCurior()
    {
        if (!Session::has('curior_id')) {
            return null;
        }

        return [
            'id' => Session::get('curior_id'),
            'name' => Session::get('curior_name'),
            'email' => Session::get('curior_email')
        ];
    }

    /**
     * Check if user is already logged in as curior (for login page)
     */
    public static function redirectIfLoggedIn()
    {
        if (Session::has('curior_id')) {
            header('Location: ' . \App\Core\View::url('curior/dashboard'));
            exit;
        }
    }

    /**
     * Logout curior
     */
    public static function logout()
    {
        Session::destroy();
        header('Location: ' . \App\Core\View::url('curior/login'));
        exit;
    }
}
