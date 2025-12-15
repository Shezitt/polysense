<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade para ReportService
 * 
 * @method static \App\Models\Report generate(int $userId, string $type, array $filters = [])
 * @method static \Illuminate\Pagination\LengthAwarePaginator getHistory(int $userId, array $filters = [])
 * @method static \App\Models\Report view(int $reportId)
 * @method static bool delete(int $reportId)
 * @method static int generateDailyReportForAdmins()
 * @method static array getQuickStats(array $filters = [])
 * 
 * @see \App\Services\ReportService
 */
class ReportFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'report.service';
    }
}
