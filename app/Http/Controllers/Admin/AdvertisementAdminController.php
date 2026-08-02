<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class AdvertisementAdminController extends Controller
{
    /**
     * نمایش تمام تبلیغات.
     */
    public function admin_list(Request $request)
    {
        $user = Auth::user();
        if(!isset($user))
        {
            return redirect('/login');
        }
        access(89);
        $model = Advertisement::orderBy($request->order ?? 'advertisementid', $request->orderby ?? 'desc');
        $model = !empty($request->title) ? $model->where( 'title',  'like' , '%'.$request->title.'%' ) : $model;
        $model = !empty($request->priority) ? $model->where( 'priority' , $request->priority - 1 ) : $model;
        $model = !empty($request->stDateFrom) ? $model->where( 'startdate1', '>=' ,  jalaligregorian($request->stDateFrom , '/')." 00:00:00" ) : $model;
        $model = !empty($request->stDateTo) ? $model->where( 'startdate2', '<=' , jalaligregorian($request->stDateTo , '/')." 23:59:59" ) : $model;
        $model = !empty($request->endDateFrom) ? $model->where( 'enddate1', '>=' , jalaligregorian($request->endDateFrom , '/')." 00:00:00" ) : $model;
        $model = !empty($request->endDateTo) ? $model->where( 'enddate2', '<=' ,  jalaligregorian($request->endDateTo , '/')." 23:59:59" ) : $model;
        $model = !empty($request->row) ? $model->where( 'rows' , $request->row) : $model;
        $model = !empty($request->display) ? $model->where( 'hidden' , $request->display - 1) : $model;
        $model = !empty($request->important) ? $model->where( 'important',  1) : $model;
        $model = !empty($request->position) ? $model->where( 'position',  $request->position) : $model;
        $filter_arr['site_id'] = session('langid');
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
            $currentuserid = $user->id;
            $view = view('advertisement.admin.list_type', compact('model','totalCount'))->render();
            return response()->json(['html' => $view, 'hasPage' => $hasPage, 'totalCount' => $totalCount]);
        }
        return view( 'advertisement.admin.list', compact( 'model'));
    }

    /**
     * ایجاد یک تبلیغ جدید.
     */
    public function admin_create()
    {
        $user = Auth::user();
        if(!isset($user))
        {
            return redirect('/login');
        }
        access(57);

        return view('advertisement.admin.create');
	}
    public function admin_store(Request $request)
    {
        Cache::forget("AdvertisementFirstPage-" . langname(session('langid')));
        // بررسی لاگین بودن کاربر
        $user = Auth::user();
        if (!$user) {
            return redirect('/login');
        }

        // بررسی دسترسی
        access(57);
        session('langid');
        // اعتبارسنجی داده‌ها
        /*$validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'position' => 'required|integer',
            'mediaid' => 'required|integer',
            'rows' => 'nullable|integer', // رفع مشکل اجباری بودن
            'link' => 'nullable|string|max:255',
            'articleid' => 'nullable|integer', // رفع مشکل اجباری بودن
            'startdate' => 'required|date',
            'enddate' => 'required|date',
            'comment' => 'nullable|string',
            'orderview' => 'nullable|integer', // رفع مشکل اجباری بودن
            'hidden' => 'nullable|integer',
            'priority' => 'nullable|integer',
            'createdate' => 'required|date',
            'site_id' => 'nullable|integer', // رفع مشکل اجباری بودن
            'important' => 'nullable|integer',
            'mediaid2' => 'nullable|integer',
            'mediaid3' => 'nullable|integer',
            'without_title' => 'nullable|integer',
        ]);

        // بازگرداندن خطاهای اعتبارسنجی
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }*/

        // ذخیره داده‌ها
        $inputs = $request->only([
             'title', 'position', 'media', 'rows', 'link', 'articleid', 'startdate',
            'enddate', 'comment', 'orderview', 'hidden', 'priority', 'createdate',
            'site_id', 'mediaid2', 'mediaid3' , 'mediashow'
        ]);
        $inputs['site_id'] = session('langid');
        $inputs['startdate'] = jalaligregorian($inputs['startdate'], '/');
        $inputs['createdate'] = date('Y-m-d H:i:s');
        $inputs['enddate'] = jalaligregorian($inputs['enddate'], '/');
        if ($request->hasFile('media') && $request->file('media')->isValid()) {
            $inputs['mediaid'] = uploader($inputs['media'], array('grouptype' => 2, 'description' => $inputs['title'], 'description'=>$inputs['title']));
        }
        $advertisement = Advertisement::create($inputs);

        // پاسخ نهایی
        return redirect('/admin/advertisement/list')->with( 'success', 'User updated successfully' );
    }

    /**
     * به‌روزرسانی یک تبلیغ.
     */
    public function admin_edit( $id )
    {
        Cache::forget("AdvertisementFirstPage-" . langname(session('langid')));
        $user = Auth::user();
        if(!isset($user))
        {
            return redirect('/login');
        }
        access(58);
        $model = Advertisement::find($id);
        $startdate = '';
        $enddate = '';
        if($model->startdate)
        {
            $startdate1 = explode(' ',$model->startdate);
            $startdate = explode('-',$startdate1[0]);
            $startdate = gregorian_to_jalali($startdate[0] , $startdate[1] , $startdate[2] , '/');
        }
        if($model->enddate)
        {
            $enddate1 = explode(' ',$model->enddate);
            $enddate = explode('-',$enddate1[0]);
            $enddate = gregorian_to_jalali($enddate[0] , $enddate[1] , $enddate[2] , '/');
        }
		return view('advertisement.admin.create', compact('model' , 'startdate' , 'enddate') );
	}
    public function admin_update(Request $request, $id)
    {
        $user = Auth::user();
        if(!isset($user))
        {
            return redirect('/login');
        }
        access(58);
        $advertisement = Advertisement::find($id);

        if (!$advertisement) {
            return response()->json(['message' => 'تبلیغ موردنظر یافت نشد'], 404);
        }

        // اعتبارسنجی داده‌ها
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'position' => 'nullable|integer',
            'mediaid' => 'nullable|integer',
            'rows' => 'nullable|integer', // رفع مشکل اجباری بودن
            'link' => 'nullable|string|max:255',
            'articleid' => 'nullable|integer', // رفع مشکل اجباری بودن
            'startdate' => 'nullable|date',
            'enddate' => 'nullable|date',
            'comment' => 'nullable|string',
            'orderview' => 'nullable|integer', // رفع مشکل اجباری بودن
            'hidden' => 'nullable|integer',
            'priority' => 'nullable|integer',
            'createdate' => 'nullable|date',
            'site_id' => 'nullable|integer',
            'mediaid2' => 'nullable|integer',
            'mediaid3' => 'nullable|integer',
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
            'title', 'position', 'media', 'rows', 'link', 'articleid', 'startdate',
            'enddate', 'comment', 'orderview', 'hidden', 'priority', 'createdate',
            'site_id', 'mediaid2', 'mediaid3' , 'mediashow'
        ]);

        $inputs['startdate'] = jalaligregorian($inputs['startdate'], '/');
        $inputs['enddate'] = jalaligregorian($inputs['enddate'], '/');
        if ($request->hasFile('media') && $request->file('media')->isValid()) {
            $inputs['mediaid'] = uploader($inputs['media'], array('grouptype' => 2, 'description' => $inputs['title'], 'description'=>$inputs['title']));
        }
        elseif($request->mediashow == 1)
        {
            $inputs['mediaid'] = 0;
        }
        $advertisement->update($inputs);

        // پاسخ نهایی
        return redirect('/admin/advertisement/list')->with( 'success', 'User updated successfully' );
    }

    /**
     * حذف یک تبلیغ.
     */
    public function admin_destroy($id)
    {
        $user = Auth::user();
        if (!isset($user)) {
            return redirect('/login');
        }
        // بررسی دسترسی
        access(61);
        $advertisement = Advertisement::find($id);

        if (!$advertisement) {
            return response()->json(['message' => 'تبلیغ موردنظر یافت نشد'], 404);
        }

        $advertisement->delete();

        return response()->json(['success' => true, 'message' => 'تبلیغ با موفقیت حذف شد']);
    }

    public function admin_display( $id ) {
        $user = Auth::user();
        if(!isset($user))
        {
            return redirect('/login');
        }
        access(60);
		$model = Advertisement::find( $id );
        $model->update( [ 'hidden' => $model->hidden ? 0 : 1 ] );
		return response()->json('' , 200, [], JSON_UNESCAPED_UNICODE);
	}
}
