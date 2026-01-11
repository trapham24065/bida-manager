<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{

//    protected string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Tổng quan hệ thống';

    protected static ?string $navigationLabel = 'Tổng quan';

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-home';

}
