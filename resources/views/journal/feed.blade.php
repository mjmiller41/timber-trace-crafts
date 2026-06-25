<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>Timber Trace Crafts Journal</title>
        <link>{{ url('/journal') }}</link>
        <description>Stories, techniques, and inspiration from the Timber Trace Crafts studio.</description>
        <language>en-us</language>
        <lastBuildDate>{{ now()->toRssString() }}</lastBuildDate>
        <atom:link href="{{ route('journal.feed') }}" rel="self" type="application/rss+xml"/>

        @foreach($posts as $post)
        <item>
            <title><![CDATA[{{ $post->title }}]]></title>
            <link>{{ route('journal.show', $post->slug) }}</link>
            <guid isPermaLink="true">{{ route('journal.show', $post->slug) }}</guid>
            <pubDate>{{ \Carbon\Carbon::parse($post->published_at)->toRssString() }}</pubDate>
            @if($post->author)
            <author>{{ $post->author->email }} ({{ $post->author->name }})</author>
            @endif
            @if($post->excerpt)
            <description><![CDATA[{{ $post->excerpt }}]]></description>
            @endif
            <content:encoded><![CDATA[{!! $post->body !!}]]></content:encoded>
        </item>
        @endforeach
    </channel>
</rss>
