$ErrorActionPreference = 'Stop'

$projectRoot = $PSScriptRoot
$backupDir = Join-Path $projectRoot 'storage\ui-backups\permit-grouping-20260820'

if (-not (Test-Path -LiteralPath $backupDir -PathType Container)) {
    throw "نسخه پشتیبان پیدا نشد: $backupDir"
}

$files = @(
    @{ Backup = 'PermitWorkflowController.php'; Target = 'app\Http\Controllers\Association\PermitWorkflowController.php' },
    @{ Backup = 'DozbalaghController.php'; Target = 'app\Http\Controllers\Company\DozbalaghController.php' },
    @{ Backup = 'association-permits-index.blade.php'; Target = 'resources\views\association\permits\index.blade.php' },
    @{ Backup = 'company-items-index.blade.php'; Target = 'resources\views\company\dozbalagh\items-index.blade.php' }
)

foreach ($file in $files) {
    $source = Join-Path $backupDir $file.Backup
    $target = Join-Path $projectRoot $file.Target

    if (-not (Test-Path -LiteralPath $source -PathType Leaf)) {
        throw "فایل پشتیبان ناقص است: $source"
    }

    Copy-Item -LiteralPath $source -Destination $target -Force
    Write-Output "بازگردانی شد: $($file.Target)"
}

Write-Output 'بازگشت سناریوی گروه‌بندی انجام شد.'
