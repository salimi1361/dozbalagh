<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ImportAssociationMemberCompanies extends Command
{
    protected $signature = 'companies:import-members {file : UTF-8 CSV file} {--commit : Persist the import}';

    protected $description = 'Import verified association member companies without duplicating national IDs';

    public function handle(): int
    {
        $path = realpath($this->argument('file'));
        if (! $path || ! is_readable($path)) {
            $this->error('Import file is not readable.');
            return self::FAILURE;
        }

        $rows = $this->readRows($path);
        $roleId = Role::query()->where('name', 'company')->value('id');
        if (! $roleId) {
            $this->error('Company role was not found.');
            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $skipped = [];

        foreach ($rows as $line => $row) {
            $nationalId = $this->digits($row['national_id'] ?? '');
            $mobile = $this->normalizeMobile($row['ceo_mobile'] ?? '');
            $name = $this->memberCompanyName($row['name_fa'] ?? '');

            if (strlen($nationalId) !== 11 || ! preg_match('/^09\d{9}$/', $mobile) || $name === '') {
                $skipped[] = "line {$line}: invalid required values";
                continue;
            }

            $existingCompany = Company::query()->where('national_id', $nationalId)->first();
            $existingUser = User::query()->where('username', $nationalId)->first();
            if (! $existingCompany && $existingUser) {
                $skipped[] = "line {$line}: username {$nationalId} belongs to another account";
                continue;
            }

            if (! $this->option('commit')) {
                $existingCompany ? $updated++ : $created++;
                continue;
            }

            DB::transaction(function () use ($row, $roleId, $nationalId, $mobile, $name, $existingCompany, &$created, &$updated) {
                $user = $existingCompany?->user ?: User::create([
                    'role_id' => $roleId,
                    'username' => $nationalId,
                    'mobile' => $mobile,
                    'password' => Hash::make('12345678'),
                    'status' => 'active',
                    'is_manual' => true,
                ]);

                $values = [
                    'user_id' => $user->id,
                    'name' => $name,
                    'name_fa' => $name,
                    'national_id' => $nationalId,
                    'registration_number' => trim((string) ($row['registration_number'] ?? '')),
                    'ceo_name' => trim((string) ($row['ceo_name'] ?? '')),
                    'ceo_mobile' => $mobile,
                    'is_association_member' => true,
                ];

                if ($existingCompany) {
                    $existingCompany->forceFill($values)->saveQuietly();
                    $user->update(['mobile' => $mobile, 'status' => 'active']);
                    $updated++;
                    return;
                }

                $company = new Company();
                $company->forceFill($values + [
                    'status' => 'profile_incomplete',
                    'company_code' => 'C-M-'.$nationalId,
                    'name_en' => $name,
                    'phone' => '000',
                    'address_fa' => 'ثبت نشده',
                    'address_en' => 'N/A',
                ])->save();
                $created++;
            });
        }

        $mode = $this->option('commit') ? 'COMMIT' : 'DRY RUN';
        $this->info("{$mode}: create={$created}, update={$updated}, skipped=".count($skipped));
        foreach ($skipped as $message) {
            $this->warn($message);
        }

        return $skipped === [] ? self::SUCCESS : self::FAILURE;
    }

    private function readRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new RuntimeException('Unable to open import file.');
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            throw new RuntimeException('Import file has no header.');
        }
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $required = ['name_fa', 'ceo_name', 'national_id', 'registration_number', 'ceo_mobile'];
        if (array_diff($required, $headers)) {
            fclose($handle);
            throw new RuntimeException('Import file headers are invalid.');
        }

        $rows = [];
        $line = 1;
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            if (count($values) !== count($headers)) {
                throw new RuntimeException("Invalid column count on line {$line}.");
            }
            $rows[$line] = array_combine($headers, $values);
        }
        fclose($handle);

        return $rows;
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function normalizeMobile(mixed $value): string
    {
        $mobile = $this->digits($value);
        return strlen($mobile) === 10 && str_starts_with($mobile, '9') ? '0'.$mobile : $mobile;
    }

    private function memberCompanyName(mixed $value): string
    {
        $name = trim((string) $value);
        $prefix = 'شرکت حمل و نقل بین المللی ';

        return str_starts_with($name, $prefix) ? $name : $prefix.$name;
    }
}
