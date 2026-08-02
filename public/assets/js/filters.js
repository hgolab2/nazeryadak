
// --------------------------------
// ------ Common filters ----------
// --------------------------------

// فیلتر بر اساس امکانات در
function other_facilities_filters() {
    var list_search = ['سونا','جکوزی','کولرگازی','شومینه','پارکینگ','بالکن','حیاط'];
    $("#another_filter_1").append(`
        <p class="hidden lg:block text-gray-500 text-lg lg:text-xl font-light"><span><i class="fa-thin fa-chevron-left"></i></span> بر اساس امکانات</p>
        <div class="rounded-25 border-[1px] border-gray-400 bg-white p-[10px] lg:mt-4">
            <span class="text-[32px] text-gray-400 float-left pl-2"><i class="fa-thin fa-xmark"></i></span>
            <div>
                <ul class="flex items-center justify-start flex-wrap space-y-1" id="js_search_result_Basics_facilities">

                </ul>
            </div>
            <div class="mt-3 relative">
                <input class="w-full h-8 outline-none font-light text-gray-500" type="text" name="" id="js_other_facilities" value="");">
                <span class="absolute right-0 bottom-[3px]">
                    <span class="invisible" id="js_text_shadow_visibility"></span>
                    <span class="text-gray-400 font-light absolute select-none pointer-events-none w-max" id="js_text_shadow"></span>
                </span>
            </div>
        </div>
        <div class="mt-3 mb-3 lg:mb-0">
            <ul class="flex flex-wrap justify-start items-center" id="js_Pre_displayed_filters">

            </ul>
        </div>
    `);

    for (let i = 0; i < 4; i++) {
        $('#js_Pre_displayed_filters').append(`
            <li class="rounded-25 border-[1px] border-gray-400 h-[38px] flex items-center justify-center w-fit px-3 py-3 mx-1 my-1">
                <span class="pl-2 text-lg lg:text-xl text-gray-400 cursor-pointer js_add_tag"><i class="fa-thin fa-plus"></i></span>
                <p class="text-base text-gray-500 font-light">${list_search[i]}</p>
            </li>
        `);
    }
    js_add_tag();
    function js_add_tag(){
        $('.js_add_tag').click(function (e) {
            var tag = $(this).parent()[0].innerText;
            add_filter(tag,tag);
        });
    }
    function js_remove_tag(element) {
        $(element).click(function (e) {
            var tag = $(element)[0].innerText;
            list_search.push(tag);
            $(element)[0].remove();
            filter_text('');
        });
    }
    // add filter to top
    var counter = 0;
    function add_filter(text,final_text) {
        if (text == final_text) {
            $('#js_search_result_Basics_facilities').append(`
                <li class="flex items-center justify-between h-[38px] px-3 bg-[#f9f9f9] border-[1px] border-gray-400 rounded-25 ml-4 mt-1">
                    <p class="text-base text-gray-500 font-light w-max">${final_text}</p>
                    <span class="text-base text-red-200 pr-2 cursor-pointer js_remove_tag"><i class="fa-thin fa-xmark"></i></span>
                </li>
            `);
            $('#js_other_facilities').val('');
            var func = $('.js_remove_tag').parent()[counter];
            js_remove_tag(func);
            counter ++;
            filter_text(final_text);
        }
    }
    // when filter add to list
    function filter_text(text) {
        $('#js_Pre_displayed_filters').html('');
        if (text !='') {
            list_search.splice($.inArray(text, list_search), 1);
        }
        for (let i = 0; i < 4; i++) {
            if(list_search[i]){
                $('#js_Pre_displayed_filters').append(`
                    <li class="rounded-25 border-[1px] border-gray-400 h-[38px] flex items-center justify-center w-fit px-3 py-3 mx-1 my-1">
                        <span class="pl-2 text-lg lg:text-xl text-gray-400 cursor-pointer js_add_tag"><i class="fa-thin fa-plus"></i></span>
                        <p class="text-base text-gray-500 font-light">${list_search[i]}</p>
                    </li>
                `);
            }
        }
        js_add_tag();
    }
    $('#js_other_facilities').keyup(function (e) {
        var input_search = $(this).val();
        for (let i = 0; i < list_search.length; i++) {
            var result = list_search[i].match(input_search);
            if (result != null && result != "" ) {
                var text_shadow = result['input'].replace(result[0],'');
                $('#js_text_shadow_visibility')[0].innerText = result[0];
                $('#js_text_shadow')[0].innerText = text_shadow;
                add_filter(result[0],result['input']);
            }
        }
        if (!text_shadow || $('#js_other_facilities').val() == "") {
            $('#js_text_shadow_visibility')[0].innerText = '';
            $('#js_text_shadow')[0].innerText = '';
        }
    });
}

