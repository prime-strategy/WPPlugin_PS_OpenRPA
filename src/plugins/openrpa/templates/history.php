<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit();
	}
	
	if ( ! defined( 'PS_OPENRPA_SCHEDULE_KEY' ) ) {
		define( 'PS_OPENRPA_SCHEDULE_KEY', '_schedule_time' );
	}
?>

<?php
	// あらかじめユーザ情報は取っておく
	if ( function_exists( 'wp_get_current_user' ) ) {
		$user = wp_get_current_user();
	}
?>

<div class="container">
	<div class="row" style="margin-top: 20px;">
		<h3>タスク実行履歴</h3>
	</div>
	
	<div class="row justify-content-center">
		<div class="col-12">
			<table class="table">
				<thead class="sticky-top">
					<tr>
						<th scope="col" style="width: 30%;">名前</th>
						<th scope="col" style="width: 30%;">開始日時</th>
						<th scope="col" style="width: 30%;">完了日時</th>
						<th scope="col" style="width: 10%;">ステータス</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$paged = ( isset( $_GET['paged'] ) ) ? $_GET['paged'] : 1;
						$args = array(
							'author' => $user->ID,
							'post_type' => 'result',
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
								$result_obj = json_decode( $post->post_content );
								$task_id = $result_obj->id;
								$schedules = get_post_meta( $task_id, PS_OPENRPA_SCHEDULE_KEY );
								$schedules_tag = '';
								foreach( $schedules as $schedule ) {
									if ( '' === $schedules_tag ) {
										$schedules_tag .= $schedule['description'];
									} else {
										$schedules_tag .= '<br>' . $schedule['description'];
									}
								}
								echo '<tr>';
								echo "<td class='align-middle'>{$result_obj->name}</td>";	
								echo "<td class='align-middle'>{$result_obj->start}</td>";	
								echo "<td class='align-middle'>{$result_obj->end}</td>";	
								if ( $result_obj->status ) {
									echo '<td class="align-middle" style="color: green;">〇</td>';
								} else {
									echo '<td class="align-middle" style="color: red;">×</td>';
								}
							}
						}
					?>
				</tbody>
			</table>
		</div>
	</div>
	<div class="row">
		<div class="col text-end history-pagination">
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
