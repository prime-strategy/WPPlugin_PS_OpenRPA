<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! defined( 'PS_OPENRPA_SCHEDULE_KEY' ) ) {
	define( 'PS_OPENRPA_SCHEDULE_KEY', '_schedule_time' );
}

/**
 * Task 設定 JSON データフォーマットの保存バージョン
 */
const PS_OPENRPA_TASK_JSON_VERSION = 1.0;

// 週
function ps_openrpa_get_week() {
	return array(
		'sunday'    => '日',
		'monday'    => '月',
		'tuesday'   => '火',
		'wednesday' => '水',
		'thursday'  => '木',
		'friday'    => '金',
		'saturday'  => '土',
	);
}

// POST サニタイジング
function ps_openrpa_post_sanitize() {
	if ( ! check_admin_referer( 'openrpa_task' ) ) {
		return array();
	}

	$post_sanitize = array();

	foreach ( $_POST as $key => $val ) {
		if ( isset( $_POST[ $key ] ) ) {
			$post_sanitize[ $key ] = sanitize_text_field( wp_unslash( $val ) );
		}
	}

	return $post_sanitize;
}

// タスク名重複確認
function ps_openrpa_check_taskname( $user_id, $name ) {
	$args  = array(
		'author'         => $user_id,
		'post_type'      => 'task',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	);
	$posts = get_posts( $args );

	foreach ( $posts as $key => $post ) {
		$task_obj = json_decode( $post->post_content );

		if ( $name === $task_obj->name ) {
			return false;
		}
	}

	return true;
}

// タスク登録
function ps_openrpa_add_task( $user_id, $task_name, $command ) {
	if ( ! check_admin_referer( 'openrpa_task' ) ) {
		return false;
	}

	// 同じタスク名では登録できないよう
	if ( false === ps_openrpa_check_taskname( $user_id, $task_name ) ) {
		echo '<script>window.addEventListener("load", function(){document.getElementById("error").innerHTML+="※タスク名はユニークでなければいけません<br>";});</script>';

		return false;
	}

	$timezone  = new \DateTimeZone( \DateTimeZone::UTC );
	$now       = new \DateTimeImmutable( 'now', $timezone );
	$post_content = array(
		'version' => PS_OPENRPA_TASK_JSON_VERSION,
		'name'    => $task_name,
		'command' => $command,
	);
	$post_args    = array(
		'post_title'   => $user_id . $now->format( '_Ymd_His' ),
		'post_content' => wp_json_encode( $post_content, JSON_UNESCAPED_UNICODE ),
		'post_type'    => 'task',
		'post_author'  => $user_id,
		'post_status'  => 'publish',
	);

	$post_id = wp_insert_post( $post_args );

	return $post_id;
}

// スケジュール登録
function ps_openrpa_add_schedule( $post_id, $post_sanitize ) {
	if ( ! check_admin_referer( 'openrpa_task' ) ) {
		return false;
	}

	$week        = ps_openrpa_get_week();
	$postmeta_id = 0;
	$schedule    = $post_sanitize['schedule'] ?? '';
	$delta       = array(
		'month'  => $post_sanitize['month'] ?? 0,
		'week'   => $post_sanitize['week'] ?? 0,
		'day'    => $post_sanitize['day'] ?? 0,
		'hour'   => $post_sanitize['hour'] ?? 0,
		'minute' => $post_sanitize['minute'] ?? 0,
	);

	switch ( $schedule ) {
		case 'minute':
			$postmeta_id = add_post_meta(
				$post_id,
				PS_OPENRPA_SCHEDULE_KEY,
				array(
					'format'      => "PT{$delta['minute']}M",
					'description' => "{$delta['minute']}分ごとに開始",
				)
			);
			break;
		case 'hour':
			$postmeta_id = add_post_meta(
				$post_id,
				PS_OPENRPA_SCHEDULE_KEY,
				array(
					'format'      => "PT{$delta['hour']}H{$delta['minute']}M",
					'description' => "{$delta['hour']}時間ごと{$delta['minute']}分に開始",
				),
			);
			break;
		case 'day':
			$postmeta_id = add_post_meta(
				$post_id,
				PS_OPENRPA_SCHEDULE_KEY,
				array(
					'format'      => "P1DT{$delta['hour']}H{$delta['minute']}M",
					'description' => "毎日{$delta['hour']}時{$delta['minute']}分に開始",
				),
			);
			break;
		case 'week':
			$dotw        = ps_openrpa_calc_dotw( $post_sanitize, $week );
			$postmeta_id = add_post_meta(
				$post_id,
				PS_OPENRPA_SCHEDULE_KEY,
				array(
					'format'      => "P{$dotw['calc']}AT{$delta['hour']}H{$delta['minute']}M",
					'description' => "毎週{$dotw['description']}曜日{$delta['hour']}時{$delta['minute']}分に開始",
				),
			);
			break;
		case 'month':
			$dotw        = ps_openrpa_calc_dotw( $post_sanitize, $week );
			$postmeta_id = add_post_meta(
				$post_id,
				PS_OPENRPA_SCHEDULE_KEY,
				array(
					'format'      => "P{$delta['month']}M{$dotw['calc']}AT{$delta['hour']}H{$delta['minute']}M",
					'description' => "{$delta['month']}カ月ごと毎週{$dotw['description']}曜日 {$delta['hour']}時{$delta['minute']}分に開始",
				),
			);
			break;
		case 'custom':
			break;
		default:
			break;
	}

	return $post_id;
}

