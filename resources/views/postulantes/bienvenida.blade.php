<!DOCTYPE html>

<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>ITSA - Sistema de Admisiones e Inscripciones</title>
    <!-- Tailwind CSS v3 with Plugins -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- Tailwind Configuration for Custom Theme Colors -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        itsa: {
                            dark: '#013813',
                            deep: '#01471d',
                            primary: '#075b28',
                            accent: '#109b46',
                            mint: '#4ade80',
                            softMint: '#7ee787',
                            lightBg: '#f4f8f5',
                            badgeBg: '#eaf6ee',
                            cardBorder: '#e1ede4',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
                        script: ['"Dancing Script"', '"Caveat"', 'cursive']
                    }
                }
            }
        };
    </script>
    <!-- Google Fonts: Inter & Caveat for Script Watermark -->
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link
        href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&amp;family=Inter:wght@400;500;600;700;800&amp;display=swap"
        rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/postulantes/formularios.css') }}">
    <link rel="stylesheet" href="{{ asset('css/postulantes/bienvenida.css') }}">
</head>

<body class="bg-[#f0f5f1] font-sans antialiased text-gray-800 min-h-screen flex flex-col justify-between">
    <!-- BEGIN: SiteHeaderAndHero -->
    <header class="relative text-white hero-bg-overlay overflow-hidden pb-32">
        <!-- Campus Building Background Decorative Silhouette/Image Simulation -->
        <div class="absolute inset-0 z-0 opacity-25 pointer-events-none mix-blend-luminosity overflow-hidden">
            <img alt="Campus ITSA Background" class="w-full h-full object-cover object-top scale-110 filter blur-[1px]"
                src="{{ asset('images/image.png') }}">
        </div>
        <!-- Top Navigation Bar -->
        <nav class="relative z-10 border-b border-white/10 px-4 sm:px-8 py-3.5" data-purpose="primary-navbar">
            <div class="max-w-6xl mx-auto flex items-center justify-between">
                <!-- Logo and Brand -->
                <div class="flex items-center gap-3.5">
                    <a href="{{ url('/') }}" aria-label="Ir al inicio" class="shrink-0">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo ITSA" class="postulantes-logo postulantes-logo-inicio">
                    </a>
                    <div class="leading-tight">
                        <span class="text-2xl font-black tracking-tight text-white block">ITSA</span>
                        <span
                            class="text-[10px] text-gray-200 font-medium tracking-wide block uppercase opacity-85">Instituto
                            Tecnológico Sacaba</span>
                    </div>
                </div>
                <!-- External Link Button -->
                <div>
                    <a class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold text-white bg-white/10 hover:bg-white/20 border border-white/30 transition backdrop-blur-sm shadow-sm"
                        href="{{ url('/') }}">
                        <i class="fa-solid fa-house text-[11px]"></i>
                        <span>Sitio principal</span>
                    </a>
                </div>
            </div>
        </nav>
        <!-- Hero Content Banner -->
        <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-8 pt-8 pb-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <!-- Left Banner Headline -->
                <div class="max-w-2xl">
                    <span class="inline-block text-[11px] font-extrabold tracking-wider text-itsa-mint uppercase mb-2">
                        TU FUTURO COMIENZA AQUÍ
                    </span>
                    <h1
                        class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-white mb-3 leading-tight">
                        Sistema de Admisiones e<br class="hidden sm:inline" /> Inscripciones
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-200/90 leading-relaxed font-normal max-w-xl">
                        El Instituto Tecnológico Sacaba te da la bienvenida. Aquí podrás realizar tu proceso de
                        postulación de manera fácil, rápida y segura.
                    </p>
                </div>
                <!-- Right Script Watermark (Formando profesionales del futuro) -->
                <div class="hidden md:flex items-center justify-end pr-4 select-none">
                    <div
                        class="custom-script-text text-itsa-softMint/90 text-2xl lg:text-3xl text-right transform -rotate-3 leading-snug drop-shadow-sm font-semibold">
                        Formando<br />
                        profesionales<br />
                        del futuro
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- END: SiteHeaderAndHero -->
    <!-- BEGIN: MainContentSection -->
    <main class="relative z-20 max-w-6xl mx-auto px-4 sm:px-8 -mt-24 mb-12 w-full flex flex-col gap-6"
        data-purpose="registration-cards-container">
        @include('partials.sweetalert-messages')
        <!-- Card 1: Proceso de Postulación Académica -->
        <section class="bg-white rounded-3xl shadow-xl shadow-green-950/5 border border-itsa-cardBorder p-6 sm:p-8"
            data-purpose="admission-steps-card">
            <!-- Card Section Header -->
            <div class="flex items-center gap-3.5 mb-6 sm:mb-8">
                <div
                    class="w-10 h-10 rounded-2xl bg-itsa-badgeBg border border-emerald-200/60 flex items-center justify-center text-itsa-primary shadow-xs">
                    <i class="fa-solid fa-graduation-cap text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">Proceso de Postulación
                        Académica</h2>
                    <p class="text-xs sm:text-sm text-gray-500 font-medium">Sigue estos pasos para completar tu
                        inscripción</p>
                </div>
            </div>
            <!-- Inner Two-Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left Column: Steps List and Primary Action Button -->
                <div class="lg:col-span-7 flex flex-col justify-between h-full space-y-6">
                    <div class="space-y-3.5 text-xs sm:text-sm" data-purpose="requirements-checklist">
                        <!-- Requirement 1 -->
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-itsa-badgeBg border border-emerald-200/50 flex-shrink-0 flex items-center justify-center text-itsa-primary mt-0.5">
                                <i class="fa-solid fa-user text-xs"></i>
                            </div>
                            <div class="pt-1 text-gray-700">
                                <strong class="font-bold text-gray-900">Fotografía personal:</strong> Tipo carnet con
                                fondo blanco o rojo.
                            </div>
                        </div>
                        <!-- Requirement 2 -->
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-itsa-badgeBg border border-emerald-200/50 flex-shrink-0 flex items-center justify-center text-itsa-primary mt-0.5">
                                <i class="fa-solid fa-address-card text-xs"></i>
                            </div>
                            <div class="pt-1 text-gray-700">
                                <strong class="font-bold text-gray-900">Fotocopia de C.I.:</strong> Cédula de Identidad
                                (anverso y reverso).
                            </div>
                        </div>
                        <!-- Requirement 3 -->
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-itsa-badgeBg border border-emerald-200/50 flex-shrink-0 flex items-center justify-center text-itsa-primary mt-0.5">
                                <i class="fa-solid fa-file-contract text-xs"></i>
                            </div>
                            <div class="pt-1 text-gray-700">
                                <strong class="font-bold text-gray-900">Certificado de Nacimiento:</strong> Copia clara
                                y legible.
                            </div>
                        </div>
                        <!-- Requirement 4 -->
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-itsa-badgeBg border border-emerald-200/50 flex-shrink-0 flex items-center justify-center text-itsa-primary mt-0.5">
                                <i class="fa-solid fa-scroll text-xs"></i>
                            </div>
                            <div class="pt-1 text-gray-700">
                                <strong class="font-bold text-gray-900">Diploma de Bachiller:</strong> Título o Diploma
                                de grado escolar.
                            </div>
                        </div>
                        <!-- Requirement 5 -->
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-itsa-badgeBg border border-emerald-200/50 flex-shrink-0 flex items-center justify-center text-itsa-primary mt-0.5">
                                <i class="fa-solid fa-receipt text-xs"></i>
                            </div>
                            <div class="pt-1 text-gray-700">
                                <strong class="font-bold text-gray-900">Comprobante de Depósito:</strong> Voucher único
                                e independiente por cada carrera a postular.
                            </div>
                        </div>
                    </div>
                    <!-- Primary CTA Button -->
                    <div class="pt-3">
                        <a class="inline-flex items-center justify-center gap-2.5 px-6 py-3 rounded-full bg-itsa-dark hover:bg-itsa-deep text-white font-semibold text-xs sm:text-sm tracking-wide shadow-md transition-all duration-200 hover:shadow-lg active:scale-[0.99] border border-emerald-900"
                            href="{{ route('postulantes.registrar') }}">
                            <span>Iniciar Registro por Primera Vez</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
                <!-- Right Column: Video Preview Card -->
                <div class="lg:col-span-5 flex flex-col justify-start">
                    <div
                        class="bg-white rounded-2xl border border-gray-200/90 p-3 sm:p-4 shadow-sm hover:shadow-md transition">
                        <!-- Video Thumbnail with Play Button -->
                        <div
                            class="relative w-full aspect-video rounded-xl overflow-hidden bg-slate-900 group cursor-pointer">
                            <img alt="Campus ITSA Video Preview"
                                class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-300"
                                src="https://lh3.googleusercontent.com/aida-public/AB6AXuCYtEpMC_-lNn7q_bUImr8qA-4XxyvyyasqX53g5WgHNkMnsjKb4PkBu7WNgHjfONWiLUgUqx6HmglFmPfdXVi6yxnLR6ax0bvnVgqdyyj1aTGi0XGNTGoUqaMI8QGD9zA9jVo617ILec_kfVMYTXLRAWipwRUT1FSB0XRT5jp79aOG6UicJ4hf9d-tsljeIp5AMmX31_1XwrkWkbD3wKLXYJfWB9TS4-WIN_EmIguh1Shk4Zonc2NMtgZoBU6FrDMY" />
                            <div
                                class="absolute inset-0 bg-black/35 group-hover:bg-black/25 transition flex items-center justify-center">
                                <!-- Play Circle Button -->
                                <div
                                    class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-white/95 text-itsa-dark flex items-center justify-center shadow-lg transform group-hover:scale-110 transition duration-200 pl-0.5">
                                    <i class="fa-solid fa-play text-lg sm:text-xl text-itsa-primary"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Video Captions -->
                        <div class="mt-3.5">
                            <h3 class="font-bold text-gray-900 text-sm sm:text-base">Video de Orientación y Guía de
                                Inscripción</h3>
                            <p class="text-[11px] sm:text-xs text-gray-500 mt-1 leading-relaxed">
                                Revisa detalladamente las recomendaciones antes de adjuntar tus documentos y registrar
                                tu número de comprobante bancario.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Card 2: Consulta de Postulación o Registro Previo -->
        <section class="bg-white rounded-3xl shadow-xl shadow-green-950/5 border border-itsa-cardBorder p-6 sm:p-8"
            data-purpose="search-applicant-card">
            <!-- Card Section Header -->
            <div class="flex items-start sm:items-center gap-3.5 mb-4">
                <div
                    class="w-9 h-9 rounded-2xl bg-itsa-badgeBg border border-emerald-200/60 flex items-center justify-center text-itsa-primary flex-shrink-0 mt-0.5 sm:mt-0">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-gray-900 leading-snug">¿Ya te registraste o deseas
                        postular a otra carrera?</h2>
                    <p class="text-xs sm:text-sm text-gray-500 font-medium">Si ya realizaste tu registro, consulta el
                        número de carnet para verificar tus carreras postuladas, revisar tus datos o inscribirte a una
                        nueva carrera.</p>
                </div>
            </div>
            <!-- Search Input & Button Bar -->
            <form class="mt-5" data-purpose="ci-lookup-form" action="{{ route('postulantes.bienvenida') }}" method="GET">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 max-w-3xl">
                    <div class="relative flex-1">
                        <input
                            class="w-full rounded-2xl border-gray-300 bg-gray-50/70 text-gray-800 placeholder-gray-400 text-xs sm:text-sm px-4 py-3 focus:bg-white focus:border-itsa-primary focus:ring-1 focus:ring-itsa-primary transition shadow-inner"
                            id="ci_consulta"
                            name="ci_consulta"
                            value="{{ request('ci_consulta') }}"
                            placeholder="Ingrese número de Carnet de Identidad (CI)..." type="text" required />
                    </div>
                    <button
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-itsa-dark hover:bg-itsa-deep text-white font-semibold text-xs sm:text-sm shadow-md transition hover:shadow-lg active:scale-[0.99] border border-emerald-900 flex-shrink-0"
                        type="submit">
                        <span>Consultar CI</span>
                        <i class="fa-solid fa-arrow-right-to-bracket text-xs"></i>
                    </button>
                </div>
                <!-- Footnote Warning -->
                <p class="text-[11px] sm:text-xs text-rose-600 font-medium mt-3.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-exclamation text-[10px]"></i>
                    <span>Recuerda: Cada postulación tiene un único número de comprobante bancario independiente.</span>
                </p>
            </form>

            @if(request()->filled('ci_consulta'))
                <div id="modalResultadosCI" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4" aria-hidden="true">
                    <div class="w-full max-w-3xl rounded-3xl bg-white shadow-2xl border border-gray-200 overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="modalResultadosCILabel">
                        <div class="flex items-center justify-between bg-emerald-700 px-5 py-4">
                            <h2 class="text-lg font-bold text-white" id="modalResultadosCILabel">Consulta de CI</h2>
                            <button type="button" class="text-white text-xl leading-none p-1 transition hover:opacity-80" data-close-ci-modal data-bs-dismiss="modal" aria-label="Cerrar modal">×</button>
                        </div>

                        <div class="p-5 sm:p-6">
                            @if($postulanteBase && $postulaciones->isNotEmpty())
                                <div class="mb-5">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-700">Postulante encontrado</p>
                                    <h3 class="mt-1 text-xl font-bold text-gray-900">{{ $postulanteBase->nombre }} {{ $postulanteBase->primerapellido }} {{ $postulanteBase->segundoapellido ?? '' }}</h3>
                                    <p class="text-sm text-gray-600">CI: <span class="font-semibold text-gray-800">{{ $postulanteBase->ci }}</span></p>
                                </div>

                                <div class="overflow-hidden rounded-xl border border-emerald-100" role="table" aria-label="Postulaciones del postulante">
                                    <div class="hidden grid-cols-[minmax(0,1.4fr)_minmax(5rem,.7fr)_minmax(6rem,.8fr)_minmax(15rem,2fr)] gap-3 bg-emerald-50 px-3 py-3 text-xs uppercase tracking-wide text-emerald-900 md:grid" role="row">
                                        <span class="font-bold" role="columnheader">Carrera</span>
                                        <span class="font-bold" role="columnheader">Gestión</span>
                                        <span class="font-bold" role="columnheader">Comprobante</span>
                                        <span class="text-center font-bold" role="columnheader">Acciones</span>
                                    </div>
                                    <div class="divide-y divide-emerald-100 bg-white">
                                        @foreach($postulaciones as $postulacion)
                                            <div class="grid grid-cols-2 gap-x-3 gap-y-3 p-3 transition hover:bg-emerald-50/40 md:grid-cols-[minmax(0,1.4fr)_minmax(5rem,.7fr)_minmax(6rem,.8fr)_minmax(15rem,2fr)] md:items-center md:gap-3" role="row">
                                                <div class="col-span-2 min-w-0 md:col-span-1" role="cell">
                                                    <span class="text-[10px] font-bold uppercase tracking-wide text-emerald-800 md:hidden">Carrera</span>
                                                    <p class="break-words font-semibold text-gray-900">{{ $postulacion->carrera?->nombre ?? 'Carrera no registrada' }}</p>
                                                </div>
                                                <div role="cell">
                                                    <span class="text-[10px] font-bold uppercase tracking-wide text-emerald-800 md:hidden">Gestión</span>
                                                    <p class="text-sm text-gray-600">
                                                        {{ $postulacion->gestion?->gestion ?? 'Sin gestión' }}
                                                        @if($postulacion->gestion?->periodo)
                                                            · {{ $postulacion->gestion->periodo }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <div role="cell">
                                                    <span class="text-[10px] font-bold uppercase tracking-wide text-emerald-800 md:hidden">Comprobante</span>
                                                    <p><span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase text-emerald-800">{{ $postulacion->deposito ? 'Registrado' : 'Pendiente' }}</span></p>
                                                </div>
                                                <div class="col-span-2 flex flex-wrap items-center justify-start gap-2 md:col-span-1 md:justify-center" role="cell">
                                                    <a href="{{ route('postulantes.edit', $postulacion->id_postulante) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-itsa-dark px-3 py-2 text-xs font-semibold text-white transition hover:bg-itsa-deep">
                                                        <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                                        <span>Editar</span>
                                                    </a>
                                                    <a href="{{ route('postulantes.pdf', $postulacion->id_postulante) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-700 px-3 py-2 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-50">
                                                        <i class="fa-solid fa-print text-[10px]"></i>
                                                        <span>Imprimir ficha</span>
                                                    </a>
                                                    <button
                                                        type="button"
                                                        data-delete-postulacion
                                                        data-delete-url="{{ route('postulantes.destroy', $postulacion->id_postulante) }}"
                                                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-rose-300 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                                        <i class="fa-solid fa-trash-can text-[10px]"></i>
                                                        <span>Eliminar ficha</span>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div id="modalEliminarFicha" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" aria-hidden="true">
                                    <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="modalEliminarFichaTitulo" aria-describedby="modalEliminarFichaDescripcion">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                        </div>
                                        <h2 id="modalEliminarFichaTitulo" class="mt-4 text-lg font-bold text-gray-900">¿Seguro que quieres eliminar esta ficha?</h2>
                                        <p id="modalEliminarFichaDescripcion" class="mt-2 text-sm text-gray-600">Esta acción eliminará únicamente la postulación seleccionada y no se puede deshacer.</p>
                                        <div class="mt-6 flex justify-end gap-3">
                                            <button type="button" data-cancelar-eliminar-ficha class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancelar</button>
                                            <form id="formEliminarFicha" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-800">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                    <span>Sí, eliminar ficha</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 flex flex-col items-center gap-3 text-center">
                                    <p class="text-sm font-semibold text-gray-700">¿Quieres postular a otra carrera?</p>
                                    <a href="{{ route('postulantes.registrar', ['ci_consulta' => $postulanteBase->ci]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-white px-5 py-3 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50">
                                        <i class="fa-solid fa-plus text-xs"></i>
                                        <span>Postular a otra carrera</span>
                                    </a>
                                </div>
                            @else
                                <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-amber-200 bg-amber-100 text-amber-700">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-700">No se encontró registro</p>
                                        <h3 class="mt-1 text-lg font-bold text-gray-900">Usted no se encuentra registrado en la presente gestión académica.</h3>
                                        <p class="mt-1 text-sm text-gray-600">Puedes continuar con el registro o verificar el número de carnet antes de intentar nuevamente.</p>
                                        <a href="{{ route('postulantes.registrar', ['ci_consulta' => request('ci_consulta')]) }}" class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-itsa-dark px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-itsa-deep">
                                            <i class="fa-solid fa-user-plus text-[10px]"></i>
                                            <span>Registrar postulante</span>
                                        </a>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-5 flex justify-center">
                                <button type="button" data-close-ci-modal data-bs-dismiss="modal" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
                                    <i class="fa-solid fa-xmark text-[10px]"></i>
                                    <span>Cerrar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </section>
    </main>
    <!-- END: MainContentSection -->
    @if(request()->filled('ci_consulta'))
        <script>
            const modalResultados = document.getElementById('modalResultadosCI');
            const ciInput = document.getElementById('ci_consulta');

            if (modalResultados) {
                const modalBootstrap = window.bootstrap?.Modal ? new bootstrap.Modal(modalResultados) : null;

                if (modalBootstrap) {
                    modalBootstrap.show();
                } else {
                    modalResultados.classList.remove('hidden');
                    modalResultados.classList.add('flex');
                    modalResultados.setAttribute('aria-hidden', 'false');
                }

                document.querySelectorAll('[data-close-ci-modal]').forEach((button) => {
                    button.addEventListener('click', () => {
                        if (modalBootstrap) {
                            modalBootstrap.hide();
                        } else {
                            modalResultados.classList.add('hidden');
                            modalResultados.classList.remove('flex');
                            modalResultados.setAttribute('aria-hidden', 'true');
                        }

                        const urlActual = new URL(window.location.href);
                        urlActual.searchParams.delete('ci_consulta');

                        if (ciInput) {
                            ciInput.value = '';
                        }

                        window.location.href = urlActual.toString();
                    });
                });

                modalResultados.addEventListener('click', (event) => {
                    if (event.target === modalResultados) {
                        document.querySelector('[data-close-ci-modal]').click();
                    }
                });
            }

            const modalEliminarFicha = document.getElementById('modalEliminarFicha');
            const formEliminarFicha = document.getElementById('formEliminarFicha');

            if (modalEliminarFicha && formEliminarFicha) {
                const cerrarModalEliminarFicha = () => {
                    modalEliminarFicha.classList.add('hidden');
                    modalEliminarFicha.classList.remove('flex');
                    modalEliminarFicha.setAttribute('aria-hidden', 'true');
                };

                document.querySelectorAll('[data-delete-postulacion]').forEach((button) => {
                    button.addEventListener('click', () => {
                        formEliminarFicha.action = button.dataset.deleteUrl;
                        modalEliminarFicha.classList.remove('hidden');
                        modalEliminarFicha.classList.add('flex');
                        modalEliminarFicha.setAttribute('aria-hidden', 'false');
                    });
                });

                document.querySelectorAll('[data-cancelar-eliminar-ficha]').forEach((button) => {
                    button.addEventListener('click', cerrarModalEliminarFicha);
                });

                modalEliminarFicha.addEventListener('click', (event) => {
                    if (event.target === modalEliminarFicha) {
                        cerrarModalEliminarFicha();
                    }
                });
            }
        </script>
    @endif
    <!-- BEGIN: Footer -->
    <footer class="text-center text-xs text-gray-500 pb-8 pt-4 border-t border-gray-200/50">
        <p>© 2025 Instituto Tecnológico Sacaba (ITSA) - Todos los derechos reservados.</p>
    </footer>
    <!-- END: Footer -->
    @include('partials.sweetalert-assets')
</body>

</html>
