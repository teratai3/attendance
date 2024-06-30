<?php

namespace Drupal\attendance\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\attendance\Model\AttendanceModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class RemarksForm extends FormBase
{
    protected $attendanceModel;
    protected $requestStack;
    public function __construct(AttendanceModel $attendanceModel, RequestStack $request_stack)
    {
        $this->attendanceModel = $attendanceModel;
        $this->requestStack = $request_stack;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('drupal.attendance.attendance_model'),
            $container->get('request_stack')
        );
    }


    public function getFormId()
    {
        return 'remarks_form';
    }


    public function buildForm(array $form, FormStateInterface $form_state, $id = null)
    {
        $account = \Drupal::currentUser();

        if ($id) {
            $data = $this->attendanceModel->findOneBy([
                "id" => $id,
                "user_id" => $account->id()
            ]);

            if (!$data) {
                $url = Url::fromRoute('attendance.list')->toString();
                $response = new RedirectResponse($url);
                return $response->send();
            }

            // IDを隠しフィールドに設定
            $form['id'] = [
                '#type' => 'hidden',
                '#value' => $id,
            ];
        } else {
            $current_date = DrupalDateTime::createFromTimestamp(\Drupal::time()->getCurrentTime());
            $date = $this->requestStack->getCurrentRequest()->query->get('date');
            $yearMonthPattern = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/';

            $data = $this->attendanceModel->getFirstAttendanceByDate(\Drupal::currentUser()->id(), $date);

            if ($date === null || !preg_match($yearMonthPattern, $date) || $date > $current_date || $data) {
                $url = Url::fromRoute('attendance.list')->toString();
                $response = new RedirectResponse($url);
                return $response->send();
            }

            // 日付を隠しフィールドに設定
            $form['date'] = [
                '#type' => 'hidden',
                '#value' => $date,
            ];
        }

        $form['remarks'] = [
            '#type' => 'textarea',
            '#title' => "備考",
            '#required' => false,
            '#default_value' => isset($data["remarks"]) ? $data["remarks"] : "",
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
        ];

        return $form;
    }


    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues();
        $account = \Drupal::currentUser();
        $id = isset($values['id']) ? $values['id'] : null;

        $date = isset($values['date']) ? $values['date'] : null;
        $current_date = DrupalDateTime::createFromTimestamp(\Drupal::time()->getCurrentTime());
        $yearMonthPattern = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/';

        if ($id) {
            $data = $this->attendanceModel->findOneBy([
                'id' => $id,
                'user_id' => $account->id(),
            ]);

            if (!$data) {
                return $form_state->setErrorByName('id', 'ID が無効であるか、このレコードを編集する権限がありません。');
            }
        } else {

            if ($date === null || !preg_match($yearMonthPattern, $date) || $date > $current_date->format('Y-m-d')) {
                return $form_state->setErrorByName('date', '無効な日付が指定されました。');
            }

            $existing_data = $this->attendanceModel->getFirstAttendanceByDate($account->id(), $date);
            if ($existing_data) {
                return $form_state->setErrorByName('date', '指定された日付のデータは既に存在します。');
            }
        }
    }




    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        $values = $form_state->getValues();
        $account = \Drupal::currentUser();
    
        $id = isset($values['id']) ? $values['id'] : null;
        $date = isset($values['date']) ? $values['date'] : null;
    
        try {
            if ($id) {
                $result = $this->attendanceModel->update($id, [
                    'remarks' => $values['remarks'],
                ]);
            } else {
                $result = $this->attendanceModel->insert([
                    'user_id' => $account->id(),
                    'record_date' => $date,
                    'remarks' => $values['remarks'],
                ]);
            }
    
            if (!$result) {
                throw new \Exception("備考欄の保存中にエラーが発生しました。");
            }
        } catch (\Exception $e) {
            $this->messenger()->addError($e->getMessage());
            return;
        }
    
        return $this->messenger()->addMessage('保存しました。');
    }
}
