"use strict";

WebFont.load({
  google: { "families": ["Lato:300,400,700,900"] },
  custom: { "families": ["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: [mainurl + '/assets/admin/css/fonts.min.css'] },
  active: function () {
    sessionStorage.fonts = true;
  }
});

/*****************************************************
  ==========Bootstrap Notify start==========
  ******************************************************/

function bootnotify(message, title, type) {
  var content = {};

  content.message = message;
  content.title = title;
  content.icon = 'fa fa-bell';

  $.notify(content, {
    type: type,
    placement: {
      from: 'top',
      align: 'right'
    },
    showProgressbar: true,
    time: 1000,
    allow_dismiss: true,
    delay: 4000
  });
}
/*****************************************************
==========Bootstrap Notify end==========
******************************************************/

/*****************************************************
 ==========Demo code ==========
 ******************************************************/
if (demo_mode == 'active') {
  $.ajaxSetup({
    beforeSend: function (jqXHR, settings, event) {
      if (settings.type == 'POST' && settings.url.indexOf('user/qr-code/generate') == -1) {
        if ($(".request-loader").length > 0) {
          $(".request-loader").removeClass('show');
        }
        if ($(".modal").length > 0) {
          $(".modal").modal('hide');
        }
        if ($("button[disabled='disabled']").length > 0) {
          $("button[disabled='disabled']").removeAttr('disabled');
        }
        bootnotify('This is demo version. You cannot change anything here!', 'Demo Version', 'warning')
        jqXHR.abort(event);
      }
    },
    complete: function () {
      // hide progress spinner
    }
  });
}
/*****************************************************
==========Demo code end==========
******************************************************/


function cloneInput(fromId, toId, event) {

  let $target = $(event.target);
  let $formId = $('#' + fromId);

  if ($target.is(':checked')) {
    $('#' + fromId + ' .form-control').each(function (i) {
      let index = i;
      let val = $(this).val();
      let $toInput = $('#' + toId + ' .form-control').eq(index);
      // console.log($toInput)
      if ($(this).hasClass('summernote')) {
        $toInput.summernote('code', val);
      } else if ($(this).data('role') == 'tagsinput') {
        if (val.length > 0) {
          let tags = val.split(',');
          tags.forEach(tag => {
            $toInput.tagsinput('add', tag);
          });
        } else {
          $toInput.tagsinput('removeAll');
        }
      } else if ($(this).data('role') == 'checkbox') {
        if ($(this).is(':checked')) {
          $toInput.prop('checked', true);
        }
      } else {
        $toInput.val(val);
      }
    });
  } else {
    $('#' + toId + ' .form-control').each(function (i) {
      let $toInput = $('#' + toId + ' .form-control').eq(i);

      if ($(this).hasClass('summernote')) {
        $toInput.summernote('code', '');
      } else if ($(this).data('role') == 'tagsinput') {
        $toInput.tagsinput('removeAll');
      } else {
        $toInput.val('');
      }
    });
  }
}

$(function ($) {

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  /* ***************************************************************
  ==========disabling default behave of form submits start==========
  *****************************************************************/
  $("#ajaxEditForm").attr('onsubmit', 'return false');
  $("#ajaxForm").attr('onsubmit', 'return false');
  $(".modalform").attr('onsubmit', 'return false');
  /* *************************************************************
  ==========disabling default behave of form submits end==========
  ***************************************************************/

  // make any post as a featured post or not.
  $(document).on('change', '.featured-portfoliCat', function () {
    $('.request-loader').addClass('show');
    let catInfo = $(this).data();
    $("#featuredPortfoliCat" + catInfo.data).submit();
  });


  // get subcategory for item insert
  $(document).on('change', '.getSubCategory', function () {
    let url = $("#subcatGetterForItem").attr('value');
    let id = $(this).val();
    let code = $(this).data('code');

    var formData = new FormData();
    formData.append('url', url);
    formData.append('category_id', id);
    formData.append('code', code);
    $.ajax({
      url: url,
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        jQuery("#" + code + '_subcategory').empty();
        jQuery.each(response.subcategories, function (key, value) {
          jQuery("#" + code + '_subcategory').append('<option value="' + value.id + '">' + value.name + '</option>')
        });
      },
      error: function (data) {
        console.log('Error......');
      }
    });
  });

  // Language wise Category
  $('.category_language').on('change', function () {
    $('.request-loader').addClass('show');
    // send ajax request to get all the categories of that selected language
    $.get(mainurl + "/user/subcategory/get-categories/" + $(this).val(), function (response) {
      console.log(response, "response")
      $('.request-loader').removeClass('show');
      if ('successData' in response) {
        $('select[name="category_id"]').removeAttr('disabled');
        let categoryData = response.successData;
        let markup = `<option selected disabled>Select a category</option>`;
        if (categoryData.length > 0) {
          for (let index = 0; index < categoryData.length; index++) {
            markup += `<option value="${categoryData[index].id}">${categoryData[index].name}</option>`;
          }
        } else {
          markup += `<option>No Category Exist</option>`;
        }
        $('select[name="category_id"]').html(markup);
      } else {
        alert(response.errorData);
      }
    });
  });

  // Sidebar Search

  $(".sidebar-search").on('input', function () {
    let term = $(this).val().toLowerCase();

    if (term.length > 0) {
      $(".sidebar ul li.nav-item").each(function (i) {
        let menuName = $(this).find("p").text().toLowerCase();
        let $mainMenu = $(this);

        // if any main menu is matched
        if (menuName.indexOf(term) > -1) {
          $mainMenu.removeClass('d-none');
          $mainMenu.addClass('d-block');
        } else {
          let matched = 0;
          let count = 0;
          // search sub-items of the current main menu (which is not matched)
          $mainMenu.find('span.sub-item').each(function (i) {
            // if any sub-item is matched  of the current main menu, set the flag
            if ($(this).text().toLowerCase().indexOf(term) > -1) {
              count++;
              matched = 1;
            }
          });


          // if any sub-item is matched  of the current main menu (which is not matched)
          if (matched == 1) {
            $mainMenu.removeClass('d-none');
            $mainMenu.addClass('d-block');
          } else {
            $mainMenu.removeClass('d-block');
            $mainMenu.addClass('d-none');
          }
        }
      });
    } else {
      $(".sidebar ul li.nav-item").addClass('d-block');
    }
  });




  /* ***************************************************
  ==========bootstrap datepicker start==========
  ******************************************************/
  $('.datepicker').datepicker({
    autoclose: true,
    format: 'yyyy-mm-dd'
  });
  /* ***************************************************
  ==========bootstrap datepicker end==========
  ******************************************************/



  /* ***************************************************
  ==========fontawesome icon picker start==========
  ******************************************************/
  $('.icp-dd').iconpicker();

  $('.icp').on('iconpickerSelected', function (event) {
    $("#inputIcon").val($(".iconpicker-component").find('i').attr('class'));
  });
  $('.icp-dd2').iconpicker();
  $('.icp-dd2').on('iconpickerSelected', function (event) {
    $("#in_icon").val($(".iconpicker-upd").find('i').attr('class'));
  });

  /* ***************************************************
  ==========fontawesome icon picker upload end==========
  ******************************************************/


  /* ***************************************************
  ==========Summernote initialization start==========
  ******************************************************/
  $(".summernote").each(function (i) {
    let theight;
    let $summernote = $(this);
    if ($(this).data('height')) {
      theight = $(this).data('height');
    } else {
      theight = 200;
    }
    $('.summernote').eq(i).summernote({
      height: theight,
      dialogsInBody: true,
      dialogsFade: false,
      toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'underline', 'clear']],
        ['fontname', ['fontname']],
        ['fontsize', ['fontsize']],
        ['height', ['height']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link', 'picture', 'video']],
        ['view', ['fullscreen', 'codeview', 'help']],
      ],
      popover: {
        image: [
          ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone']],
          ['float', ['floatLeft', 'floatRight', 'floatNone']],
          ['remove', ['removeMedia']]
        ],
        link: [
          ['link', ['linkDialogShow', 'unlink']]
        ],
        table: [
          ['add', ['addRowDown', 'addRowUp', 'addColLeft', 'addColRight']],
          ['delete', ['deleteRow', 'deleteCol', 'deleteTable']],
        ],
        air: [
          ['color', ['color']],
          ['font', ['bold', 'underline', 'clear']],
          ['para', ['ul', 'paragraph']],
          ['table', ['table']],
          ['insert', ['link', 'picture']]
        ]
      },
      callbacks: {
        onImageUpload: function (files) {
          $(".request-loader").addClass('show');

          let fd = new FormData();
          fd.append('image', files[0]);

          $.ajax({
            url: imgupload,
            method: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            success: function (data) {
              $summernote.summernote('insertImage', data);
              $(".request-loader").removeClass('show');
            }
          });

        }
      }
    });
  });



  $(document).on('click', ".note-video-btn", function () {

    let i = $(this).index();

    if ($(".summernote").eq(i).parents(".modal").length > 0) {

      setTimeout(() => {
        $("body").addClass('modal-open');
      }, 500);
    }
  });


  /* ***************************************************
  ==========Summernote initialization end==========
  ******************************************************/




  $('.icp-dd').iconpicker();
  $('.icp').on('iconpickerSelected', function (event) {
    $("#inputIcon").val($(".iconpicker-component").find('i').attr('class'));
  });

  $('.icp-dd2').iconpicker();
  $('.icp2').on('iconpickerSelected', function (event) {
    $("#inputIcon2").val($(".picker").find('i').attr('class'));
  });

  /* ***************************************************
  ==========Summernote initialization end==========
  ******************************************************/



  /* ***************************************************
  ==========Bootstrap Notify start==========
  ******************************************************/
  function bootnotify(message, title, type) {
    var content = {};

    content.message = message;
    content.title = title;
    content.icon = 'fa fa-bell';

    $.notify(content, {
      type: type,
      placement: {
        from: 'top',
        align: 'right'
      },
      showProgressbar: true,
      time: 1000,
      allow_dismiss: true,
      delay: 4000,
    });
  }
  /* ***************************************************
  ==========Bootstrap Notify end==========
  ******************************************************/



  /* ***************************************************
  ==========Form Submit with AJAX Request Start==========
  ******************************************************/


  // submitBtn_item_add
  $(document).on('click', '#submitBtn_item_add', function (e) {
    console.log('clicked2');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('whyChooseUsSecForm_item_add');
    let fd = new FormData(ajaxForm);
    let url = $("#whyChooseUsSecForm_item_add").attr('action');
    let method = $("#whyChooseUsSecForm_item_add").attr('method');

    if ($("#whyChooseUsSecForm_item_add .summernote").length > 0) {
        $("#whyChooseUsSecForm_item_add .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
        });
    }

    $.ajax({
        url: url,
        method: method,
        data: fd,
        contentType: false,
        processData: false,
        success: function (data) {
        console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
            $(this).html('');
        })
        if (data == "warning") {
            location.reload();
        }
        if (data == "success") {
            location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
            for (let x in data) {
            if (x == 'error') {
                continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
            }
        }
        },
        error: function (error) {

        $(".em").each(function () {
            $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
            document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
        }
    });
  });

  // updateBtnbrand
  $(document).on('click', '#updateBtnbrand', function (e) {
    console.log('clicked2');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('ajaxEditFormbrand');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxEditFormbrand").attr('action');
    let method = $("#ajaxEditFormbrand").attr('method');

    if ($("#ajaxEditFormbrand .summernote").length > 0) {
        $("#ajaxEditFormbrand .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
        });
    }

    $.ajax({
        url: url,
        method: method,
        data: fd,
        contentType: false,
        processData: false,
        success: function (data) {
        console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
            $(this).html('');
        })
        if (data == "warning") {
            location.reload();
        }
        if (data == "success") {
            location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
            for (let x in data) {
            if (x == 'error') {
                continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
            }
        }
        },
        error: function (error) {

        $(".em").each(function () {
            $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
            document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
        }
    });
  });

  // updateBtnPropertyAmenity
  $(document).on('click', '#updateBtnPropertyAmenity', function (e) {
    console.log('clicked2');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('ajaxEditFormPropertyAmenity');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxEditFormPropertyAmenity").attr('action');
    let method = $("#ajaxEditFormPropertyAmenity").attr('method');

    if ($("#ajaxEditFormPropertyAmenity .summernote").length > 0) {
        $("#ajaxEditFormPropertyAmenity .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
        });
    }

    $.ajax({
        url: url,
        method: method,
        data: fd,
        contentType: false,
        processData: false,
        success: function (data) {
        console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
            $(this).html('');
        })
        if (data == "warning") {
            location.reload();
        }
        if (data == "success") {
            location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
            for (let x in data) {
            if (x == 'error') {
                continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
            }
        }
        },
        error: function (error) {

        $(".em").each(function () {
            $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
            document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
        }
    });
  });

  // submitBtnPropertyAmenity
  $(document).on('click', '#submitBtnPropertyAmenity', function (e) {
    console.log('clicked2');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('ajaxFormPropertyAmenity');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxFormPropertyAmenity").attr('action');
    let method = $("#ajaxFormPropertyAmenity").attr('method');

    if ($("#ajaxFormPropertyAmenity .summernote").length > 0) {
        $("#ajaxFormPropertyAmenity .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
        });
    }

    $.ajax({
        url: url,
        method: method,
        data: fd,
        contentType: false,
        processData: false,
        success: function (data) {
        console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
            $(this).html('');
        })
        if (data == "warning") {
            location.reload();
        }
        if (data == "success") {
            location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
            for (let x in data) {
            if (x == 'error') {
                continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
            }
        }
        },
        error: function (error) {

        $(".em").each(function () {
            $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
            document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
        }
    });
  });

  // updateBtnPropertyCategory
  $(document).on('click', '#updateBtnPropertyCategory', function (e) {
    console.log('clicked2');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('ajaxEditFormPropertyCategory');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxEditFormPropertyCategory").attr('action');
    let method = $("#ajaxEditFormPropertyCategory").attr('method');

    if ($("#ajaxEditFormPropertyCategory .summernote").length > 0) {
        $("#ajaxEditFormPropertyCategory .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
        });
    }

    $.ajax({
        url: url,
        method: method,
        data: fd,
        contentType: false,
        processData: false,
        success: function (data) {
        console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
            $(this).html('');
        })
        if (data == "warning") {
            location.reload();
        }
        if (data == "success") {
            location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
            for (let x in data) {
            if (x == 'error') {
                continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
            }
        }
        },
        error: function (error) {

        $(".em").each(function () {
            $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
            document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
        }
    });
  });

  // submitBtnPropertyCategory
  $(document).on('click', '#submitBtnPropertyCategory', function (e) {
    console.log('clicked2');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('ajaxFormPropertyCategory');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxFormPropertyCategory").attr('action');
    let method = $("#ajaxFormPropertyCategory").attr('method');

    if ($("#ajaxFormPropertyCategory .summernote").length > 0) {
        $("#ajaxFormPropertyCategory .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
        });
    }

    $.ajax({
        url: url,
        method: method,
        data: fd,
        contentType: false,
        processData: false,
        success: function (data) {
        console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
            $(this).html('');
        })
        if (data == "warning") {
            location.reload();
        }
        if (data == "success") {
            location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
            for (let x in data) {
            if (x == 'error') {
                continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
            }
        }
        },
        error: function (error) {

        $(".em").each(function () {
            $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
            document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
        }
    });
  });

  // submitBtnQuick_links
  $(document).on('click', '#submitBtnQuick_links', function (e) {
    console.log('clicked2');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('ajaxFormQuick_links');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxFormQuick_links").attr('action');
    let method = $("#ajaxFormQuick_links").attr('method');

    if ($("#ajaxFormQuick_links .summernote").length > 0) {
        $("#ajaxFormQuick_links .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
        });
    }

    $.ajax({
        url: url,
        method: method,
        data: fd,
        contentType: false,
        processData: false,
        success: function (data) {
        console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
            $(this).html('');
        })
        if (data == "warning") {
            location.reload();
        }
        if (data == "success") {
            location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
            for (let x in data) {
            if (x == 'error') {
                continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
            }
        }
        },
        error: function (error) {

        $(".em").each(function () {
            $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
            document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
        }
    });
  });

  // updateBtn_quick_links
  $(document).on('click', '#updateBtn_quick_links', function (e) {
    console.log('clicked2');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('ajaxEditForm_quick_links');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxEditForm_quick_links").attr('action');
    let method = $("#ajaxEditForm_quick_links").attr('method');

    if ($("#ajaxEditForm_quick_links .summernote").length > 0) {
        $("#ajaxEditForm_quick_links .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
        });
    }

    $.ajax({
        url: url,
        method: method,
        data: fd,
        contentType: false,
        processData: false,
        success: function (data) {
        console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
            $(this).html('');
        })
        if (data == "warning") {
            location.reload();
        }
        if (data == "success") {
            location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
            for (let x in data) {
            if (x == 'error') {
                continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
            }
        }
        },
        error: function (error) {

        $(".em").each(function () {
            $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
            document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
        }
    });
  });
  // submitBtnAbout
  $(document).on('click', '#submitBtnAbout', function (e) {
    console.log('clicked2');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('ajaxFormAbout');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxFormAbout").attr('action');
    let method = $("#ajaxFormAbout").attr('method');

    if ($("#ajaxFormAbout .summernote").length > 0) {
        $("#ajaxFormAbout .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
        });
    }

    $.ajax({
        url: url,
        method: method,
        data: fd,
        contentType: false,
        processData: false,
        success: function (data) {
        console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
            $(this).html('');
        })
        if (data == "warning") {
            location.reload();
        }
        if (data == "success") {
            location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
            for (let x in data) {
            if (x == 'error') {
                continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
            }
        }
        },
        error: function (error) {

        $(".em").each(function () {
            $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
            document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
        }
    });
  });

  // submitBtnservice
  $(document).on('click', '#submitBtnservice', function (e) {
      console.log('clicked2');
      $(e.target).attr('disabled', true);

      $(".request-loader").addClass("show");

      let ajaxForm = document.getElementById('ajaxFormservice');
      let fd = new FormData(ajaxForm);
      let url = $("#ajaxFormservice").attr('action');
      let method = $("#ajaxFormservice").attr('method');

      if ($("#ajaxFormservice .summernote").length > 0) {
          $("#ajaxFormservice .summernote").each(function (i) {
          let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

          fd.delete($(this).attr('name'));
          fd.append($(this).attr('name'), content);
          });
      }

      $.ajax({
          url: url,
          method: method,
          data: fd,
          contentType: false,
          processData: false,
          success: function (data) {
          console.log(data, 'success', typeof data.error);
          $(e.target).attr('disabled', false);
          $(".request-loader").removeClass("show");

          $(".em").each(function () {
              $(this).html('');
          })
          if (data == "warning") {
              location.reload();
          }
          if (data == "success") {
              location.reload();
          }

          // if error occurs
          else if (typeof data.error != 'undefined') {
              for (let x in data) {
              if (x == 'error') {
                  continue;
              }
              document.getElementById('err' + x).innerHTML = data[x][0];
              }
          }
          },
          error: function (error) {

          $(".em").each(function () {
              $(this).html('');
          })
          console.log(error.responseJSON.errors);
          for (let x in error.responseJSON.errors) {
              document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
          }
          $(".request-loader").removeClass("show");
          $(e.target).attr('disabled', false);
          }
      });
  });

  // submitBtnFooter Footer
  $(document).on('click', '#submitBtnFooter', function (e) {
  console.log('clicked2');
  $(e.target).attr('disabled', true);

  $(".request-loader").addClass("show");

  let ajaxForm = document.getElementById('ajaxFormFooter');
  let fd = new FormData(ajaxForm);
  let url = $("#ajaxFormFooter").attr('action');
  let method = $("#ajaxFormFooter").attr('method');

  if ($("#ajaxFormFooter .summernote").length > 0) {
      $("#ajaxFormFooter .summernote").each(function (i) {
      let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

      fd.delete($(this).attr('name'));
      fd.append($(this).attr('name'), content);
      });
  }

  $.ajax({
      url: url,
      method: method,
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {
      console.log(data, 'success', typeof data.error);
      $(e.target).attr('disabled', false);
      $(".request-loader").removeClass("show");

      $(".em").each(function () {
          $(this).html('');
      })
      if (data == "warning") {
          location.reload();
      }
      if (data == "success") {
          location.reload();
      }

      // if error occurs
      else if (typeof data.error != 'undefined') {
          for (let x in data) {
          if (x == 'error') {
              continue;
          }
          document.getElementById('err' + x).innerHTML = data[x][0];
          }
      }
      },
      error: function (error) {

      $(".em").each(function () {
          $(this).html('');
      })
      console.log(error.responseJSON.errors);
      for (let x in error.responseJSON.errors) {
          document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
      }
      $(".request-loader").removeClass("show");
      $(e.target).attr('disabled', false);
      }
  });
  });

  //  submitBtnPortfolio
  $(document).on('click', '#submitBtnPortfolio', function (e) {
      console.log('submitBtnPortfolio');
      $(e.target).attr('disabled', true);

      $(".request-loader").addClass("show");

      let ajaxForm = document.getElementById('ajaxFormPortfolio');
      let fd = new FormData(ajaxForm);
      let url = $("#ajaxFormPortfolio").attr('action');
      let method = $("#ajaxFormPortfolio").attr('method');

      if ($("#ajaxFormPortfolio .summernote").length > 0) {
          $("#ajaxFormPortfolio .summernote").each(function (i) {
          let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

          fd.delete($(this).attr('name'));
          fd.append($(this).attr('name'), content);
          });
      }

      $.ajax({
          url: url,
          method: method,
          data: fd,
          contentType: false,
          processData: false,
          success: function (data) {
          console.log(data, 'success', typeof data.error);
          $(e.target).attr('disabled', false);
          $(".request-loader").removeClass("show");

          $(".em").each(function () {
              $(this).html('');
          })
          if (data == "warning") {
              location.reload();
          }
          if (data == "success") {
              location.reload();
          }

          // if error occurs
          else if (typeof data.error != 'undefined') {
              for (let x in data) {
              if (x == 'error') {
                  continue;
              }
              document.getElementById('err' + x).innerHTML = data[x][0];
              }
          }
          },
          error: function (error) {

          $(".em").each(function () {
              $(this).html('');
          })
          console.log(error.responseJSON.errors);
          for (let x in error.responseJSON.errors) {
              document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
          }
          $(".request-loader").removeClass("show");
          $(e.target).attr('disabled', false);
          }
      });
  });

  //  submitBtnportfolioCategory
  $(document).on('click', '#submitBtnportfolioCategory', function (e) {
      console.log('submitBtnportfolioCategory');
      $(e.target).attr('disabled', true);

      $(".request-loader").addClass("show");

      let ajaxForm = document.getElementById('ajaxFormPortfolioCategory');
      let fd = new FormData(ajaxForm);
      let url = $("#ajaxFormPortfolioCategory").attr('action');
      let method = $("#ajaxFormPortfolioCategory").attr('method');

      if ($("#ajaxFormPortfolioCategory .summernote").length > 0) {
          $("#ajaxFormPortfolioCategory .summernote").each(function (i) {
          let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

          fd.delete($(this).attr('name'));
          fd.append($(this).attr('name'), content);
          });
      }

      $.ajax({
          url: url,
          method: method,
          data: fd,
          contentType: false,
          processData: false,
          success: function (data) {
          console.log(data, 'success', typeof data.error);
          $(e.target).attr('disabled', false);
          $(".request-loader").removeClass("show");

          $(".em").each(function () {
              $(this).html('');
          })
          if (data == "warning") {
              location.reload();
          }
          if (data == "success") {
              location.reload();
          }

          // if error occurs
          else if (typeof data.error != 'undefined') {
              for (let x in data) {
              if (x == 'error') {
                  continue;
              }
              document.getElementById('err' + x).innerHTML = data[x][0];
              }
          }
          },
          error: function (error) {

          $(".em").each(function () {
              $(this).html('');
          })
          console.log(error.responseJSON.errors);
          for (let x in error.responseJSON.errors) {
              document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
          }
          $(".request-loader").removeClass("show");
          $(e.target).attr('disabled', false);
          }
      });
  });

  //  submitBtnServices
  $(document).on('click', '#submitBtnServices', function (e) {
      console.log('submitBtnServices');
      $(e.target).attr('disabled', true);

      $(".request-loader").addClass("show");

      let ajaxForm = document.getElementById('ajaxFormServices');
      let fd = new FormData(ajaxForm);
      let url = $("#ajaxFormServices").attr('action');
      let method = $("#ajaxFormServices").attr('method');

      if ($("#ajaxFormServices .summernote").length > 0) {
          $("#ajaxFormServices .summernote").each(function (i) {
          let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

          fd.delete($(this).attr('name'));
          fd.append($(this).attr('name'), content);
          });
      }

      $.ajax({
          url: url,
          method: method,
          data: fd,
          contentType: false,
          processData: false,
          success: function (data) {
          console.log(data, 'success', typeof data.error);
          $(e.target).attr('disabled', false);
          $(".request-loader").removeClass("show");

          $(".em").each(function () {
              $(this).html('');
          })
          if (data == "warning") {
              location.reload();
          }
          if (data == "success") {
              location.reload();
          }

          // if error occurs
          else if (typeof data.error != 'undefined') {
              for (let x in data) {
              if (x == 'error') {
                  continue;
              }
              document.getElementById('err' + x).innerHTML = data[x][0];
              }
          }
          },
          error: function (error) {

          $(".em").each(function () {
              $(this).html('');
          })
          console.log(error.responseJSON.errors);
          for (let x in error.responseJSON.errors) {
              document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
          }
          $(".request-loader").removeClass("show");
          $(e.target).attr('disabled', false);
          }
      });
  });

  //  submitBtnAchievement
  $(document).on('click', '#submitBtnAchievement', function (e) {
      console.log('submitBtnAchievement');
      $(e.target).attr('disabled', true);

      $(".request-loader").addClass("show");

      let ajaxForm = document.getElementById('ajaxFormAchievement');
      let fd = new FormData(ajaxForm);
      let url = $("#ajaxFormAchievement").attr('action');
      let method = $("#ajaxFormAchievement").attr('method');

      if ($("#ajaxFormAchievement .summernote").length > 0) {
          $("#ajaxFormAchievement .summernote").each(function (i) {
          let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

          fd.delete($(this).attr('name'));
          fd.append($(this).attr('name'), content);
          });
      }

      $.ajax({
          url: url,
          method: method,
          data: fd,
          contentType: false,
          processData: false,
          success: function (data) {
          console.log(data, 'success', typeof data.error);
          $(e.target).attr('disabled', false);
          $(".request-loader").removeClass("show");

          $(".em").each(function () {
              $(this).html('');
          })
          if (data == "warning") {
              location.reload();
          }
          if (data == "success") {
              location.reload();
          }

          // if error occurs
          else if (typeof data.error != 'undefined') {
              for (let x in data) {
              if (x == 'error') {
                  continue;
              }
              document.getElementById('err' + x).innerHTML = data[x][0];
              }
          }
          },
          error: function (error) {

          $(".em").each(function () {
              $(this).html('');
          })
          console.log(error.responseJSON.errors);
          for (let x in error.responseJSON.errors) {
              document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
          }
          $(".request-loader").removeClass("show");
          $(e.target).attr('disabled', false);
          }
      });
  });

  //  submitBtnBrand
  $(document).on('click', '#submitBtnBrand', function (e) {
  console.log('submitBtnBrand');
  $(e.target).attr('disabled', true);

  $(".request-loader").addClass("show");

  let ajaxForm = document.getElementById('ajaxFormBrand');
  let fd = new FormData(ajaxForm);
  let url = $("#ajaxFormBrand").attr('action');
  let method = $("#ajaxFormBrand").attr('method');

  if ($("#ajaxFormBrand .summernote").length > 0) {
      $("#ajaxFormBrand .summernote").each(function (i) {
      let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

      fd.delete($(this).attr('name'));
      fd.append($(this).attr('name'), content);
      });
  }

  $.ajax({
      url: url,
      method: method,
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {
      console.log(data, 'success', typeof data.error);
      $(e.target).attr('disabled', false);
      $(".request-loader").removeClass("show");

      $(".em").each(function () {
          $(this).html('');
      })
      if (data == "warning") {
          location.reload();
      }
      if (data == "success") {
          location.reload();
      }

      // if error occurs
      else if (typeof data.error != 'undefined') {
          for (let x in data) {
          if (x == 'error') {
              continue;
          }
          document.getElementById('err' + x).innerHTML = data[x][0];
          }
      }
      },
      error: function (error) {

      $(".em").each(function () {
          $(this).html('');
      })
      console.log(error.responseJSON.errors);
      for (let x in error.responseJSON.errors) {
          document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
      }
      $(".request-loader").removeClass("show");
      $(e.target).attr('disabled', false);
      }
  });
  });

  //  submitBtnTestimonialUpdate
  $(document).on('click', '#submitBtnTestimonialUpdate', function (e) {
      console.log('submitBtnTestimonialUpdate');
      $(e.target).attr('disabled', true);

      $(".request-loader").addClass("show");

      let ajaxForm = document.getElementById('ajaxFormTestimonialupdate');
      let fd = new FormData(ajaxForm);
      let url = $("#ajaxFormTestimonialupdate").attr('action');
      let method = $("#ajaxFormTestimonialupdate").attr('method');

      if ($("#ajaxFormTestimonialupdate .summernote").length > 0) {
          $("#ajaxFormTestimonialupdate .summernote").each(function (i) {
          let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

          fd.delete($(this).attr('name'));
          fd.append($(this).attr('name'), content);
          });
      }

      $.ajax({
          url: url,
          method: method,
          data: fd,
          contentType: false,
          processData: false,
          success: function (data) {
          console.log(data, 'success', typeof data.error);
          $(e.target).attr('disabled', false);
          $(".request-loader").removeClass("show");

          $(".em").each(function () {
              $(this).html('');
          })
          if (data == "warning") {
              location.reload();
          }
          if (data == "success") {
              location.reload();
          }

          // if error occurs
          else if (typeof data.error != 'undefined') {
              for (let x in data) {
              if (x == 'error') {
                  continue;
              }
              document.getElementById('err' + x).innerHTML = data[x][0];
              }
          }
          },
          error: function (error) {

          $(".em").each(function () {
              $(this).html('');
          })
          console.log(error.responseJSON.errors);
          for (let x in error.responseJSON.errors) {
              document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
          }
          $(".request-loader").removeClass("show");
          $(e.target).attr('disabled', false);
          }
      });
  });

    //  submitBtnTestimonial
  $(document).on('click', '#submitBtnTestimonial', function (e) {
    console.log('submitBtnTestimonial');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('ajaxFormTestimonial');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxFormTestimonial").attr('action');
    let method = $("#ajaxFormTestimonial").attr('method');

    if ($("#ajaxFormTestimonial .summernote").length > 0) {
      $("#ajaxFormTestimonial .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
      });
    }

    $.ajax({
      url: url,
      method: method,
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {
        console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
          $(this).html('');
        })
        if (data == "warning") {
          location.reload();
        }
        if (data == "success") {
          location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
          for (let x in data) {
            if (x == 'error') {
              continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
          }
        }
      },
      error: function (error) {

        $(".em").each(function () {
          $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
          document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
      }
    });
  });
    //  submitBtnSkill
  $(document).on('click', '#submitBtnSkill', function (e) {
    console.log('clicked2');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('ajaxFormSkill');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxFormSkill").attr('action');
    let method = $("#ajaxFormSkill").attr('method');

    if ($("#ajaxFormSkill .summernote").length > 0) {
      $("#ajaxFormSkill .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
      });
    }

    $.ajax({
      url: url,
      method: method,
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {
        console.log(data, 'errrer', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
          $(this).html('');
        })
        if (data == "warning") {
          location.reload();
        }
        if (data == "success") {
          location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
          for (let x in data) {
            if (x == 'error') {
              continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
          }
        }
      },
      error: function (error) {

        $(".em").each(function () {
          $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
          document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
      }
    });
  });

    // submitBtn original
  $(document).on('click', '#submitBtn', function (e) {
    console.log('clicked2');
    $(e.target).attr('disabled', true);

    $(".request-loader").addClass("show");

    let ajaxForm = document.getElementById('ajaxForm');
    let fd = new FormData(ajaxForm);
    let url = $("#ajaxForm").attr('action');
    let method = $("#ajaxForm").attr('method');

    if ($("#ajaxForm .summernote").length > 0) {
      $("#ajaxForm .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');

        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
      });
    }

    $.ajax({
      url: url,
      method: method,
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {
        console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
          $(this).html('');
        })
        if (data == "warning") {
          location.reload();
        }
        if (data == "success") {
          location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
          for (let x in data) {
            if (x == 'error') {
              continue;
            }
            document.getElementById('err' + x).innerHTML = data[x][0];
          }
        }
      },
      error: function (error) {

        $(".em").each(function () {
          $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
          document.getElementById('err' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
      }
    });
  });

  // flash Sale form
  $(document).on('click', '.submitBtn', function (e) {
    console.log('clicked1')
    $(e.target).attr('disabled', true);
    var $id = $(this).attr('data-id')
    $(".request-loader").addClass("show");

    let modalform = document.getElementById('modalform' + $id);
    let fd = new FormData(modalform);
    let url = $("#modalform" + $id).attr('action');
    let method = $("#modalform" + $id).attr('method');

    $.ajax({
      url: url,
      method: method,
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {

        // console.log(data, 'success', typeof data.error);
        $(e.target).attr('disabled', false);
        $(".request-loader").removeClass("show");

        $(".em").each(function () {
          $(this).html('');
        })

        if (data == "success") {

          location.reload();
        }
        // if error occurs
        else if (typeof data.error != 'undefined') {

          for (let x in data) {
            if (x == 'error') {
              continue;
            }
            $("#modalform" + $id).find('#err' + x).text(data[x][0]);
          }
        }
      },
      error: function (error) {

        $(".em").each(function () {
          $(this).html('');
        })
        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
          $("#modalform" + $id).find('#err' + x).text(error.responseJSON.errors[x][0]);
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
      }
    });
  });


  // flash sale model  active / deactive

  $(document).on('change', '.manageFlash', function (e) {
    var $val = $(this).val()
    // $(".request-loader").addClass("show");
    var $itemId = $(this).attr('data-item-id')
    if ($val == 0) {
      let url = $("#flashForm" + $itemId).attr('action');
      let method = $("#flashForm" + $itemId).attr('method');
      console.log(url)
      $.ajax({
        url: url,
        method: method,
        data: { itemId: $itemId, val: $val },
        success: function (data) {
          if (data == "success") {
            location.reload();
          }
        },
        error: function (error) {
          $(".request-loader").removeClass("show");
        }
      });
    } else {
      $("#flashmodal" + $itemId).modal('show')
    }
  });






  // insertitem
  $('#itemForm').on('submit', function (e) {
    $('.request-loader').addClass('show');
    e.preventDefault();

    let action = $('#itemForm').attr('action');
    let fd = new FormData(document.querySelector('#itemForm'));

    $.ajax({
      url: action,
      method: 'POST',
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {
        console.log('Post Form', data);
        $('.request-loader').removeClass('show');
        // location.reload();
        if (data == 'success') {
          window.location = fullUrl;
        }
      },
      error: function (error) {
        $('#postErrors').show();
        let errors = ``;

        for (let x in error.responseJSON.errors) {
          errors += `<li>
              <p class="text-danger mb-0">${error.responseJSON.errors[x][0]}</p>
            </li>`;
        }

        $('#postErrors ul').html(errors);

        $('.request-loader').removeClass('show');

        $('html, body').animate({
          scrollTop: $('#postErrors').offset().top - 100
        }, 1000);
      }
    });
  });




  $("#permissionBtn").on('click', function () {
    $("#permissionsForm").trigger("submit");
  });

  $("#langBtn").on('click', function () {
    $("#langForm").trigger("submit");
  });
  /* ***************************************************
  ==========Form Submit with AJAX Request End==========
  ******************************************************/

  /* ***************************************************
  ==========datatables start==========
  ******************************************************/
  $('#basic-datatables').DataTable({
    responsive: true,
    ordering: false
  });
  /* ***************************************************
  ==========datatables end==========
  ******************************************************/


  /* ***************************************************
  ==========Form Prepopulate After Clicking Edit Button Start==========
  ******************************************************/
  $(".editbtn").on('click', function () {
    let datas = $(this).data();
    delete datas['toggle'];
    for (let x in datas) {
      if ($("#in" + x).hasClass('summernote')) {
        $("#in" + x).summernote('code', datas[x]);
      } else if ($("#in" + x).hasClass('image')) {
        $("#in" + x).attr('src', datas[x]);
      } else if ($("#in" + x).data('role') == 'tagsinput') {
        if (datas[x].length > 0) {
          let arr = datas[x].split(" ");
          for (let i = 0; i < arr.length; i++) {
            $("#in" + x).tagsinput('add', arr[i]);
          }
        } else {
          $("#in" + x).tagsinput('removeAll');
        }
      }
      else if ($("input[name='" + x + "']").attr('type') == 'radio') {
        $("input[name='" + x + "']").each(function (i) {
          if ($(this).val() == datas[x]) {
            $(this).prop('checked', true);
          }
        });
      } else if (x === 'icon') {
        $(".in_" + x).val(datas[x]);
        $("#in" + x).removeAttr("class");
        $("#in" + x).addClass(datas[x]);
      }
      else {
        $("#in" + x).val(datas[x]);
      }
    }

  });

  /* ***************************************************
  ==========Form Prepopulate After Clicking Edit Button End==========
  ******************************************************/

  /********************************************************************
    ==========Form Prepopulate After Clicking Edit Button Start=========
    ********************************************************************/
  $(".editBtn").on('click', function () {
    let datas = $(this).data();
    delete datas['toggle'];

    for (let x in datas) {
      if ($("#in_" + x).hasClass('summernote')) {
        $("#in_" + x).summernote('code', datas[x]);
      } else if ($("#in_" + x).data('role') == 'tagsinput') {
        if (datas[x].length > 0) {
          let arr = datas[x].split(" ");
          for (let i = 0; i < arr.length; i++) {
            $("#in_" + x).tagsinput('add', arr[i]);
          }
        } else {
          $("#in_" + x).tagsinput('removeAll');
        }
      } else if ($("input[name='" + x + "']").attr('type') == 'radio') {
        $("input[name='" + x + "']").each(function (i) {
          if ($(this).val() == datas[x]) {
            $(this).prop('checked', true);
          }
        });
      } else if ($("#in_" + x).hasClass('select2')) {
        $("#in_" + x).val(datas[x]);
        $("#in_" + x).trigger('change');
      } else if ($("#in_" + x).hasClass('language')) {
        $("#in_" + x).val(datas[x]);
        $("#in_" + x).trigger('change');
      } else {
        $("#in_" + x).val(datas[x]);
        $('.category-img').attr('src', datas['image']);
        $('.brand-img').attr('src', datas['brand_img']);
        $('.gallery-img').attr('src', datas['gallery_img']);
        if ($('#in_icon').length > 0) {
          $('#in_icon').attr('class', datas['icon']);
          $('.iconpicker-component i').removeClass();
          $('.iconpicker-component i').addClass(datas['icon']);
        }
      }
    }
    // focus & blur colorpicker inputs
    setTimeout(() => {
      $(".jscolor").each(function () {
        $(this).focus();
        $(this).blur();
      });
    }, 300);
  });
  /* ***************************************************
   ==========Form Prepopulate After Clicking Edit Button End==========
   ******************************************************/



  /* ***************************************************
  ==========Form Update with AJAX Request Start==========
  ******************************************************/
  $("#updateBtn").on('click', function (e) {

    $(".request-loader").addClass("show");

    let ajaxEditForm = document.getElementById('ajaxEditForm');
    let fd = new FormData(ajaxEditForm);
    let url = $("#ajaxEditForm").attr('action');
    let method = $("#ajaxEditForm").attr('method');

    if ($("#ajaxEditForm .summernote").length > 0) {
      $("#ajaxEditForm .summernote").each(function (i) {
        let content = $(this).summernote('isEmpty') ? '' : $(this).summernote('code');
        fd.delete($(this).attr('name'));
        fd.append($(this).attr('name'), content);
      })
    }

    $.ajax({
      url: url,
      method: method,
      data: fd,
      contentType: false,
      processData: false,
      success: function (data) {

        $(".request-loader").removeClass("show");

        $(".em").each(function () {
          $(this).html('');
        })

        if (data == "success") {
          location.reload();
        }

        // if error occurs
        else if (typeof data.error != 'undefined') {
          for (let x in data) {
            if (x == 'error') {
              continue;
            }
            document.getElementById('eerr' + x).innerHTML = data[x][0];
          }
        }
      },
      error: function (error) {

        $(".em").each(function () {
          $(this).html('');
        })

        console.log(error.responseJSON.errors);
        for (let x in error.responseJSON.errors) {
          document.getElementById('Eerr_' + x).innerHTML = error.responseJSON.errors[x][0];
        }
        $(".request-loader").removeClass("show");
        $(e.target).attr('disabled', false);
      }
    });
  });


  $(".update-btn").each(function () {
    $(this).on('click', function (e) {
      let $this = $(this);

      $(".request-loader").addClass("show");

      let formId = $(this).data('form_id');
      let ajaxEditForm = document.getElementById(formId);
      let fd = new FormData(ajaxEditForm);
      let url = $("#" + formId).attr('action');
      let method = $("#" + formId).attr('method');

      if ($("#" + formId + " .summernote").length > 0) {
        $("#" + formId + " .summernote").each(function (i) {
          let content = $(this).summernote('code');
          fd.delete($(this).attr('name'));
          fd.append($(this).attr('name'), content);
        })
      }

      $.ajax({
        url: url,
        method: method,
        data: fd,
        contentType: false,
        processData: false,
        success: function (data) {
          let parentCount = $this.parents('.modal').length;
          let parentId;
          // if the form is in modal
          if (parentCount > 0) {
            parentId = $this.parents('.modal').attr('id');
          }
          // if the form is not in modal
          else {
            parentId = formId;
          }
          $(".request-loader").removeClass("show");

          $("#" + parentId).children(".em").each(function () {
            $(this).html('');
          })

          if (data == "success") {
            location.reload();
          }

          // if error occurs
          else if (typeof data.error != 'undefined') {
            for (let x in data) {
              if (x == 'error') {
                continue;
              }
              $("#" + parentId + " .eerr" + x).html(data[x][0]);
            }
          }
        }
      });
    });
  });
  /* ***************************************************
  ==========Form Update with AJAX Request End==========
  ******************************************************/



  /* ***************************************************
  ==========Delete Using AJAX Request Start==========
  ******************************************************/
  $('.deletebtn').on('click', function (e) {
    e.preventDefault();

    $(".request-loader").addClass("show");

    swal({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      type: 'warning',
      buttons: {
        confirm: {
          text: 'Yes, delete it!',
          className: 'btn btn-success'
        },
        cancel: {
          visible: true,
          className: 'btn btn-danger'
        }
      }
    }).then((Delete) => {
      if (Delete) {
        $(this).parent(".deleteform").trigger('submit');
      } else {
        swal.close();
        $(".request-loader").removeClass("show");
      }
    });
  });
  /* ***************************************************
  ==========Delete Using AJAX Request End==========
  ******************************************************/


  /* ***************************************************
  ==========Close Ticket Using AJAX Request Start==========
  ******************************************************/
  $('.close-ticket').on('click', function (e) {
    e.preventDefault();

    $(".request-loader").addClass("show");

    swal({
      title: 'Are you sure?',
      text: "You want to close this ticket!",
      type: 'warning',
      buttons: {
        confirm: {
          text: 'Yes, close it!',
          className: 'btn btn-success'
        },
        cancel: {
          visible: true,
          className: 'btn btn-danger'
        }
      }
    }).then((Delete) => {
      if (Delete) {
        swal.close();
        $(".request-loader").removeClass("show");
      } else {
        swal.close();
        $(".request-loader").removeClass("show");
      }
    });
  });
  /* ***************************************************
  ==========Delete Using AJAX Request End==========
  ******************************************************/


  /* ***************************************************
  ==========Delete Using AJAX Request Start==========
  ******************************************************/
  $(document).on('change', '.bulk-check', function () {
    let val = $(this).data('val');
    let checked = $(this).prop('checked');

    // if selected checkbox is 'all' then check all the checkboxes
    if (val == 'all') {
      if (checked) {
        $(".bulk-check").each(function () {
          $(this).prop('checked', true);
        });
      } else {
        $(".bulk-check").each(function () {
          $(this).prop('checked', false);
        });
      }
    }


    // if any checkbox is checked then flag = 1, otherwise flag = 0
    let flag = 0;
    $(".bulk-check").each(function () {
      let status = $(this).prop('checked');

      if (status) {
        flag = 1;
      }
    });

    // if any checkbox is checked then show the delete button
    if (flag == 1) {
      $(".bulk-delete").addClass('d-inline-block');
      $(".bulk-delete").removeClass('d-none');
    }
    // if no checkbox is checked then hide the delete button
    else {
      $(".bulk-delete").removeClass('d-inline-block');
      $(".bulk-delete").addClass('d-none');
    }
  });

  $('.bulk-delete').on('click', function () {

    swal({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      type: 'warning',
      buttons: {
        confirm: {
          text: 'Yes, delete it!',
          className: 'btn btn-success'
        },
        cancel: {
          visible: true,
          className: 'btn btn-danger'
        }
      }
    }).then((Delete) => {
      if (Delete) {
        $(".request-loader").addClass('show');
        let href = $(this).data('href');
        let ids = [];

        // take ids of checked one's
        $(".bulk-check:checked").each(function () {
          if ($(this).data('val') != 'all') {
            ids.push($(this).data('val'));
          }
        });

        let fd = new FormData();
        for (let i = 0; i < ids.length; i++) {
          fd.append('ids[]', ids[i]);
        }

        $.ajax({
          url: href,
          method: 'POST',
          data: fd,
          contentType: false,
          processData: false,
          success: function (data) {

            $(".request-loader").removeClass('show');
            if (data == "success") {
              location.reload();
            }
          }
        });
      } else {
        swal.close();
      }
    });

  });
  /* ***************************************************
  ==========Delete Using AJAX Request End==========
  ******************************************************/


  //  image (id) preview js/
  $(document).on('change', '#image', function (event) {
    var file = event.target.files[0];
    var reader = new FileReader();
    reader.onload = function (e) {
      $('.showImage img').attr('src', e.target.result);
    };
    reader.readAsDataURL(file);
  })
  //  image (class) preview js/
  $(document).on('change', '.image', function (event) {
    let $this = $(this);
    var file = event.target.files[0];
    var reader = new FileReader();
    reader.onload = function (e) {
      $this.prev('.showImage').children('img').attr('src', e.target.result);
    };
    reader.readAsDataURL(file);
  });

  //  image (id) preview js 2/
  $(document).on('change', '#image2', function (event) {
    var file = event.target.files[0];
    var reader = new FileReader();
    reader.onload = function (e) {
      $('.showImage2 img').attr('src', e.target.result);
    };
    reader.readAsDataURL(file);
  })

  //  image (id) preview js 3/
  $(document).on('change', '#image3', function (event) {
    var file = event.target.files[0];
    var reader = new FileReader();
    reader.onload = function (e) {
      $('.showImage3 img').attr('src', e.target.result);
    };
    reader.readAsDataURL(file);
  })

  // datepicker & timepicker
  $("input.datepicker").datepicker();
  $('input.timepicker').timepicker();

  // select2
  if ($('.select2').length > 0) {
    $('.select2').select2();
  }
});

/*------------------------
   Highlight Js
  -------------------------- */
hljs.initHighlightingOnLoad();
