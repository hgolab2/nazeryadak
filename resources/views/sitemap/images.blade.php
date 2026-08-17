<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach($entries as $entry)
    <url>
        <loc>{{ seo_xml_url($entry['loc']) }}</loc>
@foreach($entry['images'] as $image)
        <image:image>
            <image:loc>{{ seo_xml_url($image['loc']) }}</image:loc>
@if(!empty($image['title']))
            <image:title>{{ $image['title'] }}</image:title>
@endif
        </image:image>
@endforeach
    </url>
@endforeach
</urlset>