// 実行曜日の加算
function ps_openrpa_calc_dotw( $post_sanitize, $week ) {
	$dotw_calc = array_sum( array_intersect_key( $post_sanitize, $week ) );
	$dotw_desc = implode( ',', array_intersect_key( $week, $post_sanitize ) ) ?? '';

	return array(
		'calc'        => $dotw_calc,
		'description' => $dotw_desc,
	);
}

// 入力エラーチェック
function ps_openrpa_error_check( array $post_sanitize, string $type = '' ) {
	$error = false;
	$week  = ps_openrpa_get_week();

	// task_nameが空の場合エラー
	if ( '' === ( $post_sanitize['task_name'] ?? '' ) && 'additional_schedule' !== $type ) {
		echo '<script>window.addEventListener("load", function(){document.getElementById("error").innerHTML+="※タスク名を入力してください<br>";});</script>';
		$error = true;
	}

	// commandが空の場合エラー
	if ( '' === ( $post_sanitize['command'] ?? '' ) && 'additional_schedule' !== $type ) {
		echo '<script>window.addEventListener("load", function(){document.getElementById("error").innerHTML+="※コマンドを入力してください<br>";});</script>';
		$error = true;
	}

	// scheduleが分で0分毎の場合エラー
	if ( 'minute' === ( $post_sanitize['schedule'] ?? '' ) && '0' === ( $post_sanitize['minute'] ?? '0' ) ) {
		echo '<script>window.addEventListener("load", function(){document.getElementById("error").innerHTML+="※分ごとの実行の場合0は指定できません<br>";});</script>';
		$error = true;
	}

	// scheduleが週、月で曜日が一つもない場合エラー
	if ( in_array( ( $post_sanitize['schedule'] ?? '' ), array( 'week', 'month' ), true ) && array() === array_intersect_key( $post_sanitize, $week ) ) {
		echo '<script>window.addEventListener("load", function(){document.getElementById("error").innerHTML+="※曜日を指定してください<br>";});</script>';
		$error = true;
	}

	return $error;
}

// あらかじめユーザ情報とpost_titleにつけるprefixは取っておく
if ( function_exists( 'wp_get_current_user' ) ) {
	$user = wp_get_current_user();
}

if ( 'POST' === $_SERVER['REQUEST_METHOD'] ?? '' ) {
	if ( ! check_admin_referer( 'openrpa_task' ) ) {
		return false;
	}

	$week          = ps_openrpa_get_week();
	$post_sanitize = ps_openrpa_post_sanitize();

	// タスク登録POSTの場合
	if ( isset( $post_sanitize['command'] ) && isset( $post_sanitize['schedule'] ) && ! ps_openrpa_error_check( $post_sanitize ) ) {
		$command   = $post_sanitize['command'];
		$task_name = $post_sanitize['task_name'];
		$post_id   = ps_openrpa_add_task( $user->ID, $task_name, $command );

		if ( $post_id ) {
			$postmeta_id = ps_openrpa_add_schedule( $post_id, $post_sanitize );
		}
	}

	// タスク削除POSTの場合
	if ( isset( $post_sanitize['delete_task'] ) ) {
		$post_id = $post_sanitize['delete_task'];

		if ( ! is_int( $post_id ) || $post_id <= 0 ) {
			echo '<script>window.addEventListener("load", function(){document.getElementById("error").innerHTML+="削除するタスク ID が不正です。<br>";});</script>';
		}

		$args = array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		);
		wp_update_post( $args );
	}

	// スケジュール削除POSTの場合
	if ( isset( $post_sanitize['delete_schedule_post_id'] ) ) {
		$post_id = $post_sanitize['delete_schedule_post_id'];

		if ( $post_id > 0 ) {
			$meta_value = array(
				'format'      => $post_sanitize['delete_schedule_format'] ?? '',
				'description' => $post_sanitize['delete_schedule_description'] ?? '',
			);

			$resp = delete_post_meta(
				$post_id,
				PS_OPENRPA_SCHEDULE_KEY,
				$meta_value,
			);
		}
	}

	// スケジュール追加POSTの場合
	if ( isset( $post_sanitize['additional_schedule'] ) ) {
		$post_id = $post_sanitize['additional_schedule'];

		if ( ! ps_openrpa_error_check( $post_sanitize, 'additional_schedule' ) ) {
			$postmeta_id = ps_openrpa_add_schedule( $post_id, $post_sanitize );
		}
	}
}
?>

