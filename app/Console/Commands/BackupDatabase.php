<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    protected $signature = 'bloomgift:backup-database {--dry-run : Chỉ kiểm tra cấu hình và mysqldump, không tạo file}';

    protected $description = 'Tạo bản sao lưu MySQL private cho BloomGift bằng mysqldump';

    public function handle(): int
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}");

        if (! is_array($database) || ! in_array($database['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            $this->error('Backup chỉ hỗ trợ kết nối MySQL/MariaDB. Không tạo file backup.');

            return self::FAILURE;
        }

        foreach (['host', 'port', 'database', 'username'] as $key) {
            if (blank($database[$key] ?? null)) {
                $this->error("Thiếu cấu hình database: {$key}. Không tạo file backup.");

                return self::FAILURE;
            }
        }

        $binary = $this->findMysqldumpBinary();

        if ($binary === null) {
            $this->error('Không tìm thấy hoặc không chạy được mysqldump. Cài MySQL client hoặc cấu hình MYSQLDUMP_BINARY; không tạo file backup.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Đã kiểm tra cấu hình MySQL và mysqldump. Dry-run không tạo file backup.');

            return self::SUCCESS;
        }

        $directory = storage_path('app/private/backups');
        File::ensureDirectoryExists($directory);
        $destination = $directory.DIRECTORY_SEPARATOR.'bloomgift-'.now()->format('Ymd-His').'.sql';

        $result = Process::timeout(120)
            ->env(['MYSQL_PWD' => (string) ($database['password'] ?? '')])
            ->run([
                $binary,
                '--host='.(string) $database['host'],
                '--port='.(string) $database['port'],
                '--user='.(string) $database['username'],
                '--single-transaction',
                '--no-tablespaces',
                '--result-file='.$destination,
                (string) $database['database'],
            ]);

        if (! $result->successful() || ! File::isFile($destination) || File::size($destination) === 0) {
            File::delete($destination);
            $this->error('Không thể tạo backup. Kiểm tra kết nối MySQL, quyền truy cập và mysqldump; không có file backup hợp lệ được giữ lại.');

            return self::FAILURE;
        }

        $this->info('Đã tạo backup private: '.$destination);

        return self::SUCCESS;
    }

    private function findMysqldumpBinary(): ?string
    {
        $candidates = array_filter([
            env('MYSQLDUMP_BINARY'),
            'mysqldump',
            ...glob('C:\\Program Files\\MySQL\\MySQL Server *\\bin\\mysqldump.exe'),
        ]);

        foreach (array_unique($candidates) as $candidate) {
            if (Process::timeout(10)->run([$candidate, '--version'])->successful()) {
                return $candidate;
            }
        }

        return null;
    }
}
