var base_url = "https://www.bazichic.com";

function getSubsPrice(obj){
	var month = obj.value;
	//alert(month);
	
	if(month == "perYear"){
	document.getElementById('perMonthPremiumSubscription').display = "none";
	document.getElementById('perYearPremiumSubscription').display = "block";
	} else{
	document.getElementById('perMonthPremiumSubscription').display = "block";
	document.getElementById('perYearPremiumSubscription').display = "none";
	}
	//document.getElementById('subPrice').innerHTML = '$'+price+' USD';
	//document.getElementById('paypalValid').value = month;
}

$(document).ready(function() {
//alert("Welcome to Bazichik");

$('#freeTrialsForm').submit(function (e) {
    e.preventDefault();
	//alert($(this).serialize());
	document.getElementById('trial_overlay').style.display = 'block';
	document.getElementById('freeTrialsForm').style.display = 'none';
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
    document.getElementById('trial_overlay').style.display = 'none';
	document.getElementById('freeTrialsForm').style.display = 'block';
			swal({
  title: '',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});

if(false){
    //document.getElementById('approveLayout').style.display = 'block';
}
}else{
//document.getElementById('resultHolder').innerHTML = '<hr><h3>'+data.message+'</h3>';		    
setTimeout(function(){ 
location = base_url+"/dashboard";
}, 100);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        //alert(xhr.status);
        //alert(thrownError);
      }
    });
});


$('#purchasePlanForm').submit(function (e) {
    e.preventDefault();
	//alert($(this).serialize());
	document.getElementById('membership_overlay').style.display = 'block';
	document.getElementById('purchasePlanForm').style.display = 'none';
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
    document.getElementById('membership_overlay').style.display = 'none';
	document.getElementById('purchasePlanForm').style.display = 'block';
			swal({
  title: '',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
}else{
//document.getElementById('resultHolder').innerHTML = '<hr><h3>'+data.message+'</h3>';		    
setTimeout(function(){ 
location = base_url+"/confirm-subscription/"+data.qcode;
}, 100);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        //alert(xhr.status);
        //alert(thrownError);
      }
    });
});


$('.filterDocumentForm').submit(function (e) {
    e.preventDefault();
    var data = $(this).serialize();
    //alert(data);
    //var categoryID = $('#filter_category_id').find('input[name="document_type"]').val();
    var categoryID = $( "#filter_document_type option:selected" ).val();
    //alert("selected "+categoryID);
       /*
    switch(categoryID){
        case 1:
            window.location.replace(base_url+'/e-book-store/e-book');
            break;
            
         case 2:
            window.location.replace(base_url+'/e-book-store/audio-book');
            break;    
        
         case 3:
            window.location.replace(base_url+'/e-book-store/magazine');
            break;
    }
    alert("selected "+categoryID);
    */

    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: data,
		dataType: 'json',
        success: function(data) {
		if(data.error){
		swal({
  title: '',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  allowOutsideClick: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
		swal({
  title: 'Message Sent Successfully.',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
window.location.replace(base_url);
}, 2200)
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        alert(xhr.status);
        //alert(thrownError);
      }
    });
    
});


	
$('#siteContactForm').submit(function (e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
		swal({
  title: '',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  allowOutsideClick: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
		swal({
  title: 'Message Sent Successfully.',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
window.location.replace(base_url);
}, 2200)
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        alert(xhr.status);
        //alert(thrownError);
      }
    });
});

$('#loginForm').submit(function (e) {
    e.preventDefault();
    $("html, body").animate({ scrollTop: 0 }, "slow");
    document.getElementById('login_overlay').style.display = 'block';
	document.getElementById('loginForm').style.display = 'none';
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
	document.getElementById('login_overlay').style.display = 'none';
	document.getElementById('loginForm').style.display = 'block';
		swal({
  title: '',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  allowOutsideClick: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
setTimeout(function(){
   document.getElementById('login_msg').html('<h5>'+data.message+'</h5>');
}, 1500);
	
setTimeout(function(){
    if(data.redirection){
	window.location.replace(base_url+'/'+data.redirection);
}else{
window.location.replace(base_url+'/dashboard');
}
}, 4000);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        alert("Error: "+xhr.status);
        //alert(thrownError);
      }
    });
});


$('#loginPageForm').submit(function (e) {
    e.preventDefault();
    $("html, body").animate({ scrollTop: 0 }, "slow");
    document.getElementById('login_page_overlay').style.display = 'block';
	document.getElementById('loginPageForm').style.display = 'none';
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
	document.getElementById('login_page_overlay').style.display = 'none';
	document.getElementById('loginPageForm').style.display = 'block';
		swal({
  title: '',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  allowOutsideClick: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
setTimeout(function(){
   document.getElementById('login_page_msg').html('<h5>'+data.message+'</h5>');
}, 1500);
	
setTimeout(function(){
    if(data.redirection){
	window.location.replace(base_url+'/'+data.redirection);
}else{
window.location.replace(base_url+'/dashboard');
}
}, 4000);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        alert("Error: "+xhr.status);
        //alert(thrownError);
      }
    });
});

