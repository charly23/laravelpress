<?php
namespace App\Http\Controllers\Admin;

// App
use App\Http\Controllers\Controller;

// Database
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

// Illuminate
use Illuminate\Http\Request;
use Illuminate\Http\Dispatcher; 
use Illuminate\Http\Response;

// load
use App\Http\Controllers\Plugins\SMTP\SMTPController;
use App\Http\Controllers\Plugins\GravityForms\GravityFormsController;

// Collective
use Collective\Html\FormBuilder;
use Collective\Html\HtmlBuilder;

class LoaderController extends Controller
{

    /**
     * load a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request,SMTPController $smtp,GravityFormsController $forms)
    {
        $this->request  = $request;
        $this->smtp     = $smtp;
        $this->forms    = $forms;
    }

    public function load () 
    {
        $load['gf'] = $this->forms->load();
        return $load;
    }

    public function label () 
    {
        $label['smtp-label'] = null;
        $label['gf-label'] = $this->forms->label;
        return $label;
    }

    public function themes ($temp=[]) 
    {
        $temp[] = null;
        return $temp;
    }

    public function current () 
    {
        return array_merge($this->forms->current());
    }

    /**
     * load a new controller scripts call.
     *
     * @return array
     */
    public function scripts () 
    {
        $script['smtp-scripts'] = null;
        $script['gf-scripts'] = $this->forms->scripts();
        return $script;
    }

    public function styles () 
    {
        $style['smtp-styles'] = null;
        $style['gf-styles'] = $this->forms->styles();
        return $style;
    }

    /**
     * load a new controller menu call.
     *
     * @return array
     */
    public function menu ($load=[]) 
    {
        $load[] = $this->smtp->menu();
        $load[] = $this->forms->menu();
        return $load;
    }

    public function register ($page=null) 
    {
        $in_page = [
            $this->smtp->register(),
            $this->forms->register()
        ];

        return in_array($page,$in_page) ? true : false;
    }

    public function page ()  
    {
        // page rounte
    }

    /**
     * load a new controller action.
     *
     * @return array()
     */
    public function action () 
    {
        return $this->forms->action();
    }

    // END
}