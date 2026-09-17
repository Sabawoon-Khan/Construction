<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use App\Models\Employee;
use App\Models\FieldReport;
use App\Models\Partner;
use App\Models\PortfolioProject;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoProjectsSeeder extends Seeder
{
    public function run(): void
    {
        if (Project::query()->where('code', '1')->exists()) {
            return;
        }

        $adminId = User::query()->where('email', 'admin@yaqeen.tech')->value('id');
        $siteSupervisor = User::query()->where('email', 'staff@yaqeen.tech')->first();

        $khairKhana = $this->project([
            'code' => '1',
            'name' => 'Khair Khana Residences',
            'type' => 'apartment',
            'location' => 'Khair Khana, District 11, Kabul',
            'start_date' => '2024-04-15',
            'expected_completion_date' => '2026-12-20',
            'estimated_budget' => 2_450_000,
            'estimated_revenue' => 3_180_000,
            'progress_percent' => 68,
            'status' => 'under_construction',
            'description' => 'Eight-storey residential tower with 24 apartments, ground-floor shops, and basement parking. Structure is poured through floor 6; finishing starts on floors 1–3 this quarter.',
            'user_id' => $adminId,
        ]);

        $shahrNow = $this->project([
            'code' => '2',
            'name' => 'Shahr-e-Naw Tower',
            'type' => 'apartment',
            'location' => 'Shahr-e-Naw, District 10, Kabul',
            'start_date' => '2023-06-01',
            'expected_completion_date' => '2026-03-30',
            'estimated_budget' => 1_820_000,
            'estimated_revenue' => 2_540_000,
            'progress_percent' => 91,
            'status' => 'ready_for_sale',
            'description' => 'Corner tower beside the main Shahr-e-Naw market. Facade and interiors complete. Handover of sold units begins after municipality inspection.',
            'user_id' => $adminId,
        ]);

        $darulaman = $this->project([
            'code' => '3',
            'name' => 'Darulaman Garden Villas',
            'type' => 'villa',
            'location' => 'Darulaman, District 6, Kabul',
            'start_date' => '2025-11-01',
            'expected_completion_date' => '2027-09-15',
            'estimated_budget' => 1_150_000,
            'estimated_revenue' => 1_640_000,
            'progress_percent' => 14,
            'status' => 'planning',
            'description' => 'Four detached villas around a shared courtyard and garden. Design drawings approved; boundary wall and site office are up. Foundations start after winter.',
            'user_id' => $adminId,
        ]);

        $karteChar = $this->project([
            'code' => '4',
            'name' => 'Kart-e-Char Compound',
            'type' => 'apartment',
            'location' => 'Kart-e-Char, District 6, Kabul',
            'start_date' => '2022-02-10',
            'expected_completion_date' => '2025-05-30',
            'actual_completion_date' => '2025-06-18',
            'estimated_budget' => 980_000,
            'estimated_revenue' => 1_260_000,
            'progress_percent' => 100,
            'status' => 'completed',
            'description' => 'Low-rise compound of 12 family apartments with a small courtyard. Handed over in Jawza 1404. One ground-floor shop remains unsold.',
            'user_id' => $adminId,
        ]);

        $this->seedKhairKhanaUnits($khairKhana);
        $this->seedShahrNowUnits($shahrNow);
        $this->seedDarulamanUnits($darulaman);
        $this->seedKarteCharUnits($karteChar);

        $this->attachShareholders($khairKhana, $shahrNow, $darulaman, $karteChar);
        $this->attachEmployees($khairKhana, $shahrNow, $darulaman, $karteChar);
        $this->seedPortfolio();
        $this->seedFieldReports($siteSupervisor);
        $this->seedContactMessages();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function project(array $attributes): Project
    {
        return Project::query()->create($attributes);
    }

    private function seedKhairKhanaUnits(Project $project): void
    {
        $specs = [
            ['no' => 'A-101', 'floor' => 1, 'beds' => 2, 'area' => 98, 'cost' => 42000, 'list' => 58000, 'status' => 'sold'],
            ['no' => 'A-102', 'floor' => 1, 'beds' => 3, 'area' => 132, 'cost' => 56000, 'list' => 79000, 'status' => 'sold'],
            ['no' => 'A-201', 'floor' => 2, 'beds' => 2, 'area' => 98, 'cost' => 43000, 'list' => 61000, 'status' => 'reserved'],
            ['no' => 'A-202', 'floor' => 2, 'beds' => 3, 'area' => 132, 'cost' => 57000, 'list' => 82000, 'status' => 'available'],
            ['no' => 'A-301', 'floor' => 3, 'beds' => 2, 'area' => 98, 'cost' => 44000, 'list' => 63000, 'status' => 'available'],
            ['no' => 'A-302', 'floor' => 3, 'beds' => 3, 'area' => 132, 'cost' => 58000, 'list' => 85000, 'status' => 'sold'],
            ['no' => 'A-401', 'floor' => 4, 'beds' => 2, 'area' => 105, 'cost' => 48000, 'list' => 69000, 'status' => 'available'],
            ['no' => 'A-402', 'floor' => 4, 'beds' => 3, 'area' => 140, 'cost' => 62000, 'list' => 92000, 'status' => 'available'],
        ];

        foreach ($specs as $row) {
            $this->apartment($project, $row);
        }
    }

    private function seedShahrNowUnits(Project $project): void
    {
        $specs = [
            ['no' => 'B-101', 'floor' => 1, 'beds' => 2, 'area' => 110, 'cost' => 51000, 'list' => 74000, 'status' => 'sold'],
            ['no' => 'B-102', 'floor' => 1, 'beds' => 3, 'area' => 148, 'cost' => 68000, 'list' => 98000, 'status' => 'sold'],
            ['no' => 'B-201', 'floor' => 2, 'beds' => 2, 'area' => 110, 'cost' => 52000, 'list' => 76000, 'status' => 'sold'],
            ['no' => 'B-202', 'floor' => 2, 'beds' => 3, 'area' => 148, 'cost' => 69000, 'list' => 102000, 'status' => 'reserved'],
            ['no' => 'B-301', 'floor' => 3, 'beds' => 2, 'area' => 118, 'cost' => 55000, 'list' => 81000, 'status' => 'available'],
            ['no' => 'B-PH1', 'floor' => 6, 'beds' => 4, 'area' => 210, 'cost' => 98000, 'list' => 145000, 'status' => 'available'],
        ];

        foreach ($specs as $row) {
            $this->apartment($project, $row, extra: ['parkings' => 2, 'balconies' => 2]);
        }
    }

    private function seedDarulamanUnits(Project $project): void
    {
        $specs = [
            ['no' => 'V-1', 'floor' => 1, 'beds' => 4, 'area' => 280, 'cost' => 145000, 'list' => 210000, 'status' => 'available'],
            ['no' => 'V-2', 'floor' => 1, 'beds' => 4, 'area' => 280, 'cost' => 145000, 'list' => 210000, 'status' => 'available'],
            ['no' => 'V-3', 'floor' => 1, 'beds' => 5, 'area' => 320, 'cost' => 168000, 'list' => 245000, 'status' => 'available'],
            ['no' => 'V-4', 'floor' => 1, 'beds' => 5, 'area' => 320, 'cost' => 168000, 'list' => 245000, 'status' => 'reserved'],
        ];

        foreach ($specs as $row) {
            $this->villa($project, $row);
        }
    }

    private function seedKarteCharUnits(Project $project): void
    {
        $specs = [
            ['no' => 'C-101', 'floor' => 1, 'beds' => 2, 'area' => 88, 'cost' => 31000, 'list' => 45000, 'status' => 'sold'],
            ['no' => 'C-102', 'floor' => 1, 'beds' => 2, 'area' => 88, 'cost' => 31000, 'list' => 45000, 'status' => 'sold'],
            ['no' => 'C-201', 'floor' => 2, 'beds' => 3, 'area' => 118, 'cost' => 39000, 'list' => 56000, 'status' => 'sold'],
            ['no' => 'C-202', 'floor' => 2, 'beds' => 3, 'area' => 118, 'cost' => 39000, 'list' => 56000, 'status' => 'sold'],
            ['no' => 'C-301', 'floor' => 3, 'beds' => 3, 'area' => 125, 'cost' => 42000, 'list' => 61000, 'status' => 'sold'],
            ['no' => 'C-SH1', 'floor' => 1, 'beds' => 0, 'area' => 42, 'cost' => 14000, 'list' => 22000, 'status' => 'available'],
        ];

        foreach ($specs as $row) {
            $type = $row['no'] === 'C-SH1' ? 'shop' : 'apartment';
            $this->apartment($project, $row, type: $type);
        }
    }

    /**
     * @param  array{no: string, floor: int, beds: int, area: float|int, cost: float|int, list: float|int, status: string}  $row
     * @param  array<string, mixed>  $extra
     */
    private function apartment(Project $project, array $row, string $type = 'apartment', array $extra = []): Unit
    {
        $beds = (int) $row['beds'];

        return Unit::query()->create([
            'project_id' => $project->id,
            'floor_number' => $row['floor'],
            'unit_no' => $row['no'],
            'type' => $type,
            'area' => $row['area'],
            'bedrooms' => $beds,
            'toilets' => $beds > 0 ? max(1, $beds - 1) : 1,
            'bathrooms' => $beds >= 3 ? 2 : 1,
            'halls' => $beds > 0 ? 1 : 0,
            'kitchens' => 1,
            'balconies' => $extra['balconies'] ?? ($beds > 0 ? 1 : 0),
            'corridors' => 1,
            'courtyards' => 0,
            'parkings' => $extra['parkings'] ?? 1,
            'cost_price' => $row['cost'],
            'cost_price_base' => $row['cost'],
            'list_price' => $row['list'],
            'list_price_base' => $row['list'],
            'currency' => 'USD',
            'exchange_rate' => 1,
            'status' => $row['status'],
            'description' => $type === 'shop'
                ? 'Ground-floor shop facing the street. Separate meter and shutter door.'
                : $beds.'-bedroom '.$type.' with south light and one parking space.',
        ]);
    }

    /**
     * @param  array{no: string, floor: int, beds: int, area: float|int, cost: float|int, list: float|int, status: string}  $row
     */
    private function villa(Project $project, array $row): Unit
    {
        return Unit::query()->create([
            'project_id' => $project->id,
            'floor_number' => $row['floor'],
            'unit_no' => $row['no'],
            'type' => 'villa',
            'area' => $row['area'],
            'bedrooms' => $row['beds'],
            'toilets' => 3,
            'bathrooms' => 3,
            'halls' => 2,
            'kitchens' => 1,
            'balconies' => 2,
            'corridors' => 2,
            'courtyards' => 1,
            'parkings' => 2,
            'cost_price' => $row['cost'],
            'cost_price_base' => $row['cost'],
            'list_price' => $row['list'],
            'list_price_base' => $row['list'],
            'currency' => 'USD',
            'exchange_rate' => 1,
            'status' => $row['status'],
            'description' => 'Detached villa with private courtyard, guest room, and two parking bays.',
        ]);
    }

    private function attachShareholders(Project $khairKhana, Project $shahrNow, Project $darulaman, Project $karteChar): void
    {
        $yaqeen = Partner::query()->where('partner_no', 'PTR-001')->firstOrFail();
        $rahman = Partner::query()->where('partner_no', 'PTR-002')->firstOrFail();
        $haroon = Partner::query()->where('partner_no', 'PTR-003')->firstOrFail();

        $khairKhana->partners()->attach([
            $yaqeen->id => ['ownership_percentage' => 50],
            $rahman->id => ['ownership_percentage' => 30],
            $haroon->id => ['ownership_percentage' => 20],
        ]);

        $shahrNow->partners()->attach([
            $yaqeen->id => ['ownership_percentage' => 60],
            $haroon->id => ['ownership_percentage' => 40],
        ]);

        $darulaman->partners()->attach([
            $yaqeen->id => ['ownership_percentage' => 70],
            $rahman->id => ['ownership_percentage' => 30],
        ]);

        $karteChar->partners()->attach([
            $yaqeen->id => ['ownership_percentage' => 50],
            $rahman->id => ['ownership_percentage' => 50],
        ]);
    }

    private function attachEmployees(Project $khairKhana, Project $shahrNow, Project $darulaman, Project $karteChar): void
    {
        $byNo = Employee::query()->get()->keyBy('employee_no');

        $khairKhana->employees()->attach([
            $byNo['EMP-001']->id => [
                'started_at' => '2024-04-15',
                'role_on_project' => 'Project manager',
                'working_hours' => '08:00–17:00',
            ],
            $byNo['EMP-003']->id => [
                'started_at' => '2024-04-20',
                'role_on_project' => 'Site supervisor',
                'working_hours' => '07:00–16:00',
            ],
            $byNo['EMP-004']->id => [
                'started_at' => '2024-05-01',
                'role_on_project' => 'Site engineer',
                'working_hours' => '07:00–16:00',
            ],
            $byNo['EMP-006']->id => [
                'started_at' => '2024-06-10',
                'role_on_project' => 'Masonry foreman',
                'working_hours' => '07:00–16:00',
            ],
        ]);

        $shahrNow->employees()->attach([
            $byNo['EMP-001']->id => [
                'started_at' => '2023-06-01',
                'role_on_project' => 'Project manager',
                'working_hours' => '08:00–17:00',
            ],
            $byNo['EMP-004']->id => [
                'started_at' => '2023-07-01',
                'role_on_project' => 'Site engineer',
                'working_hours' => '07:00–16:00',
            ],
        ]);

        $darulaman->employees()->attach([
            $byNo['EMP-001']->id => [
                'started_at' => '2025-11-01',
                'role_on_project' => 'Project manager',
                'working_hours' => 'part-time',
            ],
        ]);

        $karteChar->employees()->attach([
            $byNo['EMP-003']->id => [
                'started_at' => '2022-02-15',
                'role_on_project' => 'Handover supervisor',
                'working_hours' => 'as needed',
                'notes' => 'Closes remaining snag list and shop sale.',
            ],
        ]);
    }

    private function seedPortfolio(): void
    {
        $items = [
            [
                'title' => 'Kart-e-Char Compound',
                'slug' => 'karte-char-compound',
                'summary' => 'A quiet family compound of 12 apartments around a shared courtyard in Kart-e-Char.',
                'description' => 'Handed over in 2025. Brick facades, south-facing rooms, and a planted courtyard. Built for families who want a low-rise home close to schools and the Darulaman road.',
                'location' => 'Kart-e-Char, Kabul',
                'category' => 'Residential compound',
                'client_name' => 'Yaqeen',
                'scope' => 'Design and build',
                'area_size' => '1,450 m² built',
                'duration' => '38 months',
                'total_apartments' => 12,
                'floors' => 3,
                'plot_size' => '1,200 m²',
                'parking_spaces' => 14,
                'project_status' => 'completed',
                'year' => 2025,
                'completed_on' => '2025-06-18',
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Shahr-e-Naw Tower',
                'slug' => 'shahr-e-naw-tower',
                'summary' => 'A corner residential tower beside the Shahr-e-Naw market, now ready for handover.',
                'description' => 'Six storeys of 2- and 3-bedroom apartments plus a penthouse. Street-facing shops on the ground floor. Finishes complete; occupancy starts after inspection.',
                'location' => 'Shahr-e-Naw, Kabul',
                'category' => 'Apartment tower',
                'client_name' => 'Yaqeen',
                'scope' => 'Design and build',
                'area_size' => '4,800 m² built',
                'duration' => '34 months',
                'total_apartments' => 18,
                'floors' => 6,
                'plot_size' => '780 m²',
                'parking_spaces' => 22,
                'project_status' => 'ongoing',
                'year' => 2026,
                'completed_on' => null,
                'is_published' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Khair Khana Residences',
                'slug' => 'khair-khana-residences',
                'summary' => 'A new eight-storey community in Khair Khana with shops, parking, and family apartments.',
                'description' => 'Structure is rising through the sixth floor. The project will deliver 24 apartments with consistent floor plates, two lifts, and basement parking.',
                'location' => 'Khair Khana, Kabul',
                'category' => 'Apartment tower',
                'client_name' => 'Yaqeen',
                'scope' => 'Design and build',
                'area_size' => '6,200 m² built',
                'duration' => '32 months (planned)',
                'total_apartments' => 24,
                'floors' => 8,
                'plot_size' => '950 m²',
                'parking_spaces' => 28,
                'project_status' => 'ongoing',
                'year' => 2026,
                'completed_on' => null,
                'is_published' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($items as $row) {
            PortfolioProject::query()->updateOrCreate(
                ['slug' => $row['slug']],
                $row,
            );
        }
    }

    private function seedFieldReports(?User $author): void
    {
        if (! $author) {
            return;
        }

        $reports = [
            [
                'title' => 'Khair Khana — floor 6 slab poured',
                'body' => 'Poured the sixth-floor slab this morning. Mix from Spin Ghar cement, 12mm rebar from Kabul Steel. Weather clear. Cube samples tagged KH-F6-01 to 03.',
                'location' => 'Khair Khana Residences',
                'status' => 'submitted',
            ],
            [
                'title' => 'Shahr-e-Naw — snag list for B-101',
                'body' => 'Buyer Ahmad Shah Karimi walked unit B-101. Paint touch-up on the hall ceiling and a loose kitchen tap. Both items scheduled for Saturday.',
                'location' => 'Shahr-e-Naw Tower',
                'status' => 'submitted',
            ],
            [
                'title' => 'Darulaman — waiting on survey pegs',
                'body' => 'Boundary wall is complete on the north and east. West side waits for the municipality survey team. Site office has power and a lockable store.',
                'location' => 'Darulaman Garden Villas',
                'status' => 'draft',
            ],
        ];

        foreach ($reports as $row) {
            FieldReport::query()->create([
                'client_uuid' => (string) Str::uuid(),
                'user_id' => $author->id,
                'title' => $row['title'],
                'body' => $row['body'],
                'location' => $row['location'],
                'status' => $row['status'],
                'client_updated_at' => now()->subDays(3),
            ]);
        }
    }

    private function seedContactMessages(): void
    {
        ContactMessage::query()->create([
            'name' => 'Samiullah Habibi',
            'phone' => '078 551 2290',
            'email' => 'sami.habibi@gmail.com',
            'message' => 'Assalamu alaikum. I am looking for a 3-bedroom apartment in Khair Khana, preferably a middle floor with parking. Please send available units and payment terms.',
            'ip_address' => '203.88.12.41',
            'read_at' => now()->subDay(),
        ]);

        ContactMessage::query()->create([
            'name' => 'Dr. Palwasha Rahimi',
            'phone' => '079 220 1184',
            'email' => 'palwasha.rahimi@gmail.com',
            'message' => 'Interested in Villa V-3 at Darulaman. Can we visit the plot this Thursday after 2pm? I would also like the construction specification sheet.',
            'ip_address' => '41.205.140.18',
            'read_at' => null,
        ]);
    }
}