// چند انتخابی مخصوص فیلتر
function insert_data_popup_city_content(id_section) {
    $(id_section).append(`
        <div class="lg:hidden lg:bg-[#f9f9f9] lg:border-[1px] lg:border-gray-400 lg:rounded-25 lg:px-6 lg:py-4 lg:max-w-[558px] lg:max-h-[493px] lg:absolute lg:top-[61px] lg:right-[130px] w-full z-30" id="js_popup_Location">
            <div class="flex justify-between items-center">
                <div class="hidden lg:block"><i class="fa-thin fa-angle-up text-4xl text-gray-500"></i></div>
                <div class="overflow-x-auto mx-4 w-full">
                    <ul class="flex items-center justify-start space-x-4" id="js_city_tag">
                    </ul>
                </div>
                <div class="hidden lg:block text-[20px] text-gray-400 w-[45px]"><span>5</span><span class="pr-1"><i class="fa-thin fa-plus"></i></span></div>
            </div>
            <div class="rounded-25 border-[1px] border-gray-400 flex justify-between items-center px-4 h-[60px] bg-white overflow-hidden mt-4">
                <input class="h-full w-full outline-0 ml-4 text-gray-400 font-light" type="search" id="js_city_search">
                <span class="text-4xl mt-2 text-gray-400"><i class="fa-thin fa-magnifying-glass"></i></span>
            </div>
            <div class="mt-4">
                <ul dir="ltr" class="space-y-4 overflow-auto lg:max-h-[200px] pt-2 text-right pr-3" id="js_city_search_items">
                    <li class="relative mb-5">
                        <input class="hidden css_city js_city_btn" type="checkbox" name="city" id="city_1">
                        <label class="before:content-[' '] before:w-[27px] before:h-[27px] before:bg-white before:border-[1px] border-gray-400 before:absolute before:top-[-4px] before:right-0 before:rounded-25 mr-9 css_city_label" for="city_1">قدس</label>
                    </li>
                    <li class="relative mb-5">
                        <input class="hidden css_city js_city_btn" type="checkbox" name="city" id="city_2">
                        <label class="before:content-[' '] before:w-[27px] before:h-[27px] before:bg-white before:border-[1px] border-gray-400 before:absolute before:top-[-4px] before:right-0 before:rounded-25 mr-9 css_city_label" for="city_2">مطهری</label>
                    </li>
                    <li class="relative mb-5">
                        <input class="hidden css_city js_city_btn" type="checkbox" name="city" id="city_3">
                        <label class="before:content-[' '] before:w-[27px] before:h-[27px] before:bg-white before:border-[1px] border-gray-400 before:absolute before:top-[-4px] before:right-0 before:rounded-25 mr-9 css_city_label" for="city_3">سالاریه</label>
                    </li>
                    <li class="relative mb-5">
                        <input class="hidden css_city js_city_btn" type="checkbox" name="city" id="city_4">
                        <label class="before:content-[' '] before:w-[27px] before:h-[27px] before:bg-white before:border-[1px] border-gray-400 before:absolute before:top-[-4px] before:right-0 before:rounded-25 mr-9 css_city_label" for="city_4">آذر</label>
                    </li>
                    <li class="relative mb-5">
                        <input class="hidden css_city js_city_btn" type="checkbox" name="city" id="city_5">
                        <label class="before:content-[' '] before:w-[27px] before:h-[27px] before:bg-white before:border-[1px] border-gray-400 before:absolute before:top-[-4px] before:right-0 before:rounded-25 mr-9 css_city_label" for="city_5">صفائیه</label>
                    </li>
                    <li class="relative mb-5">
                        <input class="hidden css_city js_city_btn" type="checkbox" name="city" id="city_6">
                        <label class="before:content-[' '] before:w-[27px] before:h-[27px] before:bg-white before:border-[1px] border-gray-400 before:absolute before:top-[-4px] before:right-0 before:rounded-25 mr-9 css_city_label" for="city_6">شهر ششم</label>
                    </li>
                </ul>
            </div>
            <div class="mt-4 absolute bottom-5 left-5 right-5 lg:relative">
                <button class="flex items-center justify-center bg-blue-500 rounded-25 px-4 w-full h-[60px] relative">
                    <span class="text-base text-white font-light absolute right-4">10 محله</span>
                    <span class="text-lg lg:text-2xl font-medium text-white ">اعمال فیلتر</span>
                </button>
            </div>
        </div>
    `)
    select_tag();
    $("#js_open_Location").click(function () {
        $("#js_popup_Location").show("fast");
        $("#js_overlay").fadeIn("500");
    });
    overlay_controll("#js_popup_Location");
}

