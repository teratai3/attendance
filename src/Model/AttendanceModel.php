<?php

namespace Drupal\attendance\Model;

use Drupal\attendance\Model\BaseModel;

class AttendanceModel extends BaseModel
{
    protected string $table_name = 'attendances';

    // public function getFirstAttendanceByDate(int $user_id, $start_date)
    // {
    //     try {
    //         $query = $this->getDatabase()->select($this->table_name, 'a');
    //         $query->fields('a', ['id', 'user_id', 'start_time', 'end_time', 'remarks']);
    //         $query->condition('user_id', $user_id);
    //         $query->where('DATE(a.start_time) = :start_date', [':start_date' => $start_date]);
    //         return $query->execute()->fetchAssoc();
    //     } catch (\Exception $e) {
    //         $this->getLogger()->error($e->getMessage());
    //         return null;
    //     }
    // }

    public function getFirstAttendanceByDate(int $user_id, $record_date)
    {
        try {
            $query = $this->getDatabase()->select($this->table_name, 'a');
            $query->fields('a', ['id', 'user_id', 'record_date','start_time', 'end_time', 'remarks']);
            $query->condition('user_id', $user_id);
            $query->where('DATE(a.record_date) = :record_date', [':record_date' => $record_date]);
            return $query->execute()->fetchAssoc();
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            return null;
        }
    }

    // public function getDataForCurrentMonth(int $user_id, $start_date,$end_date)
    // {
    //     try {
    //         $query = $this->getDatabase()->select($this->table_name, 'a');
    //         $query->fields('a', ['id', 'user_id', 'start_time', 'end_time', 'remarks']);
    //         $query->condition('user_id', $user_id);
    //         $query->condition('start_time', [$start_date, $end_date], 'BETWEEN');
    //         $query->orderBy('start_time', 'ASC');
    //         return $query->execute()->fetchAll();
    //     } catch (\Exception $e) {
    //         $this->getLogger()->error($e->getMessage());
    //         return [];
    //     }
    // }

    public function getDataForCurrentMonth(int $user_id, $start_date,$end_date)
    {
        try {
            $query = $this->getDatabase()->select($this->table_name, 'a');
            $query->fields('a', ['id', 'user_id','record_date','start_time', 'end_time', 'remarks']);
            $query->condition('user_id', $user_id);
            $query->condition('record_date', [$start_date, $end_date], 'BETWEEN');
            $query->orderBy('record_date', 'ASC');
            return $query->execute()->fetchAll();
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            return [];
        }
    }
}
