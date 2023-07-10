<?php

namespace Bitfumes\Multiauth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Bitfumes\Multiauth\Model\Admin;
use Bitfumes\Multiauth\Model\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use DB;

class AdminController extends Controller
{
    use AuthorizesRequests;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('role:super;super-admin;admin');
        //$this->middleware('role:super;super-admin;admin', ['only'=>'show']);
        //$this->adminModel = config('multiauth.models.admin');
    }

    public function index()
    {
        return redirect()->intended('/dashboard');
        //return redirect('/dashboard');
        //return view('multiauth::admin.home');
    }


     //Fetchs data of search results
     public function fetchData(Request $request){
       // return $request->all();

            $roles = new Role;
            $terms = explode(' ', request('keyword'));
            $admins   = DB::table('admin_role')
                                ->join('roles', 'roles.id', '=', 'admin_role.role_id')
                                ->join('admins', 'admins.id', '=', 'admin_role.admin_id')
                                ->select('admins.active','admins.name', 'admins.email', 'admins.id', 'admin_role.role_id', 'roles.name AS role_name')
                                ->where(function($query) use ($terms){
                                    foreach($terms as $term){
                                        $query->where('admins.name', 'LIKE', "%{$term}%")
                                                ->orWhere('admins.email', 'LIKE', '%' . $term . '%')
                                                ->orWhere('roles.name', 'LIKE', '%' . $term . '%');
                                    }
                                })
                                ->groupBy('admins.email')
                                ->orderBy('admins.name', 'ASC')
                                ->paginate(30);




            return view('vendor.multiauth.admin._results')
                            ->with('admins', $admins)
                            ->with('roles', $roles)
                            ->render();

    }

    public function show()
    {
        //$admins = Admin::where('id', '!=', auth()->id())->get();

       $admins = Admin::where('id', '!=', auth()->id())
                        ->groupBy('admins.email')
                        ->orderBy('admins.name', 'ASC')
                        ->paginate(30);

        $roles      = new Role;
        $message    = "Customers data is currently empty!";
        //dd($admins);
        return view('multiauth::admin.show')
                ->with('admins', $admins)
                ->with('roles', $roles)
                ->with('message', $message);
    }

    public function showChangePasswordForm()
    {
        return view('multiauth::admin.passwords.change');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'oldPassword'   => 'required',
            'password'      => 'required|confirmed',
        ]);
        auth()->user()->update(['password' => bcrypt($data['password'])]);

        return redirect(route('admin.home'))->with('success', 'Your password was changed successfully');
    }

    public function delete($id)
    {
        $admin = Admin::find($id);
        $admin->delete();
        return redirect('/admin/show')->with('success', 'Admin [' .$admin->name. '] has been deleted!');

    }
}