// --------------------------------
// ------ desktop mod popups ------
// --------------------------------

// بک گراند مشکی
function overlay_controll(section) {
    $("#js_overlay , .js_close , .js_close_popup").click(function (e) {
        $(section).hide("fast");
        $("#js_overlay").fadeOut("500");
    });
}

// باکس سایر فیلتر ها
function another_filters_popup() {
    $("#desktop_filter_content").append(`
        <div class="hidden absolute top-[61px] z-30" id="js_another_filter_content">
            <div class="p-5 rounded-25 border-[1px] border-gray-400 bg-[#F9F9F9] max-w-[820px] flex flex-wrap justify-between items-start w-full">
                <div class="flex justify-between items-center w-full mb-4">
                    <span class="text-lg lg:text-xl font-light text-gray-500">سایر فیلتر ها</span>
                    <span class="text-[32px] text-gray-400 js_close"><i class="fa-thin fa-xmark"></i></span>
                </div>
                <div class="w-1/2 pl-4">
                    <div id="another_filter_1">

                    </div>
                    <div>
                        <div class="mt-6">
                            <p class="text-gray-500 text-lg lg:text-xl font-light"><span><i class="fa-thin fa-chevron-left"></i></span> نوع کاربری</p>
                            <div class="h-12 w-full rounded-25 border-[1px] border-gray-400 overflow-hidden bg-white px-4 mt-3">
                                <select dir="ltr" name="" id="" class="w-full h-full outline-none text-gray-500 font-light text-right pr-3">
                                    <option value="نوع کاربری">نوع کاربری</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-6">
                            <p class="text-gray-500 text-lg lg:text-xl font-light"><span><i class="fa-thin fa-chevron-left"></i></span> جهت جغرافیایی</p>
                            <div class="h-12 w-full rounded-25 border-[1px] border-gray-400 overflow-hidden bg-white px-4 mt-3">
                                <select dir="ltr" name="" id="" class="w-full h-full outline-none text-gray-500 font-light text-right pr-3">
                                    <option value="جنوبی">جنوبی</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-1/2 pr-4">
                    <div class="flex justify-between">
                        <p class="text-gray-500 text-lg lg:text-xl font-light">قابلیت معاوضه</p>
                        <div class="h-8 rounded-full relative w-[61px] switch">
                            <input class="absolute top-0 left-0 h-full w-full opacity-0 cursor-pointer" type="checkbox" name="switch" />
                            <div class="before:content-[''] before:block before:absolute before:rounded-full before:pointer-events-none before:duration-500 before:top-0 before:min-h-full before:min-w-full before:bg-blue-500 before:delay-200 after:content-[''] after:block after:absolute after:rounded-full after:pointer-events-none after:duration-500 after:top-[2px] after:left-[2px] after:bg-white after:min-h-[86%] after:aspect-square"></div>
                        </div>
                    </div>
                    <div class="mt-6">
                        <p class="text-lg lg:text-xl text-gray-500 text-lg lg:text-xl font-light"><span class="text-lg lg:text-xl"><i class="fa-thin fa-chevron-left"></i></span> تعداد طبقات</p>
                        <div dir="ltr" class="px-[5px] mt-4 border-[1px] border-gray-400 rounded-25 h-12 flex items-center justify-between overflow-hidden">
                            <input class="hidden radios" type="radio" name="Number_of_floors" id="floors_1">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="floors_1">1</label>
                            <input class="hidden radios" type="radio" name="Number_of_floors" id="floors_2">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="floors_2">2</label>
                            <input class="hidden radios" type="radio" name="Number_of_floors" id="floors_3">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="floors_3">3</label>
                            <input class="hidden radios" type="radio" name="Number_of_floors" id="floors_4">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="floors_4">4</label>
                            <input class="hidden radios" type="radio" name="Number_of_floors" id="floors_5" checked="">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="floors_5">+5</label>
                        </div>
                    </div>
                    <div class="mt-6">
                        <p class="text-gray-500 text-lg lg:text-xl font-light"><span><i class="fa-thin fa-chevron-left"></i></span> نوع سند</p>
                        <div class="h-12 w-full rounded-25 border-[1px] border-gray-400 overflow-hidden bg-white px-4 mt-3">
                            <select dir="ltr" name="" id="" class="w-full h-full outline-none text-gray-500 font-light text-right pr-3">
                                <option value="شش دانگ">شش دانگ</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6">
                        <p class="text-lg lg:text-xl text-gray-500 text-lg lg:text-xl font-light"><span class="text-lg lg:text-xl"><i class="fa-thin fa-chevron-left"></i></span> تعداد اتاق</p>
                        <div dir="ltr" class="px-[5px] mt-4 border-[1px] border-gray-400 rounded-25 h-12 flex items-center justify-between overflow-hidden">
                            <input class="hidden radios" type="radio" name="Number_of_room" id="room_1">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="room_1">1</label>
                            <input class="hidden radios" type="radio" name="Number_of_room" id="room_2">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="room_2">2</label>
                            <input class="hidden radios" type="radio" name="Number_of_room" id="room_3">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="room_3">3</label>
                            <input class="hidden radios" type="radio" name="Number_of_room" id="room_4">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="room_4">4</label>
                            <input class="hidden radios" type="radio" name="Number_of_room" id="room_5" checked="">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="room_5">+5</label>
                        </div>
                    </div>
                    <div class="mt-6">
                        <p class="text-gray-500 text-lg lg:text-xl font-light"><span><i class="fa-thin fa-chevron-left"></i></span> سال ساخت</p>
                        <div class="h-12 w-full rounded-25 border-[1px] border-gray-400 overflow-hidden bg-white px-4 mt-3">
                            <select dir="ltr" name="" id="" class="w-full h-full outline-none text-gray-500 font-light text-right pr-3">
                                <option value="انتخاب سال ساخت">انتخاب سال ساخت</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-11">
                        <input class="text-white text-lg lg:text-xl font-medium w-full h-12 rounded-25 bg-blue-500 flex items-center justify-center cursor-pointer" type="button" value="اعمال فیلتر">
                    </div>
                </div>
            </div>
        </div>
    `)

    $("#js_open_another_filter").click(function () {
        $("#js_another_filter_content").show("fast");
        $("#js_overlay").fadeIn("500");
    });
    overlay_controll("#js_another_filter_content");
}

