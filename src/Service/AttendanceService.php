<?php

namespace Drupal\attendance\Service;

use Drupal\attendance\Model\AttendanceModel;
use Drupal\Core\Session\AccountInterface;


class AttendanceService
{
  protected $attendanceModel;
  protected $currentUser;

  public function __construct(AttendanceModel $attendanceModel, AccountInterface $currentUser)
  {
    $this->attendanceModel = $attendanceModel;
    $this->currentUser = $currentUser;
  }

  public function getDataMonth($yearMonth = null)
  {
    return $this->getDataMonthByUserId($yearMonth, $this->currentUser->id());
  }

  public function getDataMonthByUserId($yearMonth = null, $user_id = null)
  {
    $days = $this->getDaysMonth($yearMonth);
    $data = $this->attendanceModel->getDataForCurrentMonth($user_id, $days['start_date'], $days['end_date']);
    $dateMap = array_fill_keys($days['dates'], []); // 初期化

    foreach ($data as $record) {
      if (isset($dateMap[$record->record_date])) {
        $dateMap[$record->record_date] = $record;
      }
    }

    return $dateMap;
  }


  private function getDaysMonth($yearMonth = null)
  {
    // 年月の形式が正しいかをチェックするための正規表現
    $yearMonthPattern = '/^\d{4}-(0[1-9]|1[0-2])$/';

    // 引数として年と月が提供されない場合、現在の年と月を使用
    if ($yearMonth && preg_match($yearMonthPattern, $yearMonth)) {
      $currentDate = new \DateTime($yearMonth . '-01');
    } else {
      $currentDate = new \DateTime();
    }

    $start_date = clone $currentDate->modify('first day of this month');
    $end_date = clone $currentDate->modify('last day of this month');

    $dateStrings = [];
    $date = clone $start_date;

    while ($date <= $end_date) {
      $dateStrings[] = $date->format('Y-m-d');
      $date->modify('+1 day');
    }

    return [
      'start_date' => $start_date->format('Y-m-d'),
      'end_date' => $end_date->format('Y-m-d'),
      'dates' => $dateStrings,
    ];
  }
}
