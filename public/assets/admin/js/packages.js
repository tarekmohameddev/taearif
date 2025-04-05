"use strict";
$(document).ready(function () {
    $(".selectgroup-input").on('change', function () {
        var val = $(this).val();
        console.log("Value:", val, "Checked:", this.checked);

        if (val === 'vCard') {
            if (this.checked) {
                $(".v-card-box").removeClass('vcrd-none').show();
            } else {
                $(".v-card-box").hide();
            }
        }

        if (val === 'projectLimit') {
            if (this.checked) {
                $(".project-limit-box").removeClass('project-limit-none').show();
            } else {
                $(".project-limit-box").addClass('project-limit-none').hide();

            }
        }

        if (val === 'real_estate_Limit') {
            if (this.checked) {
                $(".real_estate-limit-box").removeClass('real_estate-limit-none').show();
            } else {
                $(".real_estate-limit-box").addClass('real_estate-limit-none').hide();

            }
        }
    });

    if ($('#CourseManagement').prop('checked')) {
        $("#max_video_size").show();
        $("#max_file_size").show();
    } else {
        $("#max_video_size").hide();
        $("#max_file_size").hide();
    }

    $(document).on('click', '#CourseManagement', function () {
        const isChecked = $(this).is(':checked');
        if (isChecked) {
            $("#max_video_size").show();
            $("#max_file_size").show();
        } else {
            $("#max_video_size").hide();
            $("#max_file_size").hide();
        }
    });
});