// -----------------------------
// ----- mobile mod popups -----
// -----------------------------

// فعال و غیر فعال کردن چکباکس های در حالت موبایل
function mobile_active_and_deactive_checkbox(input_hidden,open_btn,class_input) {
    var checkbox =$(input_hidden).val();
    var active = $(input_hidden).val().split(",");
    let count = $(class_input);
    for (let i = 0; i < count.length; i++) {
        for (let j = 0; j < active.length; j++) {
            if ($(count[i]).next()[0].innerText == active[j]) {
                $(count[i]).prop("checked", true);
            }
        }
    }
    $(class_input).click(function (e) {
        if($(this).is(':checked')){
            checkbox += $(this).next()[0].innerText + ",";
        }else{
            checkbox = checkbox.replace($(this).next()[0].innerText + "," , "");
        }
        $(input_hidden).val(checkbox);
        active = $(input_hidden).val().split(",");

        if (active.length-1 != 0) {
            $(open_btn+" "+"#counter")[0].innerText = active.length-1 + "نوع";
        }else{
            $(open_btn+" "+"#counter")[0].innerText = 'انتخاب';
        }
    });
}
// دارپ دان برای حالت گوشی
function insert_data_popup_dropdown(title, body, custom = " ", input,id=" ") {
    $("#js_popup_title_dropdown").html(title);
    $("#js_popup_custom_dropdown").html(custom);
    var input_element = input;
    var list = "";
    switch (input_element) {
        case 'button':
            body.forEach(element => {
                list += "<li class='text-center'><button class='text-lg lg:text-xl text-gray-500 font-light'>" +
                    element + "</button></li>"
            });
            break;
        case 'checkbox':
            let count = 0;
            body.forEach(element => {
                count++;
                list += `<li class='text-center'>
                        <input type='checkbox' class='text-lg lg:text-xl text-gray-500 font-light ${id}' id="${id}${count}">
                        <label class="" for="${id}${count}">${element}</label>
                    </li>`;
            });
            break;
    }
    $("#js_popup_body_dropdown").html(list);
}
//دراپ دان نوع ملک
function mobile_insert_data_popup_filter_Property_Type(){
    $('#js_mobile_open_Property_Type').click(function (e) {
        $("#js_popup_dropdown").css("height", "65%");
        $("#js_blure_overlay").fadeIn("slow");
        insert_data_popup_dropdown('انتخاب محله ها', Array('همه', 'آپارتمان', 'ویلایی', 'زمین', 'مغازه',
            'تجاری'),'', 'checkbox','js_mobile_Property_Type_');
        mobile_active_and_deactive_checkbox('#mobile_property_type','#js_mobile_open_Property_Type','.js_mobile_Property_Type_');
    });
}
//دراپ دان نوع سند
function mobile_insert_data_popup_type_of_Document() {
    $("#js_mobile_type_of_Document").click(function (e) {
        $("#js_popup_dropdown").css("height", "65%");
        $("#js_blure_overlay").fadeIn("slow");
        insert_data_popup_dropdown("نوع سند", Array("شش دانگ", "سه دانگ"), '', 'checkbox','js_mobile_type_of_Document_');
        mobile_active_and_deactive_checkbox('#mobile_type_of_Document','#js_mobile_type_of_Document','.js_mobile_type_of_Document_');
    });
}
// دراپ دان نوع کاربری
function mobile_insert_data_popup_Account_Type() {
    $("#js_mobile_Account_Type").click(function (e) {
        $("#js_popup_dropdown").css("height", "65%");
        $("#js_blure_overlay").fadeIn("slow");
        insert_data_popup_dropdown("نوع کاربری", Array("نوع کاربری 1", "نوع کاربری 2"), '', 'checkbox','js_mobile_type_of_Document_');
        mobile_active_and_deactive_checkbox('#mobile_Account_Type','#js_mobile_Account_Type','.js_mobile_type_of_Document_');
    });
}
// دراپ دان سال ساخت
function mobile_insert_data_popup_construction() {
    $("#js_mobile_Year_construction").click(function (e) {
        $("#js_popup_dropdown").css("height", "65%");
        $("#js_blure_overlay").fadeIn("slow");
        insert_data_popup_dropdown("سال ساخت", Array("1378", "1379",'1380','1381','1382'), '', 'checkbox','js_mobile_Year_construction_');
        mobile_active_and_deactive_checkbox('#mobile_Year_construction','#js_mobile_Year_construction','.js_mobile_Year_construction_');
    });
}
// دراپ دان موقعیت جغرافیایی
function mobile_insert_data_popup_geographical_direction(){
    $("#js_mobile_geographical_direction").click(function (e) {
        $("#js_popup_dropdown").css("height", "65%");
        $("#js_blure_overlay").fadeIn("slow");
        insert_data_popup_dropdown("جهت جغرافیایی", Array("شمال", "جنوب",'شرق','غرب'), '', 'checkbox','js_mobile_geographical_direction_');
        mobile_active_and_deactive_checkbox('#mobile_geographical_direction','#js_mobile_geographical_direction','.js_mobile_geographical_direction_');
    });
}
// دراپ دان آگهی دهنده
function mobile_insert_data_popup_mobile_Advertiser() {
    $("#js_mobile_Advertiser").click(function (e) {
        $("#js_popup_dropdown").css("height", "65%");
        $("#js_blure_overlay").fadeIn("slow");
        insert_data_popup_dropdown("آگهی دهنده", Array("کاشناس", "فروشنده"), '', 'checkbox','js_mobile_Advertiser_');
        mobile_active_and_deactive_checkbox('#mobile_Advertiser','#js_mobile_Advertiser','.js_mobile_Advertiser_');
    });
}

