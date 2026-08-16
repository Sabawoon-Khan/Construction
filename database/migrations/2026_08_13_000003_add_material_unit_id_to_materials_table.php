<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('materials', 'material_unit_id')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->foreignId('material_unit_id')->nullable()->after('name')->constrained('material_units')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('materials', 'unit')) {
            return;
        }

        $now = now();

        DB::table('materials')
            ->whereNotNull('unit')
            ->where('unit', '!=', '')
            ->distinct()
            ->pluck('unit')
            ->each(function (string $unitName) use ($now) {
                $existing = DB::table('material_units')->where('unit_name', $unitName)->first();

                if (! $existing) {
                    DB::table('material_units')->insert([
                        'unit_name' => $unitName,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });

        DB::table('materials')
            ->whereNotNull('unit')
            ->where('unit', '!=', '')
            ->orderBy('id')
            ->get(['id', 'unit'])
            ->each(function ($material) {
                $unitId = DB::table('material_units')->where('unit_name', $material->unit)->value('id');

                if ($unitId) {
                    DB::table('materials')->where('id', $material->id)->update([
                        'material_unit_id' => $unitId,
                    ]);
                }
            });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('materials', 'unit')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->string('unit')->default('pcs')->after('name');
            });
        }

        if (Schema::hasColumn('materials', 'material_unit_id')) {
            DB::table('materials')
                ->join('material_units', 'materials.material_unit_id', '=', 'material_units.id')
                ->select('materials.id', 'material_units.unit_name')
                ->get()
                ->each(function ($row) {
                    DB::table('materials')->where('id', $row->id)->update([
                        'unit' => $row->unit_name,
                    ]);
                });

            Schema::table('materials', function (Blueprint $table) {
                $table->dropConstrainedForeignId('material_unit_id');
            });
        }
    }
};
