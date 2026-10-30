<?php

namespace Tests\Feature;

use App\Models\Instituto;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstituteBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_institute_name_logo_and_green_theme_appear_on_login_and_dashboard(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('instituto/itsa-logo.png', 'logo');

        Instituto::create([
            'nombreinstituto' => 'Instituto Técnico Superior ITSA',
            'abreviatura' => 'ITSA',
            'logo' => 'instituto/itsa-logo.png',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Instituto Técnico Superior ITSA')
            ->assertSee('<span class="itsa-brand-word">ITSA</span>', false)
            ->assertDontSee('ITSA-DEMO')
            ->assertSee('/storage/instituto/itsa-logo.png', false)
            ->assertSee('css/itsa-theme.css', false);

        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'dni' => '11111111',
            'tipo' => 'admin',
        ]);
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_branding',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee(' | Instituto Técnico Superior ITSA')
            ->assertSee('Bienvenido a Instituto Técnico Superior ITSA')
            ->assertSee('/storage/instituto/itsa-logo.png', false)
            ->assertSee('css/itsa-theme.css', false);
    }

    public function test_itsa_branding_has_a_logo_fallback_when_institute_logo_is_missing(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('ITSA')
            ->assertSee('images/itsa-mark.svg', false)
            ->assertSee('css/itsa-theme.css', false);
    }

    public function test_welcome_page_uses_the_institute_name_and_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('instituto/itsa-welcome.png', 'logo');
        Instituto::create([
            'nombreinstituto' => 'Instituto ITSA',
            'logo' => 'instituto/itsa-welcome.png',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Instituto ITSA</title>', false)
            ->assertSee('src="http://localhost/storage/instituto/itsa-welcome.png"', false)
            ->assertSee('Plataforma de gestión académica e institucional.');
    }
}
