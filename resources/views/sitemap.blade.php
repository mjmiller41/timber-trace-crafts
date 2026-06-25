<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url>
        <loc>{{ route('home') }}</loc>
        <lastmod>{{ $products->first()?->updated_at?->toAtomString() ?? now()->toAtomString() }}</lastmod>
    </url>
    <url>
        <loc>{{ route('shop') }}</loc>
        <lastmod>{{ $products->first()?->updated_at?->toAtomString() ?? now()->toAtomString() }}</lastmod>
    </url>
    <url>
        <loc>{{ route('journal.index') }}</loc>
        <lastmod>{{ $posts->first()?->updated_at?->toAtomString() ?? now()->toAtomString() }}</lastmod>
    </url>

    @foreach($pages as $page)
    <url>
        <loc>{{ route('page.show', $page->slug) }}</loc>
        @if($page->updated_at)
        <lastmod>{{ $page->updated_at->toAtomString() }}</lastmod>
        @endif
    </url>
    @endforeach

    @foreach($products as $product)
    <url>
        <loc>{{ route('product.show', $product->slug) }}</loc>
        <lastmod>{{ $product->updated_at->toAtomString() }}</lastmod>
    </url>
    @endforeach

    @foreach($posts as $post)
    <url>
        <loc>{{ route('journal.show', $post->slug) }}</loc>
        <lastmod>{{ $post->updated_at->toAtomString() }}</lastmod>
    </url>
    @endforeach

</urlset>
