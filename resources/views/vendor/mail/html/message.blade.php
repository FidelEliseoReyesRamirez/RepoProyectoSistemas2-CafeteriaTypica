@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header')
Cafetería Típica
@endcomponent
@endslot

{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
&copy; {{ date('Y') }} Cafetería Típica. Todos los derechos reservados.
@endcomponent
@endslot
@endcomponent