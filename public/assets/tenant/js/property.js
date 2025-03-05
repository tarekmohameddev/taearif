'use strict'
$(document).ready(function () {


  $('.js-example-basic-single3').select2();
  $('.js-example-basic-multiple').select2({
    placeholder: 'قم بتحديد الميزات',
  });

  $(".country").on('change', function (e) {
    $('.request-loader').addClass('show');
    let addedState = "state_id";
    let addedCity = "city_id";
    $('.' + addedState).find('option').remove();
    $('.' + addedCity).find('option').remove();
    let id = $(this).val();
    let URL = stateUrl.replace(':countryId', id);
    $.ajax({
      type: 'GET',
      url: URL,
      data: {},
      success: function (data) {
        if (data.states.length > 0) {
          $('.state').show();
          $('.city').hide()
          // $('.' + addedState).find('option').remove();
          // $('.' + addedCity).find('option').remove();
          $.each(data.states, function (key, value) {

            $('.' + addedState).append($(
              `<option></option>`
            ).val(value
              .id).html(value.name));
          });

          let firstStateId = data.states[0].id;



          $.ajax({
            type: 'GET',
            url: cityUrl,
            data: {
              state_id: firstStateId,
            },
            success: function (data) {

              if (data.cities.length > 0) {
                $('.city').show();
                $('.' + addedCity).find('option').remove()
                  .end();
                $.each(data.cities, function (key, value) {
                  $('.' + addedCity).append(
                    $(`<option ></option>`).val(value.id).html(value.name));
                });
              }
              $('.request-loader').removeClass('show');
            }
          });
          $('.request-loader').removeClass('show');

        } else if (data.cities.length > 0) {
          $('.state').hide()
          $('.city').show();
          $('.' + addedCity).find('option').remove();
          $.each(data.cities, function (key, value) {
            $('.' + addedCity).append(
              $(`<option ></option>`).val(value.id).html(value.name));
          });
        }
        $('.request-loader').removeClass('show');
      }

    });
  });


});

function getCities(e) {

  let $this = e.target;
  $('.request-loader').addClass('show');
  let addedCity = "city_id";
  let id = $($this).val();
  $.ajax({
    type: 'GET',
    url: cityUrl,
    data: {
      state_id: id,
    },
    success: function (data) {
      if (data.cities.length > 0) {
        $('.city').show();
        $('.' + addedCity).find('option').remove().end();
        $.each(data.cities, function (key, value) {
          $('.' + addedCity).append(
            $(
              `<option></option>`).val(value
                .id).html(value.name));
        });
      } else {
        $('.' + addedCity).find('option').remove().end().append(
          $(
            `<option selected ></option>`).val('').html('No City Found'));

      }
      $('.request-loader').removeClass('show');
    }
  });
}