<div class="container">
	<div class="row" style="margin-top: 20px;">
		<h3>タスク登録</h3>
	</div>

	<div class="row">
		<div class="col-12" id="error" style="color: red; margin: 5px 0 10px 0;"></div>
	</div>

	<form class="row" id="task" method="post">
		<?php wp_nonce_field( 'openrpa_task' ); ?>
		<div class="col-2">
			<h4>タスク名</h4>
			<div class="row">
				<div class="col-12">
					<label class="form-label">名前</label>
					<input type="text" class="form-control" name="task_name" placeholder="Myタスク" id="task_name">
				</div>
			</div>
		</div>

		<div class="col-3">
			<h4>OpenRPAタスク</h4>
			<div class="row">
				<div class="col-12">
					<label class="form-label">ワークフロー</label>
					<input type="text" class="form-control" name="command" placeholder="FilenameまたはタスクId" id="command">
				</div>
			</div>
			<div class="row">
				<div class="col-12" id="command_error" style="margin-top: 5px; color: red;"></div>
			</div>
		</div>

		<div class="col-7">
			<h4>スケジュール</h4>
			<div class="row">
				<div class="col-2" style="border-right: 1px solid black;">
					<div class="form-check" style="padding-left: 0;">
						<input type="radio" class="form-check-input schedule" name="schedule" value="minute" id="minute" style="margin: auto; float: none;" checked>
						<label class="form-check-label" for="minute">分</label>
					</div>
					<div class="form-check" style="padding-left: 0;">
						<input type="radio" class="form-check-input schedule" name="schedule" value="hour" id="hour" style="margin: auto; float: none;">
						<label class="form-check-label" for="hour">時間</label>
					</div>
					<div class="form-check" style="padding-left: 0;">
						<input type="radio" class="form-check-input schedule" name="schedule" value="day" id="day" style="margin: auto; float: none;">
						<label class="form-check-label" for="day">日</label>
					</div>
					<div class="form-check" style="padding-left: 0;">
						<input type="radio" class="form-check-input schedule" name="schedule" value="week" id="week" style="margin: auto; float: none;">
						<label class="form-check-label" for="week">週</label>
					</div>
					<div class="form-check" style="padding-left: 0;">
						<input type="radio" class="form-check-input schedule" name="schedule" value="month" id="month" style="margin: auto; float: none;">
						<label class="form-check-label" for="month">月</label>
					</div>
					<div class="form-check" style="padding-left: 0;">
						<input type="radio" class="form-check-input schedule" name="schedule" id="custom" style="margin: auto; float: none;" disabled>
						<label class="form-check-label" for="custom">カスタム</label>
					</div>
				</div>

				<div class="col-8" id="schedule_form">
					<div class="row">
						<p class="">※このプロセスは(UTC)協定世界時のタイムゾーンでスケジュール設定され、夏時間の調整も自動的に行われます</p>
					</div>
					<div class="row justify-content-end">
						<div class="col-auto">
							<button type="submit" form="task" class="btn btn-primary">追加</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>