$('#registerForm').submit(function (e) {
    e.preventDefault();
    
     if($('#check_agree').is(":checked")){
    $("html, body").animate({ scrollTop: 0 }, "slow");
	document.getElementById('registerForm').style.display = 'none';
	document.getElementById('register_overlay').style.display = 'block';
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
		 document.getElementById('register_overlay').style.display = 'none';
	     document.getElementById('registerForm').style.display = 'block';   
		swal({
  title: '',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  allowOutsideClick: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
		swal({
  title: '',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
window.location.replace(base_url+'/dashboard');
}, 2200);

		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        alert(xhr.status);
        //alert(thrownError);
      }
    });
    }else{
        swal({
  title: '',
  html: '<b>You must agree that the information provided by you is correct.</b>',
  type: 'warning',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
    }
});




$('#recoverPassForm').submit(function (e) {
    e.preventDefault();
    $("html, body").animate({ scrollTop: 0 }, "slow");
    document.getElementById('recovery_overlay').style.display = 'block';
	document.getElementById('recoverPassForm').style.display = 'none';
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
	document.getElementById('recovery_overlay').style.display = 'none';
	document.getElementById('recoverPassForm').style.display = 'block';
		swal({
  title: '',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  allowOutsideClick: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
		    swal({
  title: '',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  allowOutsideClick: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){
   //document.getElementById('login_msg').html('<h5>'+data.message+'</h5>');
}, 1500);
	
setTimeout(function(){
window.location.replace(base_url+'/login');
}, 4000);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        alert(xhr.status);
        //alert(thrownError);
      }
    });
});


/**********######  REVIEWS  ###### *********/
$('#reviewDocumentForm').submit(function (e) {
    e.preventDefault();
	 //$("html, body").animate({ scrollTop: 0 }, "slow");
	 var stars = $('#rating').val();
	 var plan_msg = "Are you sure you want to submit your review for this project.";
	 if(stars < 0){
		alert("Please rate this project to proceed.");
		return;
	 }
	//alert($(this).serialize());

  swal({
  title: 'Submit Review',
  html: '<b>'+plan_msg+'</b>',
  type: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  allowOutsideClick: false,
  confirmButtonText: 'Confirm'
}).then((result) => {
  if (result.value) {
	  document.getElementById("rating_overlay").style.display = 'block';
	 document.getElementById("reviewDocumentForm").style.display = 'none';
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
	 document.getElementById("rating_overlay").style.display = 'none';
	 document.getElementById("reviewDocumentForm").style.display = 'block';
		swal({
  title: '',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  allowOutsideClick: false,
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
setTimeout(function(){
location.reload()
}, 2500);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        alert(xhr.status);
        //alert(thrownError);
		document.getElementById("form_overlay").style.display = 'none';
	    document.getElementById("projectReviewArea").style.display = 'block';
      }
    });
	/********** End of  Operation *********/
  }
});
	});
/********************************/
	
/**********######  LIBRARY SAVE  ###### *********/
$('.saveDocForm').submit(function (e) {
    e.preventDefault();
	var actionTitle = "Add to library?";
	var actionBtn = "Save Now";
	var actionMsg = "This document will be saved to your library.";
	var submit = $(this).closest('form').find(':submit');
  swal({
  title: actionTitle,
  html: '<b>'+actionMsg+'</b>',
  type: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  confirmButtonText: actionBtn
}).then((result) => {
  if (result.value) {
	  submit.html('<i class="fa fa-refresh fa-spin"></i>Wait...');
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
		submit.html('<i class="fa fa-plus"></i> ADD TO LIBRARY');
		swal({
  title: 'Failed To Save',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  allowOutsideClick: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
swal({
  title: data.title,
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
}).then((result) => {
  if (result.value) {
	  $(this).html(data.next_action);
	  window.location.reload();
	  }
});

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
/********************************/


/**********######  LIKES  ###### *********/
 $('.favouriteDocument').click(function (e) {
    e.preventDefault();
    //alert($(this).serialize());
	var docID = $(this).attr("data-id");
	var inputTitle = $(this).attr("data-title");
	var actionTitle = "Liked this document?";
	var actionBtn = "Favourite Now";
	var actionMsg = "Favourite this document to help others find their best interest. All your favourites will be available in boomarks.";
	if(inputTitle == "Liked"){
		actionTitle = "Confirm";
		actionMsg = "Are you sure that you want to remove your favourite mark from this document?";
		actionBtn = "Remove Now";
	}
  swal({
  title: actionTitle,
  html: '<b>'+actionMsg+'</b>',
  type: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6',
  allowOutsideClick: false,
  cancelButtonColor: '#d33',
  confirmButtonText: actionBtn
}).then((result) => {
  if (result.value) {
	$(this).html('<i class="fa fa-refresh fa-spin"></i>Wait...');
	var formData = {'doc_id':docID};
    $.ajax({
        url: base_url+"/documents/endorse",
        type: "POST",
        data: formData,
		dataType: 'json',
        success: function(data) {
		if(data.error){
		swal({
  title: 'Failed To Process',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
swal({
  title: data.title,
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
}).then((result) => {
  if (result.value) {
	  $(this).html(data.next_action);
	  window.location.reload();
	  }
});

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
/********************************/
});