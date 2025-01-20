$(window).on('load', function () {
  // scroll to bottom
  if ($('.messages-container').length > 0) {
    $('.messages-container')[0].scrollTop = $('.messages-container')[0].scrollHeight;
  }
});

$(document).ready(function () {
  'use strict';



  $('thead').on('click', '.addRow', function (e) {
    e.preventDefault();
    var tr = `<tr>
                <td>
                  ${labels}
                </td>
                <td>
                  ${values}
                </td>
                <td>
                  <a href="javascript:void(0)" class="btn btn-danger  btn-sm deleteRow">
                    <i class="fas fa-minus"></i></a>
                </td>
              </tr>`;
    $('#tbody').append(tr);
  });

  $('tbody').on('click', '.deleteRow', function () {
    $(this).parent().parent().remove();
  });




  // Form Submit with AJAX Request Start
  $("#propertySubmit").on('click', function (e) {

    $(e.target).attr('disabled', true);
    $(".request-loader").addClass("show");

    if ($(".iconpicker-component").length > 0) {
      $("#inputIcon").val($(".iconpicker-component").find('i').attr('class'));
    }

    let propertyForm = document.getElementById('propertyForm');
    let fd = new FormData(propertyForm);
    let url = $("#propertyForm").attr('action');
    let method = $("#propertyForm").attr('method');


    if ($("#propertySubmit .summernote").length > 0) {
      $("#propertySubmit .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
      });
    }
    if (myDropzone && Array.isArray(myDropzone.files) && myDropzone.files.length > 0) {
      // Append uploaded files to form data
      myDropzone.files.forEach(function (file) {
        fd.append('slider_images[]', file);
      });
    }
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

  $("#propertySubmit2").on('click', function (e) {

    $(e.target).attr('disabled', true);
    $(".request-loader").addClass("show");

    if ($(".iconpicker-component").length > 0) {
      $("#inputIcon").val($(".iconpicker-component").find('i').attr('class'));
    }

    let carForm = document.getElementById('propertyForm2');
    let fd = new FormData(carForm);
    let url = $("#propertyForm2").attr('action');
    let method = $("#propertyForm2").attr('method');

    //if summernote has then get summernote content
    $('.form-control').each(function (i) {
      let index = i;

      let $toInput = $('.form-control').eq(index);

      if ($(this).hasClass('summernote')) {
        let tmcId = $toInput.attr('id');
        let content = tinyMCE.get(tmcId).getContent();
        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
      }
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

        $('#propertyErrors2 ul').html(errors);
        $('#propertyErrors2').show();

        $('.request-loader').removeClass('show');

        $('html, body').animate({
          scrollTop: $('#propertyErrors2').offset().top - 100
        }, 1000);
      }

    });
    $(e.target).attr('disabled', false);
  });

  // spacification delete
  $('tbody').on('click', '.deleteSpecification', function () {
    let spacification = $(this).data('specification');
    $('.request-loader').addClass('show');
    $(this).parent().parent().remove();
    $.ajax({
      url: specificationRmvUrl,
      method: 'POST',
      data: {
        spacificationId: spacification,
      },

      success: function (data) {

        if (data.status == 'success') {


          $('.request-loader').removeClass('show');

          var content = {};
          content.message = 'Additional feature has been delete';
          content.title = "Warning";
          content.icon = 'fa fa-bell';

          $.notify(content, {
            type: 'success',
            placement: {
              from: 'top',
              align: 'right'
            },
            showProgressbar: true,
            time: 1000,
            delay: 4000,
          });

        }

      },
      error: function (error) {
        if (data.status == 'success') {

          var content = {};

          content.message = 'Something went worng!';
          content.title = "Warning";
          content.icon = 'fa fa-bell';

          $.notify(content, {
            type: 'warning',
            placement: {
              from: 'top',
              align: 'right'
            },
            showProgressbar: true,
            time: 1000,
            delay: 4000,
          });

        }

      }
    });
  });

});

function searchFormSubmit(e) {
  if (e.keyCode == 13) {
    $('#searchForm').submit();
  }
}
function appendDropzoneFilesToFormData(dropzoneInstance, formData, inputName) {
  if (dropzoneInstance && Array.isArray(dropzoneInstance.files) && dropzoneInstance.files.length > 0) {
    dropzoneInstance.files.forEach(function (file) {
      formData.append(inputName, file);
    });
  }
}
