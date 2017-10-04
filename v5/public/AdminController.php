<?php
namespace App\Http\Controllers\Admin;

// App
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\LoaderController;
use App\Http\Controllers\Admin\MailController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\AppearanceController;

// Database
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

// Illuminate
use Illuminate\Http\Request;
use Illuminate\Http\Dispatcher; 
use Illuminate\Http\Response;

// Facaded
use Illuminate\Support\ServiceProvider;
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
use Illuminate\Contracts\Routing\ResponseFactory;

// Collective
use Collective\Html\FormBuilder;
use Collective\Html\HtmlBuilder;

// App - Includes
use App\Includes\DBQuerys;
use App\Includes\ThumbFeatured;

// http://bensmith.io/email-verification-with-laravel

class AdminController extends Controller
{
    /**
     * load label default 0 modular
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
    public function __construct(Request $request,UrlGenerator $url,MailController $mail,MenuController $menu,PostsController $posts,DBQuerys $querys,ThumbFeatured $featured, AppearanceController $appearance)
    {
        $this->request      = $request; 
        $this->url          = $url;
        $this->mail         = $mail;
        $this->menu         = $menu;
        $this->querys       = $querys;
        $this->featured     = $featured;
        $this->posts        = $posts;
        $this->appearance   = $appearance;
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

        $type = $this->type(true,$vals);
        $keys = ($type!=false) ? $meta : null;

        return $meta;
    }

    public function current ($subpage=null) 
    {
        $in_dashboard = [
            1 => 'home',
            2 => 'updates',
            3 => 'themes',
            4 => 'profile'
        ];

        $in_value = [
            1 => 'add',
            2 => 'create',
            3 => 'edit',
            4 => 'update',
            5 => 'category'
        ];

        $in_filter = [
            1 => 'all',
            2 => 'publish',
            3 => 'draft',
            4 => 'trash'
        ];

        $in_key = array_merge($in_dashboard,$in_value,$in_filter);

        $end  = end($subpage);
        $ints = is_numeric($end) ? true : false;

        if(in_array($end,$in_key)){
            $current = ($subpage[count($subpage)-2]);
        } else if($ints==true) {
            $current = ($subpage[count($subpage)-3]);
        } else {
            $current = $ints != false ? ($subpage[count($subpage)-2]) : ($end);
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
        return DB::table('posts')->where(['id'=>$id])->get();
    }

    public function meta_value ($id=0) 
    {
        return DB::table('posts_meta')->where(['pid'=>$id])->get();
    }

    /**
     * app.config controller load function handler for author information.
     *
     * @return objects
     */
    
    public function appconfig () {
        return [
            'name'      => config('app.name'),
            'author'    => config('app.author'), 
            'website'   => config('app.website'),
            'version'   => config('app.version'),
            'info'      => config('app.info')
        ];
    }

    /**
     * Create a new controller load.
     *
     * @return objects
     */
    public function load ($id=0,$actions=[],$validate=null) 
    {
        $data = $this->request->session()->all();

        $subpage    = explode('/',$this->url->current());
        $end        = $this->ended($subpage);
        $current    = $this->current($subpage);
        $type       = $this->type(true,$current);

        $returns = [
            'index'     =>  $this->i,
            'id'        =>  $id != 0 ? $id : $this->intel($subpage),
            'appconfig' =>  $this->appconfig(),
            'validate'  =>  $validate,
            'actions'   =>  $actions,
            'page'      =>  $this->page,
            'subpage'   =>  $current,
            'end'       =>  $end,
            'table'     =>  $this->table,
            'type'      =>  $type,
            'menu'      =>  $this->menu,
            'posts'     =>  $this->posts,
            'request'   =>  $this->request,
            'session'   =>  $data,
            'url'       =>  $this->url,
            'rows'      =>  $this->rows($this->intel($subpage)),
            'meta'      =>  $this->meta($type),
            'meta_value'=>  $this->meta_value($this->intel($subpage)),
            'querys'    =>  $this->querys,
            'featured'  =>  $this->featured
        ];

        return $returns;
    }

    public function external_load () 
    {
        // external loader
    }

    /**
     * Create a view-id controller load.
     *
     * @return void
     */
    public function view_id ($page=null,$id=null) 
    {
        return view($page,$this->load($id,null,null));
    }

    /**
     * Create a view-actions controller load.
     *
     * @return void
     */
    public function view_actions ($page=null,$actions=[]) 
    {
        return view($page,$this->load($this->i,$actions,null));
    }
 
