jQuery(document).ready(function(){
		function readURL(input) {
			if (input.files && input.files[0]) {
				var reader = new FileReader(); 
				reader.onload = function (e) {
					$('.bwlmsfields_profilepic').attr('src', e.target.result);
				}                   
				reader.readAsDataURL(input.files[0]);
			}
		}
		$("#bwlmsf_pic").change(function(){
			readURL(this);
		});
	 });