<?php
namespace App\Http\Controllers\Plugins\GravityForms;

// App
use App\Http\Controllers\Controller;

// Database
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

// Illuminate
use Illuminate\Http\Request;
use Illuminate\Http\Dispatcher; 
use Illuminate\Http\Response;

// Collective
use Collective\Html\FormBuilder;
use Collective\Html\HtmlBuilder;

// App - Includes
use App\Includes\DBQuerys;
use App\Includes\ThumbFeatured;
use App\Includes\StringTools;

// Aliases
use View;
use Validator;
use Crypt;
use File;
use Storage;

// GF - Includes
use App\Http\Controllers\Plugins\GravityForms\Includes\FormType;

class GravityFormsController extends Controller
{
    /**
     * index int(0) default-variable
     */
    var $i = 0;

    var $instance = null;

    /**
     * global variable instances (slug/folder)
     * @return string()    
     */
    var $slug = 'gravityforms';

    var $folder = 'plugins/gravityforms';

    /**
     * global variable instances (label)
     * @return string()    
     */
    var $label = 'Forms';

    /**
     * global variable instances (tables)
     * @return array()    
     */
    var $tbls = ['gf-fields','gf-entries'];

    /**
     * controller instances (construct)
     * @return void    
     */
    public function __construct(Request $request,DBQuerys $querys,StringTools $tools,FormType $formtype)
    {
        $this->request  = $request;
        $this->querys   = $querys;
        $this->tools    = $tools;
        $this->formtype = $formtype;
    }

    public function load () 
    {
        $class = get_class_methods($this);

        return (object) [
            'tbls'      => $this->tbls,
            'querys'    => $this->querys,
            'formtype'  => $this->formtype,
            'tabmenu'   => $this->tabmenu()
        ];
    }

    /**
     * controller menu
     * @return array()    
     */
    public function menu () 
    {
        return [
            'label'     => 'Forms',
            'class'     => 'gravityforms-admin-page',
            'page'      => 'gravityforms',
            'url'       => url('/admin/gravityforms'),
            'column'    => 12,
            'submenu'   => $this->submenu()
        ];
    }

    /**
     * controller submenu
     * @return array()    
     */
    public function submenu () {
        return [
            1   =>  [
                'label'     => 'Add New',
                'class'     => 'add-gf-page',
                'page'      => 'add',
                'url'       => url('/admin/gravityforms/add'),
                'column'    => 1.1 
            ],
            2   =>  [
                'label'     => 'Entries',
                'class'     => 'entries-admin-page',
                'page'      => 'entries',
                'url'       => url('/admin/gravityforms/entries'),
                'column'    => 1.2 
            ],
            3   =>  [
                'label'     => 'Settings',
                'class'     => 'settings-admin-page',
                'page'      => 'settings',
                'url'       => url('/admin/gravityforms/settings'),
                'column'    => 1.3 
            ]
        ];
    }

    /**
     * controller tabmenu
     * @return array()    
     */
    public function tabmenu () 
    {

        $counts = $this->querys->count($this->tbls[1]);

        return [
            1   =>  [
                'label'     => 'Editor',
                'class'     => 'edit-admin-page',
                'page'      => 'edit',
                'url'       => url('/admin/gravityforms/add'),
                'column'    => 1 
            ],
            2   =>  [
                'label'     => 'Confirmation',
                'class'     => 'confirmation-admin-page',
                'page'      => 'confirmation',
                'url'       => url('/admin/gravityforms/confirmation'),
                'column'    => 2 
            ],
            3   =>  [
                'label'     => 'Notification',
                'class'     => 'notification-admin-page',
                'page'      => 'notification',
                'url'       => url('/admin/gravityforms/notification'),
                'column'    => 3 
            ],
            4   =>  [
                'label'     => "Entries ({$counts})",
                'class'     => 'entries-admin-page',
                'page'      => 'entries',
                'url'       => url('/admin/gravityforms/entries'),
                'column'    => 4 
            ],
            5   =>  [
                'label'     => 'Reviews',
                'class'     => 'reviews-admin-page',
                'page'      => 'reviews',
                'url'       => url('/admin/gravityforms/reviews'),
                'column'    => 5
            ]
        ];
    }