<!-- for modal -->
<div class="container">
	<div class="modal fade" id="additional_schedule" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header text-center">
					<h5 class="modal-title w-100">スケジュール追加</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>

				<form class="modal-body" id="modal-body" method="post">
					<?php wp_nonce_field( 'openrpa_task' ); ?>
					<div class="row">
						<div class="col-2" style="border-right: 1px solid black;">
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" value="minute" id="modal_minute" style="margin: auto; float: none;" checked>
								<label class="form-check-label" for="modal_minute">分</label>
							</div>
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" value="hour" id="modal_hour" style="margin: auto; float: none;">
								<label class="form-check-label" for="modal_hour">時間</label>
							</div>
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" value="day" id="modal_day" style="margin: auto; float: none;">
								<label class="form-check-label" for="modal_day">日</label>
							</div>
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" value="week" id="modal_week" style="margin: auto; float: none;">
								<label class="form-check-label" for="modal_week">週</label>
							</div>
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" value="month" id="modal_month" style="margin: auto; float: none;">
								<label class="form-check-label" for="modal_month">月</label>
							</div>
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" id="modal_custom" style="margin: auto; float: none;" disabled>
								<label class="form-check-label" for="modal_custom">カスタム</label>
							</div>
						</div>

						<div class="col-8" id="modal_schedule_form">
							<div class="row">
								<p class="">※このプロセスは(UTC)協定世界時のタイムゾーンでスケジュール設定され、夏時間の調整も自動的に行われます</p>
							</div>
						</div>
					</div>
				</form>
				<div class="modal-footer">
					<button type="submit" form="modal-body" class="btn btn-primary" id="add_modal"
							name="additional_schedule" value="" data-bs-dismiss="modal">追加
					</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="container">
	<div class="row" style="margin-top: 20px;">
		<h3>登録タスク一覧</h3>
	</div>
	<div class="row justify-content-center">
		<div class="col-12">
			<table class="table">
				<thead class="sticky-top">
				<tr>
					<th scope="col" style="width: 10%;">名前</th>
					<th scope="col" style="width: 25%;">コマンド</th>
					<th scope="col" style="width: 35%;">スケジュール</th>
					<th scope="col" style="width: 15%;">スケジュール追加</th>
					<th scope="col" style="width: 15%;">タスク削除</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$paged = sanitize_text_field( wp_unslash( $_GET['paged'] ?? 1 ) );

				if ( ! is_int( $paged ) || $paged <= 0 ) {
					$paged = 1;
				}

				$args = array(
					'author'         => $user->ID,
					'post_type'      => 'task',
					'post_status'    => 'publish',
					'posts_per_page' => 10,
					'paged'          => $paged,
					'orderby'        => 'date',
					'order'          => 'DESC',
				);

				$query     = new WP_Query( $args );
				$max_pages = $query->max_num_pages;

				if ( $query->have_posts() ) {
					foreach ( $query->posts as $key => $post ) {
						$task_obj  = json_decode( $post->post_content );
						$schedules = get_post_meta( $post->ID, PS_OPENRPA_SCHEDULE_KEY );

						echo '<tr>';
						echo '<td class="align-middle">' . esc_html( $task_obj->name ) . '</td>';
						echo '<td class="align-middle">' . esc_html( $task_obj->command ) . '</td>';
						echo '<td class="align-middle">';

						foreach ( $schedules as $schedule ) {
							echo '<form action="" method="post">';
							wp_nonce_field( 'openrpa_task' );
							echo '<input type="hidden" name="delete_schedule_post_id" value="' . esc_attr( $post->ID ) . '"><input type="hidden" name="delete_schedule_format" value="' . esc_attr( $schedule['format'] ) . '"><input type="hidden" name="delete_schedule_description" value="' . esc_attr( $schedule['description'] ) . '"><button type="submit" class="btn btn-light" name="delete_schedule" style="vertical-align: baseline; color: red; margin: 2px 5px 2px; padding: 2px;">×</button><span>' . esc_html( $schedule['description'] ) . '</span></form>';
						}

						echo '</td>';
						echo '<td class="align-middle"><button type="button" class="btn btn-success add" value="' . esc_attr( $post->ID ) . '" data-bs-target="#additional_schedule" data-bs-toggle="modal">追加</button></td>';
						echo '<td class="align-middle"><form action="" method="post">';
						wp_nonce_field( 'openrpa_task' );
						echo '<button type="submit" class="btn btn-danger" name="delete_task" value="' . esc_attr( $post->ID ) . '">削除</button></form></td>';
						echo '</tr>';
					}
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
	<div class="row">
		<div class="col text-end task-pagination">
			<?php
			$big             = 9999999;
			$pagination_args = array(
				'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
				'format'    => '?paged=%#%',
				'current'   => max( 1, $paged ),
				'prev_text' => __( '<' ),
				'next_text' => __( '>' ),
				'total'     => $max_pages,
			);
			echo esc_url( paginate_links( $pagination_args ) );
			?>
		</div>
	</div>
</div>
