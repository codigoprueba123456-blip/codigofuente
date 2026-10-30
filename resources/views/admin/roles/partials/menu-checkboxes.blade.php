@foreach($items as $item)
    @if(!empty($item['text']) && !isset($item['type']))
        @php
            $url = isset($item['url']) ? trim($item['url'], '/') : null;
            $recurso = $url ? $recursosPorUrl->get($url) : null;
        @endphp

        @if($recurso)
            <option
                value="{{ $recurso->id_recurso }}"
                {{ in_array($recurso->id_recurso, $recursosSeleccionados) ? 'selected' : '' }}
            >
                {{ str_repeat('  ', $nivel) }}{{ $item['text'] }}
            </option>
        @elseif(!empty($item['submenu']) && is_array($item['submenu']))
            <optgroup label="{{ $item['text'] }}">
                @include('admin.roles.partials.menu-checkboxes', ['items' => $item['submenu'], 'nivel' => ($nivel ?? 0) + 1])
            </optgroup>
        @endif
    @endif
@endforeach