(function ($) {
  "use strict";

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
  // gallery Dropzone initialization

  var storeUrl = document.getElementById('projectForm').action;
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


  function rmvimg(fileid) {
    // If you want to the delete the file on the server as well,
    // you can do the AJAX request here.
    $.ajax({
      url: galleryRemoveUrl,
      type: 'POST',
      data: {
        fileid: fileid
      },
      success: function (data) {
        $("#galleries" + fileid).remove();

      }
    });

  }


  var myDropzone2 = new Dropzone("#myDropzoneII", {
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


  function floorPlaninrmvimg(fileid) {
    // If you want to the delete the file on the server as well,
    // you can do the AJAX request here.
    $.ajax({
      url: floorPlanRemoveUrl,
      type: 'POST',
      data: {
        fileid: fileid
      },
      success: function (data) {
        $("#floorPlan" + fileid).remove();

      }
    });

  }


  //remove existing gallery images
  $(document).on('click', '.rmvbtndb', function () {
    let indb = $(this).data('indb');
    $(".request-loader").addClass("show");
    $.ajax({
      url: galleryImagRrmvdbUrl,
      type: 'POST',
      data: {
        fileid: indb
      },
      success: function (data) {

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
          location.reload();
        }
        $(".request-loader").removeClass("show");
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
      }
    });
  });

  //remove existing gallery images
  $(document).on('click', '.rmvbtndb2', function () {
    let indb = $(this).data('indb');
    $(".request-loader").addClass("show");
    $.ajax({
      url: floorPlanRmvdbUrl,
      type: 'POST',
      data: {
        fileid: indb
      },
      success: function (data) {

        var content = {};
        if (data == 'false') {
          content.message = "You can't delete all images.!!";
          content.title = 'Warning';
          var type = 'warning';
        } else {
          $("#trdb" + indb).remove();
          content.message = 'Floor plan image deleted successfully!';
          content.title = 'Success';
          var type = 'success';
          location.reload();
        }
        $(".request-loader").removeClass("show");
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
      }
    });
  });








  // Form Submit with AJAX Request Start
  $("#projectSubmit").on('click', function (e) {

    $(e.target).attr('disabled', true);
    $(".request-loader").addClass("show");

    if ($(".iconpicker-component").length > 0) {
      $("#inputIcon").val($(".iconpicker-component").find('i').attr('class'));
    }

    let projectForm = document.getElementById('projectForm');
    let fd = new FormData(projectForm);
    let url = $("#projectForm").attr('action');
    let method = $("#projectForm").attr('method');


    if ($("#projectSubmit .summernote").length > 0) {
      $("#projectSubmit .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
      });
    }

    // Append uploaded files to form data
    myDropzone.files.forEach(function (file) {
      fd.append('gallery_images[]', file);
    });
    // Append uploaded files to form data
    myDropzone2.files.forEach(function (file) {
      fd.append('floor_plan_images[]', file);
    });

    $.ajax({
      url: url,
      method: method,
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {
        $(e.target).attr('disabled', false);
        $('.request-loader').removeClass('show');

        $('.em').each(function () {
          $(this).html('');
        });

        if (data.status == 'success') {
          location.reload();
        }




      },
      error: function (error) {

        if (error.responseJSON.deactive) {

          deactive(error)
          $('.request-loader').removeClass('show');
          return
        }
        let errors = ``;

        for (let x in error.responseJSON.errors) {
          errors += `<li>
                <p class="text-danger mb-0">${error.responseJSON.errors[x][0]}</p>
              </li>`;
        }

        $('#propertyErrors ul').html(errors);
        $('#propertyErrors').show();

        $('.request-loader').removeClass('show');

        $('html, body').animate({
          scrollTop: $('#propertyErrors').offset().top - 100
        }, 1000);
      }

    });
    $(e.target).attr('disabled', false);
  });

})(jQuery);