    /**
     * Create a view controller load.
     *
     * @return void
     */
    public function view ($page=null,$validate=null) 
    {
        return view($page,$this->load($this->i,null,$validate));
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

    public function template_actions ($page=null,$actions=[]) 
    {
        $html  = null;
        $html .= $this->view('admin/layouts/header');
        $html .= $this->view_actions($page,$actions); 
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

            if($this->appearance->is_page($page)==true) {
                return $this->template_actions('admin/pages/'.$page,$this->appearance->active(true));
            } else {
                return $this->show($page,null);
            }
        } else {
            return $this->template('admin/interface/login');
        }
    }

    public function subpage ($page=null,$subpage=null) 
    {
        if( $this->request->session()->exists('username') AND $this->request->session()->exists('password')) {

            if($this->appearance->is_page($page)==true) {
                return $this->template_actions('admin/pages/'.$page,$this->appearance->disable());
            } else {
                return $this->show($page,$subpage);
            }
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
            return ($type == true) ? $this->template('admin/pages/posts') : $this->manual($page);
        }
    }

    public function manual ($page=null) 
    {
        $manual = [
            'media',
            'appearance'
        ];
        $type = $this->type(true,$page);
        if($type != true AND in_array($page,$manual)) {
            return $this->template('admin/pages/'.$page);
        } else {
            return $this->template('admin/pages/'.$page);
        }
    }

