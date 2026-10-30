@php
    $selectedResources = old('recursos', $selectedResources ?? []);
@endphp

<div class="accordion" id="recursosAccordion">
    @foreach($menu as $item)
        @if(!empty($item['submenu']) && is_array($item['submenu']) && !isset($item['type']))
            @php
                $submenuItems = array_values(array_filter($item['submenu'], function ($subItem) {
                    return !empty($subItem['url']) && !isset($subItem['type']);
                }));
            @endphp

            @if(!empty($submenuItems))
                @php
                    $moduleId = 'module-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($item['text']));
                @endphp

                <div class="card card-light mb-2 shadow-sm">
                    <div class="card-header py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <label class="mb-0 d-flex align-items-center">
                                <input type="checkbox" class="module-check mr-2" data-module="{{ $moduleId }}">
                                <span><strong>{{ $item['text'] }}</strong></span>
                            </label>
                            <button
                                class="btn btn-link btn-sm p-0 collapsed"
                                type="button"
                                data-toggle="collapse"
                                data-target="#{{ $moduleId }}"
                                aria-expanded="false"
                                aria-controls="{{ $moduleId }}"
                                style="text-decoration:none;"
                            >
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>

                    <div id="{{ $moduleId }}" class="collapse" data-parent="#recursosAccordion">
                        <div class="card-body">
                            <div class="row">
                                @foreach($submenuItems as $subItem)
                                    @php
                                        $url = trim((string) ($subItem['url'] ?? ''), '/');
                                        $recurso = $url ? $recursos->firstWhere('urlpattern', $url) : null;
                                    @endphp

                                    @if($recurso)
                                        <div class="col-md-4 mb-2">
                                            <label class="d-flex align-items-center border rounded p-2 bg-light mb-0 w-100">
                                                <input
                                                    type="checkbox"
                                                    name="recursos[]"
                                                    value="{{ $recurso->id_recurso }}"
                                                    class="resource-check {{ $moduleId }} mr-2"
                                                    {{ in_array($recurso->id_recurso, $selectedResources) ? 'checked' : '' }}
                                                >
                                                <span>{{ $subItem['text'] }}</span>
                                            </label>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endforeach
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.module-check').forEach(function (moduleCheck) {
            const moduleId = moduleCheck.dataset.module;
            const checks = document.querySelectorAll('.resource-check.' + moduleId);

            const updateState = function () {
                const checkedCount = Array.from(checks).filter(function (check) {
                    return check.checked;
                }).length;

                moduleCheck.checked = checks.length > 0 && checkedCount === checks.length;
                moduleCheck.indeterminate = checks.length > 0 && checkedCount > 0 && checkedCount < checks.length;
            };

            checks.forEach(function (check) {
                check.addEventListener('change', updateState);
            });

            moduleCheck.addEventListener('change', function () {
                checks.forEach(function (check) {
                    check.checked = moduleCheck.checked;
                });
            });

            updateState();
        });
    });
</script>
