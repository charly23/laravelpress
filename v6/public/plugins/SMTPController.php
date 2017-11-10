<?php
namespace App\Http\Controllers\Plugins\SMTP;

// App
use App\Http\Controllers\Controller;

// Illuminate
use Illuminate\Routing\UrlGenerator;

class SMTPController extends Controller
{
    public function __construct()
    {
        // instances
    }

    public function menu () 
    {
        return [
            'label'     => 'SMTP',
            'class'     => 'smtp-admin-page',
            'page'      => 'smtp',
            'url'       => url('/admin/smtp'),
            'column'    => 11,
            'submenu'   => $this->submenu()    
        ];
    }

    public function submenu () 
    {
        $value[] = [
            'label'     => 'Entries',
            'class'     => 'entries-admin-page',
            'page'      => 'entries',
            'url'       => url('/admin/smtp/entries'),
            'column'    => 1.1 
        ];

        return $value;
    }

    public function register () {
        return 'smtp';
    }

    public function index () {
        return 'smtp';
    }
}