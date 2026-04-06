<?php

namespace Lkn\HookNotification\Core\NotificationReport\Http\Controllers;

use Lkn\HookNotification\Core\NotificationReport\Application\NotificationReportService;
use Lkn\HookNotification\Core\Shared\Infrastructure\Interfaces\BaseController;
use Lkn\HookNotification\Core\Shared\Infrastructure\View\View;

final class NotificationReportController extends BaseController
{
    private NotificationReportService $notificationReportService;

    public function __construct(View $view)
    {
        $this->notificationReportService = new NotificationReportService();

        parent::__construct($view);
    }

    public function viewReports(array $request): void
    {
        $currentPage    = $request['pageN'] ?? 1;
        $reportsPerPage = 30;

        $filters = [
            'status'       => $request['filter_status'] ?? '',
            'platform'     => $request['filter_platform'] ?? '',
            'notification' => $request['filter_notification'] ?? '',
            'date_from'    => $request['filter_date_from'] ?? '',
            'date_to'      => $request['filter_date_to'] ?? '',
            'client'       => $request['filter_client'] ?? '',
        ];

        $reportsForView = $this->notificationReportService->getReportsForView($reportsPerPage, $currentPage, $filters);

        $viewParams = [
            'reports'          => $reportsForView['reports'],
            'current_page'     => $currentPage,
            'reports_per_page' => $reportsPerPage,
            'total_reports'    => $reportsForView['totalReports'],
            'filters'          => $filters,
            'notifications'    => $this->notificationReportService->getDistinctNotifications(),
        ];

        $this->view->view('pages/reports', $viewParams);
    }
}
