<div class="border rounded p-3 bg-secondary mb-2">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <p class="m-0">
            <b> {{l('تعداد نتایج')}}:  </b>
            {{$totalCount}}
        </p>

    </div>
</div>
<div class="table-p">
    <table class="table table-bordered table-striped table-hover">
        <thead class="table-primary">
            <tr>
                <th valign="middle" style="text-align:center" scope="col">{{l('ابزار')}}</th>
                <th valign="middle" style="text-align:center" scope="col">
                    {{l('کد')}}
                </th>
                <th valign="middle" style="text-align:center" scope="col">{{l('نام')}}</th>
                <th valign="middle" style="text-align:center" scope="col">{{l('تلفن')}}</th>
                <th valign="middle" style="text-align:center" scope="col">{{l('راه ورود')}}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($model as $item)
            <tr>
                <td valign="middle" align="center">
                    <button class="btn btn-icon btn-light btn-xs rounded-circle shadow-sm" type="button" id="contextMenu2" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fi-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu my-1" aria-labelledby="contextMenu2">

                        <li>
                            <a  class="dropdown-item"  target="_blank" href="/admin/customer/edit/{{$item->id}}">
                                <i class=" opacity-60 me-2 fa-light fa fa-edit"></i>
                                {{l('ویرایش')}}
                            </a>
                        </li>
                        {{-- ورود به حساب مشتری برای پیگیری مشکلی که گزارش کرده است --}}
                        <li>
                            <a  class="dropdown-item" target="_blank" href="/admin/customer/{{$item->id}}/impersonate"
                                onclick="return confirm('وارد حساب این مشتری می‌شوید. ادامه می‌دهید؟');">
                                <i class=" opacity-60 me-2 fa fa-user-secret"></i>
                                {{l('ورود به حساب مشتری')}}
                            </a>
                        </li>
                        <li >
                            <a  class="dropdown-item remove"  onclick="return destroy({{$item->id}})" style="cursor:pointer">
                                <i class=" opacity-60 me-2 fa-light fa-trash-can"></i>
                                {{l('حذف')}}
                            </a>
                        </li>

                    </ul>
                </td>

                <td valign="middle" align="center">{{$item->id}}</td>
                <td valign="middle" align="center">{{$item->fullName()}}</td>
                <td valign="middle" align="center">{{$item->phone}}</td>
                <td valign="middle" align="center">
                    @if(!empty($item->password))
                        <span class="badge bg-success">{{l('رمز + پیامک')}}</span>
                    @else
                        <span class="badge bg-secondary">{{l('فقط پیامک')}}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script>

    jQuery(document).ready(function($) {
        $(".clickable-row").click(function() {
            window.location = $(this).data("href");
        });
});
</script>
<style>
    .clickable-row{cursor: pointer}
    .sortable{
        /* color:blue !important; */
        cursor: pointer
    }
    .table > :not(caption) > * > * {
        padding: 0.5rem;
    }
</style>
