<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="google-site-verification" content="kxClVC3qwiFSrTv2G1c3K5QleHgwG5KKqMAkPlwcryY" />

<title>{{ $title ?? 'DPMI - Best Paramedical Institute in Delhi NCR , India' }}</title>

<meta name="description" content="{{ $description ?? 'DPMI is a leading vocational institute offering courses in paramedical health education, hotel management, catering technology and tourism, etc.' }}">
<meta name="keywords" content="{{ $keywords ?? 'DPMI - Best Paramedical Institute in Delhi NCR , India' }}">

{{-- Favicon --}}
<link rel="shortcut icon" type="image/x-icon" href="{{ $favicon ?? asset('public/uploads/page_section_attributes/setting-6809d5f897bb2-1745475064.svg') }}">
<link href="{{ $favicon ?? asset('public/uploads/page_section_attributes/setting-6809d5f897bb2-1745475064.svg') }}" rel="icon">
<link href="{{ $apple_icon ?? asset('public/uploads/page_section_attributes/setting-6809d5ff154c6-1745475071.svg') }}" rel="apple-touch-icon">

{{-- Open Graph --}}
<meta property="og:site_name" content="{{ $og_site_name ?? 'DPMI - Best Paramedical Institute in Delhi NCR , India' }}">
<meta property="og:url" content="{{ $og_url ?? 'https://www.dpmiindia.com' }}">
<meta property="og:title" content="{{ $og_title ?? ($title ?? 'DPMI - Best Paramedical Institute in Delhi NCR , India') }}">
<meta property="og:description" content="{{ $og_description ?? ($description ?? 'DPMI is a leading vocational institute offering courses in paramedical health education, hotel management, catering technology and tourism, etc.') }}">
{{-- FIX: og:type was empty — must be "website" for homepage/general pages --}}
<meta property="og:type" content="{{ $og_type ?? 'website' }}">
{{-- FIX: removed duplicate og:image; only one tag with a valid URL --}}
@if (!empty($og_image))
<meta property="og:image" content="{{ $og_image }}">
@else
<meta property="og:image" content="{{ asset('public/uploads/page_section_attributes/setting-681322d650bbb-1746084566.svg') }}">
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@dpmi_india">
<meta name="twitter:title" content="{{ $twitter_title ?? ($title ?? 'DPMI - Best Paramedical Institute in Delhi NCR , India') }}">
<meta name="twitter:description" content="{{ $twitter_description ?? ($description ?? 'DPMI is a leading vocational institute offering courses in paramedical health education, hotel management, catering technology and tourism, etc.') }}">
{{-- FIX: added twitter:image (was missing) --}}
@if (!empty($twitter_image))
<meta name="twitter:image" content="{{ $twitter_image }}">
@elseif (!empty($og_image))
<meta name="twitter:image" content="{{ $og_image }}">
@else
<meta name="twitter:image" content="{{ asset('public/uploads/page_section_attributes/setting-681322d650bbb-1746084566.svg') }}">
@endif

<link rel="canonical" href="{{ $canonical ?? url()->current() }}">

<meta name="robots" content="{{ $robots ?? 'index,follow' }}">
<meta name="author" content="Sterco Digitex">
