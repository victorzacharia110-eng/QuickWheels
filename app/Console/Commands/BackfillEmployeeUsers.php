<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BackfillEmployeeUsers extends Command
{
    protected $signature = 'app:backfill-employee-users';
    protected $description = 'Create User accounts for existing employees without one';

    public function handle()
    {
        $employees = Employee::whereNull('user_id')->get();

        if ($employees->isEmpty()) {
            $this->info('All employees already have user accounts.');
            return Command::SUCCESS;
        }

        $this->info("Found {$employees->count()} employees without user accounts.");

        $bar = $this->output->createProgressBar($employees->count());
        $bar->start();

        foreach ($employees as $employee) {
            $password = Str::random(10);

            $user = User::create([
                'name' => $employee->name,
                'email' => $employee->email ?: $employee->phone . '@temp.quickwheel.online',
                'password' => Hash::make($password),
                'phone' => $employee->phone,
                'role' => 'employee',
                'is_active' => true,
            ]);

            $employee->update(['user_id' => $user->id]);

            $this->line(PHP_EOL . "  {$employee->name}: email={$user->email}, password={$password}");

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done!');

        return Command::SUCCESS;
    }
}
