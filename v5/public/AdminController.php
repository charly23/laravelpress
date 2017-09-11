<?php
namespace App\Http\Controllers\Admin;

// App
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\MailController;
use App\Http\Controllers\Admin\MenuController;

// Database
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

// Illuminate
use Illuminate\Http\Request;
use Illuminate\Http\Dispatcher; 

// Facaded
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Validation\Rule;

// Validator
use View;
use Validator;
use Crypt;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Validation\Validator as Validate;
use Illuminate\Foundation\Validation\ValidatesRequests;

// Collective
use Collective\Html\FormBuilder;
use Collective\Html\HtmlBuilder;

// App - Includes
use App\Includes\DBQuerys;

// http://bensmith.io/email-verification-with-laravel

class AdminController extends Controller
{
    /**
     * loo label default 0 modular
     */
    public $i = 0;

    /**
     * page label slug
     */
    public $page = 'admin';

    /**
     * database table (sql).
     *
     * @return string
     */
    public $table = 'users';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request,UrlGenerator $url,MailController $mail,MenuController $menu,DBQuerys $querys)
    {
        $this->request  = $request;
        $this->url      = $url;
        $this->mail     = $mail;
        $this->menu     = $menu;
        $this->querys   = $querys;
    }

    /**
     * Create a SESSION username/password controller load.
     *
     * @return boolean(true/false)
     */
    public function username_exits () 
    {
        return $this->request->session()->has('username') ? 
            $this->request->session()->exists('username') : false;
    }
    public function password_exits () 
    {
        return $this->request->session()->has('password') ? 
            $this->request->session()->exists('password') : false;
    }

    /**
     * post type controller handler.
     *
     * @return objects
     */
    public function type ($fltr=false,$vals=null) 
    {
        $type[] = [
                'type'  => 'posts',
                'meta'  => true,
                'media' => false 
            ];

        $type[] = [
                'type'  => 'pages',
                'meta'  => true,
                'media' => false
            ];

        $keys = array_search($vals,array_column($type,'type'));
        if($fltr!=false){
            $return = ($type[$keys]['type']) == $vals ? true : false;
        } else {
            $return = ($type);
        }

        return $return;
    }

    public function meta ($vals=null) 
    {
        $meta[] = [
            'key'   => '',
            'value' => ''
        ];

        $meta[] = [
            'key'   => '',
            'value' => ''
        ];

        $meta[] = [
            'key'   => '',
            'value' => ''
        ];

        $type = $this->type(true,$vals);
        $keys = ($type!=false) ? $meta : null;

        return $meta;
    }

    public function current ($subpage=null) 
    {
        $in_value = [
            1 => 'add',
            2 => 'edit',
            3 => 'category'
        ];

        $end  = end($subpage);
        $ints = is_numeric($end) ? true : false;

        if(in_array($end,$in_value)){
            $current = ($subpage[count($subpage)-2]);
        } else if($ints==true) {
            $current = ($subpage[count($subpage)-3]);
        } else {
            $current = $ints != false ?  ($subpage[count($subpage)-2]) : ($end);
        }

        return ($current);
    }

    public function ended ($subpage=null) 
    {
        $end  = end($subpage);
        $ints = is_numeric($end) ? true : false;

        $return = $ints==true ? ($subpage[count($subpage)-2]) : ($end);

        return $return;
    }

    public function intel ($subpage=null) 
    {
        $end  = end($subpage);
        $ints = is_numeric($end) ? true : false;

        $return = $ints==true ? ($end) : 0;

        return $return;
    }

    public function rows ($id=0) 
    {
        return DB::table('posts')->where('id',$id)->get();
    }

    /**
     * app.config controller load function handler for author information.
     *
     * @return objects
     */
    
    public function appconfig () 
    {
        return ['author' => config('app.author'), 'website' => config('app.website'), 'version' => config('app.version')];
    }

    /**
     * Create a new controller load.
     *
     * @return objects
     */
    public function load ($id=null,$validate=null) 
    {
        $data = $this->request->session()->all();

        $subpage    = explode('/',$this->url->current());
        $end        = $this->ended($subpage);
        $current    = $this->current($subpage);
        $type       = $this->type(true,$current);

        $returns = [
            'index'     =>  $this->i,
            'id'        =>  $this->intel($subpage),
            'appconfig' =>  $this->appconfig(),
            'validate'  =>  $validate,
            'page'      =>  $this->page,
            'subpage'   =>  $current,
            'end'       =>  $end,
            'table'     =>  $this->table,
            'type'      =>  $type,
            'menu'      =>  $this->menu,
            'request'   =>  $this->request,
            'session'   =>  $data,
            'url'       =>  $this->url,
            'rows'      =>  $this->rows($this->intel($subpage)),
            'meta'      =>  $this->meta($type),
            'querys'    =>  $this->querys
        ];

        return $returns;
    }
 
    /**
     * Create a view controller load.
     *
     * @return void
     */
    public function view ($page=null,$validate=null) 
    {
        return view($page,$this->load($id=$this->i,$validate));
    }

    /**
     * Create a view-template controller load.
     *
     * @return void
     */
    public function template ($page=null,$validate=null) 
    {
        $html  = null;
        $html .= $this->view('admin/layouts/header');
        $html .= $this->view($page,$validate); 
        $html .= $this->view('admin/layouts/footer');
        return $html;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index ()
    {
        if( $this->request->session()->exists('username') AND $this->request->session()->exists('password')) {
            return $this->template('admin/admin'); 
        } else {
            return $this->template('admin/interface/login');
        }
    }

    /**
     * Show the application register.
     *
     * @return \Illuminate\Http\Response
     */
    public function register ()
    {
        if( $this->request->session()->exists('username') AND $this->request->session()->exists('password')) {
            return $this->template('admin/admin');
        } else {
            return $this->template('admin/interface/register');
        }
    }

    /**
     * Show the application dynamic page call.
     *
     * @return \Illuminate\Http\Response
     */
    public function page ($page=null) 
    {
        if( $this->request->session()->exists('username') AND $this->request->session()->exists('password')) {
            return $this->show($page,null);
        } else {
            return $this->template('admin/interface/login');
        }
    }

    public function subpage ($page=null,$subpage=null) 
    {
        if( $this->request->session()->exists('username') AND $this->request->session()->exists('password')) {
            return $this->show($page,$subpage);
        } else {
            return $this->template('admin/interface/login');
        }
    }

    public function tabpage ($page=null,$subpage=null,$tabpage=null) 
    {
        if( $this->request->session()->exists('username') AND $this->request->session()->exists('password')) {
            return $this->show($page,$subpage);
        } else {
            return $this->template('admin/interface/login');
        }
    }

    public function show ($page=null,$subpage=null) 
    {
        $type = $this->type(true,$page);
        if($page == 'admin' AND !is_null($subpage)) { 
            return $this->template('admin/admin');
        } else {
            return ($type == true) ? $this->template('admin/pages/posts') : $this->template('admin/pages/'.$page);
        }
    }

    /**
     * application signup - action.
     *
     * @return \Illuminate\Http\Response
     */
    public function store ()
    {
        $method = $this->request->method();  
        $inputs = $this->request->all();

        if ($this->request->isMethod('post') AND $this->request->has('_token')) :
            
        $password = $this->request->has('password') ? Hash::make($this->request->password) : null;

        $datas = [
            'username'  => trim($this->request->username),
            'password'  => $password,
            'email'     => trim($this->request->email),
            'date'      => date("Y-m-d H:i:s")
        ];

        $rules = [
            'username'  => 'required|max:100',  
            'password'  => 'required|max:100',
            'email'     => 'required|email',  
            'date'      => 'required'
        ];

        $validator = Validator::make($datas,$rules);
        $errors = $validator->errors();  

        $is_password = Hash::check($this->request->password,$password) ? true : false;
        $confirmation_code = str_random(30);

        if ( $validator->fails() != true AND $is_password != false ) {

            DB::table($this->table)->insert($datas); 
            return redirect('/admin');

        } else {

            $message = $errors->all();
            return $this->template('admin/interface/register',$message);    
        }
            
        endif;
    }

    /**
     * application signin - action.
     *
     * @return \Illuminate\Http\Response
     */
    public function login ()
    {
        $method = $this->request->method();
        $inputs = $this->request->all();

        if ($this->request->isMethod('post') AND $this->request->has('_token'))  :
            
        $password = Hash::make($this->request->password);

        $datas = [
            'username' => trim($this->request->username),
            'password' => trim($this->request->password)
        ];

        $rules = [
            'username' => 'required|max:100',
            'password' => 'required|max:100'
        ];

        $validator = Validator::make($datas,$rules);
        $errors = $validator->errors();

        $is_password = Hash::check($this->request->password,$password) ? true : false;
        $confirmation_code = str_random(30);
        
        if ( $validator->fails() != true AND $is_password != false ) {

            $querys = DB::table($this->table)->where('username',trim($this->request->username))->get();

            foreach($inputs as $keys => $vals) : if ($this->request->has($keys)) :
                    $this->request->session()->put($keys,$vals);
                endif;
            endforeach;

            if ($this->request->session()->has('username') AND $this->request->session()->has('password')) {
                return redirect('/admin');
            }

        } else {

            $message = $errors->all();
            return $this->template('admin/interface/login',$message);
        }

        endif;
    }

    /**
     * application logout - action.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout ()
    {
        $data = $this->request->session()->all();

        if ($this->request->session()->has('_token') AND array_key_exists('_token',$data)) :

            foreach($data as $keys => $vals) :
                $this->request->session()->forget($keys);
            endforeach;

            $this->request->session()->flush();
            return redirect('/admin');

        endif;
    }

    /**
     * application event - action.
     *
     * @return \Illuminate\Http\Response
     */

    public function action () 
    {
        $inputs = $this->request->all();
        $sessions = $this->request->session()->all();

        if ($this->request->isMethod('post') AND $this->request->has('_token'))  :

            $ids    = ($this->request->id);
            $fields = ($this->request->fields);

            if(!is_null($fields)) : foreach($fields as $keys => $vals) : $name = $vals['name'];
                    $datas[$name] = $vals['value'];
                    $rules[$name] = 'required';
                endforeach;
            endif;

            $author = [
                'parent'    => 0,
                'status'    => 1,
                'author'    => 0,
                'date'      => date("Y-m-d H:i:s")    
            ];

            $merge = array_merge($datas,$author);

            $messages = [
                'title.required' => 'The (:attribute) field is required',
            ];

            $validator = Validator::make($datas,$rules,$messages);
            $errors = $validator->errors(); 

            if($validator->fails() != true AND is_array($merge)) :
                return ($ids==0) ? DB::table('posts')->insertGetId($merge) : DB::table('posts')->where('id',$ids)->update($merge);
            endif;

            if($validator->fails() != false) :
                $message = $errors->all();
                return $this->view('admin/pages/posts/validate',$message);
            endif;

        endif;

        if (!$this->request->session()->has('username')) {
            return redirect('/admin');
        }
    }
}
