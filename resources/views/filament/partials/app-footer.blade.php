@php
    $sirkulasi = request()->routeIs('filament.dashboard.pages.sirkulasi');
@endphp
<div class="app-footer{{ isset($authTop) && $authTop ? ' app-footer--auth-top' : '' }}{{ $sirkulasi ? ' app-footer--fixed-sirkulasi' : '' }}">
    &copy; {{ now()->year }} MTs Negeri 1 Pandeglang | built with &#9829;&#65039; by
    <a href="https://github.com/zulfikriyahya" target="_blank" rel="noopener noreferrer">Yahya Zulfikri</a>
</div>
