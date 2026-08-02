<?php

namespace App\Helpers;
use App\Models\QuranText;
use App\Models\QuranSoreh;
use App\Models\QuranBaseTable;
use App\Models\QuranTranslatMain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class QuranHelper
{
    public static function sorehListArray()
    {
        return [
            'فاتحه', 'بقره', 'آل عمران', 'نساء', 'مائده', 'انعام', 'اعراف', 'انفال', 'توبه', 'یونس',
            'هود', 'یوسف', 'رعد', 'ابراهيم', 'حجر', 'نحل', 'اسراء', 'کهف', 'مريم', 'طه', 'انبياء', 'حج',
            'مومنون', 'نور', 'فرقان', 'شعراء', 'نمل', 'قصص', 'عنکبوت', 'روم', 'لقمان', 'سجده', 'احزاب',
            'سبأ', 'فاطر', 'يس', 'صافات', 'ص', 'زمر', 'غافر', 'فصلت', 'شوری', 'زخرف', 'دخان', 'جاثیه',
            'احقاف', 'محمد', 'فتح', 'حجرات', 'ق', 'ذاريات', 'طور', 'نجم', 'قمر', 'رحمن', 'واقعه', 'حديد',
            'مجادله', 'حشر', 'ممتحنه', 'صف', 'جمعة', 'منافقون', 'تغابن', 'طلاق', 'تحريم', 'ملک', 'قلم',
            'حاقه', 'معارج', 'نوح', 'جن', 'مزمل', 'مدثر', 'قيامت', 'انسان', 'مرسلات', 'نبأ', 'نازعات',
            'عبس', 'تکوير', 'انفطار', 'مطففين', 'انشقاق', 'بروج', 'طارق', 'اعلى', 'غاشيه', 'فجر', 'بلد',
            'شمس', 'ليل', 'ضحي', 'شرح', 'تين', 'علق', 'قدر', 'بينة', 'زلزله', 'عاديات', 'قارعه', 'تكاثر',
            'عصر', 'همزه', 'فيل', 'قريش', 'ماعون', 'کوثر', 'کافرون', 'نصر', 'مسد', 'اخلاص', 'فلق', 'ناس'
        ];
    }

    public static function getTranslate()
    {
        $cacheKey = 'getTranslate';
        $cacheTTL = now()->addDays(365); // کش برای 10 روز
        return  Cache::remember($cacheKey, $cacheTTL, function () {
            return QuranTranslatMain::where('id_tr_q', 1)->value('text_translate_q');
        });
    }

    public static function DetailSoreh($id_soreh, $index)
    {
        $cacheKey = 'DetailSoreh'.$id_soreh.'-'.$index;
        $cacheTTL = now()->addDays(365); // کش برای 10 روز
        return  Cache::remember($cacheKey, $cacheTTL, function () use ($id_soreh, $index){
            // گرفتن اطلاعات سوره از پایگاه داده
            $soreh = QuranSoreh::where('id_srh', $id_soreh)->first();

            // بررسی اینکه سوره پیدا شده باشد
            if (!$soreh) {
                return null;
            }

            // انجام عملیات مختلف بر اساس index
            switch ($index) {
                case 1:
                    echo ' <a href="/quran?soreh=' . $soreh->id_srh . '&ayeh=1" title="القرآن الکریم -  سوره ' . $soreh->title_soreh_srh . ' - ترتيبها  ' . $soreh->id_srh . ' -  آياتها ' . $soreh->num_ayah_srh . ' - ' . ($soreh->case_soreh_srh == 1 ? 'مكيه' : 'مدينه') . '"><section class="sorehTitle2 w100p relative dtable cb text-center" >' . ' ترتيبها ' . '&nbsp;' . $soreh->id_srh . '&nbsp;&nbsp;&nbsp;' . ' سوره ' . '&nbsp;' . $soreh->title_soreh_srh . 'آياتها ' . '&nbsp;' . $soreh->num_ayah_srh . '&nbsp;&nbsp;&nbsp;' . ($soreh->case_soreh_srh == 1 ? 'مكيه' : 'مدينه') . ' </section></a>';
                    break;

                case 2:
                    echo ' <a href="/quran?soreh=' . $soreh->id_srh . '&ayeh=1" title="القرآن الکریم -  سوره ' . $soreh->title_soreh_srh . ' - ترتيبها  ' . $soreh->id_srh . ' -  آياتها ' . $soreh->num_ayah_srh . ' - ' . ($soreh->case_soreh_srh == 1 ? 'مكيه' : 'مدينه') . '"><section class="sorehTitle2 w100p relative dtable cb text-center" >' . ' ترتيبها ' . '&nbsp;' . $soreh->id_srh . '&nbsp;&nbsp;&nbsp;' . ' سوره ' . '&nbsp;' . $soreh->title_soreh_srh . '&nbsp;&nbsp;&nbsp;' . 'آياتها ' . '&nbsp;' . $soreh->num_ayah_srh . '&nbsp;&nbsp;&nbsp;' . ($soreh->case_soreh_srh == 1 ? 'مكيه' : 'مدينه') . ' </section></a>';
                    break;

                case 3:
                    $name_soreh_farsi = QuranBaseTable::where('top_id_base_t', $soreh->id_srh)
                        ->where('top_base_id_base_t', 6)
                        ->value('title_base_t');

                    echo ' <a href="/quran?soreh=' . $soreh->id_srh . '&ayeh=1" title="قرآن کریم - سوره ' . $name_soreh_farsi . ' -  تعداد آيات ' . $soreh->num_ayah_srh . ' - ' . ($soreh->case_soreh_srh == 1 ? 'مکي' : 'مدني') . '"><section class="sorehTitle2 w100p relative dtable cb text-center" >' . ' شماره سوره ' . '&nbsp;' . $soreh->id_srh . '&nbsp;&nbsp;&nbsp;' . '   نام سوره   ' . '&nbsp;' . $name_soreh_farsi . '&nbsp;&nbsp;&nbsp;' . 'تعداد آيات ' . '&nbsp;' . $soreh->num_ayah_srh . '&nbsp;&nbsp;&nbsp;' . ($soreh->case_soreh_srh == 1 ? 'مکي' : 'مدني') . ' </section></a>';
                    break;

                default:
                    return null;
            }

            return $soreh;
        });
    }
}