//اکشن اکاردئون بخش فیلتر ها
$(".js_accordion_btn").click(function (e) {
    var slide = $(this).next();
    $(slide).slideToggle();
    $(".js_accordion_content").not($(slide)).slideUp();
});

$("#js_mobile_Select_location_btn").click(function (e) {
    $("#js_mobile_Select_location").slideDown('slow');
    $("#js_mobile_filters_menu").css('overflow','hidden');
});
$("#js_mobile_close_Select_location").click(function (e) {
    $("#js_mobile_Select_location").slideUp('slow');
    $("#js_mobile_filters_menu").css('overflow','auto');
});

$("#js_open_another_filter").click(function (e) {
    $("#js_mobile_filters_menu").slideDown();
});
$("#js_mobile_close_filter").click(function (e) {
    $("#js_mobile_filters_menu").slideUp();
});

// دارپ دان از پایین به بالا در حالت موبایل
$("#js_open_popup_dropdown").click(function (e) {
    if (window.matchMedia('(max-width: 1024px)').matches) {
        $("#js_popup_dropdown").css("height", "65%");
        $("#js_blure_overlay").fadeIn("slow");
        insert_data_popup_dropdown("مرتب سازی بر اساس:", Array("جدیدترین", "قدیمی ترین", "گرانترین",
            "ارزانترین"), '', 'button');
    }
});

