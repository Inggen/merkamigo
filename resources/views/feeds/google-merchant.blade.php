<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
        <title>{{ config('app.name') }} Products</title>
        <link>{{ route('home') }}</link>
        <description>Feed publico de productos publicados en {{ config('app.name') }}.</description>
        @foreach ($items as $item)
            <item>
                <g:id>{{ $item['id'] }}</g:id>
                <title><![CDATA[{{ $item['title'] }}]]></title>
                <description><![CDATA[{{ $item['description'] }}]]></description>
                <link>{{ $item['link'] }}</link>
                <g:image_link>{{ $item['image_link'] }}</g:image_link>
                @foreach ($item['additional_image_links'] as $additionalImage)
                    <g:additional_image_link>{{ $additionalImage }}</g:additional_image_link>
                @endforeach
                <g:availability>{{ $item['availability'] }}</g:availability>
                <g:price>{{ $item['price'] }}</g:price>
                @if ($item['sale_price'])
                    <g:sale_price>{{ $item['sale_price'] }}</g:sale_price>
                @endif
                @if ($item['sale_price_effective_date'])
                    <g:sale_price_effective_date>{{ $item['sale_price_effective_date'] }}</g:sale_price_effective_date>
                @endif
                <g:brand><![CDATA[{{ $item['brand'] }}]]></g:brand>
                <g:identifier_exists>{{ filled($item['gtin']) || filled($item['mpn']) ? 'yes' : 'no' }}</g:identifier_exists>
                @if ($item['gtin'])
                    <g:gtin>{{ $item['gtin'] }}</g:gtin>
                @endif
                @if ($item['mpn'])
                    <g:mpn>{{ $item['mpn'] }}</g:mpn>
                @endif
                @foreach ($item['excluded_destinations'] as $destination)
                    <g:excluded_destination>{{ $destination === 'LOCAL_INVENTORY_ADS' ? 'Local_inventory_ads' : 'Free_local_listings' }}</g:excluded_destination>
                @endforeach
                <g:condition>{{ $item['condition'] }}</g:condition>
                @if ($item['product_type'])
                    <g:product_type><![CDATA[{{ $item['product_type'] }}]]></g:product_type>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
