<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
{{ Illuminate\Mail\Markdown::parse($slot) }}
<p style="margin-top: 12px;">{{ config('compliance.company.name') }} · {{ config('compliance.company.address') }}<br>
<a href="{{ route('legal.terms') }}">Terms of Rental</a> · <a href="{{ route('legal.privacy') }}">Privacy Notice</a></p>
</td>
</tr>
</table>
</td>
</tr>
