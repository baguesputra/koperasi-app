<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugPermTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug(): void
    {
        $this->seed([
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RoleSeeder::class,
            \Database\Seeders\UserSeeder::class,
        ]);
        $u = User::factory()->create();
        $this->actingAs($u);
        $resp = $this->get(route('laporan.index'));
        fwrite(STDERR, "\nSTATUS=".$resp->status().' hasPerm='.var_export($u->hasPermissionTo('laporan.lihat'), true)." roles=".implode(',', $u->getRoleNames()->all())."\n");
        $this->assertTrue(true);
    }
}
