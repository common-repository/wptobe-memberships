function askfirst(text, url)
{
	var answer = confirm (text);
	
	if (answer)
		window.location=url;
}

function getTimestamp()
{
	var t = new Date();
	var r = "" + t.getFullYear() + t.getMonth() + t.getDate() + t.getHours() + t.getMinutes() + t.getSeconds(); 

	return(r);
}

jQuery( function ( $ ) {
	$('.membership_level_change_class').change(function()
	{
			$.ajax(
			{
				url:"/wp-admin/admin-ajax.php",
				type:'POST',
				dataType: "json",
				data:'action=membership_level_change_call&id=' + $(this).attr("id")+'&level='+$(this).val(),
	
				error: function(XMLHttpRequest, textStatus, errorThrown){
				},
				success: function(data, textStatus){
						
						if(textStatus=="success")
						{
							if (data == "0")
							{
								
							}
							else
							{
								//alert("9");
							}
						}
				}
	         });
		 });
} );


function user_del_img(val, img)
{
	var re = confirm("Do you want to delete?");
	if(re)
	{

		jQuery.ajax(
		{
			url:"/wp-admin/admin-ajax.php",
			type:'POST',
			dataType: "json",
			data:'action=user_del_img_ajax_call&val=' + val+'&img=' + img,

			error: function(XMLHttpRequest, textStatus, errorThrown){
				//statusdiv.html('<p class="ajax-error" >You might have left one of the fields blank, or be posting too quickly</p>');
			},
			success: function(data, textStatus){
				
					if(textStatus=="success")
					{
						if (data.errcode == "Y")
						{	
							alert("Deleted successfully!");
							jQuery("#d_"+val).html("");
						}
					}
			}
		 });
	}
}
function user_del_img_admin(user_id, val, img)
{
	var re = confirm("Do you want to delete?");
	if(re)
	{
		jQuery.ajax(
		{
			url:"/wp-admin/admin-ajax.php",
			type:'POST',
			dataType: "json",
			data:'action=user_del_img_admin_ajax_call&user_id=' + user_id+'&val=' + val+'&img=' + img,
			error: function(XMLHttpRequest, textStatus, errorThrown){
			},
			success: function(data, textStatus){
		
					if(textStatus=="success")
					{
						if (data.errcode == "Y")
						{	
							alert("Deleted successfully!");
							jQuery("#d_"+val).html("");
						}
					}
			}
		 });
	}
}