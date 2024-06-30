<?php

namespace Drupal\attendance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\attendance\Model\AttendanceModel;
use Drupal\attendance\Service\AttendanceService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\user\Entity\User;

class AttendanceController extends ControllerBase
{

  protected $attendanceModel;
  protected $attendanceService;
  protected $requestStack;

  public function __construct(AttendanceModel $attendanceModel, AttendanceService $attendanceService, RequestStack $request_stack)
  {
    $this->attendanceModel = $attendanceModel;
    $this->attendanceService = $attendanceService;
    $this->requestStack = $request_stack;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('drupal.attendance.attendance_model'),
      $container->get('drupal.attendance.attendance_service'),
      $container->get('request_stack')
    );
  }

  public function list()
  {

    // 現在の月の取得
    $current_month = DrupalDateTime::createFromTimestamp(\Drupal::time()->getCurrentTime())->format('Y-m');
    // URLから月のパラメータを取得
    $month = $this->requestStack->getCurrentRequest()->query->get('month', $current_month);

    // 月の開始日と終了日を設定
    try{
      $month_f = DrupalDateTime::createFromFormat('Y-m', $month);
    }catch(\Exception $e){
      $this->messenger()->addError('月の形式が無効です。');
      $url = Url::fromRoute('attendance.list', [], [])->toString();
      return new RedirectResponse($url);
    }
   

    $datas = $this->attendanceService->getDataMonth($month);

    // 前月と次月のリンクを生成
    $prev_month = DrupalDateTime::createFromFormat('Y-m', $month)->modify('-1 month')->format('Y-m');
    $next_month = DrupalDateTime::createFromFormat('Y-m', $month)->modify('+1 month')->format('Y-m');

    $prev_link = Link::fromTextAndUrl('← 前の月', Url::fromRoute('attendance.list', [], ['query' => ['month' => $prev_month]]))->toString();
    $next_link = Link::fromTextAndUrl('次の月 →', Url::fromRoute('attendance.list', [], ['query' => ['month' => $next_month]]))->toString();


    // テーブルヘッダの定義
    $header = [
      'date' => "日付",
      'start_time' => "出勤時間",
      'end_time' => "退勤時間",
      'remarks' => "備考",
      'operations' => $this->t('Operations'),
    ];

    $current_date = DrupalDateTime::createFromTimestamp(\Drupal::time()->getCurrentTime());

    // テーブル行の生成
    $rows = [];
    foreach ($datas as $index => $data) {
      $row = [];
      $row['date'] = date("j日", strtotime($index));
      $row['start_time'] = isset($data->start_time) ? date("H:i:s", strtotime($data->start_time)) : '';
      $row['end_time'] = isset($data->end_time) ? date("H:i:s", strtotime($data->end_time)) : '';

      $remarks = isset($data->remarks) ? Html::escape($data->remarks) : '';
      $row['remarks'] = Markup::create(nl2br($remarks)); // remarksをエスケープして改行を<br>に変換


      if (isset($data->id)) {
        $link = Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('attendance.remarks_form_edit', ['id' => $data->id]))->toString();
      } else {
        $link = Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('attendance.remarks_form', [], ['query' => ['date' => $index]]))->toString();
      }

      $data_date = DrupalDateTime::createFromFormat('Y-m-d', $index); // 日付を比較して、未来の日付には編集ボタンを表示しない

      if ($data_date > $current_date) {
        $link = "";
      }

      $row['operations'] = $link;
      $rows[] = $row;
    }

    $build['title'] = [
      '#type' => 'markup',
      '#markup' => "<h2>{$month_f->format('Y年n月')}</h2>",
    ];
    // 前月と次月のリンクを追加
    $build['navigation'] = [
      '#markup' => Markup::create('<div class="month-navigation">' . $prev_link . ' | ' . $next_link . '</div>'),
    ];

    // レンダー配列の作成
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No data available.'),
    ];

    return $build;
  }



  public function admin_list()
  {

    // 現在の月の取得
    $current_month = DrupalDateTime::createFromTimestamp(\Drupal::time()->getCurrentTime())->format('Y-m');
    // URLから月のパラメータを取得
    $month = $this->requestStack->getCurrentRequest()->query->get('month', $current_month);

    $user_id = $this->requestStack->getCurrentRequest()->query->get('user_id');


    // ユーザーIDからユーザー情報を取得
    if ($user_id) {
      $user = User::load($user_id);
      if (!$user) {
        $this->messenger()->addError('指定されたユーザーが見つかりません。');
        return new RedirectResponse(Url::fromRoute('entity.user.collection', [], [])->toString());
      }
    } else {
      $this->messenger()->addError('ユーザーIDが指定されていません。');
      return new RedirectResponse(Url::fromRoute('entity.user.collection', [], [])->toString());
    }


    // 月の開始日と終了日を設定
    try {
      $month_f = DrupalDateTime::createFromFormat('Y-m', $month);
    } catch (\Exception $e) {
      $this->messenger()->addError('月の形式が無効です。');
      $url = Url::fromRoute('attendance.list', [], [])->toString();
      return new RedirectResponse($url);
    }


    $datas = $this->attendanceService->getDataMonthByUserId($month, $user_id);

    // 前月と次月のリンクを生成
    $prev_month = DrupalDateTime::createFromFormat('Y-m', $month)->modify('-1 month')->format('Y-m');
    $next_month = DrupalDateTime::createFromFormat('Y-m', $month)->modify('+1 month')->format('Y-m');

    $prev_link = Link::fromTextAndUrl('← 前の月', Url::fromRoute('attendance.admin_list', [], ['query' => ['month' => $prev_month, 'user_id' => $user_id]]))->toString();
    $next_link = Link::fromTextAndUrl('次の月 →', Url::fromRoute('attendance.admin_list', [], ['query' => ['month' => $next_month, 'user_id' => $user_id]]))->toString();


    // テーブルヘッダの定義
    $header = [
      'date' => "日付",
      'start_time' => "出勤時間",
      'end_time' => "退勤時間",
      'remarks' => "備考",
    ];

    // テーブル行の生成
    $rows = [];
    foreach ($datas as $index => $data) {
      $row = [];
      $row['date'] = date("j日", strtotime($index));
      $row['start_time'] = isset($data->start_time) ? date("H:i:s", strtotime($data->start_time)) : '';
      $row['end_time'] = isset($data->end_time) ? date("H:i:s", strtotime($data->end_time)) : '';

      $remarks = isset($data->remarks) ? Html::escape($data->remarks) : '';
      $row['remarks'] = Markup::create(nl2br($remarks)); // remarksをエスケープして改行を<br>に変換

      $rows[] = $row;
    }

    $build['title'] = [
      '#type' => 'markup',
      '#markup' => "<h2>{$month_f->format('Y年n月')}</h2>",
    ];
    // 前月と次月のリンクを追加
    $build['navigation'] = [
      '#markup' => Markup::create('<div class="month-navigation">' . $prev_link . ' | ' . $next_link . '</div>'),
    ];

    $build['user_name'] = [
      '#type' => 'markup',
      '#markup' => "<h3>ユーザー名：".Html::escape($user->getDisplayName())."</h3>",
    ];

    // レンダー配列の作成
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No data available.'),
    ];

    return $build;
  }

}
