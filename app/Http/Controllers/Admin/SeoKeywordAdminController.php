<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\SeoGscSync;
use App\Console\Commands\SeoKeywordsCheck;
use App\Http\Controllers\Controller;
use App\Models\GscQuery;
use App\Models\SearchTerm;
use App\Models\SeoKeyword;
use App\Support\KeywordMatcher;
use App\Support\SearchConsole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

/**
 * کلیدواژه‌های هدف سئو و دو منبعِ کشفشان: جستجوی داخلی سایت و سرچ کنسول.
 *
 * سه صفحه:
 *   /admin/seo/keywords   عبارت‌های هدف، صفحه‌ی هدف هرکدام، نتیجه‌ی ممیزی و آمار گوگل
 *   /admin/seo/searches   آنچه کاربرها داخل سایت جستجو کرده‌اند (پرتکرار / بی‌نتیجه)
 *   /admin/seo/gsc        آنچه گوگل برای سایت نشان می‌دهد (از API یا CSV)
 *
 * از هر دو منبع با یک کلیک عبارت به فهرست هدف اضافه می‌شود و KeywordMatcher
 * صفحه‌ی مناسبش را پیشنهاد می‌دهد.
 */
class SeoKeywordAdminController extends Controller
{
    private const PER_PAGE = 40;

    /** سقف ممیزی هم‌زمان از پنل؛ هر صفحه یک درخواست HTTP است. */
    private const CHECK_ALL_LIMIT = 60;

    private function guard()
    {
        if (! Auth::user()) {
            return redirect('/loginAdmin');
        }
        access(83);

        return null;
    }

    /* ------------------------------------------------------- کلیدواژه‌های هدف */

