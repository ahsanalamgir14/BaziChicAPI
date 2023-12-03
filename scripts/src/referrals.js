$('#createReferralForm').submit(function (e) {
    e.preventDefault();
	//var blogID = $(this).attr("data-id");
	//alert("good now");
  swal({
  title: 'Generate New Referral Code',
  text: "Do you want us to create a new referral code for you?",
  type: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  confirmButtonText: 'Yes'
}).then((result) => {
  if (result.value) {
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
		swal({
  title: 'Failed To Generate',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
swal({
  title: 'Code Generated',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
window.location.replace(base_url+'/referral-codes');
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        alert(xhr.status);
        //alert(thrownError);
      }
    });
	/********** End of  Operation *********/
  }
}); 
	});