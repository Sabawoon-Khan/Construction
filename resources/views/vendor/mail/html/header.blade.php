@props(['url'])
@php
    $logoUrl = \App\Models\OrgSetting::logoUrlForMail();
    $appName = \App\Models\OrgSetting::appName();
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($logoUrl)
<img src="{{ $logoUrl }}" class="logo" alt="{{ $appName }}">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
