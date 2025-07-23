<div class="footer" style="clear: both; margin-top: 10px; text-align: center; width: 100%;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
        <tr>
            <td class="content-block" style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; color: #999999; font-size: 12px; text-align: center;" valign="top" align="center">
                <span class="apple-link" style="color: #999999; font-size: 12px; text-align: center;">{{ config('app.name') }}</span>
                <br>
                @php
                    $scheduleTimezone = app(\Padmission\DataLens\Services\TimezoneResolver::class)->getEffectiveTimezone($schedule);
                    $currentTime = now('UTC')->setTimezone($scheduleTimezone);
                @endphp
                This is an automated report sent on {{ $currentTime->format('F j, Y \a\t g:i A') }} ({{ $scheduleTimezone }}).
                <br>
                <br>
                <span style="color: #999999; font-size: 11px; text-align: center;">Report ID: {{ $schedule->id }}</span>
            </td>
        </tr>
    </table>
</div> 