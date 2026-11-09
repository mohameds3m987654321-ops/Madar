<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/support-assistant.css') }}">
<script
    src="{{ asset('js/support-assistant.js') }}"
    data-message-url="{{ route('assistant.message') }}"
    data-escalate-url="{{ route('assistant.escalate') }}"
    data-conversation-url="{{ url('/assistant/conversations') }}"
    data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
    defer
></script>
