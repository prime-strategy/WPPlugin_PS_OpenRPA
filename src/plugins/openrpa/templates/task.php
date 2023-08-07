<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit();
	}

	if ( ! defined( 'PS_OPENRPA_SCHEDULE_KEY' ) ) {
		define( 'PS_OPENRPA_SCHEDULE_KEY', '_schedule_time' );
	}
?>

<?php
	// タスク名重複確認
	function ps_openrpa_check_taskname( $name ) {

		$args = array(
			'author' => $user->ID,
			'post_type' => 'task',
			'post_status' => 'publish',
			'posts_per_page' => -1
		);
		$posts = get_posts( $args );
		foreach( $posts as $key => $post ) {
			$task_obj = json_decode( $post->post_content );
			if ( $task_obj->name === $name ) {
				return false;
			}
		}
		return true;
	}

	// タスク登録
	function ps_openrpa_add_task( $user_id, $now, $task_name, $command ) {
		// 同じタスク名では登録できないよう
		if ( false === ps_openrpa_check_taskname( $task_name ) ) {
			echo '<script>window.addEventListener("load", function(){document.getElementById("error").innerHTML+="※タスク名はユニークでなければいけません<br>";});</script>';
			return false;
		} else {
			$post_content = array(
				'name' => $task_name,
				'command' => $command
			);
			$post_args = array(
				'post_title' => "{$user_id}_{$now}",
				'post_content' => json_encode( $post_content, JSON_UNESCAPED_UNICODE ),
				'post_type' => 'task',
				'post_author' => $user_id,
				'post_status' => 'publish'
			);

			$post_id = wp_insert_post( $post_args );
		
			return $post_id;
		}
	}
	
	// スケジュール登録
	function ps_openrpa_add_schedule( $post_id ) {
		$postmeta_id = 0;
		switch( $_POST['schedule'] ) {
			case 'minute':
				$postmeta_id = add_post_meta(
					$post_id,
					PS_OPENRPA_SCHEDULE_KEY,
					array(
						'format' => "PT{$_POST['minute']}M",
						'description' => "{$_POST['minute']}分ごとに開始"
					)
			   	);
				break;
			case 'hour':
				$postmeta_id = add_post_meta(
					$post_id,
					PS_OPENRPA_SCHEDULE_KEY,
					array(
						'format' => "PT{$_POST['hour']}H{$_POST['minute']}M",
						'description' => "{$_POST['hour']}時間ごと{$_POST['minute']}分に開始"
					)
				);
				break;
			case 'day':
				$postmeta_id = add_post_meta( 
					$post_id,
					PS_OPENRPA_SCHEDULE_KEY,
					array(
						'format' => "P1DT{$_POST['hour']}H{$_POST['minute']}M",
						'description' => "毎日{$_POST['hour']}時{$_POST['minute']}分に開始"
					)
				);
				break;
			case 'week':
				$dotw = ps_openrpa_calc_dotw();
				$postmeta_id = add_post_meta(
					$post_id,
					PS_OPENRPA_SCHEDULE_KEY,
					array(
						'format' => "P{$dotw['calc']}WT{$_POST['hour']}H{$_POST['minute']}M",
						'description' => "毎週{$dotw['description']}曜日{$_POST['hour']}時および{$_POST['minute']}分に開始"
					)
				);
				break;
			case 'month':
				$dotw = ps_openrpa_calc_dotw();
				$postmeta_id = add_post_meta(
					$post_id,
					PS_OPENRPA_SCHEDULE_KEY,
					array(
						'format' => "P{$_POST['month']}M{$dotw['calc']}WT{$_POST['hour']}H{$_POST['minute']}M",
						'description' => "{$_POST['month']}カ月ごと毎週{$dotw['description']}曜日および{$_POST['hour']}時{$_POST['minute']}分に開始"
					)
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
	function ps_openrpa_calc_dotw() {
		$dotw = 0;
		$dotw_desc = '';
		if ( array_key_exists( 'monday', $_POST ) ) {
			$dotw += $_POST['monday'];
			if ( '' === $dotw_desc ) {
				$dotw_desc .= '月';
			} else {
				$dotw_desc .= ',月';
			}
		}
		if ( array_key_exists( 'tuesday', $_POST ) ) {
			$dotw += $_POST['tuesday'];
			if ( '' === $dotw_desc ) {
				$dotw_desc .= '火';
			} else {
				$dotw_desc .= ',火';
			}
		}
		if ( array_key_exists( 'wednesday', $_POST ) ) {
			$dotw += $_POST['wednesday'];
			if ( '' === $dotw_desc ) {
				$dotw_desc .= '水';
			} else {
				$dotw_desc .= ',水';
			}	
		}
		if ( array_key_exists( 'thursday', $_POST ) ) {
			$dotw += $_POST['thursday'];
			if ( '' === $dotw_desc ) {
				$dotw_desc .= '木';
			} else {
				$dotw_desc .= ',木';
			}
		}
		if ( array_key_exists( 'friday', $_POST ) ) {
			$dotw += $_POST['friday'];
			if ( '' === $dotw_desc ) {
				$dotw_desc .= '金';
			} else {
				$dotw_desc .= ',金';
			}
		}
		if ( array_key_exists( 'saturday', $_POST ) ) {
			$dotw += $_POST['saturday'];
			if ( '' === $dotw_desc ) {
				$dotw_desc .= '土';
			} else {
				$dotw_desc .= ',土';
			}
		}
		if ( array_key_exists( 'sunday', $_POST ) ) {
			$dotw += $_POST['sunday'];
			if ( '' === $dotw_desc ) {
				$dotw_desc .= '日';
			} else {
				$dotw_desc .= ',日';
			}	
		}
		return array( 'calc' => $dotw, 'description' => $dotw_desc );
	}

	// 入力エラーチェック
	function ps_openrpa_error_check() {
		// エラーフラグ
		$error = false;
		// task_nameが空の場合エラー
		if ( array_key_exists( 'task_name', $_POST ) && '' === $_POST['task_name'] ) {
			echo '<script>window.addEventListener("load", function(){document.getElementById("error").innerHTML+="※タスク名を入力してください<br>";});</script>';
			$error = true;
		}
		// commandが空の場合エラー
		if ( array_key_exists( 'command', $_POST ) && '' === $_POST['command'] ) {
			echo '<script>window.addEventListener("load", function(){document.getElementById("error").innerHTML+="※コマンドを入力してください<br>";});</script>';
			$error = true;
		}
		// scheduleが分で0分毎の場合エラー
		if ( 'minute' === $_POST['schedule'] && '0' === $_POST['minute'] ) {
			echo '<script>window.addEventListener("load", function(){document.getElementById("error").innerHTML+="※分ごとの実行の場合0は指定できません<br>";});</script>';
			$error = true;
		}
		// scheduleが週、月で曜日が一つもない場合エラー
		if ( 'week' === $_POST['schedule'] || 'month' === $_POST['schedule'] ) {
			if ( ( ! array_key_exists( 'monday', $_POST ) &&
				 ! array_key_exists( 'tuesday', $_POST ) &&
				 ! array_key_exists( 'wednesday', $_POST ) &&
				 ! array_key_exists( 'thursday', $_POST ) &&
				 ! array_key_exists( 'friday', $_POST ) &&
				 ! array_key_exists( 'saturday', $_POST ) &&
				 ! array_key_exists( 'sunday', $_POST ) ) ) {
				 echo '<script>window.addEventListener("load", function(){document.getElementById("error").innerHTML+="※曜日を指定してください<br>";});</script>';
				$error = true;
			}
		}

		return $error;
	}


	// あらかじめユーザ情報とpost_titleにつけるprefixは取っておく
	if ( function_exists( 'wp_get_current_user' ) ) {
		$user = wp_get_current_user();
		$now = date('Ymd_His');
	}

	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		// タスク登録POSTの場合
		if ( array_key_exists( 'command', $_POST ) && array_key_exists( 'schedule', $_POST ) ) {
			if ( ! ps_openrpa_error_check() ) {
				$command = $_POST['command'];
				$task_name = $_POST['task_name'];
				$post_id = ps_openrpa_add_task( $user->ID, $now, $task_name, $command );
				if ( $post_id ) {
					$postmeta_id = ps_openrpa_add_schedule( $post_id );
				}
			}
		}
		
		// タスク削除POSTの場合
		if ( array_key_exists( 'delete_task', $_POST ) ) {
			$post_id = $_POST['delete_task'];
			$args = array(
				'ID' => $post_id,
				'post_status' => 'draft'
			);
			wp_update_post( $args );
		}

		// スケジュール削除POSTの場合
		if ( array_key_exists( 'delete_schedule', $_POST ) ) {
			$data = $_POST['delete_schedule'];
			// javascript用のjsonになっているのでPHPで扱えるようにする
			$data = str_replace( '\\\\', '\\', $data );
			$data = str_replace( '\\"', '"', $data );
			$data = json_decode( $data, true );
			
			$post_id = $data['post_id'];
			$meta_value = $data['meta_value'];
			
			$resp = delete_post_meta(
				$post_id,
				PS_OPENRPA_SCHEDULE_KEY,
				$meta_value
			);
		}

		// スケジュール追加POSTの場合
		if ( array_key_exists( 'additional_schedule', $_POST ) ) {
			$post_id = $_POST['additional_schedule'];
			if ( ! ps_openrpa_error_check() ) {
				$postmeta_id = ps_openrpa_add_schedule( $post_id );
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
						<label class="form-check-label">分</label>
					</div>
					<div class="form-check" style="padding-left: 0;">
						<input type="radio" class="form-check-input schedule" name="schedule" value="hour" id="hour" style="margin: auto; float: none;">
						<label class="form-check-label">時間</label>
					</div>
					<div class="form-check" style="padding-left: 0;">
						<input type="radio" class="form-check-input schedule" name="schedule" value="day" id="day" style="margin: auto; float: none;">
						<label class="form-check-label">日</label>
					</div>
					<div class="form-check" style="padding-left: 0;">
						<input type="radio" class="form-check-input schedule" name="schedule" value="week" id="week" style="margin: auto; float: none;">
						<label class="form-check-label">週</label>
					</div>
					<div class="form-check" style="padding-left: 0;">
						<input type="radio" class="form-check-input schedule" name="schedule" value="month" id="month" style="margin: auto; float: none;">
						<label class="form-check-label">月</label>
					</div>
					<div class="form-check" style="padding-left: 0;">
						<input type="radio" class="form-check-input schedule" name="schedule" id="custom" style="margin: auto; float: none;" disabled>
						<label class="form-check-label">カスタム</label>
					</div>
				</div>
				
				<div class="col-8" id="schedule_form">
					<div class="row schedule_forms" style="margin-bottom: 5px;">
						<div class="col-2" id="minute_pre_text"></div>
						<div class="col-6">
							<select class="form-select" name="minute">
							<?php
								for( $i = 0; $i < 60; $i+=5 ) {
									echo "<option value={$i}>{$i}</option>";
								}
							?>
							</select>
						</div>
						<div class="col-4" id="minute_text">分ごとに開始</div>
					</div>
					
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
					<div class="row">
						<div class="col-2" style="border-right: 1px solid black;">
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" value="minute" id="minute" style="margin: auto; float: none;" checked>
								<label class="form-check-label">分</label>
							</div>
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" value="hour" id="hour" style="margin: auto; float: none;">
								<label class="form-check-label">時間</label>
							</div>
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" value="day" id="day" style="margin: auto; float: none;">
								<label class="form-check-label">日</label>
							</div>
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" value="week" id="week" style="margin: auto; float: none;">
								<label class="form-check-label">週</label>
							</div>
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" value="month" id="month" style="margin: auto; float: none;">
								<label class="form-check-label">月</label>
							</div>
							<div class="form-check" style="padding-left: 0;">
								<input type="radio" class="form-check-input modal_schedule" name="schedule" id="custom" style="margin: auto; float: none;" disabled>
								<label class="form-check-label">カスタム</label>
							</div>
						</div>
				
						<div class="col-8" id="modal_schedule_form">
							<div class="row modal_schedule_forms" style="margin-bottom: 5px;">
								<div class="col-2" id="modal_minute_pre_text"></div>
								<div class="col-6">
									<select class="form-select" name="minute">
									<?php
										for( $i = 0; $i < 60; $i+=5 ) {
											echo "<option value={$i}>{$i}</option>";
										}
									?>
									</select>
								</div>
								<div class="col-4" id="modal_minute_text">分ごとに開始</div>
							</div>
					
							<div class="row">
								<p class="">※このプロセスは(UTC)協定世界時のタイムゾーンでスケジュール設定され、夏時間の調整も自動的に行われます</p>
							</div>
						</div>
					</div>
      			</form>
      			<div class="modal-footer">
        			<button type="submit" form="modal-body" class="btn btn-primary" id="add_modal" name="additional_schedule" value="" data-bs-dismiss="modal">追加</button>
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
						$paged = ( isset( $_GET['paged'] ) ) ? $_GET['paged'] : 1;
						$args = array(
							'author' => $user->ID,
							'post_type' => 'task',
							'post_status' => 'publish',
							'posts_per_page' => 10,
							'paged' => $paged,
							'orderby' => 'date',
							'order' => 'DESC'
						);
						
						$query = new WP_Query( $args );
						$max_pages = $query->max_num_pages;
						if ( $query->have_posts() ) {
							foreach( $query->posts as $key => $post ) {
								$task_obj = json_decode( $post->post_content );
								$schedules = get_post_meta( $post->ID, PS_OPENRPA_SCHEDULE_KEY );
								$schedules_tag = '';
								foreach( $schedules as $schedule ) {
									// meta削除に必要なデータはjson化
									$metas = array(
										'post_id' => $post->ID,
										'meta_value' => $schedule
									);
									$metas = json_encode( $metas );
									$schedules_tag .= "<form action='' method='post'><button type='submit' class='btn btn-light' name='delete_schedule' value={$metas} style='vertical-align: baseline; color: red; margin: 2px 5px 2px; padding: 2px;'>×</button><span>{$schedule['description']}</span></form>";
								}
								echo '<tr>';
								echo "<td class='align-middle'>{$task_obj->name}</td>";
								echo "<td class='align-middle'>{$task_obj->command}</td>";
								echo "<td class='align-middle'>{$schedules_tag}</td>";
								echo "<td class='align-middle'><button type='button' class='btn btn-success add' value={$post->ID} data-bs-target='#additional_schedule' data-bs-toggle='modal'>追加</button></td>";
								echo "<td class='align-middle'><form action='' method='post'><button type='submit' class='btn btn-danger' name='delete_task' value={$post->ID}>削除</button></form></td>";
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
				$big = 9999999;
				$pagination_args = array(
					'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format' => '?paged=%#%',
					'current' => max(1, $paged ),
					'prev_text' => __('<'),
					'next_text' => __('>'),
					'total' => $max_pages,
				);
				echo paginate_links( $pagination_args );
			?>
		</div>
	</div>	
</div>

