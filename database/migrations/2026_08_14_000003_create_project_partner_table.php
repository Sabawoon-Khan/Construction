<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_partner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->decimal('ownership_percentage', 8, 4)->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'partner_id']);
        });

        if (Schema::hasColumn('partners', 'project_id')) {
            DB::table('partners')
                ->whereNotNull('project_id')
                ->orderBy('id')
                ->get()
                ->each(function (object $partner) {
                    DB::table('project_partner')->insert([
                        'project_id' => $partner->project_id,
                        'partner_id' => $partner->id,
                        'ownership_percentage' => $partner->ownership_percentage ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });

            Schema::table('partners', function (Blueprint $table) {
                $table->dropConstrainedForeignId('project_id');
            });
        }

        if (Schema::hasColumn('partners', 'ownership_percentage')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->dropColumn('ownership_percentage');
            });
        }
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->decimal('ownership_percentage', 8, 4)->default(0)->after('national_id');
        });

        foreach (DB::table('project_partner')->get() as $row) {
            DB::table('partners')
                ->where('id', $row->partner_id)
                ->update([
                    'project_id' => $row->project_id,
                    'ownership_percentage' => $row->ownership_percentage,
                ]);
        }

        Schema::dropIfExists('project_partner');
    }
};
