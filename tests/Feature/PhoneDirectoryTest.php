<?php

namespace Tests\Feature;

use App\Livewire\Admin\PhoneDirectory\Index as AdminPhoneDirectory;
use App\Livewire\Admin\Users\Index as AdminUsers;
use App\Livewire\Auth\Login;
use App\Livewire\PhoneDirectory;
use App\Models\PhoneBuilding;
use App\Models\PhoneEntry;
use App\Models\PhoneFloor;
use App\Models\User;
use App\Services\PhoneDirectoryService;
use Database\Seeders\PhoneDirectorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhoneDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PhoneDirectorySeeder::class]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
    }

    public function test_guest_sees_the_first_building_and_can_switch_tabs(): void
    {
        $buildingB = PhoneBuilding::where('name', 'Building B')->firstOrFail();

        $this->get('/')
            ->assertOk()
            ->assertSeeText('Building A')
            ->assertSeeText('MAYETH MARQUEZ')
            ->assertDontSeeText('DANNY MENDEZ');

        Livewire::test(PhoneDirectory::class)
            ->call('selectBuilding', $buildingB->id)
            ->assertSeeText('DANNY MENDEZ')
            ->assertDontSeeText('MAYETH MARQUEZ');
    }

    public function test_search_looks_across_every_building(): void
    {
        Livewire::test(PhoneDirectory::class)
            ->set('search', 'jasmine') // Building B, while Building A is the open tab
            ->assertSeeText('JASMINE')
            ->assertSeeText('Building B')
            ->assertDontSeeText('MAYETH MARQUEZ');

        Livewire::test(PhoneDirectory::class)
            ->set('search', '1820') // shared local, two lines on Building A's 8th floor plus the 7th
            ->assertSeeText('SIMOUN / STEPH')
            ->assertSeeText('JACOB')
            ->assertSeeText('PRINCESS DANOS');
    }

    public function test_consecutive_departments_and_locals_merge_like_the_printed_sheet(): void
    {
        $floor = PhoneFloor::where('name', '8th Floor')
            ->whereHas('building', fn ($q) => $q->where('name', 'Building A'))
            ->firstOrFail();

        $rows = collect(app(PhoneDirectoryService::class)->rows($floor->entries))
            ->mapWithKeys(fn ($row) => [$row['entry']->name => [$row['departmentSpan'], $row['localSpan']]]);

        $this->assertSame([1, 2], $rows['SIMOUN / STEPH']);          // MARKETING alone, 1820 shared with PR
        $this->assertSame([1, 0], $rows['JACOB']);                   // local covered by the row above
        $this->assertSame([2, 2], $rows['MONIQUE / FRANZ / SHALOM']); // GRAPHICS x2, 1826 x2
        $this->assertSame([0, 0], $rows['VHON / ROSELLE']);
    }

    public function test_admin_can_manage_buildings_floors_and_lines(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AdminPhoneDirectory::class)
            ->call('openCreateBuilding')
            ->set('buildingForm.name', 'Building C')
            ->set('buildingForm.trunkline', '8 521-0000')
            ->call('save')
            ->assertHasNoErrors();

        $building = PhoneBuilding::where('name', 'Building C')->firstOrFail();
        $this->assertSame($building->id, $component->get('buildingId'));
        $this->assertTrue($building->last_updated_on->isToday());

        $component->call('openCreateFloor')
            ->set('floorForm.name', '2nd Floor')
            ->set('floorForm.access_code', '204')
            ->call('save')
            ->assertHasNoErrors();

        $floor = $building->floors()->firstOrFail();
        $this->assertSame('204', $floor->access_code);

        $component->call('startAddEntry', $floor->id)
            ->set('entryForm.department', 'IT')
            ->set('entryForm.name', 'Ana')
            ->set('entryForm.local', '2201')
            ->call('saveEntry')
            ->assertHasNoErrors()
            ->call('startAddEntry', $floor->id)
            ->set('entryForm.department', 'IT')
            ->set('entryForm.name', 'Ben')
            ->set('entryForm.local', '2202')
            ->call('saveEntry');

        $this->assertSame(['Ana', 'Ben'], $floor->entries()->pluck('name')->all());

        $ben = PhoneEntry::where('name', 'Ben')->firstOrFail();
        $component->call('moveEntry', $ben->id, -1);
        $this->assertSame(['Ben', 'Ana'], $floor->entries()->pluck('name')->all());

        $component->call('startEditEntry', $ben->id)
            ->set('entryForm.local', '2299')
            ->call('saveEntry');
        $this->assertSame('2299', $ben->fresh()->local);

        $component->call('deleteEntry', $ben->id);
        $this->assertDatabaseMissing('phone_entries', ['id' => $ben->id]);

        $component->call('deleteBuilding', $building->id);
        $this->assertDatabaseMissing('phone_buildings', ['id' => $building->id]);
        $this->assertDatabaseMissing('phone_floors', ['id' => $floor->id]);
    }

    public function test_a_line_needs_at_least_one_field(): void
    {
        $floor = PhoneFloor::firstOrFail();

        Livewire::actingAs($this->admin)->test(AdminPhoneDirectory::class)
            ->call('startAddEntry', $floor->id)
            ->call('saveEntry')
            ->assertHasErrors('entryForm.department');
    }

    public function test_editing_a_line_bumps_the_building_last_update(): void
    {
        $building = PhoneBuilding::where('name', 'Building A')->firstOrFail();
        $this->assertSame('2026-08-01', $building->last_updated_on->toDateString());

        $entry = $building->floors()->first()->entries()->first();

        Livewire::actingAs($this->admin)->test(AdminPhoneDirectory::class)
            ->call('startEditEntry', $entry->id)
            ->set('entryForm.name', 'NEW NAME')
            ->call('saveEntry');

        $this->assertTrue($building->fresh()->last_updated_on->isToday());
    }

    public function test_super_admin_can_create_an_admin_who_can_sign_in(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        Livewire::actingAs($superAdmin)->test(AdminUsers::class)
            ->call('openCreate')
            ->set('form.name', 'Front Desk')
            ->set('form.email', 'frontdesk@onecherry.group')
            ->set('form.role', 'Admin')
            ->set('form.password', 'secret-pass')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'frontdesk@onecherry.group')->firstOrFail();
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertFalse($user->can('manage employees'));
        $this->assertTrue($user->can('manage phone directory'));
    }

    public function test_super_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        Livewire::actingAs($superAdmin)->test(AdminUsers::class)
            ->call('openEdit', $superAdmin->id)
            ->set('form.role', 'Admin')
            ->set('form.is_active', false)
            ->call('save');

        $superAdmin->refresh();
        $this->assertTrue($superAdmin->hasRole('Super Admin'));
        $this->assertTrue($superAdmin->is_active);
    }

    public function test_inactive_users_cannot_sign_in(): void
    {
        User::factory()->create(['email' => 'off@onecherry.group', 'password' => 'password', 'is_active' => false])
            ->assignRole('Admin');

        Livewire::test(Login::class)
            ->set('email', 'off@onecherry.group')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertHasErrors('email');

        $this->assertGuest();
    }
}