    /**
     * application signup - action.
     *
     * @return \Illuminate\Http\Response
     */
    public function store ()
    {
        $method     = $this->request->method();  
        $inputs     = $this->request->all();
        $username   = $this->request->username;

        if ($this->request->isMethod('post') AND $this->request->has('_token')) :
            
        $password = $this->request->has('password') ? Hash::make($this->request->password) : null;

        $datas = [
            'username'  => trim($username),
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

            $exists = $this->autoLogin($username);

            if($exists != false) :
                return $this->template('admin/interface/register',["Username - Already Exists ({$username})"]);
            endif;

            if($exists != true) :

                foreach($inputs as $keys => $vals) : if ($this->request->has($keys)) :
                        $this->request->session()->put($keys,$vals);
                    endif;
                endforeach;

                DB::table($this->table)->insert($datas); 
                return redirect('/admin');

            endif;

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
        $method     = $this->request->method();
        $inputs     = $this->request->all();
        $username   = $this->request->username;

        if ($this->request->isMethod('post') AND $this->request->has('_token'))  :
        
        $password = Hash::make($this->request->password);

        $datas = [
            'username' => trim($username),
            'password' => $this->request->password
        ];

        $rules = [
            'username' => 'required|max:100',
            'password' => 'required|max:100'
        ];

        $validator = Validator::make($datas,$rules);
        $errors = $validator->errors();

        $querys = $this->querys->get_users_by_name($username);

        $username_validate = isset($querys->username) ? true : false;
        $password_validate = isset($querys->password) ? trim($querys->password) : null;

        $is_password = Hash::check($this->request->password,$password_validate) ? true : false;
        $confirmation_code = str_random(30);

        if($username_validate==true) :
        
            if ( $validator->fails() != true AND $is_password != false ) {

                $exists = $this->autoLogin($username);    

                if($exists != false) :

                    foreach($inputs as $keys => $vals) : if ($this->request->has($keys)) :
                            $this->request->session()->put($keys,$vals);
                        endif;
                    endforeach;

                    if ($this->request->session()->has('username') AND $this->request->session()->has('password')) :
                        return redirect('/admin');
                    endif;

                endif;

                if($exists != true) :
                    return $this->template('admin/interface/login',["Username - Not Exists ({$username})"]);
                endif;

            } else {

                $message = count($errors->all()) !=0 ? $errors->all() : ['Password - Wrong'];
                return $this->template('admin/interface/login',$message);
            }

        else :

            $message = count($errors->all()) !=0 ? $errors->all() : ['Username - Not Exists'];
            return $this->template('admin/interface/login',$message);    

            endif;
        endif;
    }

    /**
     * application auto-login - exists validate.
     *
     * @return \Illuminate\Http\Response
     */

    public function autoLogin ($username=null) {
        return $this->querys->get_users_by_name($username) ? true : false;
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
    public function action ($page=null,$subpage=null,$tab=null) 
    {
        $inputs = $this->request->all();
        $sessions = $this->request->session()->all();

        /**
         * ajaxs-handler
         * javascripts-ajaxs action-handler
        **/
        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'posts-action' )  :
            return $this->postsInsert();
        endif;

        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'meta-action' )  :
            return $this->view('admin/pages/posts/meta');
        endif;

        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'media-action' )  :
            $media_id = intval($inputs['id']);
            return $this->view_id('admin/pages/media/browse',$media_id);
        endif;

        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'media-thumbnail' )  :
            $media_id = intval($inputs['id']);
            return $this->view_id('admin/pages/media/selected',$media_id);
        endif;

        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'categorys-action' )  :
            return $this->categoryInsert();
        endif;

        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'categorys-row' )  :
            return $this->categoryInsertRow();
        endif;

        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->action == 'comments-reply' )  :
            return $this->view('admin/pages/comments/reply');
        endif;

        /**
         * actions-handler
         * phpscripts-load action-handler
        **/
        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->has('media-action') )  :
            return $this->mediaInsert($tab);
        endif;

        if ($this->request->isMethod('post') AND $this->request->has('_token') AND $this->request->has('filter-submit') )  :
            return $this->postsFilter($page);
        endif;

        if (!$this->request->session()->has('username')) :
            return redirect('/admin');
        endif;

        die();
    }

    /**
     * application event - action function (posts-insert).
     *
     * @return \Illuminate\Http\Response
     */
    public function postsInsert () 
    {
        $dval   = null;

        $data = $this->request->session()->all();

        $ids    = intval($this->request->id);
        $mid    = intval($this->request->mid);
        $fields = ($this->request->fields);
        $dates  = ($this->request->date);
        $status = intval($this->request->status);
        $metas  = ($this->request->meta);
        $categorys = ($this->request->category);

        if(!is_null($fields)) : foreach($fields as $keys => $vals) : $name = $vals['name'];
                $datas[$name] = $vals['value'];
                $rules[$name] = 'required';
            endforeach;
        endif;

        $dvalue = null;
        if(!is_null($dates)) : foreach($dates as $dkeys => $dvals) :
                if(in_array($dkeys,array(0,1))) :
                    $dvalue .= ($dvals['value']).'-';
                elseif(in_array($dkeys,array(2))):
                    $dvalue .= ($dvals['value']).' ';
                elseif(in_array($dkeys,array(3))):
                    $dvalue .= ($dvals['value']);
                endif;    
            endforeach;
        endif;

        $querys = $this->querys->get_users_by_name($data['username']);

        $author = [
            'status'    => $status,
            'mid'       => $mid,
            'author'    => $querys->id,
            'date'      => $dvalue    
        ];

        $merge = array_merge($datas,$author);
        
        $messages = [
            'title.required' => 'The (:attribute) field is required',
        ];

        $validator = Validator::make($datas,$rules,$messages);
        $errors = $validator->errors(); 

        if($validator->fails() != true AND is_array($merge)) :

            $metas_id = $this->metaInsert($ids,$metas);

            $this->catsInsert($categorys,$ids);

            if($ids==0) :

                $posts_id = DB::table('posts')->insertGetId($merge);
            
                $return = [
                    'pid'  => $posts_id,
                    'mid'  => $metas_id
                ];

            else :

                DB::table('posts')->where('id',$ids)->update($merge);

                $return = [
                    'pid'  => $ids,
                    'mid'  => $metas_id
                ];

            endif;

            return response()->json($return);

        endif;

        if($validator->fails() != false) :
            $message = $errors->all();
            return $this->view('admin/pages/posts/validate',$message);
        endif;
    }

    /**
     * application event - action function (meta-insert).
     *
     * @return \Illuminate\Http\Response
     */
    public function metaInsert ($id=null,$meta=null) 
    {
        $metas = [];
        if(count($meta)>=0 AND is_array($meta)) : foreach($meta as $keys => $vals) :
                $mids = $vals['id'];
                $data = array_merge($vals,['pid'=>$id]);
                unset($data['id']);
                if($mids==0) :
                    $ids = DB::table('posts_meta')->insertGetId($data);
                else :
                    DB::table('posts_meta')->where('id',$mids)->update($data);
                    $ids = $mids;
                endif;
                $metas[] = $ids;
            endforeach;
        endif; 
        return $metas;
    }

    /**
     * application event - action function (cats-insert).
     *
     * @return \Illuminate\Http\Response
     */
    public function catsInsert ($categorys=[],$ids=0) 
    {
        if(count($categorys) > 0) : 
            foreach($categorys as $ckeys => $cvals) :

                $cid = intval($cvals['id']);
                $option = ($cvals['option']);

                $cats_exists = DB::table('categorys_meta')->where(['cid'=>$cid,'pid'=>$ids])->first();
                $cats = $cats_exists == true ? intval($cats_exists->id) : 0;

                if($cats != 0 ) :
                    DB::table('categorys_meta')->where('id',$cats)->update(['cid'=>$cid,'pid'=>$ids,'option'=>$option]);
                else :
                    DB::table('categorys_meta')->insert(['cid'=>$cid,'pid'=>$ids,'option'=>$option]);
                endif;

            endforeach;
        endif;
    } 

    /**
     * application event - action function (media-insert).
     *
     * @return \Illuminate\Http\Response
     */
    public function mediaInsert ($id=null) 
    {
        $data   = null;
        $files  = $this->request->file('media-file');
        $fields = $this->request->all();

        $avl = [
            '_token',
            'id',
            'media-action',
            'width',
            'height'
        ];

        if(!is_null($files) && count($files)>=0 || is_array($files)) : 

            foreach($files as $keys => $vals):

                $names = time().$keys.'.'.$vals->getClientOriginalExtension();
                $paths = storage_path('app/public/uploads/'.date('Y/n'));

                $data = [
                    'name'  => $names,
                    'path'  => $paths,
                    'description' => $this->request->description,
                    'pid'   => 0,  
                    'author'=> 0,
                    'date'  => date("Y-m-d H:i:s")
                ];

                if(!is_null($id)) {
                    $action = DB::table('media')->where('id',$id)->update($data);
                } else {
                    $action = DB::table('media')->insert($data);
                }

                if($action) :
                    $vals->move($paths,$names);
                endif;

            endforeach;

        else :

            if(!is_null($fields)) : foreach($fields as $keys => $vals) : 
                        if(!in_array($keys,$avl)) : $data[$keys] = $vals;
                    endif;
                endforeach;
            endif;

            if(!is_null($data)) :
                DB::table('media')->where('id',$id)->update($data);
            endif;

        endif;

        if(!is_null($id)) {
            return redirect('/admin/media/edit/'.$id);
        } else {
            return redirect('/admin/media');
        }

    }

    /**
     * application event - action function (category-insert).
     *
     * @return \Illuminate\Http\Response
     */
    public function categoryInsert () 
    {
        $ids = intval($this->request->ids);
        $fields = $this->request->fields;

        $avl = [
            '_token',
            'id',
            'action'
        ];

        if(!is_null($fields)) : foreach($fields as $keys => $vals) : $name = $vals['name'];
                $datas[$name] = $vals['value'];
                $rules[$name] = 'required';
            endforeach;
        endif;

        $author = [
            'author'    => 0,
            'date'      => date("Y-m-d H:i:s")    
        ];

        $merge = array_merge($datas,$author);

        $validator = Validator::make($datas,$rules);
        $errors = $validator->errors(); 

        if($validator->fails() != true) :

            if( $ids !=0 ) :

                DB::table('categorys')->where('id',$ids)->update($merge);
                $return = ['cid'=>$ids];

            else :

                $ids = DB::table('categorys')->insertGetId($merge);
                $return = ['cid'=>$ids];

            endif;

            return response()->json($return);

        endif;

        if($validator->fails() != false) :
            $message = $errors->all();
            return $this->view('admin/pages/posts/validate',$message);
        endif;
    }

    public function categoryInsertRow () 
    {
        $ids = intval($this->request->ids);
        var_dump($ids);
        return $this->view('admin/pages/posts/categoryrow');
    }

    /**
     * application event - filter function (filter-action).
     *
     * @return \Illuminate\Http\Response
     */
    public function postsFilter ($page=null) 
    {
        $actions = $this->request->action;
        $searchs = $this->request->search;

        if ($actions == 0 AND !is_null($searchs))  :

            $filters = [
                'action' => $actions,
                'search' => $searchs
            ];

            return $this->template_actions('admin/pages/posts',$filters);

        elseif ($actions == 1 AND is_null($searchs)) :

            return $this->template('admin/pages/posts');

        elseif ($actions == 2 AND is_null($searchs)) :

        else :

            return $this->template('admin/pages/posts');

        endif;
    }

    // END
}
