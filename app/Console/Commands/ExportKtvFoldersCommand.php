<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportKtvFoldersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:ktv-folders 
                            {--path= : Đường dẫn thư mục đầu ra} 
                            {--skip-existing : Tự động bỏ qua KTV đã xuất đầy đủ}
                            {--after-id= : Chỉ tải các KTV có ID lớn hơn ID này}
                            {--force : Ép buộc ghi đè toàn bộ kể cả đã tải}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xuất dữ liệu KTV theo cấu trúc thư mục gồm ảnh Avatar, CCCD và file ttcn.txt (Có hỗ trợ Resume / Skip)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $outputPath = $this->option('path') ?: storage_path('app/public/ktv_exports');
        $skipExisting = $this->option('skip-existing') || !$this->option('force'); // Mặc định tự động skip nếu không dùng --force
        $afterId = $this->option('after-id');

        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        $query = User::with(['profile', 'files', 'reviewApplication'])
            ->where('role', UserRole::KTV->value)
            ->orderBy('id', 'asc');

        if ($afterId) {
            $query->where('id', '>', $afterId);
            $this->info("🔍 Lọc các KTV có ID sau: {$afterId}");
        }

        $ktvs = $query->get();

        if ($ktvs->isEmpty()) {
            $this->warn('Không tìm thấy KTV nào cần xuất!');
            return 0;
        }

        $this->info("🚀 Bắt đầu xử lý {$ktvs->count()} KTV sang thư mục: {$outputPath}");

        $bar = $this->output->createProgressBar($ktvs->count());
        $bar->start();

        $successCount = 0;
        $skippedCount = 0;

        foreach ($ktvs as $ktv) {
            $name = trim($ktv->name ?: 'KTV_' . $ktv->id);
            $phone = trim($ktv->phone ?: '');

            // Tên thư mục: Tên - SĐT (Ví dụ: Lưu Ngọc Phương Vy - 0765671618)
            $folderName = $phone ? "{$name} - {$phone}" : $name;
            // Thay thế ký tự không hợp lệ trong tên thư mục
            $folderName = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $folderName);

            $ktvDir = rtrim($outputPath, '/') . '/' . $folderName;

            // KIỂM TRA ĐÃ TẢI CHƯA: Nếu đã có folder và file ttcn.txt -> Bỏ qua không tải lại
            if ($skipExisting && File::exists($ktvDir . '/ttcn.txt')) {
                $skippedCount++;
                $bar->advance();
                continue;
            }

            if (!File::exists($ktvDir)) {
                File::makeDirectory($ktvDir, 0755, true);
            }

            $imagePaths = [];

            // 1. Xử lý ảnh Avatar
            if ($ktv->profile && $ktv->profile->avatar_url) {
                $avatarPath = $ktv->profile->avatar_url;
                $sourcePath = $this->resolveFilePath($avatarPath);

                if ($sourcePath && File::exists($sourcePath)) {
                    $ext = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
                    $targetFileName = "avatar.{$ext}";
                    $targetPath = $ktvDir . '/' . $targetFileName;
                    File::copy($sourcePath, $targetPath);
                    $imagePaths[] = $targetPath;
                }
            }

            // 2. Xử lý các file đính kèm (CCCD, bằng cấp, ảnh hồ sơ...)
            if ($ktv->files && $ktv->files->isNotEmpty()) {
                foreach ($ktv->files as $file) {
                    if ($file->file_path) {
                        $sourcePath = $this->resolveFilePath($file->file_path, $file->is_public);

                        if ($sourcePath && File::exists($sourcePath)) {
                            $fileName = $file->file_name ?: basename($file->file_path);
                            // Tránh trùng với avatar
                            if ($fileName === 'avatar.jpg' || $fileName === 'avatar.png') {
                                $fileName = 'hoso_' . $fileName;
                            }
                            $targetPath = $ktvDir . '/' . $fileName;
                            File::copy($sourcePath, $targetPath);
                            $imagePaths[] = $targetPath;
                        }
                    }
                }
            }

            // 3. Xử lý địa chỉ KTV
            $address = '';
            if ($ktv->reviewApplication && !empty($ktv->reviewApplication->address)) {
                $address = trim($ktv->reviewApplication->address);
            } elseif ($ktv->profile && !empty($ktv->profile->temp_address)) {
                $address = trim($ktv->profile->temp_address);
            }

            // 4. Tạo file ttcn.txt
            $txtLines = [];
            // Dòng 1: Tên - SĐT
            $txtLines[] = $phone ? "{$name} - {$phone}" : $name;
            // Dòng 2: Địa chỉ
            $txtLines[] = $address;
            // Dòng 3: image:[path1\n,path2\n,path3]
            if (!empty($imagePaths)) {
                $txtLines[] = 'image:[' . implode("\n,", $imagePaths) . ']';
            } else {
                $txtLines[] = 'image:[]';
            }

            $txtContent = implode("\n", $txtLines) . "\n";
            File::put($ktvDir . '/ttcn.txt', $txtContent);

            $successCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Hoàn tất:");
        $this->line("   - 📥 Xuất mới thành công: <info>{$successCount}</info> KTV");
        $this->line("   - ⏭️ Đã có sẵn (bỏ qua): <comment>{$skippedCount}</comment> KTV");
        $this->line("   - 📂 Thư mục đích: {$outputPath}");

        return 0;
    }

    /**
     * Tìm đường dẫn tuyệt đối của file trên hệ thống
     */
    private function resolveFilePath(string $path, bool $isPublic = true): ?string
    {
        $possiblePaths = [
            storage_path('app/public/' . $path),
            storage_path('app/private/' . $path),
            storage_path('app/' . $path),
            public_path('storage/' . $path),
            public_path($path),
        ];

        foreach ($possiblePaths as $p) {
            if (File::exists($p)) {
                return $p;
            }
        }

        return null;
    }
}
