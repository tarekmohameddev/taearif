@extends('user.layout')

@section('content')

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

<!-- Custom CSS for Checkbox Styling -->
<style>
    /* Custom checkbox styling */
    .step-checkbox {
        width: 22px;
        height: 22px;
        border: 2px solid #ccc;
        border-radius: 5px;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }

    .step-checkbox:checked {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }

    /* List item hover effect */
    .checklist-item:hover {
        background-color: #f8f9fa;
        transition: 0.3s ease-in-out;
    }

    /* Icon animation */
    .toggle-icon {
        transition: transform 0.3s ease-in-out;
    }

    .rotated {
        transform: rotate(180deg);
    }

    .fw-semibold {
        padding-right: 10px;
    }

    .toggle-icon {
        cursor: pointer;
    }

    .rotated {
        transform: rotate(180deg);
        transition: transform 0.3s ease-in-out;
    }
</style>

<!--  -->
<div class="container py-4">
    <!-- Overview Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">{{ __('Setup') }}</h3>
    </div>

    <!-- Main Card -->
    <div class="card p-4 shadow-lg rounded-4 border-0">
        <h5 class="fw-bold">{{ __('Get started with your setup') }}</h5>
        <p class="text-muted">{{ __('Complete these steps to finalize your setup.') }}</p>

        <form class="" action="{{ route('onboarding.step2') }}" method="post">
            @csrf
            <div class="row">
                <!-- Left Side: Checklist -->
                <div class="col-lg-5">
                    <ul class="list-group checklist-group border-0">
                        @php
                        $sections = $data['sections'] ?? null;
                        @endphp

                        @php
                        // Define all sections
                        $sectionsList = [
                        'intro_section' => 'Intro Section',
                        'portfolio_section' => 'Portfolio Section',
                        'featured_services_section' => 'Featured Services Section',
                        'why_choose_us_section' => 'Why Choose Us Section',
                        'counter_info_section' => 'Counter Info Section',
                        'video_section' => 'Video Section',
                        'team_members_section' => 'Team Members Section',
                        'skills_section' => 'Skills Section',
                        'testimonials_section' => 'Testimonials Section',
                        'blogs_section' => 'Blogs Section',
                        'brand_section' => 'Brand Section',
                        'top_footer_section' => 'Top Footer Section',
                        'copyright_section' => 'Copyright Section'
                        ];
                        @endphp

                        @foreach ($sectionsList as $key => $label)
                        <li class="list-group-item border-0 py-3 d-flex justify-content-between align-items-center checklist-item">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" name="{{ $key }}" value="1" class="step-checkbox me-3" data-step="{{ $loop->index + 1 }}" {{ isset($sections->$key) && $sections->$key == 1 ? 'checked' : '' }}>
                                <span class="fw-semibold">{{ __($label) }}</span>
                            </div>
                            <i class="bi bi-chevron-down toggle-icon" id="icon{{ $loop->index + 1 }}"></i>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Right Side: Details Panel -->
                <div class="col-lg-7">
                    @for ($i = 1; $i <= count($sectionsList); $i++)
                    <div id="step{{ $i }}" class="collapse">
                        <i class="bi bi-x toggle-icon" id="icon-{{ $i }}"></i>
                        <div class="card p-4 border-0 rounded-3 shadow-sm">
                            <h6 class="fw-bold">{{ __('قسم المقدمة') }}</h6>
                            <p class="text-muted">{{ __('قسم المقدمة') }}</p>
                            <button class="btn btn-primary btn-sm d-none">{{ __('قسم المقدمة') }}</button>
                        </div>
                    </div>
                    @endfor
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success btn-lg px-5">{{ __('Save Changes') }}</button>
            </div>
        </form>
    </div>
</div>

<!--  -->

<!-- JavaScript Toggle -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".step-checkbox").forEach(checkbox => {
            checkbox.addEventListener("click", function(event) {
                let stepNumber = this.getAttribute("data-step");
                let step = document.getElementById("step" + stepNumber);
                let icon = document.getElementById("icon" + stepNumber);

                event.stopPropagation();

                if (step) {
                    if (step.classList.contains("show")) {
                        step.classList.remove("show");
                        icon.classList.remove("rotated");
                    } else {
                        step.classList.add("show");
                        icon.classList.add("rotated");
                    }
                }
            });
        });
    });

    // Close the details panel when the close icon is clicked
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".toggle-icon").forEach(icon => {
            icon.addEventListener("click", function() {
                let stepNumber = this.id.split("-")[1];
                let step = document.getElementById("step" + stepNumber);

                if (step) {
                    step.classList.remove("show");
                    this.classList.remove("rotated");
                }
            });
        });
    });
</script>
@endsection
