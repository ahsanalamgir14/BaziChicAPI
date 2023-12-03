var base_url = "https://www.bazichic.com";

/*
function parseDoc(){
var input = document.getElementById("files");
var reader = new FileReader();
reader.readAsBinaryString(input.files[0]);
reader.onloadend = function(){
    var count = reader.result.match(/\/Type[\s]*\/Page[^s]/g).length;
    alert(count);
    console.log('Number of Pages:',count );
}
}
*/

$(document).ready(function() {
    
//     $('#selectFaqCategory').change(function() {
// 		var selectSavedStatus = $(this).val();
// 		//alert(selectSavedStatus);
//   //let data = {'category' : selectSavedStatus};
//   document.getElementById('loadingCategories').style.display = 'block';
   
//     $.ajax({
//         url: base_url+'/apis/faqsubcategories/list/'+selectSavedStatus,
//         type: "GET",
// 		dataType: 'json',
//         success: function(data) {
//         document.getElementById('loadingCategories').style.display = 'none';    
// 		if(data.error){
// 		swal({
//   title: '',
//   html: '<b>'+data.message+'</b>',
//   type: '',
//   position: 'top-right',
//   showCancelButton: false,
//   duration:1600
// });
// //document.getElementById('faqFormOverlay').style.display = 'none';   
// 		}else{
// //alert(JSON.stringify(data.result));
//   var $dropdown = $('#selectFaqSubCategory');                  
// $.each(data.result, function() {
//     //alert(this.title);
//     //$('#selectFaqSubCategory').append(new Option(this.id, this.title));
//     $dropdown.append($("<option></option>")
//                     .attr("value",this.id)
//                     .text(this.title));
// });

// 		}
//         },
//         error: function (xhr, ajaxOptions, thrownError) {
//         alert(xhr.status);
//         //alert(thrownError);
//       }
//     });
    
//     });
    

$('.deleteFAQ').click(function (e) {
    e.preventDefault();
	var category_id = $(this).attr("data-id");
  swal({
  title: 'Delete FAQ',
  text: "Are you sure you want to delete this FAQ?",
  type: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  confirmButtonText: 'Confirm Delete'
}).then((result) => {
  if (result.value) {
	var formData = {'id':category_id};
    $.ajax({
        url: base_url+"/apis/faqs/delete",
        type: "POST",
        data: formData,
         beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
		dataType: 'json',
        success: function(data) {
		if(data.error){
		swal({
  title: 'Failed To Delete',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
swal({
  title: 'FAQ Deleted',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
window.location.replace(base_url+'/manage-faqs');
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
		alert("There was an error.");
      }
    });
	/********** End of  Operation *********/
  }
});  
});

/****************** END OF FAQS ****************************/


/*******************************************************/    
$('#assignSubscriptionForm').submit(function (e) {
     e.preventDefault();
    $("html, body").animate({ scrollTop: 0 }, "slow");
	document.getElementById('assignSubscriptionForm').style.display = 'none';
	document.getElementById('formOverlay').style.display = 'block';
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
		 document.getElementById('assignSubscriptionForm').style.display = 'none';
	     document.getElementById('formOverlay').style.display = 'block';   
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
window.location.replace(base_url+'/view-profile/'+data.user_name);
}, 2200);

		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        alert(xhr.status);
        //alert(thrownError);
      }
    });
});
/**********************************************/


/*******************************************************/    
$('#updateProfileForm').submit(function (e) {
     e.preventDefault();
    $("html, body").animate({ scrollTop: 0 }, "slow");
	document.getElementById('updateProfileForm').style.display = 'none';
	document.getElementById('register_overlay').style.display = 'block';
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
		 document.getElementById('register_overlay').style.display = 'none';
	     document.getElementById('updateProfileForm').style.display = 'block';   
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
window.location.replace(base_url+'/view-profile/'+data.user_name);
}, 2200);

		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        alert(xhr.status);
        //alert(thrownError);
      }
    });
});
/**********************************************/



