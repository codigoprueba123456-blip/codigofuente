<!DOCTYPE html>

<html class="scroll-smooth" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>ITSA | Instituto Tecnológico Sacaba - Educación Técnica Superior Fiscal y Gratuita</title>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&amp;family=Cinzel:wght@600;700&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#015001",
                        "primary-dark": "#003b01",
                        "primary-deep": "#002800",
                        "primary-light": "#057a05",
                        "secondary-gold": "#C59B27",
                        "accent-green": "#79c369",
                        "surface-canvas": "#FFFFFF",
                        "surface-subtle": "#F8FAFC",
                        "surface-muted": "#F1F5F9",
                        "surface-dark": "#0b120c",
                        "text-primary": "#0F172A",
                        "text-secondary": "#334155",
                        "text-muted": "#64748B",
                        "border-subtle": "#E2E8F0"
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        display: ['"Plus Jakarta Sans"', 'sans-serif']
                    }
                }
            }
        };
    </script>
    <style>
        @layer base {

            html,
            body {
                margin: 0;
                padding: 0;
                font-family: 'Plus Jakarta Sans', sans-serif;
                color: #0F172A;
            }
        }

        .text-balance {
            text-wrap: balance;
        }

        .glass-nav {
            background: rgba(0, 59, 1, 0.96);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .glass-nav-scrolled {
            background: rgba(0, 59, 1, 0.98);
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
    </style>
</head>

<body class="bg-surface-canvas text-text-primary antialiased selection:bg-[#015001] selection:text-white">
    <!-- 1. NAVBAR ELEGANTE UNIVERSITARIO -->
    <header class="fixed top-0 left-0 w-full z-50 transition-all duration-300 glass-nav border-b border-white/15"
        id="site-header">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 h-20 flex items-center justify-between gap-6">
            <!-- Logo Oficial ITSA -->
            <a class="flex items-center gap-3.5 group shrink-0" href="{{ url('/') }}">
                <img alt="Escudo Oficial ITSA"
                    class="h-10 w-auto object-contain brightness-0 invert transition-transform group-hover:scale-105 duration-200"
                    src="{{ asset('images/logo.png') }}" />
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-lg lg:text-xl tracking-tight text-white">ITSA</span>
                        <span class="text-white/50 text-xs">|</span>
                        <span class="font-bold text-xs lg:text-sm tracking-wider uppercase text-white">Instituto
                            Tecnológico Sacaba</span>
                    </div>
                    <span class="text-[10px] tracking-widest uppercase font-semibold text-white/70">Educación Fiscal
                        Gratuita · R.M. Nº 995/2015</span>
                </div>
            </a>
            <!-- Menú de Navegación Académico -->
            <nav
                class="hidden xl:flex items-center gap-7 text-[13px] font-semibold tracking-wide uppercase text-white/90">
                <a class="text-white font-bold border-b-2 border-white pb-1 transition-colors"
                    href="#inicio">Inicio</a>
                <a class="hover:text-emerald-200 pb-1 transition-colors" href="#institucion">Institución</a>
                <a class="hover:text-emerald-200 pb-1 transition-colors" href="#carreras">Carreras</a>
                <a class="hover:text-emerald-200 pb-1 transition-colors"
                    href="{{ route('postulantes.bienvenida') }}">Admisiones</a>
                <a class="hover:text-emerald-200 pb-1 transition-colors" href="#experiencia">Experiencia</a>
                <a class="hover:text-emerald-200 pb-1 transition-colors" href="#actualidad">Actualidad</a>
                <a class="hover:text-emerald-200 pb-1 transition-colors" href="#contacto">Contacto</a>
            </nav>
            <!-- CTA Destacado & Enlace Orion -->
            <div class="flex items-center gap-4 shrink-0">
                <a class="hidden md:inline-flex items-center gap-1.5 text-xs font-bold text-white hover:text-emerald-200 transition-colors py-2 px-3 rounded-lg hover:bg-white/10"
                    href="{{ url('/') }}" rel="noopener">
                    <span class="material-symbols-outlined text-[17px] text-white">home</span>
                    <span>Sitio principal</span>
                </a>
                @guest
                    <a class="inline-flex items-center justify-center gap-2 border border-white/70 text-white hover:bg-white/10 text-xs lg:text-sm font-bold tracking-wider uppercase px-4 py-2.5 rounded-lg transition-all"
                        href="{{ route('login') }}">
                        <span class="material-symbols-outlined text-[16px]">login</span>
                        <span>Iniciar sesión</span>
                    </a>
                @else
                    <a class="inline-flex items-center justify-center gap-2 border border-white/70 text-white hover:bg-white/10 text-xs lg:text-sm font-bold tracking-wider uppercase px-4 py-2.5 rounded-lg transition-all"
                        href="{{ route('home') }}">
                        <span class="material-symbols-outlined text-[16px]">dashboard</span>
                        <span>Ir al sistema</span>
                    </a>
                @endguest
                <a class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs lg:text-sm font-bold tracking-wider uppercase px-5 py-2.5 rounded-lg shadow-sm hover:shadow-md transition-all"
                    href="{{ route('postulantes.bienvenida') }}">
                    <span>Admisiones 2026</span>
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
            </div>
        </div>
    </header>
    <!-- 2. HERO CINEMATOGRÁFICO UNIVERSITARIO (60-70% Fotografía Documental) -->
    <section class="relative min-h-[92vh] lg:min-h-screen flex items-end pt-24 pb-12 lg:pb-16 overflow-hidden"
        id="inicio">
        <!-- Background Image -->
        <div class="absolute inset-0 z-0">
            <img alt="Estudiantes en el campus universitario del Instituto Tecnológico Sacaba"
                class="w-full h-full object-cover object-center brightness-[0.88] contrast-[1.05] scale-100 transform origin-center transition-transform duration-1000 ease-out"
                src="{{ asset('images/image.png') }}" />
            <!-- Multilayer Overlay for Prestige & Legibility -->
            <div class="absolute inset-0 bg-gradient-to-t from-[#021802] via-[#022b02]/65 to-black/35"></div>
            <div
                class="absolute inset-0 bg-radial-at-c from-transparent via-[#015001]/20 to-black/60 pointer-events-none">
            </div>
        </div>
        <div class="relative z-10 max-w-[1440px] mx-auto px-6 lg:px-12 w-full text-white">
            <div class="max-w-3xl space-y-6">
                <!-- Institutional Tag -->
                <div
                    class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-semibold tracking-widest uppercase text-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Instituto Tecnológico Sacaba · R.M. Nº 995/2015</span>
                </div>
                <!-- Hero Title -->
                <h1
                    class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight uppercase leading-[1.02] text-white">
                    Forma tu <br class="hidden sm:inline" />
                    <span
                        class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-emerald-100 to-white">futuro
                        profesional.</span>
                </h1>
                <!-- Subtitle -->
                <p class="text-base sm:text-xl text-slate-200 font-light max-w-2xl leading-relaxed text-balance">
                    Educación técnica superior de excelencia fiscal y 100% gratuita para transformar tus conocimientos
                    prácticos en oportunidades laborales reales en el corazón productivo de Cochabamba.
                </p>
                <!-- CTA Action Buttons -->
                <div class="flex flex-wrap items-center gap-4 pt-4">
                    <a class="inline-flex items-center justify-center gap-2.5 bg-[#015001] hover:bg-[#003b01] text-white border border-emerald-400/40 text-xs sm:text-sm font-bold tracking-wider uppercase px-7 py-4 rounded-xl shadow-lg hover:shadow-emerald-900/40 transition-all transform hover:-translate-y-0.5"
                        href="#carreras">
                        <span>Conoce nuestras carreras</span>
                        <span class="material-symbols-outlined text-[18px]">school</span>
                    </a>
                    <a class="inline-flex items-center justify-center gap-2.5 bg-white/15 hover:bg-white/25 text-white backdrop-blur-md border border-white/30 text-xs sm:text-sm font-bold tracking-wider uppercase px-7 py-4 rounded-xl shadow-md transition-all transform hover:-translate-y-0.5"
                        href="{{ route('postulantes.bienvenida') }}">
                        <span>Proceso de Admisión</span>
                        <span class="material-symbols-outlined text-[18px]">how_to_reg</span>
                    </a>
                </div>
            </div>
            <!-- Institutional Micro-data Baseline Ribbon -->
            <div
                class="mt-14 pt-8 border-t border-white/15 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs tracking-wider uppercase font-semibold text-slate-300">
                <div class="flex items-center gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>09 Carreras Técnicas</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Técnico Superior Nacional</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Sacaba · Cochabamba, Bolivia</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>100% Fiscal y Gratuito</span>
                </div>
            </div>
        </div>
    </section>
    <!-- 3. SECCIÓN INSTITUCIONAL ("CONOCE ITSA") - FONDO BLANCO / GRIS ULTRA CLARO -->
    <section class="py-20 lg:py-28 bg-[#FAFCFA]" id="institucion">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
                <!-- Left Column: Editorial Photo with Stamp Badge -->
                <div class="lg:col-span-6 relative">
                    <div
                        class="relative rounded-3xl overflow-hidden shadow-2xl bg-slate-900 border border-slate-200 aspect-[4/3] lg:aspect-[5/4] group">
                        <img alt="Estudiantes en visita técnica a planta industrial Coboce"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out"
                            src="https://lh3.googleusercontent.com/aida/AEtjO1VSmewtDy7lPtVUMhNMuGg2atDQyEDCAEMLI94BAe33_xoVsl7dwWdKJviLjmCOmoIFOatx8ynuhi-MLpIS9duE7yA08dqDlOJESm5cDJfq8TT535F75Wn1P5LHgUTUBbFPxdeRdmEWVgkE0YrKSyQKHOx7fca9E-Cf9mGXrjy6d3Vi5z9b1Sr1LGSrl9YW7XHxOgCv6un1gao4ABjyHLRxNH2dX1eo3Mg5ZBvZU28lj02ReE-hp6W-dzo" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                        <div class="absolute bottom-6 left-6 right-6 text-white">
                            <span
                                class="text-[11px] font-bold tracking-widest uppercase text-emerald-300 block mb-1">Formación
                                en Campo Real</span>
                            <p class="text-sm font-medium text-slate-200">Prácticas directas con el parque industrial y
                                productivo de la región de Sacaba y Cochabamba.</p>
                        </div>
                    </div>
                    <!-- Official Stamp Badge -->
                    <div
                        class="absolute -bottom-6 -right-4 sm:-bottom-8 sm:right-6 bg-white rounded-2xl p-5 shadow-xl border border-slate-100 flex items-center gap-4 max-w-xs">
                        <div
                            class="w-12 h-12 rounded-xl bg-emerald-50 text-[#015001] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[28px]">verified</span>
                        </div>
                        <div>
                            <span
                                class="text-[11px] uppercase tracking-wider font-extrabold text-[#015001] block">Fundado
                                el 19 de Marzo de 2007</span>
                            <span class="text-xs text-slate-500">Más de 18 años formando técnicos superiores con
                                vocación productiva.</span>
                        </div>
                    </div>
                </div>
                <!-- Right Column: Institutional Narrative -->
                <div class="lg:col-span-6 flex flex-col items-start space-y-6 lg:pl-6">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-emerald-100/60 text-[#015001] text-xs font-bold tracking-widest uppercase">
                        <span>Compromiso Social y Excelencia Técnica</span>
                    </div>
                    <h2
                        class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Una institución comprometida con la formación técnica superior.
                    </h2>
                    <div class="space-y-4 text-base sm:text-lg text-slate-600 font-normal leading-relaxed">
                        <p>
                            El <strong class="font-bold text-slate-900">Instituto Tecnológico Sacaba (ITSA)</strong> es
                            una institución de carácter fiscal del Estado Plurinacional de Bolivia, que forma
                            profesionales Técnicos Superiores en nueve carreras técnicas de forma <span
                                class="text-[#015001] font-semibold">100% gratuita</span>.
                        </p>
                        <p>
                            Brindamos oportunidades formativas equitativas tanto a jóvenes bachilleres como a adultos
                            trabajadores, bajo la convicción institucional de que <em
                                class="text-slate-800 font-medium not-italic">nunca es tarde para aprender,
                                profesionalizarse y aportar al desarrollo industrial del país</em>.
                        </p>
                    </div>
                    <!-- Features List -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full pt-2">
                        <div
                            class="flex items-start gap-3 p-3.5 rounded-xl bg-white border border-slate-200/80 shadow-sm">
                            <span
                                class="material-symbols-outlined text-[#015001] text-[22px] shrink-0 mt-0.5">workspace_premium</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Título en Provisión Nacional</h3>
                                <p class="text-xs text-slate-500">Emitido directamente por el Ministerio de Educación.
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-start gap-3 p-3.5 rounded-xl bg-white border border-slate-200/80 shadow-sm">
                            <span
                                class="material-symbols-outlined text-[#015001] text-[22px] shrink-0 mt-0.5">handyman</span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Infraestructura y Talleres</h3>
                                <p class="text-xs text-slate-500">Equipamiento tecnológico real para prácticas
                                    intensivas.</p>
                            </div>
                        </div>
                    </div>
                    <div class="pt-4">
                        <a class="inline-flex items-center gap-3 text-xs sm:text-sm font-bold tracking-wider uppercase text-[#015001] hover:text-[#003b01] group"
                            href="#historia">
                            <span>Conoce nuestra historia completa</span>
                            <span
                                class="material-symbols-outlined text-[18px] group-hover:translate-x-1.5 transition-transform">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- 4. DATOS INSTITUCIONALES HORIZONTALES ELEGANTES (FONDO VERDE PROFUNDO #003B01) -->
    <section class="py-16 bg-[#003b01] text-white border-y border-emerald-900/50 relative overflow-hidden">
        <div
            class="absolute inset-0 opacity-10 bg-[radial-gradient(#a9f695_1px,transparent_1px)] [background-size:20px_20px]">
        </div>
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 relative z-10">
            <div
                class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12 divide-y lg:divide-y-0 lg:divide-x divide-emerald-800/60">
                <!-- Dato 1 -->
                <div class="pt-6 lg:pt-0 lg:px-8 flex flex-col justify-center">
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-5xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white stat-counter"
                            data-target="9">09</span>
                        <span class="text-emerald-400 font-bold text-xl">Carreras</span>
                    </div>
                    <span class="text-xs sm:text-sm uppercase tracking-wider font-semibold text-emerald-200/90 mt-2">
                        Formación a Nivel Técnico Superior
                    </span>
                    <p class="text-[11px] text-emerald-300/70 mt-1">Con planes homologados y certificados a nivel
                        nacional.</p>
                </div>
                <!-- Dato 2 -->
                <div class="pt-6 lg:pt-0 lg:px-8 flex flex-col justify-center">
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-5xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white stat-counter"
                            data-target="56">56</span>
                        <span class="text-emerald-400 font-bold text-xl">Docentes</span>
                    </div>
                    <span class="text-xs sm:text-sm uppercase tracking-wider font-semibold text-emerald-200/90 mt-2">
                        Ítem Ministerial de Educación
                    </span>
                    <p class="text-[11px] text-emerald-300/70 mt-1">Especialistas con alta experiencia en el rubro
                        técnico.</p>
                </div>
                <!-- Dato 3 -->
                <div class="pt-6 lg:pt-0 lg:px-8 flex flex-col justify-center">
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-5xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white stat-counter"
                            data-target="5">05</span>
                        <span class="text-emerald-400 font-bold text-xl">Directivos</span>
                    </div>
                    <span class="text-xs sm:text-sm uppercase tracking-wider font-semibold text-emerald-200/90 mt-2">
                        Gestión Académica e Institucional
                    </span>
                    <p class="text-[11px] text-emerald-300/70 mt-1">Administración eficiente al servicio del
                        estudiante.</p>
                </div>
                <!-- Dato 4 -->
                <div class="pt-6 lg:pt-0 lg:px-8 flex flex-col justify-center">
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-5xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white stat-counter"
                            data-target="3">03</span>
                        <span class="text-emerald-400 font-bold text-xl">Turnos</span>
                    </div>
                    <span class="text-xs sm:text-sm uppercase tracking-wider font-semibold text-emerald-200/90 mt-2">
                        Mañana · Tarde · Noche
                    </span>
                    <p class="text-[11px] text-emerald-300/70 mt-1">Horarios flexibles compatibles con el trabajo y la
                        familia.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- 5. OFERTA ACADÉMICA - LISTA EDITORIAL INTERACTIVA (FONDO BLANCO) -->
    <section class="py-20 lg:py-28 bg-white border-b border-slate-100" id="carreras">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12">
            <!-- Section Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-12 border-b border-slate-200">
                <div>
                    <span class="text-xs font-bold tracking-widest uppercase text-[#015001] block mb-2">Formación
                        Técnica Superior de Excelencia</span>
                    <h2
                        class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight uppercase">
                        Nuestra Oferta Académica
                    </h2>
                </div>
                <p class="text-slate-500 max-w-md text-sm sm:text-base leading-relaxed">
                    Encuentra el camino profesional que quieres construir. 9 carreras técnicas fiscalmente respaldadas,
                    con 3 años de duración y titulación oficial del Estado.
                </p>
            </div>
            <!-- Main Interactive Editorial Container -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-10">
                <!-- List of 9 Degrees (Left/Center Column) -->
                <div class="lg:col-span-8 flex flex-col divide-y divide-slate-200" id="degrees-list">
                    <!-- Carrera 01 -->
                    <div class="degree-row group py-5 px-4 rounded-xl transition-all duration-200 hover:bg-emerald-50/60 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        data-desc="Se desempeña resolviendo y proponiendo soluciones de bases de datos, redes de comunicación de datos, inteligencia de negocios y modelados de sistemas informáticos, integrando tecnologías modernas de software y hardware."
                        data-id="1"
                        data-skills="Bases de Datos · Redes CISCO · Programación Web · Inteligencia Artificial"
                        data-title="Sistemas Informáticos" data-turnos="Mañana y Noche">
                        <div class="flex items-start sm:items-center gap-5">
                            <span
                                class="text-2xl sm:text-3xl font-extrabold text-slate-300 group-hover:text-[#015001] transition-colors w-10">01</span>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3
                                        class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-[#015001] transition-colors">
                                        Sistemas Informáticos</h3>
                                    <span
                                        class="hidden sm:inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-[#015001]">3
                                        Años</span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-500 line-clamp-1">Desarrollo de software,
                                    redes, bases de datos e inteligencia aplicada.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 sm:self-center self-end">
                            <span
                                class="text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1 rounded-full">Turno
                                Mañana y Noche</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#015001] group-hover:translate-x-1 transition-transform">
                                <span>Ver Perfil</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                    <!-- Carrera 02 -->
                    <div class="degree-row group py-5 px-4 rounded-xl transition-all duration-200 hover:bg-emerald-50/60 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        data-desc="Profesional en manejo de herramientas tecnológicas, gestión documental digital y física, etiqueta, protocolo, administración ejecutiva y relaciones públicas interinstitucionales con alta confidencialidad."
                        data-id="2"
                        data-skills="Gestión Documental · Protocolo Corporativo · Ofimática Avanzada · Relaciones Públicas"
                        data-title="Secretariado Ejecutivo" data-turnos="Mañana y Noche">
                        <div class="flex items-start sm:items-center gap-5">
                            <span
                                class="text-2xl sm:text-3xl font-extrabold text-slate-300 group-hover:text-[#015001] transition-colors w-10">02</span>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3
                                        class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-[#015001] transition-colors">
                                        Secretariado Ejecutivo</h3>
                                    <span
                                        class="hidden sm:inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-[#015001]">3
                                        Años</span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-500 line-clamp-1">Gestión documental
                                    tecnológica, relaciones públicas y administración de oficinas de alto nivel.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 sm:self-center self-end">
                            <span
                                class="text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1 rounded-full">Turno
                                Mañana y Noche</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#015001] group-hover:translate-x-1 transition-transform">
                                <span>Ver Perfil</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                    <!-- Carrera 03 -->
                    <div class="degree-row group py-5 px-4 rounded-xl transition-all duration-200 hover:bg-emerald-50/60 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        data-desc="Administra todo tipo de organizaciones productivas y de servicios. Lidera equipos operativos, maneja presupuestos, finanzas, comercialización y tributación según la legislación empresarial nacional."
                        data-id="3"
                        data-skills="Dirección Financiera · Gestión de Operaciones · Marketing Estratégico · Emprendimiento"
                        data-title="Administración de Empresas" data-turnos="Noche">
                        <div class="flex items-start sm:items-center gap-5">
                            <span
                                class="text-2xl sm:text-3xl font-extrabold text-slate-300 group-hover:text-[#015001] transition-colors w-10">03</span>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3
                                        class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-[#015001] transition-colors">
                                        Administración de Empresas</h3>
                                    <span
                                        class="hidden sm:inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-[#015001]">3
                                        Años</span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-500 line-clamp-1">Liderazgo operativo, finanzas
                                    empresariales, comercialización y dirección de unidades de negocio.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 sm:self-center self-end">
                            <span
                                class="text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1 rounded-full">Turno
                                Noche</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#015001] group-hover:translate-x-1 transition-transform">
                                <span>Ver Perfil</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                    <!-- Carrera 04 -->
                    <div class="degree-row group py-5 px-4 rounded-xl transition-all duration-200 hover:bg-emerald-50/60 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        data-desc="Ejecuta funciones en áreas de finanzas, contabilidad tributaria y de remuneraciones. Elabora balances generales, estados financieros completos y declaraciones ante el Servicio de Impuestos Nacionales."
                        data-id="4"
                        data-skills="Auditoría · Tributación SIN · Estados Financieros · Costos Industriales"
                        data-title="Contaduría General" data-turnos="Noche">
                        <div class="flex items-start sm:items-center gap-5">
                            <span
                                class="text-2xl sm:text-3xl font-extrabold text-slate-300 group-hover:text-[#015001] transition-colors w-10">04</span>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3
                                        class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-[#015001] transition-colors">
                                        Contaduría General</h3>
                                    <span
                                        class="hidden sm:inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-[#015001]">3
                                        Años</span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-500 line-clamp-1">Proceso contable integral,
                                    finanzas corporativas, auditoría y cumplimiento tributario boliviano.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 sm:self-center self-end">
                            <span
                                class="text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1 rounded-full">Turno
                                Noche</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#015001] group-hover:translate-x-1 transition-transform">
                                <span>Ver Perfil</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                    <!-- Carrera 05 -->
                    <div class="degree-row group py-5 px-4 rounded-xl transition-all duration-200 hover:bg-emerald-50/60 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        data-desc="Desarrolla trabajos especializados para realizar levantamientos de alta precisión, cubicaciones y movimientos de tierra para proyectos mineros, viales, agrícolas y de obras civiles con teodolitos, GPS geodésico y drones."
                        data-id="5"
                        data-skills="Aerofotogrametría con Drones · Estación Total · Cartografía Digital · Obras Viales"
                        data-title="Topografía y Geodesia" data-turnos="Mañana">
                        <div class="flex items-start sm:items-center gap-5">
                            <span
                                class="text-2xl sm:text-3xl font-extrabold text-slate-300 group-hover:text-[#015001] transition-colors w-10">05</span>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3
                                        class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-[#015001] transition-colors">
                                        Topografía y Geodesia</h3>
                                    <span
                                        class="hidden sm:inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-[#015001]">3
                                        Años</span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-500 line-clamp-1">Levantamientos topográficos,
                                    fotogrametría con drones, cartografía y obras civiles de gran envergadura.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 sm:self-center self-end">
                            <span
                                class="text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1 rounded-full">Turno
                                Mañana</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#015001] group-hover:translate-x-1 transition-transform">
                                <span>Ver Perfil</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                    <!-- Carrera 06 -->
                    <div class="degree-row group py-5 px-4 rounded-xl transition-all duration-200 hover:bg-emerald-50/60 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        data-desc="Mantenimiento preventivo y correctivo, diagnóstico computarizado con escáner, reparación integral de vehículos a gasolina y diésel, sistemas de inyección electrónica y transmisiones automáticas y manuales."
                        data-id="6"
                        data-skills="Diagnóstico Computarizado · Inyección Electrónica · Motores Diésel · Seguridad Industrial"
                        data-title="Mecánica Automotriz" data-turnos="Mañana y Tarde">
                        <div class="flex items-start sm:items-center gap-5">
                            <span
                                class="text-2xl sm:text-3xl font-extrabold text-slate-300 group-hover:text-[#015001] transition-colors w-10">06</span>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3
                                        class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-[#015001] transition-colors">
                                        Mecánica Automotriz</h3>
                                    <span
                                        class="hidden sm:inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-[#015001]">3
                                        Años</span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-500 line-clamp-1">Diagnóstico computarizado,
                                    inyección electrónica, motores diésel y gasolina con instrumental moderno.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 sm:self-center self-end">
                            <span
                                class="text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1 rounded-full">Turno
                                Mañana y Tarde</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#015001] group-hover:translate-x-1 transition-transform">
                                <span>Ver Perfil</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                    <!-- Carrera 07 -->
                    <div class="degree-row group py-5 px-4 rounded-xl transition-all duration-200 hover:bg-emerald-50/60 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        data-desc="Desarrolla habilidades en alta cocina nacional e internacional, nutrición, ingeniería de menús, pastelería y repostería, inocuidad de alimentos, costos gastronómicos y dirección de restaurantes y servicios de catering."
                        data-id="7"
                        data-skills="Cocina Internacional · Pastelería Fina · Ingeniería de Menú · Gestión Gastronómica"
                        data-title="Gastronomía" data-turnos="Mañana y Tarde">
                        <div class="flex items-start sm:items-center gap-5">
                            <span
                                class="text-2xl sm:text-3xl font-extrabold text-slate-300 group-hover:text-[#015001] transition-colors w-10">07</span>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3
                                        class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-[#015001] transition-colors">
                                        Gastronomía</h3>
                                    <span
                                        class="hidden sm:inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-[#015001]">3
                                        Años</span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-500 line-clamp-1">Cocina boliviana e
                                    internacional, repostería, nutrición y gerencia integral de establecimientos
                                    culinarios.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 sm:self-center self-end">
                            <span
                                class="text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1 rounded-full">Turno
                                Mañana y Tarde</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#015001] group-hover:translate-x-1 transition-transform">
                                <span>Ver Perfil</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                    <!-- Carrera 08 -->
                    <div class="degree-row group py-5 px-4 rounded-xl transition-all duration-200 hover:bg-emerald-50/60 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        data-desc="Aplica sistemas computacionales en sectores extractivos, agroindustriales y de telecomunicaciones. Opera y programa sistemas automatizados con PLC, robótica, sensores industriales y control de procesos productivos."
                        data-id="8"
                        data-skills="Controladores PLC · Robótica Industrial · Sistemas SCADA · Instrumentación"
                        data-title="Informática Industrial" data-turnos="Tarde y Noche">
                        <div class="flex items-start sm:items-center gap-5">
                            <span
                                class="text-2xl sm:text-3xl font-extrabold text-slate-300 group-hover:text-[#015001] transition-colors w-10">08</span>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3
                                        class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-[#015001] transition-colors">
                                        Informática Industrial</h3>
                                    <span
                                        class="hidden sm:inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-[#015001]">3
                                        Años</span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-500 line-clamp-1">Automatización de líneas
                                    industriales, robótica aplicada, controladores PLC y sistemas ciberfísicos.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 sm:self-center self-end">
                            <span
                                class="text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1 rounded-full">Turno
                                Tarde y Noche</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#015001] group-hover:translate-x-1 transition-transform">
                                <span>Ver Perfil</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                    <!-- Carrera 09 -->
                    <div class="degree-row group py-5 px-4 rounded-xl transition-all duration-200 hover:bg-emerald-50/60 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        data-desc="Desempeña gestión operativa y administrativa en obras de infraestructura y edificación. Planificación, cómputos métricos, análisis de costos, supervisión de estructuras e instalaciones sanitarias y agua potable."
                        data-id="9"
                        data-skills="Cómputos y Presupuestos · Control de Calidad en Obra · Instalaciones Hidrosanitarias · AutoCAD Civil"
                        data-title="Construcción Civil" data-turnos="Mañana y Noche">
                        <div class="flex items-start sm:items-center gap-5">
                            <span
                                class="text-2xl sm:text-3xl font-extrabold text-slate-300 group-hover:text-[#015001] transition-colors w-10">09</span>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3
                                        class="text-lg sm:text-xl font-bold text-slate-900 group-hover:text-[#015001] transition-colors">
                                        Construcción Civil</h3>
                                    <span
                                        class="hidden sm:inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-[#015001]">3
                                        Años</span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-500 line-clamp-1">Dirección operativa de obras
                                    civiles, diseño de instalaciones, presupuestos y control de estructuras.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 sm:self-center self-end">
                            <span
                                class="text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1 rounded-full">Turno
                                Mañana y Noche</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#015001] group-hover:translate-x-1 transition-transform">
                                <span>Ver Perfil</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                </div>
                <!-- Sticky Preview Card for Hovered/Selected Degree (Right Column) -->
                <div class="lg:col-span-4 lg:sticky lg:top-28 h-fit">
                    <div
                        class="bg-gradient-to-br from-slate-900 to-[#002800] text-white rounded-3xl p-7 shadow-2xl border border-slate-800 relative overflow-hidden">
                        <div
                            class="absolute -right-8 -bottom-8 w-36 h-36 bg-emerald-600/10 rounded-full blur-2xl pointer-events-none">
                        </div>
                        <div class="flex items-center justify-between mb-4">
                            <span
                                class="text-[11px] uppercase tracking-widest font-extrabold text-emerald-400 bg-emerald-950/80 px-3 py-1 rounded-full border border-emerald-800/60">
                                Ficha Técnica
                            </span>
                            <span class="text-xs text-slate-400 font-semibold" id="preview-turnos">Turno Mañana y
                                Noche</span>
                        </div>
                        <h3 class="text-2xl font-extrabold text-white tracking-tight mb-3" id="preview-title">
                            Sistemas Informáticos
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed mb-6" id="preview-desc">
                            Se desempeña resolviendo y proponiendo soluciones de bases de datos, redes de comunicación
                            de datos, inteligencia de negocios y modelados de sistemas informáticos, integrando
                            tecnologías modernas de software y hardware.
                        </p>
                        <div class="border-t border-slate-800 pt-4 mb-6">
                            <span
                                class="text-[11px] tracking-wider uppercase font-bold text-slate-400 block mb-2">Áreas
                                de especialidad:</span>
                            <div class="text-xs font-medium text-emerald-200 bg-white/5 p-3 rounded-xl border border-white/10"
                                id="preview-skills">
                                Bases de Datos · Redes CISCO · Programación Web · Inteligencia Artificial
                            </div>
                        </div>
                        <div class="space-y-3">
                            <a class="w-full inline-flex items-center justify-center gap-2 bg-[#015001] hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider py-3.5 rounded-xl shadow-md transition-colors"
                                href="{{ route('postulantes.bienvenida') }}">
                                <span>Postular a esta Carrera</span>
                                <span class="material-symbols-outlined text-[16px]">send</span>
                            </a>
                            <p class="text-[11px] text-center text-slate-400">Título oficial en Provisión Nacional
                                respaldado por el Estado.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- 6. CARRERAS DESTACADAS ASIMÉTRICAS (COMPOSICIÓN EDITORIAL DE REVISTA) -->
    <section class="py-20 lg:py-28 bg-[#F4F6F4]">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12">
            <div class="max-w-2xl mb-12">
                <span class="text-xs font-bold tracking-widest uppercase text-[#015001] block mb-2">Vanguardia
                    Tecnológica</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight uppercase">
                    Especialidades con Mayor Demanda Laboral
                </h2>
                <p class="text-slate-600 text-sm sm:text-base mt-2">
                    Áreas estratégicas donde las industrias de Cochabamba y el país requieren técnicos calificados con
                    formación práctica desde el primer día.
                </p>
            </div>
            <!-- Asymmetric Editorial Grid -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-stretch">
                <!-- Bloque Grande 1: Sistemas Informáticos (Col 7) -->
                <div
                    class="md:col-span-7 rounded-3xl overflow-hidden shadow-xl bg-slate-900 relative min-h-[420px] flex flex-col justify-end p-8 group">
                    <img alt="Laboratorio de computación y redes ITSA"
                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuBp_WlgqDgazmH-z9oHGcEBSqUD9LWERlL5oGdNuKkkgl29oVa81qPJFJOkY-Wii7v5ISDciKujucmJkyqUkn3v5oKSOguJOKZe1WDaAuQem_eeMU7RrHrnNswohacKw3Qzs2-K-gYwb4y8k5mjdlnhP8X2iRvH-MZJ5J-Rajr6__RYW58umUaR9DzEcVYrZOQHtpCqLu48fHBctdWki1E46-Pix9KJQgmz6tmkUovbcYsw12lh7v9T" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
                    <div class="relative z-10 text-white space-y-3">
                        <span
                            class="px-3 py-1 rounded-full bg-[#015001] text-emerald-200 text-[10px] font-bold tracking-widest uppercase inline-block">
                            Tecnología &amp; Software
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Sistemas Informáticos
                        </h3>
                        <p class="text-sm text-slate-200 max-w-xl font-light leading-relaxed">
                            Laboratorios equipados con conectividad de alta velocidad para prácticas reales en
                            desarrollo web, administración de bases de datos SQL y arquitectura de servidores.
                        </p>
                        <div class="pt-2">
                            <a class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-300 hover:text-white transition-colors"
                                href="{{ route('postulantes.bienvenida') }}">
                                <span>Inscribirse ahora</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Bloque Vertical 2: Mecánica Automotriz (Col 5) -->
                <div
                    class="md:col-span-5 rounded-3xl overflow-hidden shadow-xl bg-slate-900 relative min-h-[420px] flex flex-col justify-end p-8 group">
                    <img alt="Taller automotriz con diagnóstico electrónico"
                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuA5eR0OKFQPDUw3KtucsRxZoFJkhmKIuuvgxEf0lyQXOaMnTQQdcVDi-NIfa5z4vAiycONcu8cXhhWIlco1YFh2WWPScpaXoRDLUNL2Iya1k2dttMQ1i-nBlM7XGnGxRTCjtjUZXhaPjBaCzb2ple1vz6Ko9eDVLaOw3lYNHOZmFEYwEFZRo3v1xhTt8FR6GHR8trkeLUv_j7J5NJ-IFtAUpZkOYBK_eWcYDOEfCUaINJMd_TcZGPTq" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
                    <div class="relative z-10 text-white space-y-3">
                        <span
                            class="px-3 py-1 rounded-full bg-slate-800 text-emerald-300 text-[10px] font-bold tracking-widest uppercase inline-block border border-slate-700">
                            Sector Automotriz
                        </span>
                        <h3 class="text-2xl font-extrabold tracking-tight text-white">Mecánica Automotriz</h3>
                        <p class="text-sm text-slate-200 font-light leading-relaxed">
                            Manejo de instrumental de precisión, escáner automotriz y bancos de prueba de inyección en
                            talleres propios.
                        </p>
                        <div class="pt-2">
                            <a class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-300 hover:text-white transition-colors"
                                href="{{ route('postulantes.bienvenida') }}">
                                <span>Ver requisitos</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Bloque Horizontal 3: Gastronomía (Col 12) -->
                <div
                    class="md:col-span-12 rounded-3xl overflow-hidden shadow-xl bg-slate-900 relative min-h-[300px] flex flex-col justify-end p-8 sm:p-10 group">
                    <img alt="Cocina taller industrial de gastronomía"
                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuAEVXvASGaSfATu1i-u3yo5iikQVgz8juLW03HnXQazCvdZ1hv51Ylv1QEByW48KuOx69oWwIcG4CMOGGThM5Ytu4m4qmVISLfbGmctbESfvUGmx9zvOL8_2ZwwLNhSQGYViwy5mouwe1FY5iyIrvi7l1dl3L7RYQ9_vW3dr3R6wt8JTYOE0tPB49755W-htWLJQow9yLf8KdEEQbv2YhWW-W1JRNTcbM4Xz4t56y4C0eG9kzPOQrGL" />
                    <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/50 to-transparent"></div>
                    <div class="relative z-10 text-white max-w-2xl space-y-3">
                        <span
                            class="px-3 py-1 rounded-full bg-amber-900/80 text-amber-200 text-[10px] font-bold tracking-widest uppercase inline-block border border-amber-700/60">
                            Gastronomía &amp; Servicios
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Gastronomía y Arte
                            Culinario</h3>
                        <p class="text-sm text-slate-200 font-light leading-relaxed">
                            Cocina industrial moderna para formación en cocina nacional, pastelería internacional,
                            gestión de eventos y dirección operativa de hoteles y restaurantes.
                        </p>
                        <div class="pt-2">
                            <a class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-300 hover:text-white transition-colors"
                                href="#contacto">
                                <span>Consultar horarios de taller</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- 7. PROCESO DE ADMISIÓN (SECCIÓN VERDE INSTITUCIONAL #015001) -->
    <section class="py-20 lg:py-28 bg-[#015001] text-white relative overflow-hidden" id="admisiones">
        <!-- Subtle Architectural Background Grids -->
        <div
            class="absolute inset-0 opacity-10 bg-[linear-gradient(to_right,#ffffff_1px,transparent_1px),linear-gradient(to_bottom,#ffffff_1px,transparent_1px)] bg-[size:4rem_4rem]">
        </div>
        <div class="absolute -right-24 -top-24 w-96 h-96 bg-emerald-400/20 rounded-full blur-3xl pointer-events-none">
        </div>
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 relative z-10">
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span
                    class="inline-block px-4 py-1.5 rounded-full bg-white/10 backdrop-blur border border-white/20 text-emerald-200 text-xs font-bold tracking-widest uppercase mb-4">
                    Convocatoria e Inscripciones 2026
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight uppercase">
                    Tu Ingreso Comienza Aquí
                </h2>
                <p class="text-emerald-100/90 text-sm sm:text-base mt-3 leading-relaxed">
                    Admisión e inscripción sin costo de matrícula. Educación técnica superior pública y gratuita al
                    servicio del pueblo boliviano.
                </p>
            </div>
            <!-- 5-Step Connected Timeline Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-14">
                <!-- Paso 01 -->
                <div
                    class="p-6 rounded-2xl bg-white/10 backdrop-blur border border-white/15 hover:bg-white/15 transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-3xl font-extrabold text-emerald-300 block mb-2">01</span>
                        <h3 class="text-base font-bold text-white mb-2">Elige tu Carrera</h3>
                        <p class="text-xs text-emerald-100/80 leading-relaxed">
                            Revisa las 9 opciones formativas y sus turnos disponibles (Mañana, Tarde o Noche).
                        </p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-300 pt-6">Paso
                        Inicial</span>
                </div>
                <!-- Paso 02 -->
                <div
                    class="p-6 rounded-2xl bg-white/10 backdrop-blur border border-white/15 hover:bg-white/15 transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-3xl font-extrabold text-emerald-300 block mb-2">02</span>
                        <h3 class="text-base font-bold text-white mb-2">Conoce Requisitos</h3>
                        <p class="text-xs text-emerald-100/80 leading-relaxed">
                            Diploma de Bachiller, Fotocopia de Cédula de Identidad y Certificado de Nacimiento. ¡Sin
                            límite de edad!
                        </p>
                    </div>
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider text-emerald-300 pt-6">Documentación</span>
                </div>
                <!-- Paso 03 -->
                <div
                    class="p-6 rounded-2xl bg-white/10 backdrop-blur border border-white/15 hover:bg-white/15 transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-3xl font-extrabold text-emerald-300 block mb-2">03</span>
                        <h3 class="text-base font-bold text-white mb-2">Realiza tu Proceso</h3>
                        <p class="text-xs text-emerald-100/80 leading-relaxed">
                            Registro oficial en línea a través del Sistema Orion institucional y Formulario 2 RITT.
                        </p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-300 pt-6">Sistema
                        Orion</span>
                </div>
                <!-- Paso 04 -->
                <div
                    class="p-6 rounded-2xl bg-white/10 backdrop-blur border border-white/15 hover:bg-white/15 transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-3xl font-extrabold text-emerald-300 block mb-2">04</span>
                        <h3 class="text-base font-bold text-white mb-2">Presenta Carpeta</h3>
                        <p class="text-xs text-emerald-100/80 leading-relaxed">
                            Entrega física de los documentos foliados en las ventanillas de secretaría académica del
                            ITSA.
                        </p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-300 pt-6">Ventanilla
                        ITSA</span>
                </div>
                <!-- Paso 05 -->
                <div
                    class="p-6 rounded-2xl bg-white/10 backdrop-blur border border-white/15 hover:bg-white/15 transition-all flex flex-col justify-between">
                    <div>
                        <span class="text-3xl font-extrabold text-emerald-300 block mb-2">05</span>
                        <h3 class="text-base font-bold text-white mb-2">Comienza a Estudiar</h3>
                        <p class="text-xs text-emerald-100/80 leading-relaxed">
                            Asignación oficial de aula y bienvenida al nuevo periodo lectivo de formación superior.
                        </p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-300 pt-6">¡Éxito
                        Académico!</span>
                </div>
            </div>
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a class="w-full sm:w-auto inline-flex items-center justify-center gap-3 bg-white text-[#015001] hover:bg-emerald-50 text-xs sm:text-sm font-bold uppercase tracking-wider px-8 py-4 rounded-xl shadow-xl transition-all transform hover:-translate-y-0.5"
                    href="https://orion.itsa.edu.bo/postulantes/registrarPostulante.xhtml" rel="noopener"
                    target="_blank">
                    <span>Iniciar Proceso de Admisión en Sistema Orion</span>
                    <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                </a>
                <a class="w-full sm:w-auto inline-flex items-center justify-center gap-3 bg-white/15 hover:bg-white/25 text-white border border-white/30 text-xs sm:text-sm font-bold uppercase tracking-wider px-8 py-4 rounded-xl transition-all"
                    href="#contacto">
                    <span>Consultar Dudas con Secretaría</span>
                    <span class="material-symbols-outlined text-[18px]">support_agent</span>
                </a>
            </div>
        </div>
    </section>
    <!-- 8. EXPERIENCIA ITSA ("MUCHO MÁS QUE UN AULA") - COLLAGE FOTOGRÁFICO MODERNO -->
    <section class="py-20 lg:py-28 bg-white" id="experiencia">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold tracking-widest uppercase text-[#015001] block mb-2">Metodología Basada
                    en la Práctica</span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight uppercase">
                    Mucho más que un aula
                </h2>
                <p class="text-slate-600 text-sm sm:text-base mt-3 leading-relaxed">
                    <strong class="font-bold text-slate-800">Aprender haciendo:</strong> Prácticas intensivas en
                    talleres y laboratorios equipados desde el primer semestre, complementadas con visitas técnicas al
                    parque industrial.
                </p>
            </div>
            <!-- Collage Fotográfico Moderno utilizando fotos reales del ITSA -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                <!-- Foto 1: Práctica Industrial (Col 6, Grande) -->
                <div
                    class="md:col-span-6 rounded-3xl overflow-hidden shadow-md bg-slate-100 relative min-h-[320px] group">
                    <img alt="Estudiantes en planta Coboce con cascos de protección"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        src="https://lh3.googleusercontent.com/aida/AEtjO1VSmewtDy7lPtVUMhNMuGg2atDQyEDCAEMLI94BAe33_xoVsl7dwWdKJviLjmCOmoIFOatx8ynuhi-MLpIS9duE7yA08dqDlOJESm5cDJfq8TT535F75Wn1P5LHgUTUBbFPxdeRdmEWVgkE0YrKSyQKHOx7fca9E-Cf9mGXrjy6d3Vi5z9b1Sr1LGSrl9YW7XHxOgCv6un1gao4ABjyHLRxNH2dX1eo3Mg5ZBvZU28lj02ReE-hp6W-dzo" />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent">
                    </div>
                    <div class="absolute bottom-6 left-6 right-6 text-white">
                        <span
                            class="text-[10px] font-bold uppercase tracking-widest text-emerald-300 block mb-1">Vinculación
                            Industrial Real</span>
                        <h3 class="text-lg font-bold">Visitas técnicas a industrias como Coboce y plantas de producción
                        </h3>
                    </div>
                </div>
                <!-- Foto 2: Topografía de Campo (Col 6) -->
                <div
                    class="md:col-span-6 rounded-3xl overflow-hidden shadow-md bg-slate-100 relative min-h-[320px] group">
                    <img alt="Estudiantes realizando levantamientos topográficos con teodolito"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        src="https://lh3.googleusercontent.com/aida/AEtjO1U_Izkq5o84SCPYO-PPnIeUsFOzawyo7u178mrd7tp4qgHbov2WtvMhzFGEpXCcpENVV5KKFyMBkjWl3JO6tVa3HXYAiTSgqcGtvhsfp-SbrCpuO8eGNMUJjDyLmN-FOBJqSi1IXrgZssqRugRfpFQoBUxYDBnJZ7hi7nFptlLOgTiepO3zaGNCmOX5CpSnk5rDAI2FNoD8a7Vmy9y94ZgoaMAn2rvCvwNaTM6HGFF35w8_WpWxHb3E_g" />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent">
                    </div>
                    <div class="absolute bottom-6 left-6 right-6 text-white">
                        <span
                            class="text-[10px] font-bold uppercase tracking-widest text-emerald-300 block mb-1">Geodesia
                            y Territorio</span>
                        <h3 class="text-lg font-bold">Levantamientos en terreno, fotogrametría y cartografía satelital
                        </h3>
                    </div>
                </div>
                <!-- Foto 3: Brochure Informativo - Oferta Integral (Col 4) -->
                <div
                    class="md:col-span-4 rounded-3xl overflow-hidden shadow-md bg-slate-100 relative min-h-[260px] group">
                    <img alt="Material informativo de carreras tecnológicas ITSA"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        src="https://lh3.googleusercontent.com/aida/AEtjO1XHB9DTxouOQBh1G5wfXspF80kyWgWsYc_0bvsif8tZ6VycIGOBqEp-R-_gqxnyZzqGBTCfz-vWoaoRBreifrYXI6wK_J-XggfVHl97M10-fI4IQKcZz2ZtdPnLS_KXrxqjv1yEsL27wWeWnw1ym9MRrLPMzo343Sz-zVOE-PqL1Nmy_5cnWaa8auAi5zc1TpSfaB9g1ZkFG4a_O-ZxejeI2XOywcnKKawUs-kuyqlSE3XxFIiPI80rKqM" />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent">
                    </div>
                    <div class="absolute bottom-5 left-5 right-5 text-white">
                        <span
                            class="text-[10px] font-bold uppercase tracking-widest text-emerald-300 block mb-0.5">Sistemas
                            e Industria</span>
                        <h3 class="text-base font-bold">Talleres tecnológicos propios</h3>
                    </div>
                </div>
                <!-- Foto 4: Brochure Formativo Técnico (Col 4) -->
                <div
                    class="md:col-span-4 rounded-3xl overflow-hidden shadow-md bg-slate-100 relative min-h-[260px] group">
                    <img alt="Folleto de carreras de construcción, contaduría y secretariado"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        src="https://lh3.googleusercontent.com/aida/AEtjO1WW1G68AHG-AOj2aLu6ofB5644Ftjiik2PjRnVheIubq5STl5mbFj4xXqFxNELiskxIIZo98336y825s-JPOQPEh-V82xUEj2-OJoGPYAvyAqA3WYTxYUFyH_KbB2SQgxiyCKkgLWaFPpj1Ovek7B6y2V3_sopI3uqbe0Itm5JSZUGRc6qGeDfWB0TDqMEE6JLmUnrPOUqofmWQJctI7jj3NUVMT20bdEnQYGuGGGqSKZo5J8D6AfulRtg" />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent">
                    </div>
                    <div class="absolute bottom-5 left-5 right-5 text-white">
                        <span
                            class="text-[10px] font-bold uppercase tracking-widest text-emerald-300 block mb-0.5">Gestión
                            y Construcción</span>
                        <h3 class="text-base font-bold">Gabinete de costos y cómputos</h3>
                    </div>
                </div>
                <!-- Foto 5: Campus Central y Comunidad (Col 4) -->
                <div
                    class="md:col-span-4 rounded-3xl overflow-hidden shadow-md bg-slate-100 relative min-h-[260px] group">
                    <img alt="Vida universitaria en el campus central de ITSA"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuBm7ydjmE0KycZPldV79YSpRCLBA3n7KGXcI2q-HWK1ary7Yy4jEae95lJ5eFgWn_12tk1pL0YPNUE-5IrJ3fZtJmBW5q9VB21KXfkDvOuhqjkXsbVLzOcOm5qRZ1gJVVLXT1PxMVtZNGZYss2H1sKw2k_2YYrDy9jA8vb6np778n3pKoPim63LmZ9sLw6y_6V8mCNu2NAd2kTRLnargE96KEukgDKIhOKQx5JquiAXm3I53QePm5VX" />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent">
                    </div>
                    <div class="absolute bottom-5 left-5 right-5 text-white">
                        <span
                            class="text-[10px] font-bold uppercase tracking-widest text-emerald-300 block mb-0.5">Vida
                            Universitaria</span>
                        <h3 class="text-base font-bold">Comunidad comprometida en Sacaba</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- 9. VIDA INSTITUCIONAL & ACTUALIDAD ITSA (EDITORIAL FORMATO PRENSA / REVISTA) -->
    <section class="py-20 lg:py-28 bg-[#FAFCFA] border-t border-slate-200" id="actualidad">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12">
            <!-- Section Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-14">
                <div>
                    <span class="text-xs font-bold tracking-widest uppercase text-[#015001] block mb-2">Comunicación
                        Institucional</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight uppercase">
                        Actualidad y Vida ITSA
                    </h2>
                </div>
                <a class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold tracking-wider uppercase text-[#015001] hover:text-[#003b01] group"
                    href="#actualidad">
                    <span>Ver todas las noticias y convocatorias</span>
                    <span
                        class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </a>
            </div>
            <!-- Editorial Magazine Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                <!-- Noticia Principal (Col 7) -->
                <article
                    class="lg:col-span-7 bg-white rounded-3xl overflow-hidden shadow-md border border-slate-200/80 flex flex-col justify-between group">
                    <div class="relative aspect-[16/9] overflow-hidden bg-slate-900">
                        <img alt="Exhibición Anual de Proyectos de Innovación Tecnológica"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuBm7ydjmE0KycZPldV79YSpRCLBA3n7KGXcI2q-HWK1ary7Yy4jEae95lJ5eFgWn_12tk1pL0YPNUE-5IrJ3fZtJmBW5q9VB21KXfkDvOuhqjkXsbVLzOcOm5qRZ1gJVVLXT1PxMVtZNGZYss2H1sKw2k_2YYrDy9jA8vb6np778n3pKoPim63LmZ9sLw6y_6V8mCNu2NAd2kTRLnargE96KEukgDKIhOKQx5JquiAXm3I53QePm5VX" />
                        <div class="absolute top-4 left-4">
                            <span
                                class="px-3 py-1 rounded-full bg-[#015001] text-white text-[11px] font-bold uppercase tracking-widest">
                                Evento Central
                            </span>
                        </div>
                    </div>
                    <div class="p-8 flex flex-col justify-between flex-1">
                        <div>
                            <div class="flex items-center gap-3 text-xs text-slate-400 font-semibold mb-3">
                                <span class="flex items-center gap-1"><span
                                        class="material-symbols-outlined text-[15px]">calendar_today</span> 14 Nov,
                                    2025</span>
                                <span>•</span>
                                <span>Comunidad Académica</span>
                            </div>
                            <h3
                                class="text-2xl font-extrabold text-slate-900 group-hover:text-[#015001] transition-colors leading-tight mb-3">
                                Exhibición Anual de Proyectos de Innovación Tecnológica y Feria de Puertas Abiertas
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Estudiantes de las nueve carreras presentaron ante empresarios y autoridades de Sacaba
                                proyectos productivos aplicados en automatización, software, topografía, gastronomía e
                                infraestructura.
                            </p>
                        </div>
                        <div class="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500">Instituto Tecnológico Sacaba</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#015001] group-hover:translate-x-1 transition-transform">
                                <span>Leer memoria completa</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </span>
                        </div>
                    </div>
                </article>
                <!-- 3 Noticias Secundarias (Col 5) -->
                <div class="lg:col-span-5 flex flex-col justify-between space-y-4">
                    <!-- Noticia 1 -->
                    <article
                        class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between text-xs text-slate-400 font-semibold mb-2">
                            <span
                                class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-[#015001] font-bold text-[10px] uppercase">Admisión
                                Oficial</span>
                            <span>7 Ene, 2026</span>
                        </div>
                        <h4
                            class="text-base font-bold text-slate-900 group-hover:text-[#015001] transition-colors mb-2">
                            Proceso de Admisión Estudiantes Nuevos Gestión 2026 - Registro Orion
                        </h4>
                        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                            Habilitado el registro digital para las 9 carreras técnicas. Conoce los requisitos de
                            entrega física de carpetas y homologación de títulos.
                        </p>
                    </article>
                    <!-- Noticia 2 -->
                    <article
                        class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between text-xs text-slate-400 font-semibold mb-2">
                            <span
                                class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px] uppercase">Egresados</span>
                            <span>18 Oct, 2025</span>
                        </div>
                        <h4
                            class="text-base font-bold text-slate-900 group-hover:text-[#015001] transition-colors mb-2">
                            Encuesta de Seguimiento e Inserción Laboral para Egresados y Titulados ITSA
                        </h4>
                        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                            Convocatoria oficial para titulados del ITSA con el fin de actualizar el índice de
                            empleabilidad en industrias del eje metropolitano de Cochabamba.
                        </p>
                    </article>
                    <!-- Noticia 3 -->
                    <article
                        class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between text-xs text-slate-400 font-semibold mb-2">
                            <span
                                class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px] uppercase">Capacitación</span>
                            <span>25 Sep, 2025</span>
                        </div>
                        <h4
                            class="text-base font-bold text-slate-900 group-hover:text-[#015001] transition-colors mb-2">
                            Jornada Técnica de Mecánica Automotriz y Nuevas Tecnologías Híbridas
                        </h4>
                        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                            Talleres prácticos con especialistas del sector automotriz sobre inyección electrónica de
                            última generación y motores ecológicos.
                        </p>
                    </article>
                </div>
            </div>
        </div>
    </section>
    <!-- 10. LÍNEA DE TIEMPO HISTÓRICA ("NUESTRA HISTORIA") -->
    <section class="py-20 lg:py-28 bg-[#F5F7F5] border-t border-slate-200" id="historia">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold tracking-widest uppercase text-[#015001] block mb-2">Memoria
                    Institucional</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight uppercase">
                    Nuestra Historia
                </h2>
                <p class="text-slate-600 text-sm sm:text-base mt-2">
                    Cerca de dos décadas forjando profesionales técnicos calificados con orgullo en el municipio de
                    Sacaba y el departamento de Cochabamba.
                </p>
            </div>
            <!-- Timeline Structure -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 relative">
                <!-- Hito 1: 2007 -->
                <div
                    class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex flex-col justify-between relative group hover:border-[#015001] transition-all">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-3xl font-extrabold text-[#015001]">2007</span>
                            <span
                                class="w-8 h-8 rounded-full bg-emerald-100 text-[#015001] flex items-center justify-center font-bold text-xs">01</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Fundación Oficial</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Fundado oficialmente el 19 de marzo de 2007, iniciando con una profunda vocación de servicio
                            técnico y social para la juventud de Sacaba.
                        </p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-6 block">19 de Marzo
                        2007</span>
                </div>
                <!-- Hito 2: 2009 -->
                <div
                    class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex flex-col justify-between relative group hover:border-[#015001] transition-all">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-3xl font-extrabold text-[#015001]">2009</span>
                            <span
                                class="w-8 h-8 rounded-full bg-emerald-100 text-[#015001] flex items-center justify-center font-bold text-xs">02</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Consolidación Académica</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Ampliación estructurada del plan de estudios hasta consolidar las 9 carreras técnicas
                            demandadas por el tejido productivo regional.
                        </p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-6 block">9 Carreras
                        Técnicas</span>
                </div>
                <!-- Hito 3: 2015 -->
                <div
                    class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex flex-col justify-between relative group hover:border-[#015001] transition-all">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-3xl font-extrabold text-[#015001]">2015</span>
                            <span
                                class="w-8 h-8 rounded-full bg-emerald-100 text-[#015001] flex items-center justify-center font-bold text-xs">03</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Resolución Ministerial Nº 995</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Homologación formal con la Resolución Ministerial R.M. Nº 995/2015, garantizando títulos
                            oficiales en Provisión Nacional.
                        </p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-6 block">Validez
                        Nacional</span>
                </div>
                <!-- Hito 4: 2026 -->
                <div
                    class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex flex-col justify-between relative group hover:border-[#015001] transition-all">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-3xl font-extrabold text-[#015001]">2026</span>
                            <span
                                class="w-8 h-8 rounded-full bg-emerald-100 text-[#015001] flex items-center justify-center font-bold text-xs">04</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Campus Digital y Moderno</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Laboratorios actualizados, implementación del Sistema Orion para trámites académicos
                            digitales y fortalecimiento docente continuo.
                        </p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-6 block">Presente y
                        Futuro</span>
                </div>
            </div>
        </div>
    </section>
    <!-- 11. LLAMADA FINAL INSTITUCIONAL POTENTE -->
    <section class="relative py-24 lg:py-32 overflow-hidden flex items-center">
        <!-- Background Campus Image with Deep Forest Green Overlay -->
        <div class="absolute inset-0 z-0">
            <img alt="Estudiantes en el patio del campus ITSA" class="w-full h-full object-cover object-center"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuBm7ydjmE0KycZPldV79YSpRCLBA3n7KGXcI2q-HWK1ary7Yy4jEae95lJ5eFgWn_12tk1pL0YPNUE-5IrJ3fZtJmBW5q9VB21KXfkDvOuhqjkXsbVLzOcOm5qRZ1gJVVLXT1PxMVtZNGZYss2H1sKw2k_2YYrDy9jA8vb6np778n3pKoPim63LmZ9sLw6y_6V8mCNu2NAd2kTRLnargE96KEukgDKIhOKQx5JquiAXm3I53QePm5VX" />
            <div class="absolute inset-0 bg-gradient-to-r from-[#002800] via-[#015001]/90 to-[#003b01]/95"></div>
            <div
                class="absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:24px_24px]">
            </div>
        </div>
        <div class="relative z-10 max-w-[1440px] mx-auto px-6 lg:px-12 w-full text-center text-white">
            <div class="max-w-3xl mx-auto space-y-6">
                <span
                    class="inline-block px-4 py-1.5 rounded-full bg-white/10 backdrop-blur border border-white/20 text-emerald-300 text-xs font-bold tracking-widest uppercase">
                    Admisiones Abiertas · Gestión Académica 2026
                </span>
                <h2 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight uppercase leading-tight">
                    Tu futuro profesional comienza aquí.
                </h2>
                <p class="text-base sm:text-lg text-emerald-100 font-light leading-relaxed max-w-2xl mx-auto">
                    Formación técnica superior de excelencia, 100% gratuita y con títulos oficiales en Provisión
                    Nacional otorgados por el Ministerio de Educación.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                    <a class="inline-flex items-center justify-center gap-2.5 bg-white text-[#015001] hover:bg-emerald-50 text-xs sm:text-sm font-bold uppercase tracking-wider px-8 py-4 rounded-xl shadow-xl transition-all transform hover:-translate-y-0.5"
                        href="#carreras">
                        <span>Ver Carreras</span>
                        <span class="material-symbols-outlined text-[18px]">format_list_bulleted</span>
                    </a>
                    <a class="inline-flex items-center justify-center gap-2.5 bg-[#003b01] hover:bg-[#002800] text-white border border-emerald-400/40 text-xs sm:text-sm font-bold uppercase tracking-wider px-8 py-4 rounded-xl shadow-xl transition-all transform hover:-translate-y-0.5"
                        href="https://orion.itsa.edu.bo/postulantes/registrarPostulante.xhtml" rel="noopener"
                        target="_blank">
                        <span>Admisiones 2026</span>
                        <span class="material-symbols-outlined text-[18px]">rocket_launch</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <!-- 12. CONTACTO Y VISÍTANOS ("VISÍTANOS") -->
    <section class="py-20 lg:py-28 bg-white border-t border-slate-200" id="contacto">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12">
            <div class="max-w-2xl mb-14">
                <span class="text-xs font-bold tracking-widest uppercase text-[#015001] block mb-2">Atención
                    Institucional</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight uppercase">
                    Visítanos y Contáctanos
                </h2>
                <p class="text-slate-600 text-sm sm:text-base mt-2">
                    Estamos a tu disposición para orientarte sobre cupos, turnos, convalidaciones y requisitos de
                    ingreso.
                </p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                <!-- Left: Official Data + Map Representation (Col 5) -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 shadow-sm space-y-5">
                        <h3 class="text-lg font-bold text-slate-900 border-b border-slate-200 pb-3">Información de
                            Secretaría</h3>
                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-[#015001] flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[22px]">location_on</span>
                            </div>
                            <div>
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Dirección
                                    Oficial</span>
                                <p class="text-sm font-semibold text-slate-800">Av. Circunvalación entre Ismael
                                    Céspedes y Granado</p>
                                <p class="text-xs text-slate-500">Sacaba, Cochabamba - Bolivia</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-[#015001] flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[22px]">call</span>
                            </div>
                            <div>
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Teléfono
                                    / WhatsApp Oficial</span>
                                <a class="text-sm font-bold text-[#015001] hover:underline"
                                    href="https://wa.me/59167598222" rel="noopener" target="_blank">
                                    67598222
                                </a>
                                <p class="text-xs text-slate-500">Atención de Lunes a Viernes en horario de oficina</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-[#015001] flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[22px]">mail</span>
                            </div>
                            <div>
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Correo
                                    Institucional</span>
                                <a class="text-sm font-semibold text-slate-800 hover:text-[#015001] break-all"
                                    href="mailto:institutotecnologicoitsa@gmail.com">
                                    institutotecnologicoitsa@gmail.com
                                </a>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-[#015001] flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[22px]">schedule</span>
                            </div>
                            <div>
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Turnos
                                    de Estudio</span>
                                <p class="text-sm font-semibold text-slate-800">Mañana (07:30 a 12:30)</p>
                                <p class="text-sm font-semibold text-slate-800">Tarde (13:30 a 18:30)</p>
                                <p class="text-sm font-semibold text-slate-800">Noche (18:30 a 22:30)</p>
                            </div>
                        </div>
                    </div>
                    <!-- Stylized Campus Location Map Box -->
                    <div
                        class="rounded-3xl overflow-hidden border border-slate-200 shadow-sm relative h-56 bg-slate-100 flex items-end p-4 group">
                        <img alt="Mapa satelital de Sacaba Cochabamba"
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuBejb3rm6UpHlzZQ6uBqEsZ486xV8X2xmmLEIqhF4ENmDPsvmS7DFNokyhoPutHRTePBhfpOK2kex2bEEx44GYDw6DCQxoCCBSaOvgpIcueVPuH7SOQP_eh2DjAieEy6Z1Sc4tGvcr8jeC33_qw6HlIyvZGoeP_faTfkOv7CDR0q7L4UR3bNCt2t8WYZ2GwOVp2u3QzwhaJS1nHzzSDPQOYnDdtyoR7wpGVcXZ-MTX1Ki-JHQrpUzXz" />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>
                        <div
                            class="relative z-10 w-full bg-white/95 backdrop-blur rounded-2xl p-3.5 flex items-center justify-between shadow-lg">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-[#015001]">pin_drop</span>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900">Campus ITSA Sacaba</h4>
                                    <p class="text-[10px] text-slate-500">Cochabamba, Bolivia</p>
                                </div>
                            </div>
                            <a class="text-xs font-bold text-[#015001] hover:underline" href="https://maps.google.com"
                                rel="noopener" target="_blank">
                                Abrir en Google Maps
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Right: Modern Clean Vocational Inquiry Form (Col 7) -->
                <div class="lg:col-span-7">
                    <div class="p-8 sm:p-10 rounded-3xl bg-[#FAFCFA] border border-slate-200/90 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Envíanos tu consulta vocacional</h3>
                        <p class="text-xs sm:text-sm text-slate-500 mb-6">Completa tus datos y secretaría académica se
                            comunicará directamente a tu número de WhatsApp para brindarte atención personalizada.</p>
                        <form class="space-y-4" id="contact-form"
                            onsubmit="event.preventDefault(); document.getElementById('form-success').classList.remove('hidden');">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                        for="form-name">Nombre Completo *</label>
                                    <input
                                        class="w-full px-4 py-3 rounded-xl bg-white border border-slate-300 text-sm text-slate-800 placeholder-slate-400 focus:border-[#015001] focus:ring-1 focus:ring-[#015001] outline-none transition-all"
                                        id="form-name" placeholder="Ej. Juan Pérez Miranda" required=""
                                        type="text" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                        for="form-tel">Celular / WhatsApp *</label>
                                    <input
                                        class="w-full px-4 py-3 rounded-xl bg-white border border-slate-300 text-sm text-slate-800 placeholder-slate-400 focus:border-[#015001] focus:ring-1 focus:ring-[#015001] outline-none transition-all"
                                        id="form-tel" placeholder="Ej. 67598222" required="" type="tel" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                        for="form-carrera">Carrera de Interés *</label>
                                    <select
                                        class="w-full px-4 py-3 rounded-xl bg-white border border-slate-300 text-sm text-slate-800 focus:border-[#015001] focus:ring-1 focus:ring-[#015001] outline-none transition-all"
                                        id="form-carrera" required="">
                                        <option value="">Seleccionar carrera...</option>
                                        <option value="sistemas">Sistemas Informáticos</option>
                                        <option value="secretariado">Secretariado Ejecutivo</option>
                                        <option value="administracion">Administración de Empresas</option>
                                        <option value="contaduria">Contaduría General</option>
                                        <option value="topografia">Topografía y Geodesia</option>
                                        <option value="mecanica">Mecánica Automotriz</option>
                                        <option value="gastronomia">Gastronomía</option>
                                        <option value="industrial">Informática Industrial</option>
                                        <option value="construccion">Construcción Civil</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                        for="form-turno">Turno Preferido *</label>
                                    <select
                                        class="w-full px-4 py-3 rounded-xl bg-white border border-slate-300 text-sm text-slate-800 focus:border-[#015001] focus:ring-1 focus:ring-[#015001] outline-none transition-all"
                                        id="form-turno" required="">
                                        <option value="">Seleccionar turno...</option>
                                        <option value="manana">Turno Mañana</option>
                                        <option value="tarde">Turno Tarde</option>
                                        <option value="noche">Turno Noche</option>
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                    for="form-msg">Tu Mensaje o Consulta</label>
                                <textarea
                                    class="w-full p-4 rounded-xl bg-white border border-slate-300 text-sm text-slate-800 placeholder-slate-400 focus:border-[#015001] focus:ring-1 focus:ring-[#015001] outline-none transition-all resize-none"
                                    id="form-msg" placeholder="Escribe aquí tus dudas sobre requisitos, fechas de examen o convalidaciones..."
                                    rows="4"></textarea>
                            </div>
                            <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <button
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 bg-[#015001] hover:bg-[#003b01] text-white text-xs sm:text-sm font-bold uppercase tracking-wider px-8 py-3.5 rounded-xl shadow-md transition-all"
                                    type="submit">
                                    <span>Enviar Consulta</span>
                                    <span class="material-symbols-outlined text-[18px]">send</span>
                                </button>
                                <span class="text-xs text-slate-400 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px] text-emerald-600">lock</span>
                                    Tus datos están protegidos institucionalmente.
                                </span>
                            </div>
                            <div class="hidden mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-[#015001] text-xs font-semibold flex items-center gap-2"
                                id="form-success">
                                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                                <span>¡Mensaje registrado exitosamente! La secretaría del ITSA responderá a tu número a
                                    la brevedad.</span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- 13. FOOTER INSTITUCIONAL PREMIUM -->
    <footer class="bg-[#111111] text-white border-t border-slate-800">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 pt-16 pb-12">
            <!-- Top Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 pb-12 border-b border-slate-800">
                <!-- Columna 1: Identidad ITSA (Col 4) -->
                <div class="lg:col-span-4 space-y-4">
                    <div class="flex items-center gap-3">
                        <img alt="Logo ITSA" class="h-10 w-auto object-contain brightness-0 invert"
                            src="{{ asset('images/logo.png') }}" />
                        <div>
                            <span class="text-xl font-extrabold tracking-tight text-white block">ITSA</span>
                            <span class="text-[10px] tracking-widest uppercase font-semibold text-emerald-400">R.M. Nº
                                995/2015</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
                        Instituto Tecnológico Sacaba. Institución fiscal y gratuita de formación técnica superior al
                        servicio de la juventud y la industria boliviana.
                    </p>
                    <div class="pt-2">
                        <span class="text-xs font-bold tracking-widest uppercase text-emerald-400 block mb-1">
                            "Formación técnica para el futuro"
                        </span>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <a class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-700/80 flex items-center justify-center text-slate-300 hover:text-white transition-colors"
                            href="https://www.facebook.com/itsacaba" rel="noopener" target="_blank"
                            title="Facebook Oficial ITSA">
                            <span class="material-symbols-outlined text-[18px]">public</span>
                        </a>
                        <a class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-700/80 flex items-center justify-center text-slate-300 hover:text-white transition-colors"
                            href="https://www.tiktok.com/@useritsa" rel="noopener" target="_blank"
                            title="TikTok Oficial ITSA">
                            <span class="material-symbols-outlined text-[18px]">video_library</span>
                        </a>
                        <a class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-700/80 flex items-center justify-center text-slate-300 hover:text-white transition-colors"
                            href="mailto:institutotecnologicoitsa@gmail.com" title="Correo Electrónico">
                            <span class="material-symbols-outlined text-[18px]">mail</span>
                        </a>
                    </div>
                </div>
                <!-- Columna 2: Navegación Institucional (Col 2) -->
                <div class="lg:col-span-2 space-y-3">
                    <h4 class="text-xs font-bold tracking-widest uppercase text-emerald-400">Navegación</h4>
                    <ul class="space-y-2 text-xs text-slate-400 font-medium">
                        <li><a class="hover:text-white transition-colors" href="#inicio">Inicio</a></li>
                        <li><a class="hover:text-white transition-colors" href="#institucion">Institución</a></li>
                        <li><a class="hover:text-white transition-colors" href="#carreras">Oferta Académica</a></li>
                        <li><a class="hover:text-white transition-colors"
                                href="{{ route('postulantes.bienvenida') }}">Admisiones 2026</a></li>
                        <li><a class="hover:text-white transition-colors" href="#experiencia">Experiencia ITSA</a>
                        </li>
                        <li><a class="hover:text-white transition-colors" href="#actualidad">Actualidad</a></li>
                        <li><a class="hover:text-white transition-colors" href="#contacto">Contacto</a></li>
                    </ul>
                </div>
                <!-- Columna 3: Las 9 Carreras Técnicas (Col 3) -->
                <div class="lg:col-span-3 space-y-3">
                    <h4 class="text-xs font-bold tracking-widest uppercase text-emerald-400">9 Carreras Técnicas</h4>
                    <ul class="space-y-1.5 text-xs text-slate-400 font-medium">
                        <li><a class="hover:text-white transition-colors" href="#carreras">Sistemas Informáticos</a>
                        </li>
                        <li><a class="hover:text-white transition-colors" href="#carreras">Secretariado Ejecutivo</a>
                        </li>
                        <li><a class="hover:text-white transition-colors" href="#carreras">Administración de
                                Empresas</a></li>
                        <li><a class="hover:text-white transition-colors" href="#carreras">Contaduría General</a></li>
                        <li><a class="hover:text-white transition-colors" href="#carreras">Topografía y Geodesia</a>
                        </li>
                        <li><a class="hover:text-white transition-colors" href="#carreras">Mecánica Automotriz</a>
                        </li>
                        <li><a class="hover:text-white transition-colors" href="#carreras">Gastronomía</a></li>
                        <li><a class="hover:text-white transition-colors" href="#carreras">Informática Industrial</a>
                        </li>
                        <li><a class="hover:text-white transition-colors" href="#carreras">Construcción Civil</a></li>
                    </ul>
                </div>
                <!-- Columna 4: Contacto y Turnos (Col 3) -->
                <div class="lg:col-span-3 space-y-3">
                    <h4 class="text-xs font-bold tracking-widest uppercase text-emerald-400">Campus Sacaba</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li class="flex items-start gap-2">
                            <span
                                class="material-symbols-outlined text-[16px] text-emerald-400 shrink-0 mt-0.5">location_on</span>
                            <span>Av. Circunvalación entre Ismael Céspedes y Granado, Sacaba</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-emerald-400 shrink-0">call</span>
                            <span>67598222</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-emerald-400 shrink-0">mail</span>
                            <span class="break-all">institutotecnologicoitsa@gmail.com</span>
                        </li>
                        <li class="pt-2 border-t border-slate-800 text-[11px] text-slate-400">
                            <span class="font-bold text-slate-300 block mb-0.5">Turnos Oficiales:</span>
                            Mañana, Tarde y Noche
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Bottom Baseline Copyright -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>© 2026 Instituto Tecnológico Sacaba. Todos los derechos reservados. R.M. 995/2015.</p>
                <p class="text-emerald-400/90 font-medium">Ministerio de Educación · Estado Plurinacional de Bolivia
                </p>
            </div>
        </div>
    </footer>
    <!-- 14. ANIMACIONES Y MICROINTERACCIONES JAVASCRIPT -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Sticky Navbar Dynamic Blur and Shadow on Scroll
            const header = document.getElementById('site-header');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 20) {
                    header.classList.add('glass-nav-scrolled');
                } else {
                    header.classList.remove('glass-nav-scrolled');
                }
            });

            // 2. Interactive 9 Degree List Preview Updater
            const degreeRows = document.querySelectorAll('#degrees-list .degree-row');
            const previewTitle = document.getElementById('preview-title');
            const previewDesc = document.getElementById('preview-desc');
            const previewTurnos = document.getElementById('preview-turnos');
            const previewSkills = document.getElementById('preview-skills');

            degreeRows.forEach(row => {
                row.addEventListener('mouseenter', () => {
                    degreeRows.forEach(r => r.classList.remove('bg-emerald-50/90'));
                    row.classList.add('bg-emerald-50/90');

                    const title = row.getAttribute('data-title');
                    const desc = row.getAttribute('data-desc');
                    const turnos = row.getAttribute('data-turnos');
                    const skills = row.getAttribute('data-skills');

                    if (previewTitle) previewTitle.textContent = title;
                    if (previewDesc) previewDesc.textContent = desc;
                    if (previewTurnos) previewTurnos.textContent = 'Turno ' + turnos;
                    if (previewSkills) previewSkills.textContent = skills;
                });
            });

            // 3. Stat Counter Trigger on Intersection Observer
            const statElements = document.querySelectorAll('.stat-counter');
            let animated = false;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !animated) {
                        animated = true;
                        statElements.forEach(el => {
                            const target = parseInt(el.getAttribute('data-target'), 10);
                            let count = 0;
                            const duration = 1200;
                            const stepTime = Math.max(Math.floor(duration / target), 30);

                            const timer = setInterval(() => {
                                count++;
                                el.textContent = count < 10 ? '0' + count : count;
                                if (count >= target) {
                                    clearInterval(timer);
                                    el.textContent = target < 10 ? '0' + target :
                                        target;
                                }
                            }, stepTime);
                        });
                    }
                });
            }, {
                threshold: 0.3
            });

            const statsSection = document.querySelector('.stat-counter');
            if (statsSection) {
                observer.observe(statsSection);
            }
        });
    </script>
</body>

</html>
