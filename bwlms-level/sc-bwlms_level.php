<?php

function bwlmslevel_shortcode_show_level($atts, $content=null, $code="")
{
	global $wpdb, $bwlmslevel_levels, $current_user, $levels;

	$user_id = get_current_user_id();
	ob_start();
	?>	
	<script>
		function sendit_cart()
		{
			frm = document.frm_cart
			frm.submit();
		}
	</script>

				<!-- Notification--------------------------------------------------------------->
			<?php if( isset($_GET['success']) && $_GET['success'] =="y"){ ?>
				
				<script>
					setTimeout(function() {
											 document.getElementById("bwlmsmem-cp-success-id").style.display = "none";
											}, 3000);
					
				</script>
				<div id="bwlmsmem-cp-success-id" class="alert alert-success" role="alert">
				  <button type="button" class="close"  aria-label="Close"  onClick="closeNotification();">
					<span aria-hidden="true">&times;</span>
				  </button>
				  <span id="bwlmsmem-coupon-alert-1-success"><strong>Notice!</strong> the coupon applied successfully</span>
				</div>
			<?php }?>

				<div id="bwlmsmem-cp-failed-id" class="alert alert-danger" role="alert" style="display:none;">
				  <button type="button" class="close"  aria-label="Close"  onClick="closeinfo();">
					<span aria-hidden="true">&times;</span>
				  </button>
				  <span id="bwlmsmem-coupon-alert-2-fail"> </span>
				</div>

				<style>
					/*Notification*/
					#bwlmsmem-cp-success-id { 
					}
					#bwlmsmem-cp-failed-id {
					}

				</style>
				<script>
					function closeNotification(){
						document.getElementById("bwlmsmem-cp-success-id").style.display = "none";
					}

					function closeinfo(){
						document.getElementById("bwlmsmem-cp-failed-id").style.display = "none";
					}

					function sendit(){
						frm = document.fffm;
						quiz_num = frm.quiz_num.value;
						answer_str ="";
						user_id = frm.c_user_id.value; 	
						
						if (user_id =="" || user_id ==0)
						{
							contents ="Please login or sign up to view your test reports.";	
							$("#bwlmsmem-coupon-alert-2-fail").html(contents);
							$("#bwlmsmem-cp-failed-id").slideDown(200);

						}else{
							num = 0;
							for (i=1; i<=quiz_num ; i++)
							{

								data = eval("document.fffm.num_"+i+".length");
						
								for(j=0; j < data; j++){
									data1 = eval("document.fffm.num_"+i+"["+j+"].length");
									if( eval("document.fffm.num_"+i+"["+j+"].checked")){
										 num = num + 1;
										
										 if (answer_str =="")
										 {
											 answer_str = eval("document.fffm.num_"+i+"["+j+"].value");
										 }
										 else
										 {
											answer_str = answer_str +"/"+eval("document.fffm.num_"+i+"["+j+"].value");
										 }
									}

								}
							}
						
						
							if (quiz_num == num)
							{
								frm.answer_str.value = answer_str;
								if(frm.quiz_author_name.value =="")
								{
									alert ("Please fill in your name.");
									return;
								}
								else if(frm.quiz_author_email.value =="")
								{
									alert ("Please fill in your email address.");
									return;
								}
								else
								{
									frm.submit();
								}
							}
							else
							{
								frm = document.fffm;
								quiz_num = frm.quiz_num.value;
								num =0;
								contents1 ="";
								
								for (i=1; i<=parseInt(quiz_num) ; i++)
								{

									data = eval("document.fffm.num_"+i+".length");
									ccheck1 = "";
									for(j=0; j < data; j++){
										data1 = eval("document.fffm.num_"+i+"["+j+"].length");
										
										if( eval("document.fffm.num_"+i+"["+j+"].checked")){
												 ccheck1 = "y"; 
										}
									}
									if (ccheck1 !="y")
									{
										contents1 = contents1 + " " + i;
									}
								}

								contents ="Please answer ALL of the following questions <br>"+contents1;
								
								$("#bwlmsmem-coupon-alert-1-success").html(contents);
								$("#bwlmsmem-cp-success-id").slideDown(200);
							}
						}// ·
					}

				</script>
				<!--source:wptobe-memberships/bwmls-level/sc-bwlms_level.php-------------------------------------------------------------------------------->



				<div class="row bwlmsmembership-level-shell-1">
					<div class="col-sm-3 bwlmsmembership-level-title">
						<?php _e( 'การสมัครสมาชิก', 'wptobemem' ); ?>
					</div>
					<div class="col-sm-9"></div>
				</div>




				<hr class="bwlmsmembership-level-hr">
				<div class="row bwlmsmembership-level-shell-2">
					<div class="col-sm-3 bwlmsmembership-level-table-2L">
						<?php _e('สมาชิกแต่ละประเภท', 'wptobemem'); ?>
					</div>
					<div class="col-sm-9 bwlmsmembership-level-table-2R">

						<?php 

							function change_date($ddate)
							{
								$ddate_arr = explode("-",$ddate);
								$now_time = mktime(0,0,0,$ddate_arr[1],$ddate_arr[2],$ddate_arr[0]);
								$date_time = date("d/M/Y", $now_time);

								return $date_time;
							}

							function change_datetime($ddate)
							{
								//$ddate_arr = explode("-",$ddate);
								//$now_time = mktime(0,0,0,$ddate_arr[1],$ddate_arr[2],$ddate_arr[0]);
								$date_time = date("d/M/Y H:i:s", strtotime($ddate) );

								return $date_time;
							}

							if(bwlmslevel_hasMembershipLevel()){	

								$today_str = date("Y-m-d");
								$row_membershop = $wpdb->get_row("select * from wp_wptobe_membership_payment  where pay_status ='Y' and order_id='".$user_id."' and expire_date  >='".$today_str."' order by expire_date desc limit 1", ARRAY_A);
								
								$membership_name = "";
								if($row_membershop['product_name'])
								{
									$membership_name = $row_membershop['product_name'];
								}
								else
								{
									$membership_name = "- Expired";
								}
								
								$level = $current_user->membership_level;
								

								if (!isset($current_user->membership_level->name))
									_e('No Level','wptobemem');
								else
								{
									echo $current_user->membership_level->name ." - Renewal due on ". change_date($row_membershop['expire_date']); 

								}

							}else {
									_e('Annual membership - Renewal due on 21/SEP/2018','wptobemem');
							}
						?>
					</div>
				</div>
				
				<hr class="bwlmsmembership-level-hr">
				<form name="frm_cart" method="post" action="<?php echo site_url('cart')?>">
				<input type="hidden" name="mode" value="incart">
				<div class="row bwlmsmembership-level-shell-2" >
					<div class="col-sm-3 bwlmsmembership-level-table-3L">
						<?php _e( 'การชำระเงินสมาชิก', 'wptobemem' );?>
					</div>
					<div class="col-sm-9 bwlmsmembership-level-table-3R">
						<div class="row">
							<div class="col-sm-6 bwlmsmembership-level-table-3RL">

								<div class="">
									<label>
									<input type="radio" name="product_period" value="1" checked onClick="javascript:change_price('1');"> <?php echo "Monthly - $39"; ?>
									</label>
								</div>
								<div class="">
									<label>
									<input type="radio" name="product_period" value="6" onClick="javascript:change_price('6');"> <?php echo "Half-yearly - $210"; ?>
									</label>
								</div>
								<div class="">
									<label>
									<input type="radio" name="product_period" value="12" onClick="javascript:change_price('12');"> <?php echo "Annual - $396"; ?>
									</label>
								</div>

							</div>
							<div class="col-sm-6 bwlmsmembership-level-table-3RR">

									<div class="bwlmsmembership-level-buy-membership-section">

										<span class="bwlmsmembership-level-buy-membership-radio radio">
											<label>
												<input type="radio" name="optradio" checked="checked">
												<img src="<?php echo plugins_url( '../images/visa.png', __FILE__ ) ?>" height="22" alt=" Credit Card Logos">
											</label>
										</span>

									</div>
									


								<script src="https://cdn.pin.net.au/pin.v2.js"></script>
								<script>
									function change_price(val)
									{
										if(val =="1")
										{
											$("#card_c_price").attr("href", "https://pay.pin.net.au/r13v?amount=39&currency=AUD&description=Monthly&success_url=../wp-content/plugins/wptobe-multi-vendor/ajax/quiz/payment.php&amount_editable=false");
										}
										else if(val =="6")
										{
											$("#card_c_price").attr("href", "https://pay.pin.net.au/r13v?amount=210&currency=AUD&description=Half-yearly&success_url=../wp-content/plugins/wptobe-multi-vendor/ajax/quiz/payment.php&amount_editable=false");
										}
										else if(val =="12")
										{
											$("#card_c_price").attr("href", "https://pay.pin.net.au/r13v?amount=396&currency=AUD&description=Annual&success_url=../wp-content/plugins/wptobe-multi-vendor/ajax/quiz/payment.php&amount_editable=false");
										}
									}

									function coupon_payment()
									{
										frm = document.frm_cart;

										$.ajax({
											type: "POST",
											  contentType: "application/x-www-form-urlencoded; charset=utf-8",
											url: "<?php echo plugins_url();?>/wptobe-multi-vendor/ajax/quiz/coupon_payment.php",
											data: {"coupon_num": frm.coupon_num.value}, 
											dataType: "html",
											cache: false,
											success: function(data, textStatus, jqXHR)
											{
												if(data =="Y")
												{
													location.href="/membership/?success=y";
												}
												else
												{
													document.getElementById("bwlmsmem-cp-failed-id").style.display = "block";
													$("#bwlmsmem-coupon-alert-2-fail").html(data);
													setTimeout(function() {
													  document.getElementById("bwlmsmem-cp-failed-id").style.display = "none";
													}, 3000);

													//alert(data);
													//alert("실패했습니다. 다시시도해주세요");
												}
											},
											error: function(data, textStatus, jqXHR)
											{
												alert("Failed!");
												//alert(textStatus);
												//data - response from server
											}
										});

									}
								</script>
								<?php
									$description = "Monthly";
									$price ="39";
								?>
	
							</div>

						</div>
					</div>

					<div class="col-sm-3 bwlmsmembership-level-table-3L">
					</div>
					<div class="col-sm-9 bwlmsmembership-level-table-3R">
						<div class="row">
							<div class="col-sm-6 bwlmsmembership-level-table-3RL">
							  <div class="bwlmsmem-level-coupon-wrapper">
								<div class="bwlmsmem-level-coupon-linktxt-wrapper">
									<a id="bwlmsmem-level-coupon-linktxt">
										<i class="material-icons">&#xE54E;</i> 
										Apply shop coupon codes
									</a>
								</div>
								<div id="bwlmsmem-level-coupon-input-wrap" class="bwlmsmem-level-coupon-hide-cls">
									<div class="input-group mb-3">
									  <input type="text" name="coupon_num" class="form-control" placeholder="Enter coupon code" aria-label="Recipient's username" aria-describedby="basic-addon2">
									  <div class="input-group-append">
										<button class="btn btn-outline-secondary" type="button" onClick="javascript:coupon_payment();">Apply</button>
									  </div>
									</div>
								</div>

								<script>
									$(document).ready(function(){
									  $("#bwlmsmem-level-coupon-linktxt").click(function(){
										$("#bwlmsmem-level-coupon-input-wrap").addClass("bwlmsmem-level-coupon-show-cls");
										$(".bwlmsmem-level-coupon-linktxt-wrapper").addClass("bwlmsmem-level-coupon-hide-cls");
									  });
									});
								</script>
							  </div>
							</div>
							<div class="col-sm-6 bwlmsmembership-level-table-3RL">
								<a class="pin-payment-button" id="card_c_price" href="https://pay.pin.net.au/r13v?amount=<?php  echo $price;?>&currency=AUD&description=<?php echo $description;?>&success_url=../wp-content/plugins/wptobe-multi-vendor/ajax/quiz/payment.php&amount_editable=false"><button type="button" class="bwlmsmembership-pay-btn btn"><?php _e('Buy Membership','wptobemem');?></button></a>
							</div>
						</div>
					</div>
				</div>
				</form> 

						<?php
							$result = $wpdb->get_results("select * from wp_wptobe_membership_payment  where order_id ='".$user_id."' order by idx asc" );
						?>

          
				<hr class="bwlmsmembership-level-hr">
				<div class="row bwlmsmembership-level-shell-2">
					<div class="col-sm-3 bwlmsmembership-level-table-2L">
						<?php _e('Payment history', 'wptobemem'); ?>
					</div>
					<div class="col-sm-9 bwlmsmembership-level-table-2R">
					</div>
				</div>

				<div class="bwlmsmembership-level-shell-3">
				<!-- Payment history Table: Start -------------------------------------->

					<div class="bwlmslevel_vue2_table_wrapper">
					<!-- VUE Table ------------------>
					
					<?php
						//Step:0> Question Serch Options: Default(All) value (Membership, Status, Amount)(Step 0 -> 4 ->6)
						//$pay_records_membership[] = array( 'value'=>'', 'text'=> 'Membership');
						$pay_records_status[] =  array('value'=>'', 'text'=> 'Status');
						$pay_records_amountmoney[] =  array( 'value'=>'-1', 'text'=> 'Amount');
						
						$i = 0;
						foreach( $result as $row ) 
						{
							$i++;
							//Step:1> Initialize table column variable
							$p_period=''; 
							//$lev_membership='';
							$lev_status= ''; 
							$lev_amount_money=0;
							$lev_expiration=''; 
							//$lev_q_slug='';
							$lev_idx ='';
							$lev_order_id ='';
							$lev_registered ='';
							$lev_payment_date='';
							$lev_charge_token='';
							$payment_method ='';

							//Step:2> Fill out table data

							$no=$i;
							$p_period=$row->product_period;
							$lev_membership= $row->product_name;
							$lev_status= $row->pay_status ;
							$lev_amount_money= "$".substr($row->price,0,-2);
							
							$lev_expiration= change_date($row->expire_date );
							//$lev_expiration= $row->expire_date ;

							//$lev_q_slug='';
							$lev_idx = $row->idx; // 
							$lev_order_id = $row->order_id;
							
							//$lev_registered = $row->registered;
							$lev_registered = change_datetime($row->registered);

							$lev_payment_date  = $row->payment_date ;
							$lev_charge_token  = $row->charge_token ;

							$payment_method  = $row->pay_method ;

							//echo "----------".$row->product_name."================";

							//Step:3> Data array
							$question_records[] = array(
								'no'=> $no, 
								'membership'=> $lev_membership, 
								'p_period'=>$p_period, 
								'status'=> $lev_status, 
								'amount_money'=>$lev_amount_money, 
								'expiration'=>$lev_expiration, 
								'payment_method'=>$payment_method, 
								//'q_slug'=>$lev_q_slug, 
								'level_idx'=>$lev_idx,
								'level_orderid'=>$lev_order_id,
								'level_registered'=>$lev_registered,
								'level_paydate'=>$lev_payment_date,
								'level_chgtoken'=>$lev_charge_token
							);

							//Step:4> Array for search option column (Step 0 -> 4 ->6)
							//$pay_records_membership[] = array( 'value'=> $lev_membership, 'text'=>  $lev_membership);
							$pay_records_status[] = array( 'value'=>$lev_status, 'text'=> $lev_status);
							$pay_records_amountmoney[] = array( 'value'=>$lev_amount_money, 'text'=> $lev_amount_money);
								
						}

						//Step:5> Dummy data (When there is no data)
						if(empty($question_records)) { 
								$question_records[] = array(
									'no'=> '', 
									'membership'=> '', 
									'status'=> '', 
									'amount_money'=>0, 
									'p_period'=>'', 
									'expiration'=>'', 
									'payment_method'=>'', 
									//'q_slug'=>'', 
									'level_idx'=>'',
									'level_orderid'=>'',
									'level_registered'=>'',
									'level_paydate'=>'',
									'level_chgtoken'=>'',
									);
						}

						//Step:6> Remove duplicated lists for search options [Select box](Step 0 -> 4 ->6)
						//$pay_records_membership_new[] = unique_multidim_array($pay_records_membership,'value'); 
						$pay_records_status_new[] = unique_multidim_array($pay_records_status,'value'); 
						$pay_records_amountmoney_new[] = unique_multidim_array($pay_records_amountmoney,'value'); 
						//print_r($pay_records_amountmoney_new);
						?>
						<!-- Step:7> Render VUE Table =================================================== -->
							<div id="WptobeVueTable">

								<!-- Search Options -->
								<div id="bwlmslevel_vue2_table_srh">
									 <div class="container bwlmslevel_vue2_table_srh_max_width">
									  <div class="row justify-content-center">

											<div class="bwlmslevel_vue2_table_srh_top_block">

												<div class="bwlmslevel_vue2_table_srh_filter_wrapper">
												  
												  <span class="bwlmslevel_vue2_table_srh_textbox_wrapper">
													<i class="material-icons">&#xE8B6;</i>
													<b-form-input class="bwlmslevel_vue2_table_srh_inputbox" v-model="filter" placeholder="Search" />
												  </span>

												  <!--span class="bwlmslevel_vue2_table_srh_selectA_wrapper">
													<template>
														<b-form-select v-model="search_by_membership" class="bwlmslevel_vue2_table_srh_selectbox mb-3">
														  <option v-for="f_selectMembership in sortMembership" v-bind:value="f_selectMembership.value" @>
															{{f_selectMembership.text}}
														  </option>
														</b-form-select>
													</template>
													<span class="bwlmslevel_vue2_table_selectbox_down_icon">	
														<i class="material-icons">&#xE313;</i>
													</span>
												  </span-->

												  <span class="bwlmslevel_vue2_table_srh_selectC_wrapper">
													<template>
														<b-form-select v-model="search_by_amountmon" class="bwlmslevel_vue2_table_srh_selectbox">
														  <!--option v-for="f_selectTestNum in amountmon_options" v-bind:value="f_selectTestNum.value" @-->
														  <option v-for="f_selectTestNum in sortTestnums" :key="f_selectTestNum.text" v-bind:value="f_selectTestNum.value" @>
															{{f_selectTestNum.text}} 
														  </option>
														</b-form-select>
													</template>
													<span class="bwlmslevel_vue2_table_selectbox_down_icon">	
														<i class="material-icons">&#xE313;</i>
													</span>
												  </span>

												  <span class="bwlmslevel_vue2_table_srh_selectB_wrapper">
													<template>
														<b-form-select v-model="search_by_status" class="bwlmslevel_vue2_table_srh_selectbox mb-3">
														  <option v-for="f_selectStatus in sortStatus" v-bind:value="f_selectStatus.value" @>
															{{f_selectStatus.text}}
														  </option>
														</b-form-select>
													</template>
													<span class="bwlmslevel_vue2_table_selectbox_down_icon">	
														<i class="material-icons">&#xE313;</i>
													</span>
												  </span>

													<!--b-btn class="m-1 bwlmslevel_vue2_table_srh_btn">Search</b-btn-->
												</div>

											</div>

									  </div>
									 </div>
								</div><!-- Search Options -->

								<b-table 
									show-empty
									hover 
									stacked="md"
									responsive
									:current-page="currentPage"
									:per-page="perPage"
									:striped=false
									:bordered=true
									:outlined=false
									:fields="fields"
									:items="searchOptions"
									:filter="filter"
									:sort-by.sync="sortBy"
									:sort-desc.sync="sortDesc"
									@filtered="onFiltered"
								>
									
									<tr class="bwlmslevel_vue2_table_head_row">
										
										<template slot="no" slot-scope="data">
												{{ data.value }} 
										</template>
										<template slot="membership" slot-scope="data">
												{{ data.value }} 
										</template>
										<template slot="status" slot-scope="data">
												{{ data.value }} 
										</template>
										<template slot="amount_money" slot-scope="data">
												{{ data.value }} 
										</template>
										<template slot="p_period" slot-scope="data">
												<div class="bwlmslevel_vue2_table_td_md">
												{{ data.value }}
												</div>
										</template>

										<template slot="level_idx" slot-scope="data">
											<div v-html="data.value" class="bwlmslevel_vue2_table_td_sm">
											</div>
										</template>

										<template slot="level_orderid" slot-scope="data">
											<div v-html="data.value" class="bwlmslevel_vue2_table_td_sm">
											</div>
										</template>
										<template slot="level_registered" slot-scope="data">
											<div v-html="data.value">
											</div>
										</template>
										<template slot="level_paydate" slot-scope="data">
											<div v-html="data.value" class="bwlmslevel_vue2_table_td_sm">
											</div>
										</template>
										<template slot="level_chgtoken" slot-scope="data">
											<div v-html="data.value" class="bwlmslevel_vue2_table_td_sm">
											</div>
										</template>

										
									</tr>
								</b-table>
								<!--p>
								  Sorting By: <b>{{ sortBy }}</b>,
								  Sort Direction: <b>{{ sortDesc ? 'Descending' : 'Ascending' }}</b>
								</p-->
								<b-pagination size="sm"  :total-rows="totalRows" :per-page="perPage" v-model="currentPage" class="my-0" />

							</div><!--VUE Table id:WptobeVueTable-->

					<!------------------------------->
					</div>


					<script>
					var question_records_json = <?php echo json_encode($question_records); ?>;
					/* var pay_records_membership_json  = <?php echo json_encode($pay_records_membership_new[0]); ?>;*/
					var pay_records_status_json  = <?php echo json_encode($pay_records_status_new[0]); ?>;
					var pay_records_amountmoney_json = <?php echo json_encode($pay_records_amountmoney_new[0]); ?>;


					window.app = new Vue({
					  el: '#WptobeVueTable',

					  data: {
						fields: {
							no: { label: 'no', sortable: true   },
							// Table columns
							level_registered: { label: 'Payment date', sortable: true   },
                       //     level_paydate: { label: 'Payment Date', sortable: true   },
							
						//	p_period: { label: 'Product Period', sortable: true   },
							membership: { label: 'Membership', sortable: true   },
							amount_money: { label: 'Payment amount ', sortable: true   },
                        	payment_method: { label: 'Payment method', sortable: true   },
							expiration: { label: 'Expiration date', sortable: true, formatter: (value) => { return value.replace(/<p>|<\/p>|<br>/g, "")} },
							status: { label: 'Status', sortable: true   },

								

						//	level_idx: { label: 'Idx', sortable: true   },
						//	level_orderid: { label: 'Order ID', sortable: true   },
						//	level_chgtoken: { label: 'Change Token', sortable: true   }
						},
						sortBy: 'no',
						sortDesc: true,
						filter: '',
						listitem:'',
						search_by_membership: '',
						search_by_status: '',
						search_by_amountmon: -1,
						currentPage: 1,
						perPage: 25,
						totalRows: question_records_json.length,
						selectedRecords: [],
						visibleRecords: [],
						
						isBusy: false,
						providerType: 'array',

						status_options:pay_records_status_json,
						//membership_options:  pay_records_membership_json,
						amountmon_options: pay_records_amountmoney_json,
							
						items: question_records_json
					  },
					  computed: {
						provider () {
						  // we are using provider wrappers here to trigger a reload
						},

						searchOptions () {
						  if (this.items) {
								return this.items.filter((listitem) => 
									//listitem.membership.includes(this.search_by_membership) 
									listitem.status.includes(this.search_by_status) 
									&& ((listitem.amount_money == this.search_by_amountmon)||(this.search_by_amountmon == -1))
									//this.search_by_amountmon == -1 > Any amount money
								);
						  }
						},
//						sortMembership: function () {
//							return _.orderBy(this.membership_options, 'text','desc');
//						},
						sortStatus: function () {
							return _.orderBy(this.status_options, 'text');
						},
						sortTestnums: function () {
							return _.orderBy(this.amountmon_options, 'text');
						},

					  },
					  methods: {

						onFiltered (filteredItems) {
						  // Trigger pagination to update the number of buttons/pages due to filtering
						  this.totalRows = filteredItems.length
						  this.currentPage = 1
						},
						clearFilters: function() {
							this.filter = '';
							this.search_by_membership= '';
							this.search_by_status= '';
							this.search_by_amountmon= -1;
						}
					  }
					})
					</script>
				<!-- Payment history Table: End -------------------------------------->

					<!--table class="wptobemv_yourlisting_all_table table" >
						<tr class="wptobemv-yourlisting-listtitlerow">
							<th>
								<?php _e( 'Date', 'wptobemem');?>
							</th>
							<th>
								<?php _e( 'Membership', 'wptobemem');?>
							</th>
							<th>
								<?php _e( 'Amount', 'wptobemem');?>
							</th>
							<th> 
								<?php _e( 'Expiration', 'wptobemem');?> 
							</th>
							<th> 
								<?php _e( 'Status', 'wptobemem');?> 
							</th>
						</tr>

						<tr>
							<td class="wptobemv-yourlisting-colB"> <?php _e( '', 'wptobemem');?> </td>
							<td class="wptobemv-yourlisting-colB"> <?php _e( '', 'wptobemem');?> </td>
							<td class="wptobemv-yourlisting-colB"> <?php _e( '', 'wptobemem');?> </td>
							<td class="wptobemv-yourlisting-colB"> <?php _e( '', 'wptobemem');?> </td>
							<td class="wptobemv-yourlisting-colB"> <?php _e( '', 'wptobemem');?> </td>
						</tr>
					</table-->

				</div>

	<?php
	
	$content = ob_get_contents();
	ob_end_clean();
	
	return $content;
}
add_shortcode('bwlmslevel_level', 'bwlmslevel_shortcode_show_level');