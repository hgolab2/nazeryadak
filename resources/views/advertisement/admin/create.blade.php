@extends('layout.managmentLayout', [
    'title' => !empty($model) ? l('ویرایش بنر') : l('ثبت بنر جدید'),
])
@section('main_content')
    <!-- Breadcrumb-->
    <nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">{{ l('خانه') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">
                {{ !empty($model) ? l('ویرایش بنر') : l('ثبت بنر جدید') }}</li>
        </ol>
    </nav>
    <!-- Title-->
    <div class="mb-4">
        <h2 class="h5 mb-0">{{ !empty($model) ? l('ویرایش بنر') : l('ثبت بنر جدید') }} </h2>
    </div>
    <!-- Basic info-->
    <form id="js_singup-expert" role="form" method="POST" enctype="multipart/form-data" action="<?php if (!empty($model)) {
        echo '/admin/advertisement/update/' . $model->advertisementid;
    } else {
        echo '/admin/advertisement/store';
    } ?>">
        @if (!empty($model))
            @method('put')
        @endif
        @csrf

        <section class="card card-body shadow-sm p-4 mb-4" id="basic-info">
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <label for="title" id="label_title" class="form-label">عنوان<span class="required">*</span></label>
                    <div><input type="text" name="title" id="title" value="{{ !empty($model) ? $model->title : '' }}" class="form-control" required></div>
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="link" id="label_link" class="form-label">لینک</label>
                    <div><input type="text" name="link" id="link" value="{{ !empty($model) ? $model->link : '' }}" class="form-control"></div>
                </div>

                <div class="col-sm-6 mb-3">
                    <label for="startdate" id="label_startdate" class="form-label">تاریخ شروع<span class="required">*</span></label>
                    <input type="text" name="startdate" id="startdate" readonly value="{{!empty($model)?$startdate:''}}" style="cursor: pointer" class="form-control" required>
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="enddate" id="label_enddate" class="form-label">تاریخ پایان<span class="required">*</span></label>
                    <input type="text" name="enddate" id="enddate" readonly value="{{!empty($model)?$enddate:''}}" style="cursor: pointer"  class="form-control" required>
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="priority" id="label_priority" class="form-label">رتبه</label>
                    <select name="priority" id="priority" class="form-control">
                        <option value="0" {{!empty($model) && $model->priority == 0 ? " selected " :''}}>0</option>
                        <option value="1" {{!empty($model) && $model->priority == 1 ? " selected " :''}}>1</option>
                        <option value="2" {{!empty($model) && $model->priority == 2 ? " selected " :''}}>2</option>
                        <option value="3" {{!empty($model) && $model->priority == 3 ? " selected " :''}}>3</option>
                        <option value="4" {{!empty($model) && $model->priority == 4 ? " selected " :''}}>4</option>
                        <option value="5" {{!empty($model) && $model->priority == 5 ? " selected " :''}}>5</option>
                        <option value="6" {{!empty($model) && $model->priority == 6 ? " selected " :''}}>6</option>
                        <option value="7" {{!empty($model) && $model->priority == 7 ? " selected " :''}}>7</option>
                        <option value="8" {{!empty($model) && $model->priority == 8 ? " selected " :''}}>8</option>
                        <option value="9" {{!empty($model) && $model->priority == 9 ? " selected " :''}}>9</option>
                        <option value="10" {{!empty($model) && $model->priority == 10 ? " selected " :''}}>10</option>
                    </select>
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="priority" id="label_priority" class="form-label">مکان</label>
                    <select name="position" id="position" class="form-control">
                        <option value="2" {{!empty($model) && $model->position == 2 ? " selected " :''}}>تبلیغات</option>
                        <option value="3" {{!empty($model) && $model->position == 3 ? " selected " :''}}>منوی تصاویر</option>
                        <option value="4" {{!empty($model) && $model->position == 4 ? " selected " :''}}>بنر بالای مطالب</option>
                        <option value="5" {{!empty($model) && $model->position == 5 ? " selected " :''}}>اسلایدر فروشگاه</option>
                    </select>
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="img" id="label_img" class="form-label">عکس</label>
                    <input
                        type="file"
                        name="media"
                        id="media"
                        class="form-control file"
                        accept="
                            .jpg,.jpeg,.png,.gif,.webp,
                            image/jpeg,image/pjpeg,
                            image/png,image/x-png,
                            image/gif,
                            image/webp
                        "
                    >

                    @if(!empty($model) && $model->media != null && $model->mediaid > 0)
                    <input type="hidden" name="mediashow" id="mediashow" class="photo" value="0" />
                    <img src="{{$model->media->getPath()}}" id="preview" style="width: 200px" />
                    <span id="deletemedia"  style="cursor: pointer" class="cursor-pointer absolute bottom-7 left-0 text-blue-500 text-[14px] font-light {{(!empty($model) && !empty($model->media))?'':'d-none'}}">حذف</span>
                    @endif
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="comment" id="label_comment" class="form-label">توضیحات</label>
                    <textarea name="comment" id="comment" rows="5" cols="80" class="form-control">{{ !empty($model) ? $model->comment : '' }}</textarea>
                </div>

            </div>
        </section>

        <!-- Action buttons -->
        <section class="d-sm-flex justify-content-between pt-2">
            <button type="submit" class="btn btn-primary btn-lg d-block mb-2">
                {{ !empty($model) ? l('ویرایش بنر') : l('ثبت بنر جدید') }}
            </button>
        </section>
    </form>
    <script src="/js/jquery.js"></script>
    <script src="/vendor/select2/select2.min.js"></script>
    <!-- Main theme script-->
    <script src="{{ asset('/assets/js/valid.js') }}"></script>
    <script>
        $('.select2').select2();

        $("#deletemedia").click(function() {
            $("#deletemedia").addClass('d-none');
            $('#preview').attr("src", "").addClass('d-none');
            $("#mediashow").val(1);
        });


        $(document).ready(function() {
            $('#js_singup-expert').validate({
                rules: {
                    username: {
                        required: true,
                    }
                },
                errorPlacement: function(error, element) {
                    var type = $(element).attr('cus-valid')
                    if (type == 'true') {
                        error.insertAfter(element.parent().parent());
                    } else {
                        error.insertAfter(element)
                    }
                },
            });
        });
    </script>
    <link href="/vendor/date_picker/kamadatepicker.css" rel="stylesheet">
    <script src="/vendor/date_picker/kamadatepicker.js"></script>
    <script>
        var customOptions = {
            placeholder: "{{l('روز / ماه / سال')}}"
            , twodigit: false
            , closeAfterSelect: true
            , nextButtonIcon: "fa fa-angle-right"
            , previousButtonIcon: "fa fa-angle-left"
            , buttonsColor: "#37b5b5"
            , forceFarsiDigits: true
            , markToday: true
            , markHolidays: true
            , highlightSelectedDay: true
            , sync: true
            , gotoToday: true
        };
        kamaDatepicker('startdate', customOptions);
        kamaDatepicker('enddate', customOptions);
    </script>

@endsection