    public function index(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $sort = (string) $request->input('sort', 'priority');
        $query = SeoKeyword::query();

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('keyword', 'like', "%{$search}%")->orWhere('target_url', 'like', "%{$search}%");
            });
        }

        match ($sort) {
            'impressions' => $query->orderByDesc('gsc_impressions'),
            'clicks'      => $query->orderByDesc('gsc_clicks'),
            'position'    => $query->orderByRaw('gsc_position IS NULL, gsc_position ASC'),
            'problems'    => $query->orderByRaw('checked_at IS NULL DESC, (COALESCE(in_title,0) + COALESCE(in_h1,0) + COALESCE(in_description,0)) ASC'),
            default       => $query->orderBy('priority')->orderByDesc('gsc_impressions'),
        };

        $model = $query->orderBy('id')->paginate(self::PER_PAGE)->withQueryString();

        $stats = [
            'total'     => SeoKeyword::count(),
            'no_target' => SeoKeyword::whereNull('target_url')->orWhere('target_url', '')->count(),
            'unchecked' => SeoKeyword::whereNull('checked_at')->whereNotNull('target_url')->count(),
            'weak'      => SeoKeyword::whereNotNull('checked_at')->where(function ($q) {
                $q->where('in_title', 0)->orWhere('in_h1', 0)->orWhere('http_status', '!=', 200)->orWhere('is_indexable', 0);
            })->count(),
            'top10'     => SeoKeyword::whereNotNull('gsc_position')->where('gsc_position', '<=', 10)->count(),
            'striking'  => SeoKeyword::whereNotNull('gsc_position')->whereBetween('gsc_position', [10.01, 20])->count(),
        ];

        // پیش‌پرکردن فرم از صفحات کشف (?keyword=...&target=...)
        $prefill = [
            'keyword' => (string) $request->input('keyword', ''),
            'target'  => (string) $request->input('target', ''),
        ];
        if ($prefill['keyword'] !== '' && $prefill['target'] === '') {
            $prefill['target'] = KeywordMatcher::match($prefill['keyword'])['url'] ?? '';
        }

        return view('seo.admin.keywords', [
            'model'   => $model,
            'sort'    => $sort,
            'stats'   => $stats,
            'prefill' => $prefill,
        ]);
    }

    public function store(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $data = $request->validate([
            'keyword'    => 'required|string|min:2|max:160',
            'target_url' => 'nullable|string|max:500',
            'priority'   => 'required|integer|in:1,2,3',
            'note'       => 'nullable|string|max:2000',
        ]);

        $target = $this->normalizeTarget($data['target_url'] ?? null);

        $keyword = SeoKeyword::findOrCreateFromKeyword($data['keyword'], $target, (int) $data['priority']);

        if (! $keyword->wasRecentlyCreated) {
            return back()->withErrors(['keyword' => 'این عبارت از قبل در فهرست هست: «' . $keyword->keyword . '»']);
        }

        $keyword->note = $data['note'] ?? null;
        $keyword->save();

        $this->applyGscStats($keyword);

        if ($target) {
            SeoKeywordsCheck::audit($keyword);
        }

        return redirect('/admin/seo/keywords')->with('success', 'کلیدواژه اضافه شد' . ($target ? ' و صفحه‌اش بررسی شد.' : '. صفحه‌ی هدف را تعیین کنید.'));
    }

    public function update(Request $request, $id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $keyword = SeoKeyword::findOrFail($id);

        $data = $request->validate([
            'target_url' => 'nullable|string|max:500',
            'priority'   => 'required|integer|in:1,2,3',
            'note'       => 'nullable|string|max:2000',
        ]);

        $target = $this->normalizeTarget($data['target_url'] ?? null);
        $targetChanged = $target !== $keyword->target_url;

        $keyword->fill([
            'target_url' => $target,
            'priority'   => (int) $data['priority'],
            'note'       => $data['note'] ?? null,
        ]);

        if ($targetChanged) {
            // نتیجه‌ی ممیزیِ صفحه‌ی قبلی دیگر معنی ندارد
            $keyword->forceFill([
                'http_status' => null, 'page_title' => null, 'page_h1' => null, 'page_description' => null,
                'in_title' => null, 'in_h1' => null, 'in_description' => null, 'body_hits' => null,
                'is_indexable' => null, 'checked_at' => null,
            ]);
        }

        $keyword->save();

        if ($targetChanged && $target) {
            SeoKeywordsCheck::audit($keyword);
        }

        return back()->with('success', 'ذخیره شد.');
    }

    public function destroy($id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        SeoKeyword::findOrFail($id)->delete();

        return back()->with('success', 'کلیدواژه حذف شد.');
    }

    /** ممیزی یک کلیدواژه (id) یا همه‌ی آن‌هایی که صفحه‌ی هدف دارند. */
    public function check(Request $request, $id = null)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        if ($id !== null) {
            $keyword = SeoKeyword::findOrFail($id);
            if (blank($keyword->target_url)) {
                return back()->withErrors(['target_url' => 'اول صفحه‌ی هدف را تعیین کنید.']);
            }

            $result = SeoKeywordsCheck::audit($keyword);

            return back()->with('success', $result['error']
                ? 'بررسی ناموفق: ' . $result['error']
                : 'بررسی شد: HTTP ' . $result['http_status']);
        }

        // همه: قدیمی‌ترین‌ها اول، تا با چند بار کلیک همه پوشش داده شوند
        $keywords = SeoKeyword::whereNotNull('target_url')->where('target_url', '!=', '')
            ->orderByRaw('checked_at IS NULL DESC')->orderBy('checked_at')
            ->limit(self::CHECK_ALL_LIMIT)->get();

        @set_time_limit(0);
        foreach ($keywords as $keyword) {
            SeoKeywordsCheck::audit($keyword);
        }

        $remaining = max(0, SeoKeyword::whereNotNull('target_url')->where('target_url', '!=', '')->count() - $keywords->count());

        return back()->with('success', $keywords->count() . ' کلیدواژه بررسی شد.' . ($remaining ? " {$remaining} مورد دیگر مانده؛ دوباره کلیک کنید." : ''));
    }

    /**
     * فهرست اولیه از ساختار سایت (قطعه × خودرو، قطعه، خودرو).
     * صفحه‌ها بعدا با «بررسی همه» ممیزی می‌شوند؛ اینجا نه، چون ممکن است
     * صدها عبارت ساخته شود و هرکدام یک درخواست HTTP است.
     */
    public function seed()
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $before = SeoKeyword::count();
        Artisan::call('seo:keywords-seed');
        $added = SeoKeyword::count() - $before;

        return redirect('/admin/seo/keywords')->with('success', $added
            ? "{$added} عبارت از ساختار سایت اضافه شد. حالا «بررسی همه‌ی صفحات» را بزنید."
            : 'عبارت جدیدی نبود؛ همه از قبل در فهرست هستند.');
    }

    /* ---------------------------------------------------------- جستجوی داخلی */

    public function searches(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $tab = $request->input('tab') === 'empty' ? 'empty' : 'popular';

        $query = SearchTerm::query();
        if ($tab === 'empty') {
            $query->where('results_count', 0);
        } else {
            $query->where('results_count', '>', 0);
        }

        if ($search = trim((string) $request->input('q'))) {
            $query->where('display_term', 'like', "%{$search}%");
        }

        $model = $query->orderByDesc('hits')->orderByDesc('last_searched_at')
            ->paginate(self::PER_PAGE)->withQueryString();

        $tracked = SeoKeyword::whereIn('term', $model->pluck('term'))->get()->keyBy('term');

        $rows = [];
        foreach ($model as $row) {
            $rows[] = [
                'row'     => $row,
                'tracked' => $tracked[$row->term] ?? null,
                'match'   => KeywordMatcher::match($row->display_term),
            ];
        }

        $stats = [
            'popular' => SearchTerm::where('results_count', '>', 0)->count(),
            'empty'   => SearchTerm::where('results_count', 0)->count(),
            'hits'    => (int) SearchTerm::sum('hits'),
            'since'   => SearchTerm::min('created_at'),
        ];

        return view('seo.admin.searches', compact('model', 'rows', 'tab', 'stats'));
    }

    /* ------------------------------------------------------------- سرچ کنسول */

    public function gsc(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $view = (string) $request->input('view', 'striking');

        /*
        | جمع روی عبارت (نه عبارت × صفحه) — سوال مدیر «کدام عبارت مهم است»
        | است، نه «کدام صفحه». صفحه‌ی برتر جدا محاسبه می‌شود.
        */
        $query = GscQuery::query()
            ->select('term')
            ->selectRaw('MAX(query) as query')
            ->selectRaw('SUM(clicks) as clicks')
            ->selectRaw('SUM(impressions) as impressions')
            ->selectRaw('CASE WHEN SUM(impressions) > 0 THEN SUM(position * impressions) / SUM(impressions) ELSE AVG(position) END as position')
            ->selectRaw('COUNT(DISTINCT page_hash) as pages')
            ->groupBy('term');

        if ($search = trim((string) $request->input('q'))) {
            $query->where('query', 'like', "%{$search}%");
        }

        match ($view) {
            // رتبه ۱۱ تا ۲۰ با نمایش قابل توجه: با کمی کار به صفحه‌ی اول می‌رسند
            'striking' => $query->havingRaw('position BETWEEN 10 AND 20 AND impressions >= 20')->orderByDesc('impressions'),
            // نمایش زیاد، کلیک کم: title/description جذاب نیست
            'low_ctr'  => $query->havingRaw('impressions >= 50 AND position <= 10 AND clicks / impressions < 0.02')->orderByDesc('impressions'),
            // چند صفحه برای یک عبارت: رقابت داخلی
            'cannibal' => $query->havingRaw('pages >= 2 AND impressions >= 10')->orderByDesc('pages')->orderByDesc('impressions'),
            'clicks'   => $query->orderByDesc('clicks'),
            default    => $query->orderByDesc('impressions'),
        };

        $model = $query->paginate(self::PER_PAGE)->withQueryString();

        $tracked = SeoKeyword::whereIn('term', $model->pluck('term'))->get()->keyBy('term');

        $rows = [];
        foreach ($model as $row) {
            $topPage = GscQuery::where('term', $row->term)->where('page', '!=', '')
                ->orderByDesc('impressions')->value('page');

            $rows[] = [
                'row'      => $row,
                'tracked'  => $tracked[$row->term] ?? null,
                'match'    => KeywordMatcher::match($row->query),
                'top_page' => $topPage,
            ];
        }

        $stats = [
            'rows'        => GscQuery::count(),
            'terms'       => (int) GscQuery::distinct('term')->count('term'),
            'clicks'      => (int) GscQuery::sum('clicks'),
            'impressions' => (int) GscQuery::sum('impressions'),
            'synced_at'   => GscQuery::max('synced_at'),
            'period_days' => (int) (GscQuery::max('period_days') ?: 28),
            'configured'  => SearchConsole::isConfigured(),
        ];

        return view('seo.admin.gsc', compact('model', 'rows', 'view', 'stats'));
    }

    /** اجرای همگام‌سازی API از پنل. */
    public function gscSync()
    {
        if ($response = $this->guard()) {
            return $response;
        }

        if (! SearchConsole::isConfigured()) {
            return back()->withErrors(['gsc' => 'اتصال API پیکربندی نشده است. فایل CSV را آپلود کنید یا SEO_GSC_CREDENTIALS و SEO_GSC_SITE_URL را در .env تنظیم کنید.']);
        }

        @set_time_limit(0);

        try {
            $days  = (int) config('seo.search_console.days', 28);
            $rows  = SearchConsole::fetchQueries($days);
            $saved = SeoGscSync::store($rows, $days);
            SeoGscSync::applyToKeywords();
        } catch (\Throwable $e) {
            return back()->withErrors(['gsc' => 'همگام‌سازی ناموفق: ' . $e->getMessage()]);
        }

        return back()->with('success', "{$saved} ردیف از سرچ کنسول دریافت شد.");
    }

    /** آپلود CSV خروجی سرچ کنسول (Performance → Export → CSV → Queries.csv). */
    public function gscImport(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $request->validate([
            'csv'     => 'required|file|max:10240',
            'days'    => 'nullable|integer|min:1|max:480',
            'replace' => 'nullable|boolean',
        ]);

        $contents = (string) file_get_contents($request->file('csv')->getRealPath());
        $rows = SearchConsole::parseCsv($contents);

        if (! $rows) {
            return back()->withErrors(['csv' => 'ردیفی در فایل پیدا نشد. فایل باید خروجی «Queries» سرچ کنسول باشد (ستون‌ها: عبارت، کلیک، نمایش، CTR، رتبه).']);
        }

        $days = (int) ($request->input('days') ?: 28);
        $saved = SeoGscSync::store($rows, $days, (bool) $request->boolean('replace'));
        SeoGscSync::applyToKeywords();

        return back()->with('success', "{$saved} عبارت از فایل وارد شد.");
    }

    /* ---------------------------------------------------------------- کمکی */

    private function normalizeTarget(?string $target): ?string
    {
        $target = trim((string) $target);
        if ($target === '') {
            return null;
        }

        // آدرس مطلقِ خود سایت → مسیر نسبی، تا مقایسه با gsc_top_page درست باشد
        if (preg_match('#^https?://#i', $target)) {
            $target = GscQuery::relativePage($target);
        }

        return '/' . ltrim(urldecode($target), '/');
    }

    private function applyGscStats(SeoKeyword $keyword): void
    {
        $summary = GscQuery::summaryFor($keyword->term);
        if (! $summary) {
            return;
        }

        $keyword->forceFill([
            'gsc_clicks'      => $summary['clicks'],
            'gsc_impressions' => $summary['impressions'],
            'gsc_position'    => $summary['position'],
            'gsc_ctr'         => $summary['ctr'],
            'gsc_top_page'    => $summary['top_page'],
            'gsc_synced_at'   => now(),
        ])->save();
    }
}
