<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\OrgSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCompanyIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $role = Role::findOrCreate('Owner');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_home_shares_company_names_and_contact_from_database(): void
    {
        Company::query()->create([
            'name' => 'شرکت نمونه',
            'name_en' => 'Sample Company',
            'phone' => '070 111 2222',
            'email' => 'hello@sample.test',
            'address' => 'کابل',
            'address_en' => 'Kabul',
            'base_currency' => 'USD',
            'is_active' => true,
        ]);

        $this->withUnencryptedCookies(['locale' => 'fa'])
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('publicCompany.name', 'شرکت نمونه')
                ->where('publicCompany.name_en', 'Sample Company')
                ->where('publicCompany.name_primary', 'شرکت نمونه')
                ->where('publicCompany.phone', '070 111 2222')
                ->where('publicCompany.email', 'hello@sample.test')
                ->where('publicCompany.address', 'کابل')
            );

        $this->withUnencryptedCookies(['locale' => 'en'])
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('publicCompany.name', 'Sample Company')
                ->where('publicCompany.address', 'Kabul')
            );
    }

    public function test_updating_company_settings_updates_public_identity_and_branding(): void
    {
        $user = $this->owner();

        Company::query()->create([
            'name' => 'Old',
            'name_en' => 'Old En',
            'base_currency' => 'USD',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('settings.company.update'), [
                'name' => 'نام جدید',
                'name_en' => 'New Name',
                'phone' => '079 000 1111',
                'email' => 'contact@new.test',
                'address' => 'هرات',
                'address_en' => 'Herat',
                'base_currency' => 'USD',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('نام جدید', OrgSetting::getValue('app_name'));
        $this->assertSame('New Name', OrgSetting::getValue('app_name_en'));

        $this->withUnencryptedCookies(['locale' => 'en'])
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('name', 'New Name')
                ->where('publicCompany.name_en', 'New Name')
                ->where('publicCompany.email', 'contact@new.test')
                ->where('publicCompany.phone', '079 000 1111')
                ->where('publicCompany.address', 'Herat')
            );
    }

    public function test_updating_appearance_names_syncs_company_profile(): void
    {
        $user = $this->owner();
        $company = Company::query()->create([
            'name' => 'Old',
            'name_en' => 'Old En',
            'base_currency' => 'USD',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('settings.general.update'), [
                'app_name' => 'برند نو',
                'app_name_en' => 'New Brand',
                'default_locale' => 'fa',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $company->refresh();
        $this->assertSame('برند نو', $company->name);
        $this->assertSame('New Brand', $company->name_en);
    }
}
