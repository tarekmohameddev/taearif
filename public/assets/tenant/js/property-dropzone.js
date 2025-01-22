// Initialize Dropzone
var storeUrl = document.getElementById('propertyForm').action;
var uploadedImages = []; // To keep track of uploaded files
Dropzone.autoDiscover = false;

var myDropzone = new Dropzone("#myDropzoneI", {
  url: storeUrl, // Same route as the form
  paramName: "file", // Parameter name for the uploaded files
  autoProcessQueue: false, // Prevent auto-upload
  uploadMultiple: true,
  maxFilesize: 20, // MB
  acceptedFiles: ".jpeg,.jpg,.png,.svg",
  addRemoveLinks: true,
  parallelUploads: 10,
  init: function () {
    var dz = this;

    // Add uploaded files to form data
    dz.on("success", function (file, response) {
      uploadedImages.push(response.filename);
    });

    dz.on("error", function (file, response) {
      console.error("File upload failed:", response);
    });
  }
});


(function ($) {
  "use strict";

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });


  //remove existing images
  $(document).on('click', '.rmvbtndb', function () {
    let indb = $(this).data('indb');
    $(".request-loader").addClass("show");
    $.ajax({
      url: rmvdbUrl,
      type: 'POST',
      data: {
        fileid: indb
      },
      success: function (data) {
        $(".request-loader").removeClass("show");
        var content = {};
        if (data == 'false') {
          content.message = "You can't delete all images.!!";
          content.title = 'Warning';
          var type = 'warning';
        } else {
          $("#trdb" + indb).remove();
          content.message = 'Gallery image deleted successfully!';
          content.title = 'Success';
          var type = 'success';

        }

        content.icon = 'fa fa-bell';

        $.notify(content, {
          type: type,
          placement: {
            from: 'top',
            align: 'right'
          },
          showProgressbar: true,
          time: 1000,
          delay: 4000
        });
        if (content.title == 'Success') {
          window.location.reload()
        }
      }
    });
  });

})(jQuery);

