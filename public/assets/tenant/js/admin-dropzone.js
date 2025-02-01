(function ($) {
  "use strict";

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
  // function dropzone() {
  //   // Dropzone initialization
  //   Dropzone.options.myDropzone = {
  //     acceptedFiles: '.png, .jpg, .jpeg',
  //     url: storeUrl,
  //     success: function (file, response) {
  //       if (response == 'downgrade') {
  //         const progressElements = document.getElementsByClassName('dz-progress');
  //         for (let i = 0; i < progressElements.length; i++) {
  //           progressElements[i].style.opacity = 0;
  //         }

  //         const errorMarkElements = document.getElementsByClassName('dz-error-mark');
  //         for (let i = 0; i < errorMarkElements.length; i++) {
  //           errorMarkElements[i].style.opacity = 1;
  //         }
  //         packageDowngradeNotify();
  //       }
  //       $("#sliders").append(`<input type="hidden" name="slider_images[]" id="slider${response.file_id}" value="${response.file_id}">`);

  //       // Create the remove button
  //       var removeButton = Dropzone.createElement("<button class='rmv-btn'><i class='fa fa-times'></i></button>");

  //       // Capture the Dropzone instance as closure.
  //       var _this = this;
  //       // Listen to the click event
  //       removeButton.addEventListener("click", function (e) {
  //         // Make sure the button click doesn't submit the form:
  //         e.preventDefault();
  //         e.stopPropagation();

  //         _this.removeFile(file);

  //         rmvimg(response.file_id);
  //       });

  //       // Add the button to the file preview element.
  //       file.previewElement.appendChild(removeButton);

  //       if (typeof response.error != 'undefined') {
  //         if (typeof response.file != 'undefined') {
  //           document.getElementById('errpreimg').innerHTML = response.file[0];
  //         }
  //       }
  //     },

  //   };
  // }
  // dropzone()
  // function rmvimg(fileid) {
  //   // If you want to the delete the file on the server as well,
  //   // you can do the AJAX request here.

  //   $.ajax({
  //     url: removeUrl,
  //     type: 'POST',
  //     data: {
  //       fileid: fileid
  //     },
  //     success: function (data) {
  //       $("#slider" + fileid).remove();
  //     }
  //   });

  // }



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