    public function current () 
    {
        return [
            'add',
            'entries',
            'settings'
        ];
    }

    /**
     * controller register (construct)
     * @return string()    
     */
    public function register () {
        return 'gravityforms';
    }

    /**
     * controller index call
     * @return string()    
     */
    public function index () {
        return 'gravityforms';
    }

    /**
     * controller scripts (call)
     * @return array()   
     */
    public function scripts () 
    {
        $data[] = [
            'url' => asset('css/plugins/gf/scripts.js'),
            'attribute' => ['id'=>'gf-admin-scripts']
        ];

        $data[] = [
            'url' => asset('js/jquery-ui.js'),
            'attribute' => ['id'=>'gf-admin-ui']
        ];

        return $data;
    }
    public function styles () 
    {
        $data[] = [
            'url' => asset('css/plugins/gf/admin.css'),
            'attribute' => ['id'=>'gf-admin-style']
        ];

        return $data;
    }

    /**
     * controller action(event) - request
     * @return modular  
     */
    public function action () 
    {
        $inputs = $this->request->all();
        $sessions = $this->request->session()->all();

        /**
         * call action-submit
         */
        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'gf-form') :
            return $this->get_form();
        endif;

        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'gf-form-insert') :
            return $this->insert_fields();
        endif;

        /**
         * call get-form(type) - advance-option
         * standard
         */
        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'gf-add-select') :
            return $this->add_select();
        endif;

        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'gf-add-checkbox') :
            return $this->add_checkbox();
        endif;

        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'gf-add-radio') :
            return $this->add_radio();
        endif;

        /**
         * call get-form(type) - advance-option
         * advanced
         */
        
    }

    /**
     * controller get-form(type) - request
     * @return modular  
     */
    public function get_form () 
    {
        $ids  = $this->request->id;
        $form = $this->formtype->form_validate_key($ids);
        return view($this->folder.'/admin/add/addfields',['fid'=>$ids,'form'=>$form,'fields'=>null])->render();
    }

    /**
     * controller get-form(type) - request
     * @return modular  
     */
    public function insert_fields () 
    {
        $inputs   = $this->request->all();
        $sessions = $this->request->session()->all();
        $ids      = intval($this->request->id);
        $forms    = $this->request->forms;

        if(!is_null($forms)) : foreach($forms as $frm_keys => $frm_vals) : $frm_name = $frm_vals['name'];
                $frm_datas[$frm_name] = trim($frm_vals['value']);
                $frm_rules[$frm_name] = 'required';
            endforeach;
        endif;

        $validator = Validator::make($frm_datas,$frm_rules);
        $errors = $validator->errors(); 

        if($validator->fails() != true) :

            $fields = serialize($inputs);

            $data = [
                'name'    => trim($frm_datas['form-name']),
                'content' => trim($frm_datas['form-content']),
                'fields'  => $fields,
                'author'  => 0,
                'date'    => date("Y-m-d H:i:s")
            ];

            if($ids != 0) :

                DB::table('media')->where('id',$ids)->update($data);
                $return = [
                    'fid'  => $ids
                ];

            else :

                $get_ids = DB::table($this->tbls[$this->i])->insertGetId($data);
                $return = [
                    'fid'  => $get_ids
                ]; 

            endif;

            return response()->json($return);

        else :

            $message = $errors->all();  
            return view($this->folder.'/admin/global/validate',['validate'=>$message])->render();

        endif;
    }

    /**
     * controller add-select(data) - request
     * @return modular  
     */
    public function add_select () {
        return view($this->folder.'/admin/add/fields/standard/select/data')->render();
    }

    /**
     * controller add-checkbox(data) - request
     * @return modular  
     */
    public function add_checkbox () {
        return view($this->folder.'/admin/add/fields/standard/checkbox/data')->render();
    }

    /**
     * controller add-radio(data) - request
     * @return modular  
     */
    public function add_radio () {
        return view($this->folder.'/admin/add/fields/standard/radio/data')->render();
    }

    // END
}