$("#js_blure_overlay").click(function (e) {
    if (window.matchMedia('(max-width: 1024px)').matches) {
        $("#js_blure_overlay").fadeOut("slow");
        $("#js_popup_dropdown").css("height", "0");
    }
});

// -----------------------
// -----------------------
// -----------------------


// فانکشن های چند انتخابی در حالت فیلتر
function select_tag() { // انتخاب و عدم انتخاب چکباکس ها واضافه شدن و حذف تگ به بالای صفحه
    $(".js_city_btn").click(function (e) {
        var tag_name = $(this).parent()[0].innerText
        var tag = $(this).parent();
        if ($(this).is(':checked')) {
            $("#js_city_tag").append(`
                <li class="flex items-center justify-between h-12 px-3 bg-white border-[1px] border-gray-400 rounded-25 ml-4">
                    <span class="text-base text-red-200 pl-2 cursor-pointer" onclick="insert_data_popup_city_remove_checkbox(this)"><i class="fa-thin fa-xmark"></i></span>
                    <p class="text-base text-gray-500 font-light w-max">${tag_name}</p>
                </li>
            `);
        } else {
            var remove_tag = $("#js_city_tag").children();
            for (let i = 0; i < remove_tag.length; i++) {
                if (remove_tag[i].innerText == tag_name) {
                    $(remove_tag[i]).remove();
                }
            }
        }
    });
}

function insert_data_popup_city_remove_checkbox(text) { //بعد از حذف تگ انتخاب چکباکس ها غیرفعال میشود
    var tag_remove = $(text).parent()[0].innerText;
    var checkbox_name = $(".js_city_btn").parent();
    for (let i = 0; i < checkbox_name.length; i++) {
        if (tag_remove == checkbox_name[i].innerText) {
            $(text).parent()[0].remove();
            $(checkbox_name[i]).find("input").prop('checked', false);
        }
    }
}

function insert_data_popup_city() {
    // get all items city
    var city = $(".js_city_btn").parent(); // گرفتن تمام چک باکس ها
    var city_array = [];
    for (let i = 0; i < city.length; i++) { // تبدیل متن چکباکس ها به آرایه
        city_array.push($(city[i]).find("label")[0].innerText);
    }

    $("#js_city_search").keyup(function (e) { // جستجو در آرایه و نمایش چکباکس
        if ($(this).val().length >= 3) {
            let result_lenghth = [];
            for (let i = 0; i < city_array.length; i++) {
                let result = city_array[i].match($(this).val());
                if (result != null) {
                    $("#js_city_search_items").children().remove();
                    result_lenghth.push(result);
                }
            }
            for (let i = 0; i < result_lenghth.length; i++) {
                var text = result_lenghth[i].input
                $("#js_city_search_items").append(`
                    <li class="relative mb-5">
                        <input class="hidden css_city js_city_btn" type="checkbox" name="city" id="city_${i}">
                        <label class="before:content-[' '] before:w-[27px] before:h-[27px] before:bg-white before:border-[1px] border-gray-400 before:absolute before:top-[-4px] before:right-0 before:rounded-25 mr-9 css_city_label" for="city_${i}">${text}</label>
                    </li>
                `);
            }
            select_tag();
        } else {
            $("#js_city_search_items").html(city);
            select_tag();
        }
    });
}


// -------------------
// ----- sliders -----
// -------------------
// slider tags
if ($('.js_filter_search')[0]) {
    var swiper = new Swiper(".js_filter_search", {
        slidesPerView: "auto",
        spaceBetween: 16,
        freeMode: true,
    });
}




// منوی فیلتر ها در حالت دسکتاپ اصلی
function desktop_main_filter_menu() {
    $("#filter_content").append(`
        <div class="lg:z-10 lg:absolute lg:top-[60px] lg:right-0 w-full js_multiple_radio_selection_1">
            <div class="lg:hidden lg:bg-[#F9F9F9] lg:rounded-[23px] lg:border-[1px] lg:border-gray-400 lg:p-[22px] lg:w-full lg:max-w-[397px]" id="js_Buy_And_Sell">
                <div class="border-[1px] border-gray-400 rounded-25 h-[59px] flex items-center justify-between overflow-hidden px-[5px] js_inner">

                </div>
            </div>
        </div>
        <div class="lg:z-10 lg:absolute lg:top-[60px] lg:right-0 hidden z-30 top-[60px] right-[130px] rounded-25 bg-[#F9F9F9] border-[1px] border-gray-400 px-4 pb-4 pt-2 max-w-[288px] js_multiple_checkbox_selection_1">
            <ul class="flex flex-wrap items-center justify-between space-y-2 js_inner">

            </ul>
        </div>
    `);
}

