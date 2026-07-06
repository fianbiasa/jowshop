<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($funnels as $funnel)
    <url>
        <loc>{{ route('public.salespage.show', $funnel) }}</loc>
        <lastmod>{{ ($funnel->salespage->updated_at ?? $funnel->updated_at)->toAtomString() }}</lastmod>
    </url>
@endforeach
</urlset>
