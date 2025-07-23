<x-mail::message>
{{-- Greeting --}}
@if (! empty($recipient->display_name))
# Hello {{ $recipient->display_name }},
@else
# Hello,
@endif

{{-- Body Content --}}
@if($body)
{!! nl2br(e($body)) !!}
@else
Please find the latest report from **{{ $schedule->customReport->name }}**.

@php
    $scheduleTimezone = app(\Padmission\DataLens\Services\TimezoneResolver::class)->getEffectiveTimezone($schedule);
    $currentTime = now('UTC')->setTimezone($scheduleTimezone);
@endphp
This report was generated on {{ $currentTime->format('F j, Y \a\t g:i A') }} ({{ $scheduleTimezone }}).
@endif

{{-- Download Button or Attachment Notice --}}
@if($downloadLink)
Please use the secure link below to download your report.

<x-mail::button :url="$downloadLink" color="primary">
Download Report
</x-mail::button>

@if($downloadLinkExpiryDate)
<x-mail::subcopy>
This download link will expire on {{ $downloadLinkExpiryDate }}.
</x-mail::subcopy>
@endif
@elseif($filePath)
The report is attached to this email.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