// منوی فیلتر ها در حالت موبایل اصلی
function mobile_main_filter_menu() {
    $("#filter_content").append(`
        <div class="hidden p-5 h-screen bg-[#f8f8f8] fixed top-0 right-0 left-0 w-full z-10 overflow-auto" id="js_mobile_filters_menu">
            <div class="flex items-center justify-between">
                <p class="text-gray-500 text-lg lg:text-2xl font-light" id="js_mobile_close_filter">
                    <span class="text-gray-400 relative top-2"><i class="fa-thin fa-arrow-right text-[32px]"></i></span>
                    <span>فیلترها</span></p>
                <p class="text-gray-500 text-lg lg:text-xl font-light">حذف فیلترها</p>
            </div>
            <div>
                <div class="mt-6 pb-[14px] border-b-[1px] border-gray-200" id="js_mobile_popup_filter_Buy_And_Sell">
                    <p class="text-gray-500 text-lg lg:text-[22px] font-light pb-4 js_accordion_btn">نوع آگهی</p>
                    <div class="js_multiple_radio_selection_1">
                        <div class="lg:hidden lg:bg-[#F9F9F9] lg:rounded-[23px] lg:border-[1px] lg:border-gray-400 lg:p-[22px] lg:w-full lg:max-w-[397px]" id="js_Buy_And_Sell">
                            <div class="border-[1px] border-gray-400 rounded-25 h-[59px] flex items-center justify-between overflow-hidden px-[5px] js_inner">

                            </div>
                        </div>
                    </div>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-start js_accordion_btn">
                        <span class="text-lg lg:text-2xl ml-3 relative top-1"><i class="fa-thin fa-angle-down"></i></span>
                        <span>بر اساس امکانات</span>
                    </h3>
                    <div id="another_filter_1">

                    </div>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-between js_accordion_btn" id="js_mobile_open_Property_Type">انتخاب نوع ملک</h3>
                    <div class="js_multiple_checkbox_selection_1">
                        <ul class="grid grid-cols-3 md:grid-cols-6 gap-3 pb-4 js_inner">

                        </ul>
                    </div>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-start js_accordion_btn">
                        <span class="text-lg lg:text-2xl ml-3 relative top-1"><i class="fa-thin fa-angle-down"></i></span>
                        <span>قیمت</span>
                    </h3>
                    <div class="hidden js_accordion_content pb-[18px]" id="another_filter_2">

                    </div>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-start js_accordion_btn">
                        <span class="text-lg lg:text-2xl ml-3 relative top-1"><i class="fa-thin fa-angle-down"></i></span>
                        <span>متراژ</span>
                    </h3>
                    <div class="hidden js_accordion_content pb-[18px]" id="another_filter_3">

                    </div>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-start" id="js_mobile_Select_location_btn">
                        <span>انتخاب محله ها</span>
                    </h3>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <div class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-between" id="js_mobile_Select_location_btn">
                        <p>قابلیت معاوضه</p>
                        <div class="h-8 rounded-full relative w-[61px] switch">
                            <input class="absolute top-0 left-0 h-full w-full opacity-0 cursor-pointer" type="checkbox" name="switch">
                            <div class="before:content-[''] before:block before:absolute before:rounded-full before:pointer-events-none before:duration-500 before:top-0 before:min-h-full before:min-w-full before:bg-blue-500 before:delay-200 after:content-[''] after:block after:absolute after:rounded-full after:pointer-events-none after:duration-500 after:top-[2px] after:left-[2px] after:bg-white after:min-h-[86%] after:aspect-square"></div>
                        </div>
                    </div>

                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-start js_accordion_btn">
                        <span class="text-lg lg:text-2xl ml-3 relative top-1"><i class="fa-thin fa-angle-down"></i></span>
                        <span>تعداد طبقات</span>
                    </h3>
                    <div class="hidden js_accordion_content pb-[18px]">
                        <div dir="ltr" class="px-[5px] border-[1px] border-gray-400 rounded-25 h-12 flex items-center justify-between overflow-hidden">
                            <input class="hidden radios" type="radio" name="Number_of_floors" id="floors_1">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="floors_1">1</label>
                            <input class="hidden radios" type="radio" name="Number_of_floors" id="floors_2">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="floors_2">2</label>
                            <input class="hidden radios" type="radio" name="Number_of_floors" id="floors_3">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="floors_3">3</label>
                            <input class="hidden radios" type="radio" name="Number_of_floors" id="floors_4">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="floors_4">4</label>
                            <input class="hidden radios" type="radio" name="Number_of_floors" id="floors_5" checked="">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="floors_5">+5</label>
                        </div>
                    </div>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-start js_accordion_btn">
                        <span class="text-lg lg:text-2xl ml-3 relative top-1"><i class="fa-thin fa-angle-down"></i></span>
                        <span>تعداد اتاق</span>
                    </h3>
                    <div class="hidden js_accordion_content pb-[18px]">
                        <div dir="ltr" class="px-[5px] border-[1px] border-gray-400 rounded-25 h-12 flex items-center justify-between overflow-hidden">
                            <input class="hidden radios" type="radio" name="Number_of_room" id="room_1">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="room_1">1</label>
                            <input class="hidden radios" type="radio" name="Number_of_room" id="room_2">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="room_2">2</label>
                            <input class="hidden radios" type="radio" name="Number_of_room" id="room_3">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="room_3">3</label>
                            <input class="hidden radios" type="radio" name="Number_of_room" id="room_4">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="room_4">4</label>
                            <input class="hidden radios" type="radio" name="Number_of_room" id="room_5" checked="">
                            <label class="h-[38px] w-1/5 h-full flex items-center justify-center text-lg lg:text-xl text-gray-500 font-light rounded-25 cursor-pointer css_label-radio" for="room_5">+5</label>
                        </div>
                    </div>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-between" id="js_mobile_type_of_Document">
                        <span>نوع سند</span>
                        <span id="counter">انتخاب</span>
                        <input type="hidden" name="mobile_type_of_Document" id="mobile_type_of_Document" value="">
                    </h3>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-between" id="js_mobile_Account_Type">
                        <span>نوع کاربری</span>
                        <span id="counter">انتخاب</span>
                        <input type="hidden" name="mobile_Account_Type" id="mobile_Account_Type" value="">
                    </h3>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-between" id="js_mobile_Year_construction">
                        <span>سال ساخت</span>
                        <span id="counter">انتخاب</span>
                        <input type="hidden" name="mobile_Year_construction" id="mobile_Year_construction" value="">
                    </h3>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-between" id="js_mobile_geographical_direction">
                        <span>جهت جغرافیایی</span>
                        <span id="counter">2 جهت</span>
                        <input type="hidden" name="mobile_geographical_direction" id="mobile_geographical_direction">
                    </h3>
                </div>
                <div class="border-b-[1px] border-gray-200">
                    <div class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-between" id="js_mobile_Photographer">
                        <p>عکس دار</p>
                        <div class="h-8 rounded-full relative w-[61px] switch">
                            <input class="absolute top-0 left-0 h-full w-full opacity-0 cursor-pointer" type="checkbox" name="switch">
                            <div class="before:content-[''] before:block before:absolute before:rounded-full before:pointer-events-none before:duration-500 before:top-0 before:min-h-full before:min-w-full before:bg-blue-500 before:delay-200 after:content-[''] after:block after:absolute after:rounded-full after:pointer-events-none after:duration-500 after:top-[2px] after:left-[2px] after:bg-white after:min-h-[86%] after:aspect-square"></div>
                        </div>
                    </div>

                </div>
                <div class="border-b-[1px] border-gray-200">
                    <h3 class="text-gray-500 text-lg lg:text-[22px] font-light h-[75px] flex items-center justify-between" id="js_mobile_Advertiser">
                        <span>آگهی دهنده</span>
                        <span id="counter">کارشناس</span>
                        <input type="hidden" name="mobile_Advertiser" id="mobile_Advertiser">
                    </h3>
                </div>
                <div class="mt-4">
                    <button class="flex items-center justify-center bg-blue-500 rounded-25 px-4 w-full h-[60px] relative">
                        <span class="text-base text-white font-light absolute right-4">10 محله</span>
                        <span class="text-lg lg:text-2xl font-medium text-white ">اعمال فیلتر</span>
                    </button>
                </div>
            </div>
            <!-- ------------filter---------------- -->
            <div class="hidden fixed top-0 right-0 w-full h-screen z-30 bg-[#f9f9f9] p-5" id="js_mobile_Select_location">
                <p class="text-gray-500 text-lg lg:text-2xl font-light" id="js_mobile_close_Select_location">
                    <span class="text-gray-400 relative top-1"><i class="fa-thin fa-arrow-right text-[32px]"></i></span>
                    <span>فیلترها</span>
                </p>
            </div>
        </div>
    `);
}

