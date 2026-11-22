<x-mail::message>
Hi {{ $user->name }},

{!! $message !!}

Regards, <br>
{{ site('name') }} Team.
</x-mail::message>
