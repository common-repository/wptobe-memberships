<?php

if ( ! function_exists( 'bwlmscredit_render_shortcode_history_userid' ) ) :
	function bwlmscredit_render_shortcode_history_userid( $atts, $content = '' ) {

		extract( shortcode_atts( array(
			'user_id'   => 'current',
			'number'    => '10',
			'time'      => '',
			'ref'       => '',
			'order'     => '',
			'show_user' => 0,
			'show_nav'  => 1,
			'login'     => '',
			'type'      => 'bwlmscredit_default'
		), $atts ) );

		// If we are not logged in
		if ( ! is_user_logged_in() && $login != '' )
			return $login . $content;

		if ( $user_id == 'current' )
			$user_id = get_current_user_id();

		$args = array( 'ctype' => $type );

		if ( $user_id != '' )
			$args['user_id'] = $user_id;

		if ( $number != '' )
			$args['number'] = $number;

		if ( $time != '' )
			$args['time'] = $time;

		if ( $ref != '' )
			$args['ref'] = $ref;

		if ( $order != '' )
			$args['order'] = $order;

		if ( isset( $_GET['paged'] ) && $_GET['paged'] != '' )
			$args['paged'] = absint( $_GET['paged'] );

		$log = new bwlmsCREDIT_Query_Log( $args );

		if ( $show_user != 1 )
			unset( $log->headers['column-username'] ); 

		ob_start();


		//크레딧 포인트 스타일 : css/message.css 
	?>

	<form class="form" role="form" method="get" action="">

		<div class="row bwlmscredit_log_fullwidthrow">
			<div class="small-12 medium-12 large-12  large-centered medium-centered small-centered columns bwlmscredit_log_centered">	
				<div class="row bwlmscredit_log_title_wrapper">
					<div class="large-3 medium-3 small-3 columns bwlmscredit_log_title">
						<?php	// 사용자 프로파일->포인트 페이지 
								$user_point_total = get_user_meta( $user_id, 'bwlmscredit_default', true ); 
								_e( 'Points', 'wptobemem' );
								echo "( <span class='bwlmscredit-point-amount'>".$user_point_total."</span> )";
						?>
					</div>
					<div class="large-5 medium-5  small-3 columns message_title_gapcol"></div>
					
					<div class="large-4 medium-4  small-6 columns inbox_counter_titlepagecol clearfix">
						<div class="row">
						<?php 
							if ( $log->have_entries() && $log->max_num_pages > 1 )
							{
								 $log->pagination( 'top', ''); 
							}
						?>
						</div>
					</div>
				</div>
			</div><!-- end of centered -->
		</div>

			<div class="row"> 
				<div class="small-12 medium-12 large-12 large-centered medium-centered small-centered columns bwlmscredit_log_centered">
					<div class="row bwlmscredit_titlerow"> 
						<div class="large-2 medium-2 small-2 columns message_row_idcol">Points
						</div>

						<div class="large-6 medium-6 small-5 columns message_row_msgcol">Entry
						</div>

						<div class="large-4 medium-4 small-5 columns message_row_datecol">Date
						</div>
					</div>
				</div>
			</div>


						<?php
							if ( $log->have_entries() ) {
							$alt = 0;

							//print_r($log->results);
							//echo "<br><br><br>";
							
							$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );	
							foreach ( $log->results as $log_entry ) {
								$row_class = apply_filters( 'bwlmscredit_log_row_classes', array( 'bwlmsCREDIT-log-row' ), $log_entry );

								$alt = $alt+1;
								if ( $alt % 2 == 0 )
									$row_class[] = ' alt';
							?>
						
								<div class="row"> 
									<div class="small-12 medium-12 large-12 large-centered medium-centered small-centered columns bwlmscredit_log_centered">
										<div class="row bwlmscredit_row">
											<div class="large-2 medium-2 small-2 columns bwlmscredit_row_pointscol">
												<?php echo $log_entry->credits?>
											</div>

											<div class="large-6 medium-6 small-5 columns bwlmscredit_row_entrycol">
												<?php echo str_replace("%plural%","Points",$log_entry->entry);?>
											</div>

											<div class="large-4 medium-4 small-5 columns bwlmscredit_row_datecol">
												<?php echo date_i18n( $date_format, $log_entry->time )?>
											</div>
										</div>
									</div>
								</div>

							<?php
							}
						}
						// No log entry
						else {
							?>
								<tr><td colspan="<?php echo  count( $log->headers ) ?>" class="no-entries"><?php echo  $log->get_no_entries()?></td></tr>
							<?php
						}
						?>
	</form>

	<?php
	}
endif;
add_shortcode('bwlmscredit_log', 'bwlmscredit_render_shortcode_history_userid');