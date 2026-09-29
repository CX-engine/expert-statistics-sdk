{{-- Stand-in for the host app's <x-menus.item-dropdown> (tests only). --}}
@props(['title' => null, 'icon' => null, 'openPath' => null])
<li>{{ $title }}<ul>{{ $slot }}</ul></li>
