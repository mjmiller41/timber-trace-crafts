<?php echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL; ?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
        <title>{{ $brand }}</title>
        <link>{{ $storeUrl }}</link>
        <description>{{ $brand }} product feed for Google Merchant Center.</description>
        @foreach($items as $item)
        <item>
            <g:id>{{ $item['id'] }}</g:id>
            <title>{{ $item['title'] }}</title>
            <description>{{ $item['description'] }}</description>
            <link>{{ $item['link'] }}</link>
            <g:image_link>{{ $item['image_link'] }}</g:image_link>
            <g:price>{{ $item['price'] }}</g:price>
            <g:availability>{{ $item['availability'] }}</g:availability>
            <g:brand>{{ $item['brand'] }}</g:brand>
            <g:mpn>{{ $item['mpn'] }}</g:mpn>
            <g:condition>{{ $item['condition'] }}</g:condition>
        </item>
        @endforeach
    </channel>
</rss>
