<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin account ──────────────────────────────────────────────────────
        $adminEmail = env('ADMIN_EMAIL', 'admin@ai-gateway.local');

        $existing = User::where('email', $adminEmail)->first();

        if ($existing) {
            $this->command->info("Admin already exists: {$adminEmail} (key unchanged)");
            return;
        }

        $apiKey = User::generateApiKey();

        User::create([
            'name'       => 'Administrator',
            'email'      => $adminEmail,
            'api_key'    => $apiKey,
            'is_admin'   => true,
            'is_active'  => true,
            'permissions' => json_encode(['all']),
        ]);

        $this->command->newLine();
        $this->command->line('┌─────────────────────────────────────────────────────────┐');
        $this->command->line('│              ⚡  Admin Account Created                   │');
        $this->command->line('├─────────────────────────────────────────────────────────┤');
        $this->command->line("│  Email : {$adminEmail}");
        $this->command->line("│  Key   : {$apiKey}");
        $this->command->line('│                                                         │');
        $this->command->line('│  Save this key — it will NOT be shown again.            │');
        $this->command->line('│  Admin panel → http://localhost:8000/admin              │');
        $this->command->line('└─────────────────────────────────────────────────────────┘');
        $this->command->newLine();
    }
}
