<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserAdminController extends Controller
{
    /**
     * نمایش تمام کارشناسات.
     */
    public function admin_list(Request $request)
    {
        $user = Auth::user();
        if(!isset($user))
        {
            return redirect('/login');
        }
        access(83);
        $model = User::orderBy($request->order ?? 'user_id', $request->orderby ?? 'desc')->where('active' , 1);
        $model = !empty($request->name) ? $model->where( 'name',  'like' , '%'.$request->name.'%' ) : $model;
        $model = !empty($request->family) ? $model->where( 'family',  'like' , '%'.$request->family.'%' ) : $model;
        $model = !empty($request->user_id) ? $model->where( 'user_id' , $request->user_id) : $model;
        $totalCount = $model->count();
        $model = $model->paginate($request->showcount);
        if ($request->ajax() && $model->count() > 0) {
            $couter=$totalCount/$request->showcount;
            $counter1= round($couter);
            if($counter1>=$couter)
                $couter=$counter1;
            else
                $couter=$counter1+1;
            $hasPage = ($couter==$request->page)? false : true;
            $request_type = $request->request_type;
            $currentuser_id = $user->id;
            $view = view('user.admin.list_type', compact('model','totalCount'))->render();
            return response()->json(['html' => $view, 'hasPage' => $hasPage, 'totalCount' => $totalCount]);
        }
        return view( 'user.admin.list', compact( 'model'));
    }

    /**
     * ایجاد یک کارشناس جدید.
     */
    public function admin_create()
    {
        $user = Auth::user();
        if(!isset($user))
        {
            return redirect('/login');
        }

        access(82);
        $sites = Site::get();
        $roles = Role::get();
        return view('user.admin.create', compact( 'sites' , 'roles' ));
	}
    public function admin_store(Request $request)
    {
        // بررسی لاگین بودن کاربر
        $user = Auth::user();
        if (!$user) {
            return redirect('/login');
        }

        // بررسی دسترسی
        access(82);

        // اعتبارسنجی داده‌ها
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'family' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:8',

            'role_id' => 'required|integer',
            'site_id' => 'required|integer',
        ]);

        // بازگرداندن خطاهای اعتبارسنجی
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        // ذخیره داده‌ها
        $inputs = $request->only([
            'name', 'family', 'username', 'password', 'group_id', 'role_id', 'site_id'
        ]);
        $inputs['password'] = Hash::make($inputs['password']);
        $inputs['active'] = 1;
        $user = User::create($inputs);

        // پاسخ نهایی
        return redirect('/admin/user/list')->with( 'success', 'User updated successfully' );
    }

    /**
     * به‌روزرسانی یک کارشناس.
     */
    public function admin_edit( $id )
    {
        $user = Auth::user();
        if(!isset($user))
        {
            return redirect('/login');
        }
        access(84);
        $sites = Site::get();
        $roles = Role::get();
        $model = User::find($id);
		return view('user.admin.create', compact('model' , 'sites' , 'roles') );
	}
    public function admin_update(Request $request, $id)
    {
        $user = Auth::user();
        if(!isset($user))
        {
            return redirect('/login');
        }
        access(84);
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'کارشناس موردنظر یافت نشد'], 404);
        }

        // اعتبارسنجی داده‌ها
        $validator = Validator::make($request->all(), [

            'family' => 'nullable|string|max:255',
            'password' => 'required|string|min:5',
            'role_id' => 'required|integer',
            'site_id' => 'required|integer',
        ]);

        // بازگرداندن خطاهای اعتبارسنجی
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        // به‌روزرسانی داده‌ها
        $inputs = $request->only([
            'name', 'family', 'username', 'password', 'group_id', 'role_id', 'site_id'
        ]);

        if ( ! empty( $inputs['password'] ) ) {

			$inputs['password'] = Hash::make( $inputs['password'] );

		} else {
			$inputs = Arr::except( $inputs, array( 'password' ) );
		}
        //dd($inputs);
        $user->update($inputs);

        // پاسخ نهایی
        return redirect('/admin/user/list')->with( 'success', 'User updated successfully' );
    }

    /**
     * حذف یک کارشناس.
     */
    public function admin_destroy($id)
    {
        $user = Auth::user();
        if (!isset($user)) {
            return redirect('/login');
        }
        // بررسی دسترسی
        access(85);
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'کارشناس موردنظر یافت نشد'], 404);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'کارشناس با موفقیت حذف شد']);
    }
}
