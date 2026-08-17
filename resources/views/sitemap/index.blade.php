{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($maps as $map)
    <sitemap>
        <loc>{{ seo_xml_url($map['loc']) }}</loc>
@if(!empty($map['lastmod']))
        <lastmod>{{ $map['lastmod'] }}</lastmod>
@endif
    </sitemap>
@endforeach
</sitemapindex>
