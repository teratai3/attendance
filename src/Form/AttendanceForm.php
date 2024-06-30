<?php

namespace Drupal\attendance\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\attendance\Model\AttendanceModel;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AttendanceForm extends FormBase
{
    protected $attendanceModel;

    public function __construct(AttendanceModel $attendanceModel)
    {
        $this->attendanceModel = $attendanceModel;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('drupal.attendance.attendance_model'),
        );
    }


    public function getFormId()
    {
        return 'attendance_form';
    }


    public function buildForm(array $form, FormStateInterface $form_state)
    {
       
        $datetime = DrupalDateTime::createFromTimestamp(\Drupal::time()->getCurrentTime());
        $account = \Drupal::currentUser();

        $data = $this->attendanceModel->getFirstAttendanceByDate($account->id(), $datetime->format('Y/m/d'));

        $start_time = !empty($data["start_time"]) ? date("H:i:s", strtotime($data["start_time"])) : "";
        $end_time = !empty($data["end_time"]) ? date("H:i:s", strtotime($data["end_time"])) : "";

        $form['time_text'] = [
            '#type' => 'markup',
            '#markup' => "<h2>日付：{$datetime->format('Y年m月d日')}</h2>",
        ];

        $form['start_time_text'] = [
            '#type' => 'markup',
            '#markup' => "<p>出勤時間:{$start_time}</p>",
        ];

        $form['end_time_text'] = [
            '#type' => 'markup',
            '#markup' => "<p>退勤時間:{$end_time}</p>",
        ];

        $form['start_time'] = [
            '#type' => 'submit',
            '#value' => "出勤する",
            '#disabled' => !empty($start_time) ? true : false,
            '#attributes' => [
                'id' => 'start-time-button',
            ]
        ];

        $form['end_time'] = [
            '#type' => 'submit',
            '#value' => "退勤する",
            '#attributes' => [
                'id' => 'end-time-button',
            ],
        ];

        if (empty($data)) {
            $form['end_time']["#disabled"] = true;
        } else {
            $form['end_time']["#disabled"] = $end_time !== "" ? true : false;
        }


        // $form['remarks'] = [
        //     '#type' => 'textarea',
        //     '#title' => "備考",
        //     '#required' => false,
        //     '#default_value' => isset($data["remarks"]) ? $data["remarks"] : "",
        // ];

        // $form['submit'] = [
        //     '#type' => 'submit',
        //     '#value' => $this->t('Save'),
        // ];

        return $form;
    }


    public function validateForm(array &$form, FormStateInterface $form_state)
    {
    }


    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        //https://qiita.com/863/items/d13c57d9bb89e6a6315c
        $account = \Drupal::currentUser();
        $datetime = DrupalDateTime::createFromTimestamp(\Drupal::time()->getRequestTime());
        $values = $form_state->getValues();

        $data = $this->attendanceModel->getFirstAttendanceByDate($account->id(), $datetime->format('Y/m/d')); // 既存の出勤記録をチェック

        try {
            if (!empty($data)) {
                if($data["start_time"] === null){
                    $result = $this->attendanceModel->update($data['id'], [
                        'start_time' => $data["start_time"] === null ? $datetime->format('H:i:s') : $data["start_time"],
                    ]);
                }else{
                    $result = $this->attendanceModel->update($data['id'], [
                        'end_time' => $data["end_time"] === null ? $datetime->format('H:i:s') : $data["end_time"],
                    ]);
                }
            } else {
                $result = $this->attendanceModel->insert([
                    'user_id' => $account->id(),
                    'record_date' => $datetime->format('Y/m/d'),
                    'start_time' => $datetime->format('H:i:s'),
                ]);
            }

            if (!$result) {
                throw new \Exception("出勤記録の保存中にエラーが発生しました。");
            }
        } catch (\Exception $e) {
            return $this->messenger()->addError($e->getMessage());
        }

        //https://gist.github.com/signalpoint/40a3add1ccc385c558606353ebdcde00
        return $this->messenger()->addMessage('保存しました。');
    }
}
