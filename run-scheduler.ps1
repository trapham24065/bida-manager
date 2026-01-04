# ============================================
#   LARAVEL SCHEDULER - AUTO RUN
# ============================================

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  LARAVEL SCHEDULER - AUTO RUN" -ForegroundColor Yellow
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Scheduler dang chay moi 60 giay..." -ForegroundColor Green
Write-Host "Nhan Ctrl+C de dung" -ForegroundColor Red
Write-Host ""

$count = 0

while ($true) {
    $count++
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    
    Write-Host "[$timestamp] Lan chay thu $count" -ForegroundColor Cyan
    
    # Chạy scheduler
    php artisan schedule:run
    
    Write-Host ""
    
    # Chờ 60 giây
    Start-Sleep -Seconds 60
}

