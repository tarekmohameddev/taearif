
$(document).ready(function () {
  'use strict';
  $('#state').hide();
  $("#country").on('change', function (e) {
    $('.request-loader').addClass('show');

    let countryId = $(this).val();
    let url= stateUrl.replace(':countryId', countryId);
    $.ajax({
      type: 'GET',
      url: url,
      data: {

      },
      success: function (data) {
        if (data.length != 0) {
          $('#state').show()
          $('[name="state"]').html('');
          $('[name="state"]').append($(
            '<option selected disabled> Select a State </option>'
          ));
          $.each(data, function (key, value) {
            $('[name="state"]').append($('<option></option>').val(
              value.id)
              .html(value.name));
          });
        } else {
          $('#state').hide();
        }
        $('.request-loader').removeClass('show');
      }
    });
  });
});
