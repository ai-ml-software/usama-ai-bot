@extends('layouts.admin')

@section('contents')
    <div class="w-full p-3" id="refresh">


        <div class="w-full lg:flex lg:gap-3">
            <div class="w-full lg:w-2/3 ts-gray-2 rounded-lg p-5 mb-3">
                <div class="w-full grid grid-cols-1 gap-3 p-2">

                    <p>You are about to download <span class="capitalize text-orange-500">{{ $template }}
                            v{{ $version }} Template </span> for Rescron AI. If this is an update, be sure to backup
                        your website before proceeding</p>

                    <p class="text-xs ts-gray-1 rounded-lg p-3 font-mono hidden" id="updatelog">
                        <span>Initializing download...</span>
                    </p>
                    <div class="ts-gray-1 rounded-lg p-3 font-mono" id="changeLog">

                    </div class="mt-5">
                    {{-- check update --}}
                    <div id="previewButton" class="hidden">
                        <a href="{{ url('/') }}" target="_blank" class="bg-purple-500 px-2 py-1 rounded-full transition-all">View
                            Website</a>
                    </div>
                    <form action="{{ route('admin.settings.templates.check') }}" method="POST" class="mt-5 update-form"
                        data-next="#downloadUpdateButton" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="template" value="{{ $template }}">
                        <input type="hidden" name="version" value="{{ $version }}">
                        <div class="w-full grid grid-cols-1 gap-5 mt-10 mb-10">
                            @if (env('DEMO_MODE'))
                                <button id="disabledButton"
                                    class="bg-gray-500 px-2 py-1 rounded-full transition-all">Download
                                    v{{ $version }}</button>
                            @else
                                <button id="checkUpdateButton" type="submit"
                                    class="updateButton bg-purple-500 px-2 py-1 rounded-full transition-all">Download
                                    v{{ $version }}</button>
                            @endif

                        </div>

                    </form>

                    {{-- check update --}}
                    <form action="{{ route('admin.settings.templates.download-validate') }}" method="POST"
                        class="update-form" data-next="#extractUpdateButton" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="template" value="{{ $template }}">
                        <input type="hidden" name="version" value="{{ $version }}">
                        <div class="w-full grid grid-cols-1 gap-5 mt-10 mb-10">
                            <button id="downloadUpdateButton" type="submit"
                                class="updateButton bg-purple-500 px-2 py-1 rounded-full transition-all hidden">Download
                                v{{ $version }}</button>
                        </div>

                    </form>

                    {{-- check update --}}
                    <form action="{{ route('admin.settings.templates.extract') }}" method="POST" class="update-form"
                        data-next="#postUpdateButton" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="template" value="{{ $template }}">
                        <input type="hidden" name="version" value="{{ $version }}">
                        <div class="w-full grid grid-cols-1 gap-5 mt-10 mb-10">
                            <button id="extractUpdateButton" type="submit"
                                class="updateButton bg-purple-500 px-2 py-1 rounded-full transition-all hidden">Extract
                                v{{ $version }}</button>
                        </div>

                    </form>


                    {{-- post update --}}
                    <form action="{{ route('admin.settings.templates.sort') }}" method="POST" class="update-form"
                        data-next="complete" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="template" value="{{ $template }}">
                        <input type="hidden" name="version" value="{{ $version }}">
                        <div class="w-full grid grid-cols-1 gap-5 mt-10 mb-10">
                            <button id="postUpdateButton" type="submit"
                                class="updateButton bg-purple-500 px-2 py-1 rounded-full transition-all hidden">Complete
                                v{{ $version }}</button>
                        </div>

                    </form>

                </div>
            </div>
            <div class="w-full lg:w-2/3">
                {{-- getting started --}}


            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).on('click', '#disabledButton', function(e){
            e.preventDefault();
            toastNotify('error', 'Not allowed in demo');
        }) ;
        // submit general form
        $(document).on('submit', '.update-form', function(e) {
            e.preventDefault();
            $("#updatelog").removeClass('hidden');
            $("#changeLog").addClass('hidden');
            var form = $(this);
            var successNext = $(this).data('next');
            var formData = new FormData(this);
            var submitButton = $(this).find('button[type="submit"]');
            submitButton.addClass('relative disabled');
            submitButton.append('<span class="button-spinner"></span>');
            submitButton.prop('disabled', true);

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                dataType: 'json',
                contentType: false,
                processData: false,
                success: function(response) {
                    var message = response.message;

                    $("#updatelog").append('<br><br><span class="text-green-500">' + message +
                        '</span>');
                    if (successNext === 'complete') {
                        $('.updateButton').addClass('hidden');
                        $('#previewButton').removeClass('hidden');
                    } else {
                        $('.updateButton').addClass('hidden');
                        $(successNext).removeClass('hidden');
                        $(successNext).click();
                    }


                },
                error: function(xhr, status, error) {
                    submitButton.removeClass('disabled');
                    submitButton.find('.button-spinner').remove();
                    submitButton.prop('disabled', false);

                    if (status == 422) {
                        var errors = xhr.responseJSON.errors;
                        console.console.log(errors);

                        if (errors) {
                            $.each(errors, function(field, messages) {
                                var fieldErrors = '';
                                $.each(messages, function(index, message) {
                                    fieldErrors += message + '<br>';
                                });

                                $("#updatelog").append(
                                    '<br><br><span class="text-red-500">' +
                                    fieldErrors + '</span>');
                                // toastNotify('error', fieldErrors);
                            });
                        } else {
                            $("#updatelog").append('<br><br><span class="text-red-500">' +
                                "An Error occured, try again later" + '</span>');
                            // toastNotify('error', 'An Error occured, try again later');
                        }

                    } else {
                        $("#updatelog").append('<br><br><span class="text-red-500">' + status +
                            ' Error occured, try again later' + '</span>');
                        // toastNotify('error', status +  ' Error occured, try again later');
                    }


                }
            });
        });
    </script>
@endsection
