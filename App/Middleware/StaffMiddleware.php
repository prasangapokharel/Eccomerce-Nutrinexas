<?php
namespace App\Middleware;

use App\Core\Session;

class StaffMiddleware
{
    /**
     * Check if user is logged in as staff
     */
    public static function requireStaff()
    {
        if (!Session::has('staff_id')) {
            // Redirect to staff login
            header('Location: ' . \App\Core\View::url('staff/login'));
            exit;
        }
    }

    /**
     * Check if user is logged in as staff and return staff data
     */
    public static function getStaff()
    {
        if (!Session::has('staff_id')) {
            return null;
        }

        return [
            'id' => Session::get('staff_id'),
            'name' => Session::get('staff_name'),
            'email' => Session::get('staff_email')
        ];
    }

    /**
     * Check if user is already logged in as staff (for login page)
     */
    public static function redirectIfLoggedIn()
    {
        if (Session::has('staff_id')) {
            header('Location: ' . \App\Core\View::url('staff/dashboard'));
            exit;
        }
    }

    /**
     * Logout staff
     */
    public static function logout()
    {
        Session::destroy();
        header('Location: ' . \App\Core\View::url('staff/login'));
        exit;
    }
}