/**********######  SITE SETTINGS  ###### *********/
$('#siteConfigForm').submit(function (e) {
    e.preventDefault();
    $("html, body").animate({ scrollTop: 0 }, "slow");
	//alert($(this).serialize());
	document.getElementById('settings_overlay').style.display = 'block';
	document.getElementById('siteConfigForm').style.display = 'none';
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
         beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
		document.getElementById('settings_overlay').style.display = 'none';
	    document.getElementById('siteConfigForm').style.display = 'block';
			swal({
  title: 'Save Failed',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
		swal({
  title: 'Settings Saved Successfully',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
location = base_url+"/dashboard";
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        //alert(xhr.status);
        //alert(thrownError);
      }
    });
});


/**********######  MEMBERSHIPS  ###### *********/
$('#updateMembershipForm').submit(function (e) {
    e.preventDefault();
    $("html, body").animate({ scrollTop: 0 }, "slow");
	//alert($(this).serialize());
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
		 beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
        success: function(data) {
		if(data.error){
			swal({
  title: 'Update Failed',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
		swal({
  title: 'Updated Successfully',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
location = base_url+"/manage-membership-plans";
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        //alert(xhr.status);
        //alert(thrownError);
      }
    });
});



$('#bookMembershipForm').submit(function (e) {
    e.preventDefault();
	//alert($(this).serialize());
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
		
        success: function(data) {
		if(data.error){
			swal({
  title: 'Failed to complete',
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
});
setTimeout(function(){ 
location = base_url+"/manage-membership-plans";
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        //alert(xhr.status);
        //alert(thrownError);
      }
    });
});


/**********######  CATEGORIES ###### *********/
$('#addCategoryForm').submit(function (e) {
    e.preventDefault();
	//alert($(this).serialize());
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
		 beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
        success: function(data) {
		if(data.error){
			swal({
  title: 'Failed',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
		swal({
  title: 'Category Added Successfully',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
location = base_url+"/manage-categories";
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        //alert(xhr.status);
        //alert(thrownError);
      }
    });
});

$('#updateCategoryForm').submit(function (e) {
    e.preventDefault();
	//alert($(this).serialize());
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: $(this).serialize(),
		dataType: 'json',
		 beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
        success: function(data) {
		if(data.error){
			swal({
  title: 'Update Failed',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
		swal({
  title: 'Category Updated Successfully',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
location = base_url+"/manage-categories";
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        //alert(xhr.status);
        //alert(thrownError);
      }
    });
});


$('.deleteCategory').click(function (e) {
    e.preventDefault();
	var category_id = $(this).attr("data-id");
  swal({
  title: 'Delete Category',
  text: "Are you sure you want to delete this category?",
  type: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  confirmButtonText: 'Confirm Delete'
}).then((result) => {
  if (result.value) {
	var formData = {'category_id':category_id};
    $.ajax({
        url: base_url+"/categories/delete",
        type: "POST",
        data: formData,
         beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
		dataType: 'json',
        success: function(data) {
		if(data.error){
		swal({
  title: 'Failed To Delete',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
swal({
  title: 'Category Deleted',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
window.location.replace(base_url+'/manage-categories');
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
		alert("There was an error.");
      }
    });
	/********** End of  Operation *********/
  }
});  
});
/**********######  CATEGORIES ###### *********/

/**********######  2. DOCUMENTS ###### *********/
$('#addDocumentForm').submit(function (e) {
    e.preventDefault();
	
	var form = $('#addDocumentForm');
    var data = new FormData(this);
    //alert("authorization: "+authorization);
    document.getElementById("create_doc_overlay").style.display = 'block';
	document.getElementById("addDocumentForm").style.display = 'none';
    //alert(data);
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
        data: data,
		processData: false, 
        contentType: false, 
		dataType: 'json',
        success: function(data) {
		if(data.error){
    document.getElementById("create_doc_overlay").style.display = 'none';
	document.getElementById("addDocumentForm").style.display = 'block';
			swal({
  title: 'Failed',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  allowOutsideClick: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
setTimeout(function(){
location = base_url+"/edit-document/"+data.qcode;
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        //alert(xhr.status);
        //alert(thrownError);
      }
    });
});



$('#updateDocumentForm').submit(function (e) {
    e.preventDefault();
	var form = $('#updateDocumentForm');
    var data = new FormData(this);
    document.getElementById("update_doc_overlay").style.display = 'block';
	document.getElementById("updateDocumentForm").style.display = 'none';
    //alert(data);
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: data,
        beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
		processData: false, 
        contentType: false, 
		dataType: 'json',
        success: function(data) {
		if(data.error){
    document.getElementById("update_doc_overlay").style.display = 'none';
	document.getElementById("updateDocumentForm").style.display = 'block';
			swal({
  title: 'Failed',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  allowOutsideClick: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
		swal({
  title: 'Document Updated Successfully',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  allowOutsideClick: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){
location = base_url+"/manage-documents";
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        //alert(xhr.status);
        //alert(thrownError);
      }
    });
});



$('#docMediaUploadForm').submit(function (e) {
    e.preventDefault();
	var form = $('#docMediaUploadForm');
    var data = new FormData(this);
    
    /********######## THEN SWAL #######*******/
      swal({
  title: 'Upload Cover',
  text: "Are you sure that you want to upload this cover?",
  type: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  confirmButtonText: 'Confirm'
}).then((result) => {
  if (result.value) {
    document.getElementById("doc_cover_overlay").style.display = 'block';
	document.getElementById("docMediaUploadForm").style.display = 'none';
    //alert(data);
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: data,
        beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
		processData: false, 
        contentType: false, 
		dataType: 'json',
        success: function(data) {
		if(data.error){
    document.getElementById("doc_cover_overlay").style.display = 'none';
	document.getElementById("docMediaUploadForm").style.display = 'block';
			swal({
  title: 'Failed',
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
location = base_url+"/manage-documents";
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        //alert(xhr.status);
        //alert(thrownError);
      }
    });
	/********** End of  Operation *********/
  }
}); 
/**** END THEN SWAL******/
});




$('.deleteDocument').click(function (e) {
    e.preventDefault();
	var doc_id = $(this).attr("data-id");
  swal({
  title: 'Delete Document',
  text: "Are you sure you want to delete this document?",
  type: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  confirmButtonText: 'Confirm Delete'
}).then((result) => {
  if (result.value) {
	var formData = {'doc_id':doc_id};
    $.ajax({
        url: base_url+"/documents/delete",
        type: "POST",
        data: formData,
        beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
		dataType: 'json',
        success: function(data) {
		if(data.error){
		swal({
  title: 'Failed To Delete',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
swal({
  title: 'Document Deleted',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
window.location.replace(base_url+'/manage-documents');
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
		alert("There was an error.");
      }
    });
	/********** End of  Operation *********/
  }
});  
});

/********************************/
$('#profileForm').submit(function (e) {
    e.preventDefault();
	//alert($(this).serialize());
	$("html, body").animate({ scrollTop: 0 }, "slow");
	var form = $('#profileForm');
    var data = new FormData(this);
    var admin_mode =  $('#admin_mode').val();
    document.getElementById("profileFormOverlay").style.display = 'block';
	document.getElementById("profileForm").style.display = 'none';
	
    //alert(admin_mode);
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        data: data,
        beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
		processData: false, 
        contentType: false, 
		dataType: 'json',
        success: function(data) {
		if(data.error){
		 document.getElementById("profileFormOverlay").style.display = 'none';
     	document.getElementById("profileForm").style.display = 'block';    
		swal({
  title: 'Failed to Save',
  html: '<b>'+data.message+'</b>',
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
swal({
  title: 'Profile Updated',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
    if(admin_mode){
       location = base_url+"/manage-users"; 
    }else{
        location = base_url+"/profile";
    }
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        alert(xhr.status);
        //alert(thrownError);
      }
    });
});



$('#profileCredForm').submit(function (e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        type: "POST",
        beforeSend: function(request) {
               request.setRequestHeader("Authorization", authorization);
             },
        data: $(this).serialize(),
		dataType: 'json',
        success: function(data) {
		if(data.error){
			swal({
  title: 'Please Check',
  html: '<b>'+data.message+'</b>',
  allowOutsideClick: false,
  type: 'error',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
		}else{
		swal({
  title: 'Password Updated',
  html: '<b>'+data.message+'</b>',
  type: 'success',
  showCancelButton: false,
  confirmButtonColor: '#3085d6',
  focusConfirm: false
});
setTimeout(function(){ 
location.reload();
}, 2200);
		}
        },
        error: function (xhr, ajaxOptions, thrownError) {
        //alert(xhr.status);
        //alert(thrownError);
      }
    });
});


});