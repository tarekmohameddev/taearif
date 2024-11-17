<div class="modal fade" id="createLessonModal-{{ $module->id }}" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Lesson') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="lessonForm-{{ $module->id }}" class="modal-form"
                    action="{{ route('user.course_management.module.store_lesson', ['id' => $module->id]) }}"
                    method="post" onsubmit="storeLesson(event, {{ $module->id }})">
                    @csrf
                    <div class="form-group">
                        <label for="">{{ __('Title') . '*' }}</label>
                        <input type="text" class="form-control" name="title" placeholder="Enter Lesson Title">
                        <p id="err_title-{{ $module->id }}" class="mt-1 mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Status') . '*' }}</label>
                        <select name="status" class="form-control">
                            <option selected disabled>
                                {{ __('Select Lesson Status') }}</option>
                            <option value="draft">{{ $keywords['Draft'] ?? __('Draft') }}</option>
                            <option value="published">{{ $keywords['Published'] ?? __('Published') }}</option>
                        </select>
                        <p id="err_status-{{ $module->id }}" class="mt-1 mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Serial Number') . '*' }}</label>
                        <input type="number" class="form-control ltr" name="serial_number"
                            placeholder="{{ __('Enter Lesson Serial Number') }}">
                        <p id="err_serial_number-{{ $module->id }}" class="mt-1 mb-0 text-danger em"></p>
                        <p class="text-warning mt-2 mb-0">
                            <small>{{ __('The higher the serial number is, the later the lesson will be shown.') }}</small>
                        </p>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    {{ __('Close') }}
                </button>
                <button form="lessonForm-{{ $module->id }}" type="submit" class="btn btn-primary btn-sm">
                    {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>
