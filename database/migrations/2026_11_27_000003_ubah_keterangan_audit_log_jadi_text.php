<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->text('keterangan')->change();
        });
    }

    public function down(): void
    {
        DB::table('audit_log')
            ->whereRaw('CHAR_LENGTH(keterangan) > 255')
            ->get(['id', 'keterangan'])
            ->each(function ($row) {
                DB::table('audit_log')
                    ->where('id', $row->id)
                    ->update(['keterangan' => mb_substr((string) $row->keterangan, 0, 255)]);
            });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->string('keterangan', 255)->change();
        });
    }
};
